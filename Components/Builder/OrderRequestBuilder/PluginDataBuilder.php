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
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use Shopware\Models\Order\Order;
use Shopware_Components_Config;

class PluginDataBuilder implements OrderRequestBuilderInterface
{
    private $shopwareConfig;
    public function __construct(Shopware_Components_Config $shopwareConfig)
    {
        $this->shopwareConfig = $shopwareConfig;
    }

    public function build(OrderRequest $orderRequest, $controller, $container): OrderRequest
    {
        $baseUrl = $this->generateShopRootUrl($container->get('shop'));
        return $orderRequest->addPluginDetails($this->getPluginDetails($baseUrl));
    }

    public function buildBackendOrder(OrderRequest $orderRequest, Order $order): OrderRequest
    {
        $baseUrl = $this->generateShopRootUrl($order->getShop());
        return $orderRequest->addPluginDetails($this->getPluginDetails($baseUrl));
    }

    private function getPluginDetails(?string $baseUrl): PluginDetails
    {
        $pluginDetails = (new PluginDetails())
            ->addApplicationName('Shopware ' . $this->shopwareConfig->get('version'))
            ->addApplicationVersion('MultiSafepay')
            ->addPluginVersion(Helper::getPluginVersion());

        if ($baseUrl) {
            $pluginDetails->addShopRootUrl($baseUrl);
        }

        return $pluginDetails;
    }

    private function generateShopRootUrl($shop)
    {
        return implode('/', [
            $shop->getSecure() ? 'https:/' : 'http:/',
            $shop->getHost() ?? '',
            $shop->getBaseUrl() ?? '',
        ]);
    }
}
