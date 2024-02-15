<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      MultiSafepay <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2018 MultiSafepay, Inc. (http://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MltisafeMultiSafepayPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;
use Exception;
use MltisafeMultiSafepayPayment\Components\Factory\Client;
use MltisafeMultiSafepayPayment\Service\CachedConfigService;
use MltisafeMultiSafepayPayment\Service\LoggerService;
use MltisafeMultiSafepayPayment\Service\PaymentMethodsService;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PaymentComponentSubscriber
 *
 * @package MltisafeMultiSafepayPayment\Subscriber
 */
class PaymentComponentSubscriber implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var PaymentMethodsService
     */
    private $paymentMethods;

    /**
     * PaymentComponentSubscriber constructor
     *
     * @param ContainerInterface $container
     * @param Client $client
     * @param PaymentMethodsService $paymentMethods
     */
    public function __construct(ContainerInterface $container, Client $client, PaymentMethodsService $paymentMethods)
    {
        $this->container = $container;
        $this->client = $client;
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout'
        ];
    }

    /**
     * Supports payment component
     *
     * @param array $paymentMethod
     * @return bool
     */
    private function supportsPaymentComponent(array $paymentMethod): bool
    {
        return isset($paymentMethod['apps']['payment_components']) &&
            $paymentMethod['apps']['payment_components']['is_enabled'] &&
            (
                $paymentMethod['apps']['payment_components']['has_fields'] ||
                $this->supportsTokenization($paymentMethod)
            );
    }

    /**
     * Supports tokenization
     *
     * @param array $paymentMethod
     * @return bool
     */
    private function supportsTokenization(array $paymentMethod): bool
    {
        return $paymentMethod['tokenization']['is_enabled'];
    }

    /**
     * On post-dispatch checkout
     *
     * @param Enlight_Controller_ActionEventArgs $args
     * @return void
     * @throws Exception
     */
    public function onPostDispatchCheckout(Enlight_Controller_ActionEventArgs $args): void
    {
        $controller = $args->getSubject();
        $session = $controller->get('session');
        if ((string)$args->getRequest()->getActionName() === 'confirm') {
            [$cachedConfigReader, $shop] = (new CachedConfigService($this->container))->selectConfigReader();
            if (is_null($cachedConfigReader)) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::WARNING,
                    'Could not load plugin configuration',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Action' => 'confirm'
                    ]
                );
                return;
            }
            $pluginConfig = $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop);
            $paymentMethods = $this->paymentMethods->loadPaymentMethods();
            $activePaymentMethod = $controller->View()->getAssign('sPayment');

            /**
             * This restriction is invoked at this point, when the user navigates to the checkout.
             *
             * If the total amount has changed, and it does not meet the minimum or maximum limits
             * for the payment method, the checkout page is refreshed.
             *
             * This is to prevent the user from placing an order with an amount that is not allowed
             * for the selected payment method, which can happen in some cases due to a Shopware bug
             */
            if (!empty($activePaymentMethod['id'])) {
                $isAmountAllowed = (new PaymentFilter(
                    $this->container,
                    $session,
                    $this->paymentMethods
                ))->isAmountAllowed($activePaymentMethod);

                if (!$isAmountAllowed) {
                    $controller->redirect(['controller' => 'checkout', 'action' => 'confirm']);
                    return;
                }
            }

            $component = false;
            foreach ($paymentMethods as $paymentMethod) {
                $paymentMethodId = $paymentMethod['id'];
                if ('multisafepay_' . $paymentMethodId === (string)$activePaymentMethod['name']) {
                    $component = $this->supportsPaymentComponent((array)$paymentMethod);
                    $gatewayCode = (string)$paymentMethodId;
                    break;
                }
            }

            $view = $controller->View();

            if ($component === false) {
                $view->assign('component', false);
                return;
            }

            try {
                $clientSdk = $this->client->getSdk($pluginConfig);
                $apiTokenManager = $clientSdk->getApiTokenManager();
                $apiToken = $apiTokenManager->get()->getApiToken();
            } catch (ApiException | ClientExceptionInterface $exception) {
                $apiToken = null;
            }

            $shopService = $this->container->get('shop');
            $view->assign('component', true);
            $view->assign('api_token', $apiToken ?? '');
            $view->assign('currency', $shopService ? $shopService->getCurrency()->getCurrency() : '');
            $view->assign('locale', $shopService ? $shopService->getLocale()->getLocale() : '');
            $view->assign('gateway_code', $gatewayCode ?? '');
            $view->assign('env', $pluginConfig['msp_environment']);

            // Payment Component Template ID
            if (!empty($pluginConfig['multisafepay_template_id'])) {
                $view->assign('template_id', $pluginConfig['multisafepay_template_id']);
            }
        }

        if ($args->getRequest()->getParam('payload') && ((string)$controller->Request()->getActionName() === 'payment')) {
            $payload = $args->getRequest()->getParam('payload');
            $session->offsetSet('payload', $payload);
        }
    }
}
