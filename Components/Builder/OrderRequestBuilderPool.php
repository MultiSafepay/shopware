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

namespace MltisafeMultiSafepayPayment\Components\Builder;

use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\CustomerBuilder;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\DeliveryBuilder;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\DescriptionBuilder;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\PaymentOptionsBuilder;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\PluginDataBuilder;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\SecondsActiveBuilder;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\ShoppingCartBuilder;

/**
 * Class OrderRequestBuilderPool
 *
 * @package MltisafeMultiSafepayPayment\Components\Builder
 */
class OrderRequestBuilderPool
{
    /**
     * @var CustomerBuilder
     */
    private $customerBuilder;

    /**
     * @var PaymentOptionsBuilder
     */
    private $paymentOptionsBuilder;

    /**
     * @var ShoppingCartBuilder
     */
    private $shoppingCartBuilder;

    /**
     * @var DescriptionBuilder
     */
    private $descriptionBuilder;

    /**
     * @var DeliveryBuilder
     */
    private $deliveryBuilder;

    /**
     * @var SecondsActiveBuilder
     */
    private $secondsActiveBuilder;

    /**
     * @var PluginDataBuilder
     */
    private $pluginDataBuilder;

    /**
     * OrderRequestBuilderPool constructor
     *
     * @param CustomerBuilder $customerBuilder
     * @param PaymentOptionsBuilder $paymentOptionsBuilder
     * @param DescriptionBuilder $descriptionBuilder
     * @param SecondsActiveBuilder $secondsActiveBuilder
     * @param PluginDataBuilder $pluginDataBuilder
     * @param DeliveryBuilder $deliveryBuilder
     * @param ShoppingCartBuilder $shoppingCartBuilder
     */
    public function __construct(
        CustomerBuilder $customerBuilder,
        PaymentOptionsBuilder $paymentOptionsBuilder,
        DescriptionBuilder $descriptionBuilder,
        SecondsActiveBuilder $secondsActiveBuilder,
        PluginDataBuilder $pluginDataBuilder,
        DeliveryBuilder $deliveryBuilder,
        ShoppingCartBuilder $shoppingCartBuilder
    ) {
        $this->paymentOptionsBuilder = $paymentOptionsBuilder;
        $this->customerBuilder = $customerBuilder;
        $this->descriptionBuilder = $descriptionBuilder;
        $this->secondsActiveBuilder = $secondsActiveBuilder;
        $this->pluginDataBuilder = $pluginDataBuilder;
        $this->deliveryBuilder = $deliveryBuilder;
        $this->shoppingCartBuilder = $shoppingCartBuilder;
    }

    /**
     * Get the order request builder pool
     *
     * @return array
     */
    public function getOrderRequestBuilderPool(): array
    {
        return [
            'shopping_cart' => $this->shoppingCartBuilder,
            'description' => $this->descriptionBuilder,
            'payment_options' => $this->paymentOptionsBuilder,
            'customer' => $this->customerBuilder,
            'delivery' => $this->deliveryBuilder,
            'seconds_active' => $this->secondsActiveBuilder,
            'plugin_data' => $this->pluginDataBuilder
        ];
    }
}
