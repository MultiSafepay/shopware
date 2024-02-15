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
class DeliveryBuilder implements OrderRequestBuilderInterface
{
    /**
     * Build the order request
     *
     * @param OrderRequest  $orderRequest
     * @param $controller
     * @param $container
     * @return OrderRequest
     */
    public function build(OrderRequest $orderRequest, $controller, $container): OrderRequest
    {
        $user = $controller->getUser();
        $address = new Address();
        $shop = $container->get('shop');
        [$street, $houseNumber] = (new AddressParser())->parse($user['shippingaddress']['street'], $user['shippingaddress']['additionalAddressLine1'] ?? '');

        $address->addCity($user['shippingaddress']['city'])
            ->addCountryCode($user['additional']['countryShipping']['countryiso'])
            ->addHouseNumber($houseNumber)
            ->addStreetName($street)
            ->addZipCode($user['shippingaddress']['zipcode']);

        $deliveryDetails = (new OrderRequest\Arguments\CustomerDetails())
            ->addLocale(is_null($shop) ? 'en_US' : $shop->getLocale()->getLocale() ?? 'en_US')
            ->addFirstName($user['shippingaddress']['firstname'])
            ->addLastName($user['shippingaddress']['lastname'])
            ->addAddress($address)
            ->addPhoneNumberAsString($user['shippingaddress']['phone'] ?? '')
            ->addEmailAddressAsString($user['additional']['user']['email']);

        if ($user['shippingaddress']['company']) {
            $deliveryDetails->addCompanyName($user['shippingaddress']['company']);
        }

        return $orderRequest->addDelivery($deliveryDetails);
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
        $shipping = $order->getShipping();
        $customer = $order->getCustomer();
        $deliveryDetails = new CustomerDetails();

        if ($shipping) {
            [$street, $houseNumber] = (new AddressParser())->parse($shipping->getStreet(), $shipping->getAdditionalAddressLine1());

            $address = new Address();
            $address->addCity($shipping->getCity())
                ->addCountryCode($shipping->getCountry()->getIso())
                ->addHouseNumber($houseNumber)
                ->addStreetName($street)
                ->addZipCode($shipping->getZipCode());

            $deliveryDetails->addFirstName($shipping->getFirstName())
                ->addLastName($shipping->getLastName())
                ->addAddress($address)
                ->addPhoneNumberAsString($shipping->getPhone() ?? '')
                ->addEmailAddressAsString(is_null($customer) ? '' : $customer->getEmail() ?? '');
        }

        return $orderRequest->addDelivery($deliveryDetails);
    }
}
