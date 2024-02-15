<?php declare(strict_types=1);
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Shopware
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
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
use Enlight_Components_Session_Namespace;
use Enlight_Event_EventArgs;
use MltisafeMultiSafepayPayment\Service\LoggerService;
use MltisafeMultiSafepayPayment\Service\PaymentMethodsService;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use Shopware\Models\Customer\Customer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PaymentFilter
 *
 * @package MltisafeMultiSafepayPayment\Subscriber
 */
class PaymentFilter implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @var PaymentMethodsService $paymentMethods
     */
    private $paymentMethods;

    /**
     * PaymentFilter constructor
     *
     * @param ContainerInterface $container
     * @param Enlight_Components_Session_Namespace $session
     * @param PaymentMethodsService $paymentMethods
     */
    public function __construct(
        ContainerInterface $container,
        Enlight_Components_Session_Namespace $session,
        PaymentMethodsService $paymentMethods
    ) {
        $this->container = $container;
        $this->session = $session;
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
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPaymentMeans'
        ];
    }

    /**
     * On filter payment means
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onFilterPaymentMeans(Enlight_Event_EventArgs $args): void
    {
        $paymentMeans = $args->getReturn();

        try {
            $paymentMethods = $this->paymentMethods->loadPaymentMethods();
        } catch (ApiException|ClientExceptionInterface $exception) {
            foreach ($paymentMeans as $index => $paymentMean) {
                if (strpos($paymentMean['name'], 'multisafepay_') !== false) {
                    unset($paymentMeans[$index]);
                }
            }

            if (empty($paymentMeans)) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::WARNING,
                    'MultiSafepay: No payment methods available',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Exception' => $exception->getMessage()
                    ]
                );
            }
            $paymentMethods = [];
        }

        foreach ($paymentMeans as $index => $paymentMean) {
            if (strpos($paymentMean['name'], 'multisafepay_') !== false) {
                $paymentMethodId = explode('_', $paymentMean['name'])[1] ?? '';
                foreach ($paymentMethods as $paymentMethod) {
                    if ($paymentMethod['id'] === $paymentMethodId) {
                        if (!$this->isAllowedCurrency($paymentMethod['allowed_currencies']) || !$this->isAllowedBillingCountry($paymentMethod['allowed_countries'])) {
                            unset($paymentMeans[$index]);
                            continue 2;
                        }

                        if (!$this->isAmountAllowed($paymentMean)) {
                            unset($paymentMeans[$index]);
                            continue 2;
                        }
                        $paymentMeans[$index]['image'] = $paymentMethod['iconUrls']['medium'];
                    }
                }
            }
        }
        $args->setReturn($paymentMeans);
    }

    /**
     * Get order amount
     *
     * @return float
     */
    private function getOrderAmount(): float
    {
        $amount = $this->session->get('sOrderVariables')['sAmount'];

        // Fallback when session does not have sAmount
        if (is_null($amount)) {
            $amount = $this->session->get('sBasketAmount');
        }
        return (float)$amount;
    }

    /**
     * Method to check if the currency is allowed
     *
     * @param array $multiSafepayAllowedCurrencies
     * @return bool
     */
    private function isAllowedCurrency(array $multiSafepayAllowedCurrencies): bool
    {
        $shop = $this->container->get('shop');
        $activeCurrency = $shop ? $shop->getCurrency()->getCurrency() : '';

        return in_array($activeCurrency, $multiSafepayAllowedCurrencies, true);
    }

    /**
     * Method to check if the billing country is allowed
     *
     * @param array $multiSafepayAllowedCountries
     * @return bool
     */
    private function isAllowedBillingCountry(array $multiSafepayAllowedCountries): bool
    {
        if (empty($multiSafepayAllowedCountries)) {
            return true;
        }

        $userId = Shopware()->Session()->get('sUserId');

        if (is_null($userId)) {
            return false;
        }

        $user = Shopware()->Models()->getRepository(Customer::class)->find($userId);
        if (is_null($user)) {
            return false;
        }

        $billingAddress = $user->getDefaultBillingAddress();
        if (is_null($billingAddress)) {
            return false;
        }

        $country = $billingAddress->getCountry();
        if (is_null($country)) {
            return false;
        }

        $isoName = $country->getIsoName();
        if (is_null($isoName)) {
            return false;
        }

        return in_array($isoName, $multiSafepayAllowedCountries, true);
    }

    /**
     * Method to check if the amount is allowed
     *
     * @param array $paymentMethod
     * @return bool
     */
    public function isAmountAllowed(array $paymentMethod): bool
    {
        $amount = $this->getOrderAmount();
        $dataLoader = $this->container->get('shopware_attribute.data_loader');
        $attributes = $dataLoader ? $dataLoader->load('s_core_paymentmeans_attributes', $paymentMethod['id']) : [];

        $min_amount = (float)$attributes['msp_min_amount'];
        $max_amount = (float)$attributes['msp_max_amount'];

        if (empty($min_amount) && empty($max_amount)) {
            return true;
        }

        if (($amount < $min_amount) || ($amount > $max_amount)) {
            return false;
        }

        return true;
    }
}
