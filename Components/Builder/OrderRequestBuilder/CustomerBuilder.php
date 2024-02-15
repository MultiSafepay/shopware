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

namespace MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder;

use MltisafeMultiSafepayPayment\Components\Helper;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use Shopware\Models\Order\Order;

/**
 * Class CustomerBuilder
 *
 * @package MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder
 */
class CustomerBuilder implements OrderRequestBuilderInterface
{
    /**
     * Build the order request
     *
     * @param OrderRequest $orderRequest
     * @param $controller
     * @param $container
     * @return OrderRequest
     */
    public function build(OrderRequest $orderRequest, $controller, $container): OrderRequest
    {
        $user = $controller->getUser();
        $address = new Address();
        $customerDetails = new OrderRequest\Arguments\CustomerDetails();
        $shop = $container->get('shop');
        [$street, $houseNumber] = (new AddressParser())->parse($user['billingaddress']['street'], $user['billingaddress']['additionalAddressLine1'] ?? '');

        $address->addCity($user['billingaddress']['city'])
            ->addCountryCode($user['additional']['country']['countryiso'])
            ->addHouseNumber($houseNumber)
            ->addStreetName($street)
            ->addZipCode($user['billingaddress']['zipcode']);

        $customerDetails->addAddress($address)
            ->addLocale(is_null($shop) ? 'en_US' : $shop->getLocale()->getLocale() ?? 'en_US')
            ->addFirstName($user['billingaddress']['firstname'])
            ->addLastName($user['billingaddress']['lastname'])
            ->addPhoneNumberAsString($user['billingaddress']['phone'] ?? '')
            ->addEmailAddressAsString($user['additional']['user']['email'])
            ->addIpAddressAsString(Helper::getRemoteIP())
            ->addForwardedIpAsString(Helper::getForwardedIP());

        if ($user['billingaddress']['company']) {
            $customerDetails->addCompanyName($user['billingaddress']['company']);
        }

        return $orderRequest->addCustomer($customerDetails);
    }

    /**
     * Build the order request from the backend
     *
     * @param OrderRequest $orderRequest
     * @param Order $order
     * @return OrderRequest
     */
    public function buildBackendOrder(OrderRequest $orderRequest, Order $order): OrderRequest
    {
        $billing = $order->getBilling();
        $shop = Shopware()->Container()->get('shop');
        $customer = $order->getCustomer();
        $customerDetails = new CustomerDetails();

        if ($billing) {
            [$street, $houseNumber] = (new AddressParser())->parse($billing->getStreet(), $billing->getAdditionalAddressLine1());

            $address = new Address();
            $address->addCity($billing->getCity())
                ->addCountryCode($billing->getCountry()->getIso())
                ->addHouseNumber($houseNumber)
                ->addStreetName($street)
                ->addZipCode($billing->getZipCode());

            $customerDetails->addAddress($address)
                ->addLocale(is_null($shop) ? 'en_US' : $shop->getLocale()->getLocale() ?? 'en_US')
                ->addFirstName($billing->getFirstName())
                ->addLastName($billing->getLastName())
                ->addPhoneNumberAsString($billing->getPhone() ?? '')
                ->addEmailAddressAsString(is_null($customer) ? '' : $customer->getEmail() ?? '');
        }

        return $orderRequest->addCustomer($customerDetails);
    }
}
