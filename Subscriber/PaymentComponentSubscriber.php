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
use Shopware\Models\Customer\Customer;
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
            $paymentMethod['apps']['payment_components']['has_fields'];
    }

    /**
     * Supports payment component
     *
     * @param array $paymentMethod
     * @return bool
     */
    private function supportsTokenization(array $paymentMethod): bool
    {
        return isset($paymentMethod['tokenization']) &&
            $paymentMethod['tokenization']['is_enabled'];
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

            $gatewayCode = '';
            $component = $isRegistered = $tokenization = false;
            if ($session->offsetExists('sUserId')) {
                $customerReference = (string)$session->offsetGet('sUserId');
                $modelManager = $this->container->get('models');
                if ($modelManager) {
                    $customer = $modelManager->getRepository(Customer::class)->find($customerReference);
                    $isRegistered = ($customer instanceof Customer) && ($customer->getAccountMode() === Customer::ACCOUNT_MODE_CUSTOMER);
                }
            }

            foreach ($paymentMethods as $paymentMethod) {
                $paymentMethodId = $this->paymentMethods->filterBrandedPayment($paymentMethod['id']);
                $activePaymentName = $this->paymentMethods->filterBrandedPayment($activePaymentMethod['name']);
                if ('multisafepay_' . $paymentMethodId === $activePaymentName) {
                    $component = $this->supportsPaymentComponent((array)$paymentMethod);
                    if ($isRegistered) {
                        $tokenization = $this->supportsTokenization((array)$paymentMethod);
                    }
                    $gatewayCode = $paymentMethodId;
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
            } catch (Exception $exception) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::ERROR,
                    'Could not make a PHP-SDK instance',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Action' => 'confirm',
                        'Exception' => $exception->getMessage()
                    ]
                );
                return;
            }

            try {
                $apiToken = $clientSdk->getApiTokenManager()->get()->getApiToken();
            } catch (ApiException | ClientExceptionInterface $exception) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::ERROR,
                    'API Token could not be retrieved',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Action' => 'confirm',
                        'Exception' => $exception->getMessage()
                    ]
                );
                $apiToken = null;
            }

            try {
                $tokens = $clientSdk->getTokenManager()->getListByGatewayCodeAsArray((string)($customerReference ?? ''), $gatewayCode) ?? [];
            } catch (ApiException | ClientExceptionInterface $exception) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::ERROR,
                    'Tokens could not be retrieved',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Action' => 'confirm',
                        'Exception' => $exception->getMessage()
                    ]
                );
                $tokens = [];
            }

            $shopService = $this->container->get('shop');
            $view->assign('component', true);
            $view->assign('tokenization', $tokenization);
            $view->assign('tokens', json_encode($tokens));
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

        if (!is_null($args->getRequest()->getParam('tokenize')) && ((string)$controller->Request()->getActionName() === 'payment')) {
            $tokenize = $args->getRequest()->getParam('tokenize');
            $session->offsetSet('tokenize', !empty($tokenize));
        }
    }
}
