<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Shopware
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) 2022 MultiSafepay, Inc. (https://www.multisafepay.com)
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
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use Shopware\Components\OptinServiceInterface;
use Shopware\Models\Order\Order;

class PaymentOptionsBuilder implements OrderRequestBuilderInterface
{
    public function build(OrderRequest $orderRequest, $controller, $container): OrderRequest
    {
        $paymentOptions = new PaymentOptions();
        $hash = $this->createHashFromSession($container);
        $router = $controller->Front()->Router();

        $paymentOptions->addCancelUrl($router->assemble(['action' => 'cancel', 'forceSecure' => true, 'hash' => $hash]))
            ->addNotificationUrl($router->assemble(['action' => 'notify', 'forceSecure' => true, 'hash' => $hash]))
            ->addRedirectUrl($router->assemble(['action' => 'return', 'forceSecure' => true, 'hash' => $hash]));

        return $orderRequest->addPaymentOptions($paymentOptions);
    }

    private function createHashFromSession($container)
    {
        /** @var  $optinService \Shopware\Components\OptinService */
        $optinService = $container->get('shopware.components.optin_service');
        $pluginConfig = $container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $container->get('shop'));


        return $optinService->add(
            OptinServiceInterface::TYPE_CUSTOMER_LOGIN_FROM_BACKEND,
            Helper::getSecondsActive($pluginConfig["msp_time_label"], $pluginConfig["msp_time_active"]),
            [
                'sessionId' => Shopware()->Session()->get('sessionId'),
                'sessionData' => json_encode($_SESSION['Shopware'])
            ]
        );
    }

    public function buildBackendOrder(OrderRequest $orderRequest, Order $order): OrderRequest
    {
        return $orderRequest->addPaymentOptions(
            (new PaymentOptions())
                ->addNotificationUrl(Shopware()->Front()->Router()->assemble([
                    'module' => 'frontend',
                    'controller' => 'MultiSafepayPayment',
                    'action' => 'notify',
                    'forceSecure' => true
                ]))
        );
    }
}
