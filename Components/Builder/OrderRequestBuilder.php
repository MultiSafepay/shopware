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

use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\OrderRequestBuilderInterface;
use MltisafeMultiSafepayPayment\Components\Quotenumber;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\ValueObject\Money;
use Shopware\Models\Order\Order;

/**
 * Class OrderRequestBuilder
 *
 * @package MltisafeMultiSafepayPayment\Components\Builder
 */
class OrderRequestBuilder
{
    /**
     * Direct gateways without 'payment components' fields
     *
     * @var array
     */
    public const DIRECT_GATEWAYS_WITHOUT_COMPONENTS = ['IDEAL', 'PAYPAL'];

    /**
     * @var OrderRequestBuilderPool
     */
    private $orderRequestBuilderPool;

    /**
     * OrderRequestBuilder constructor
     *
     * @param OrderRequestBuilderPool $orderRequestBuilderPool
     */
    public function __construct(OrderRequestBuilderPool $orderRequestBuilderPool)
    {
        $this->orderRequestBuilderPool = $orderRequestBuilderPool;
    }

    /**
     * Build the order
     *
     * @param $controller
     * @param $container
     * @param string $signature
     * @return OrderRequest
     */
    public function build($controller, $container, string $signature): OrderRequest
    {
        /** @var Quotenumber $quoteNumber */
        $quoteNumber = $container->get('multisafepay.components.quotenumber');
        $orderId = $quoteNumber->getNextQuotenumber();

        $orderRequest = new OrderRequest();

        $orderRequest->addOrderId($orderId)
            ->addMoney(
                new Money(
                    $controller->getAmount() * 100,
                    $controller->getCurrencyShortName()
                )
            )->addType('redirect')
            ->addGatewayCode($controller->Request()->payment)
            ->addData(['var1'=> $signature]);

        /** @var OrderRequestBuilderInterface $builder */
        foreach ($this->orderRequestBuilderPool->getOrderRequestBuilderPool() as $builder) {
            $orderRequest = $builder->build($orderRequest, $controller, $container);
        }

        return $orderRequest;
    }

    /**
     * Build the order request from the backend
     *
     * @param Order $order
     * @param string $paymentMethodName
     * @return OrderRequest
     */
    public function buildBackendOrder(Order $order, string $paymentMethodName): OrderRequest
    {
        $transactionId = $order->getTransactionId();
        $orderRequest = new OrderRequest();

        $orderRequest->addOrderId($transactionId)
            ->addMoney(
                new Money(
                    round($order->getInvoiceAmount() * 100),
                    $order->getCurrency()
                )
            )->addType('paymentlink')
            ->addGatewayCode($paymentMethodName);

        /** @var OrderRequestBuilderInterface $builder */
        foreach ($this->orderRequestBuilderPool->getOrderRequestBuilderPool() as $builder) {
            $orderRequest = $builder->buildBackendOrder($orderRequest, $order);
        }

        return $orderRequest;
    }
}
