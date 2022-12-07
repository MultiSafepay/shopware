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
namespace MltisafeMultiSafepayPayment\Components\Builder;

use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder\OrderRequestBuilderInterface;
use MltisafeMultiSafepayPayment\Components\Gateways;
use MltisafeMultiSafepayPayment\Components\Quotenumber;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\GatewayInfo\Issuer;
use MultiSafepay\ValueObject\Money;
use Shopware\Models\Order\Order;

class OrderRequestBuilder
{
    private $orderRequestBuilderPool;
    private $quoteNumber;

    public function __construct(OrderRequestBuilderPool $orderRequestBuilderPool, Quotenumber $quoteNumber)
    {
        $this->orderRequestBuilderPool = $orderRequestBuilderPool;
        $this->quoteNumber = $quoteNumber;
    }

    /**
     * @param $controller
     * @param $container
     * @param string $signature
     * @return OrderRequest
     */
    public function build($controller, $container, string $signature): OrderRequest
    {
        $quoteNumber = $container->get('multi_safepay_payment.components.quotenumber');
        $orderId = $quoteNumber->getNextQuotenumber();

        $orderRequest = new OrderRequest();

        $orderRequest->addOrderId($orderId)
            ->addMoney(
                new Money(
                    $controller->getAmount() * 100,
                    $controller->getCurrencyShortName()
                )
            )->addType(Gateways::getGatewayType($controller->Request()->payment))
            ->addGatewayCode(Gateways::getGatewayCode($controller->Request()->payment))
            ->addData(['var1'=> $signature]);

        if (!empty($controller->get('session')->get('ideal_issuer')) && Gateways::getGatewayCode($controller->Request()->payment) === 'IDEAL') {
            $meta = new Issuer();
            $orderRequest->addGatewayInfo($meta->addIssuerId($controller->get('session')->get('ideal_issuer')));
        }

        /** @var OrderRequestBuilderInterface $builder */
        foreach ($this->orderRequestBuilderPool->getOrderRequestBuilderPool() as $builder) {
            $orderRequest = $builder->build($orderRequest, $controller, $container);
        }

        return $orderRequest;
    }

    public function buildBackendOrder(Order $order): OrderRequest
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
            ->addGatewayCode(Gateways::getGatewayCode(substr($order->getPayment()->getName(), 13)));

        /** @var OrderRequestBuilderInterface $builder */
        foreach ($this->orderRequestBuilderPool->getOrderRequestBuilderPool() as $builder) {
            $orderRequest = $builder->buildBackendOrder($orderRequest, $order);
        }

        return $orderRequest;
    }
}
