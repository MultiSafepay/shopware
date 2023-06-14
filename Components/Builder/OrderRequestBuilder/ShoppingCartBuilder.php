<?php declare(strict_types=1);
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
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
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\Item as CartItem;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\ShoppingCart\ShippingItem;
use MultiSafepay\ValueObject\Money;
use MultiSafepay\ValueObject\Weight;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;

class ShoppingCartBuilder implements OrderRequestBuilderInterface
{
    public function build(OrderRequest $orderRequest, $controller, $container): OrderRequest
    {
        $user = $controller->getUser();
        $chargeVat = $user['additional']['charge_vat'];

        foreach ($controller->getBasket()['content'] as $item) {
            $cart[] = (new CartItem())
                ->addName($item['articlename'])
                ->addDescription($item['additional_details']['description'] ?? '')
                ->addUnitPrice(new Money($item['netprice'] * 100, $controller->getCurrencyShortName()))
                ->addQuantity($item['quantity'])
                ->addMerchantItemId($item['ordernumber'])
                ->addTaxRate($chargeVat ? (float) $item['tax_rate'] : 0)
                ->addTaxTableSelector($chargeVat ? (string)$item['tax_rate'] : '0');

            if ($item['additional_details']['sUnit']['unit'] !== null) {
                /** @var CartItem $lastItem */
                $lastItem = end($cart);
                $lastItem->addWeight(new Weight($item['additional_details']['sUnit']['unit'], $item['additional_details']['weight']));
            }
        }

        if ($controller->getBasket()['sShippingcosts'] > 0) {
            $shippingInfo = $controller->get('session')->sOrderVariables->sDispatch;

            $cart[] = (new ShippingItem())
                ->addName(!empty($shippingInfo['name']) ? $shippingInfo['name'] : 'Shipping')
                ->addDescription(!empty($shippingInfo['description']) ? $shippingInfo['description'] : 'Shipping')
                ->addQuantity(1)
                ->addUnitPrice(new Money($controller->getBasket()['sShippingcostsNet'] * 100, $controller->getCurrencyShortName()))
                ->addTaxRate($chargeVat ? $controller->getBasket()['sShippingcostsTax'] : 0)
                ->addTaxTableSelector($chargeVat ? (string)$controller->getBasket()['sShippingcostsTax'] : '0');
        }

        return $orderRequest->addShoppingCart((new ShoppingCart($cart)));
    }

    public function buildBackendOrder(OrderRequest $orderRequest, Order $order): OrderRequest
    {
        $products = Shopware()->Models()->getRepository(Detail::class)->findBy(['order' => $order]);
        $cart = [];

        /** @var Detail $product */
        foreach ($products as $product) {
            $unitPrice = $product->getPrice() - ($product->getPrice() / (100 + $product->getTaxRate()) * $product->getTaxRate());

            $cart[] = (new CartItem())
                ->addName($product->getArticleName())
                ->addUnitPrice(new Money($unitPrice * 100, $order->getCurrency()))
                ->addQuantity($product->getQuantity())
                ->addMerchantItemId((string)$product->getId())
                ->addTaxRate($product->getTaxRate() ? (float) $product->getTaxRate() : 0)
                ->addTaxTableSelector($product->getTaxRate() ? (string)$product->getTaxRate() : '0');
        }

        $cart[] = (new ShippingItem())
            ->addName('Shipping')
            ->addUnitPrice((new Money($order->getInvoiceShippingNet() * 100, $order->getCurrency())))
            ->addQuantity(1)
            ->addTaxRate($order->getInvoiceShippingTaxRate())
            ->addTaxTableSelector((string)$order->getInvoiceShippingTaxRate());

        return $orderRequest->addShoppingCart((new ShoppingCart($cart)));
    }
}
