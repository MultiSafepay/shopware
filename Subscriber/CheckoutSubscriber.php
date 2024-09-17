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
use Enlight_Controller_ActionEventArgs as EventArgs;
use Shopware\Models\Order\Billing;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Shipping;

class CheckoutSubscriber implements SubscriberInterface
{
    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onCheckoutPostDispatch',
        ];
    }

    /**
     * @param EventArgs $args
     * @return void
     */
    public function onCheckoutPostDispatch(EventArgs $args): void
    {
        $subject = $args->getSubject();
        $view = $subject->View();
        $controllerName = $subject->Request()->getControllerName();
        $actionName = $subject->Request()->getActionName();

        if ($controllerName !== 'checkout' && $actionName !== 'finish') {
            return;
        }

        $orderDetails = $view->getAssign('sAddresses');
        $shippingAddress = $orderDetails['shipping'] ?? null;
        $billingAddress = $orderDetails['billing'] ?? null;

        $orderNumber = $view->getAssign('sOrderNumber');


        if (!empty($orderNumber) && (empty($shippingAddress) || empty($billingAddress))) {
            $orderRepository = Shopware()->Models()->getRepository(Order::class);
            $orderEntity = $orderRepository->findOneBy(['number' => $orderNumber]);
            if (!$orderEntity) {
                return;
            }

            $sAddresses = [];

            if (empty($shippingAddress)) {
                /* @var $shippingAddressEntity Shipping */
                $shippingAddressEntity = $orderEntity->getShipping();
                if ($shippingAddressEntity) {
                    $shippingAddress = $this->extractAddress($shippingAddressEntity);
                    $sAddresses['shipping'] = $shippingAddress;
                }
            }

            if (empty($billingAddress)) {
                /* @var $billingAddressEntity Billing */
                $billingAddressEntity = $orderEntity->getBilling();
                if ($billingAddressEntity) {
                    $billingAddress = $this->extractAddress($billingAddressEntity);
                    $sAddresses['billing'] = $billingAddress;
                }
            }

            if (!empty($sAddresses)) {
                $sAddresses['equal'] = $sAddresses['shipping'] === $sAddresses['billing'];
                $view->assign('sAddresses', $sAddresses);
            }
        }
    }

    /**
     * @param Shipping | Billing $addressEntity
     * @return array
     */
    private function extractAddress($addressEntity): array
    {
        return [
            'company'   => $addressEntity->getCompany(),
            'department' => $addressEntity->getDepartment(),
            'salutation' => $addressEntity->getSalutation(),
            'title' => $addressEntity->getTitle() ?? '',
            'firstname' => $addressEntity->getFirstName(),
            'lastname' => $addressEntity->getLastName(),
            'street' => $addressEntity->getStreet(),
            'additional_address_line1' => $addressEntity->getAdditionalAddressLine1() ?? '',
            'additional_address_line2' => $addressEntity->getAdditionalAddressLine2() ?? '',
            'zipcode' => $addressEntity->getZipcode(),
            'city' => $addressEntity->getCity(),
            'state' => [
                'name' => $addressEntity->getState() ? $addressEntity->getState()->getName() : null,
            ],
            'country' => [
                'name' => $addressEntity->getCountry()->getName(),
            ]
        ];
    }
}
