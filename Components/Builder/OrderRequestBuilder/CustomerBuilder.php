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

use MltisafeMultiSafepayPayment\Components\Helper;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\AddressParser;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use Shopware\Models\Order\Order;

class CustomerBuilder implements OrderRequestBuilderInterface
{
    public function build(OrderRequest $orderRequest, $controller, $container): OrderRequest
    {
        $user = $controller->getUser();
        $address = new Address();
        $customerDetails = new OrderRequest\Arguments\CustomerDetails();
        [$street, $houseNumber] =
            (new AddressParser())->parse($user["billingaddress"]["street"], $user["billingaddress"]["additionalAddressLine1"] ?? '');

        $address->addCity($user["billingaddress"]["city"])
            ->addCountryCode($user["additional"]["country"]["countryiso"])
            ->addHouseNumber($houseNumber)
            ->addStreetName($street)
            ->addZipCode($user["billingaddress"]["zipcode"]);

        $customerDetails->addAddress($address)
            ->addLocale(Shopware()->Container()->get('shop')->getLocale()->getLocale())
            ->addFirstName($user["billingaddress"]["firstname"])
            ->addLastName($user["billingaddress"]["lastname"])
            ->addPhoneNumber(new PhoneNumber($user["billingaddress"]["phone"] ?? ''))
            ->addEmailAddress(new EmailAddress($user["additional"]["user"]["email"]))
            ->addIpAddressAsString(Helper::getRemoteIP())
            ->addForwardedIpAsString(Helper::getForwardedIP());

        if ($user['billingaddress']['company']) {
            $customerDetails->addCompanyName($user['billingaddress']['company']);
        }

        return $orderRequest->addCustomer($customerDetails);
    }

    public function buildBackendOrder(OrderRequest $orderRequest, Order $order): OrderRequest
    {
        $address = new Address();
        $customerDetails = new CustomerDetails();
        [$street, $houseNumber] = (new AddressParser())->parse($order->getBilling()->getStreet(), $order->getBilling()->getAdditionalAddressLine1());

        $address->addCity($order->getBilling()->getCity())
            ->addCountryCode($order->getBilling()->getCountry()->getIso())
            ->addHouseNumber($houseNumber)
            ->addStreetName($street)
            ->addZipCode($order->getBilling()->getZipCode());

        $customerDetails->addAddress($address)
            ->addLocale(Shopware()->Container()->get('shop')->getLocale()->getLocale())
            ->addFirstName($order->getBilling()->getFirstName())
            ->addLastName($order->getBilling()->getLastName())
            ->addPhoneNumberAsString($order->getBilling()->getPhone() ?? '')
            ->addEmailAddressAsString($order->getCustomer()->getEmail() ?? '');

        return $orderRequest->addCustomer($customerDetails);
    }
}
