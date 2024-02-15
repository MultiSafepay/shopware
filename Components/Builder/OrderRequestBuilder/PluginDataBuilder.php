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
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use Shopware\Models\Order\Order;
use Shopware_Components_Config;

/**
 * Class PluginDataBuilder
 *
 * @package MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder
 */
class PluginDataBuilder implements OrderRequestBuilderInterface
{
    /**
     * @var Shopware_Components_Config
     */
    private $shopwareConfig;

    /**
     * PluginDataBuilder constructor
     *
     * @param Shopware_Components_Config $shopwareConfig
     */
    public function __construct(Shopware_Components_Config $shopwareConfig)
    {
        $this->shopwareConfig = $shopwareConfig;
    }

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
        $baseUrl = $this->generateShopRootUrl($container->get('shop'));

        return $orderRequest->addPluginDetails($this->getPluginDetails($baseUrl));
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
        $baseUrl = $this->generateShopRootUrl($order->getShop());

        return $orderRequest->addPluginDetails($this->getPluginDetails($baseUrl));
    }

    /**
     * Get the plugin details
     *
     * @param string|null $baseUrl
     * @return PluginDetails
     */
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

    /**
     * Generate the shop root url
     *
     * @param $shop
     * @return string
     */
    private function generateShopRootUrl($shop): string
    {
        return implode('/', [
            $shop->getSecure() ? 'https:/' : 'http:/',
            $shop->getHost() ?? '',
            $shop->getBaseUrl() ?? '',
        ]);
    }
}
