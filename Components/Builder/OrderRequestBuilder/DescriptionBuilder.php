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

use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\Description;
use Shopware\Models\Order\Order;

/**
 * Class DescriptionBuilder
 *
 * @package MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder
 */
class DescriptionBuilder implements OrderRequestBuilderInterface
{
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
        return $orderRequest->addDescription(
            (new Description())->addDescription(
                'Payment for order #' . $orderRequest->getOrderId() ?? 'N/A'
            )
        );
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
        return $orderRequest->addDescription(
            (new Description())->addDescription(
                'Payment for order #' . $order->getId() ?? 'N/A'
            )
        );
    }
}
