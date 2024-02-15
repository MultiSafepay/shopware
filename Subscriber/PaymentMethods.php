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
use Psr\Http\Client\ClientExceptionInterface;

/**
 * Class PaymentMethods
 *
 * @package MltisafeMultiSafepayPayment\Subscriber
 */
class PaymentMethods implements SubscriberInterface
{
    private $paymentMethod;

    /**
     * PaymentMethods constructor
     *
     * @param PaymentMethodsInstaller $paymentMethodsInstaller
     */
    public function __construct(PaymentMethodsInstaller $paymentMethodsInstaller)
    {
        $this->paymentMethod = $paymentMethodsInstaller;
    }

    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onPostDispatchCheckout'
        ];
    }

    /**
     * On post-dispatch checkout
     *
     * @throws ClientExceptionInterface
     * @return void
     */
    public function onPostDispatchCheckout(): void
    {
        $this->updatePaymentMethods();
    }

    /**
     * Update payment methods
     *
     * @throws ClientExceptionInterface
     * @return void
     */
    private function updatePaymentMethods(): void
    {
        $this->paymentMethod->installPaymentMethods();
    }
}
