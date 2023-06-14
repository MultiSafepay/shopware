<?php declare(strict_types=1);
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
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
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use Shopware\Models\Order\Order;

class DeliveryBuilder implements OrderRequestBuilderInterface
{
    public function build(OrderRequest $orderRequest, $controller, $container): OrderRequest
    {
        $user = $controller->getUser();
        $address = new Address();
        [$street, $houseNumber] =
            (new AddressParser())->parse($user["shippingaddress"]["street"], $user["shippingaddress"]["additionalAddressLine1"] ?? '');


        $address->addCity($user["shippingaddress"]["city"])
            ->addCountryCode($user["additional"]["countryShipping"]["countryiso"])
            ->addHouseNumber($houseNumber)
            ->addStreetName($street)
            ->addZipCode($user["shippingaddress"]["zipcode"]);

        $deliveryDetails = (new OrderRequest\Arguments\CustomerDetails())
            ->addFirstName($user["shippingaddress"]["firstname"])
            ->addLastName($user["shippingaddress"]["lastname"])
            ->addAddress($address)
            ->addPhoneNumber(new PhoneNumber($user["shippingaddress"]["phone"] ?? ''))
            ->addEmailAddress(new EmailAddress($user["additional"]["user"]["email"]));

        if ($user['shippingaddress']['company']) {
            $deliveryDetails->addCompanyName($user['shippingaddress']['company']);
        }

        return $orderRequest->addDelivery($deliveryDetails);
    }

    public function buildBackendOrder(OrderRequest $orderRequest, Order $order): OrderRequest
    {
        $address = new Address();
        [$street, $houseNumber] = (new AddressParser())->parse($order->getShipping()->getStreet(), $order->getShipping()->getAdditionalAddressLine1());

        $address->addCity($order->getShipping()->getCity())
            ->addCountryCode($order->getShipping()->getCountry()->getIso())
            ->addHouseNumber($houseNumber)
            ->addStreetName($street)
            ->addZipCode($order->getShipping()->getZipCode());

        $deliveryDetails = (new CustomerDetails())
            ->addFirstName($order->getShipping()->getFirstName())
            ->addLastName($order->getShipping()->getLastName())
            ->addAddress($address)
            ->addPhoneNumberAsString($order->getShipping()->getPhone() ?? '')
            ->addEmailAddressAsString($order->getCustomer()->getEmail() ?? '');

        return $orderRequest->addDelivery($deliveryDetails);
    }
}
