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
use MltisafeMultiSafepayPayment\MltisafeMultiSafepayPayment;
use MultiSafepay\Api\Transactions\OrderRequest;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Order\Order;

class SecondsActiveBuilder implements OrderRequestBuilderInterface
{
    private $configReader;
    public function __construct(CachedConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }

    public function build(OrderRequest $orderRequest, $controller, $container): OrderRequest
    {
        return $orderRequest->addSecondsActive($this->getSecondsActive($container));
    }

    private function getSecondsActive($container): int
    {
        $shop = $container->get('shop');
        $pluginConfig = $container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $shop);
        return Helper::getSecondsActive($pluginConfig["msp_time_label"], $pluginConfig["msp_time_active"]);
    }

    public function buildBackendOrder(OrderRequest $orderRequest, Order $order): OrderRequest
    {
        $pluginConfig = $this->configReader
            ->getByPluginName(MltisafeMultiSafepayPayment::PLUGIN_NAME, $order->getShop());

        return $orderRequest->addSecondsActive(Helper::getSecondsActive($pluginConfig["msp_time_label"], $pluginConfig["msp_time_active"]));
    }
}
