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
use MltisafeMultiSafepayPayment\Service\CachedConfigService;
use MultiSafepay\Api\Transactions\OrderRequest;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;

/**
 * Class SecondsActiveBuilder
 *
 * @package MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder
 */
class SecondsActiveBuilder implements OrderRequestBuilderInterface
{
    /**
     * This property holds the configuration reader instance
     */
    private $cachedConfigReader;

    /**
     * This property holds the shop instance
     *
     * It can be an integer if the version is higher than 5.7
     * or a Shop instance if it is lower
     *
     * @var int|Shop
     */
    private $shop;

    /**
     * SecondsActiveBuilder constructor
     *
     * @param $cachedConfigReader
     */
    public function __construct($cachedConfigReader)
    {
        $this->cachedConfigReader = $cachedConfigReader;
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
        [$this->cachedConfigReader, $this->shop] = (new CachedConfigService($container))->selectConfigReader();

        return $orderRequest->addSecondsActive($this->getSecondsActive());
    }

    /**
     * Get the seconds active
     *
     * @return int
     */
    private function getSecondsActive(): int
    {
        $pluginConfig = $this->cachedConfigReader ? $this->cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $this->shop) : [];

        return $pluginConfig ? Helper::getSecondsActive($pluginConfig['msp_time_label'], $pluginConfig['msp_time_active']) : 0;
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
        [$this->cachedConfigReader, $this->shop] = (new CachedConfigService(Shopware()->Container()))->selectConfigReader();

        $pluginConfig = $this->cachedConfigReader ? $this->cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $this->shop) : [];
        $secondsActive = $pluginConfig ? Helper::getSecondsActive($pluginConfig['msp_time_label'], $pluginConfig['msp_time_active']) : 0;

        return $orderRequest->addSecondsActive($secondsActive);
    }
}
