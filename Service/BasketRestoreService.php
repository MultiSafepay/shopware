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

namespace MltisafeMultiSafepayPayment\Service;

use Enlight_Event_Exception;
use Enlight_Exception;
use sBasket;
use Shopware\Bundle\OrderBundle\Service\CalculationServiceInterface;
use Shopware\Models\Order\Order;
use Zend_Db_Adapter_Exception;

/**
 * Class BasketRestoreService
 *
 * @package MltisafeMultiSafepayPayment\Service
 */
class BasketRestoreService
{
    /**
     * @var object|sBasket|null
     */
    private $basket;

    /**
     * @var string
     */
    private $shopwareVersion;

    /**
     * BasketRestoreService constructor
     *
     * @param $container
     */
    public function __construct($container)
    {
        $this->shopwareVersion = $container->getParameter('shopware.release.version');
        $this->basket = Shopware()->Modules()->Basket();
    }

    /**
     * Restore the basket by order
     *
     * @param Order $order
     *
     * @return void
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     */
    public function restoreBasketByOrder(Order $order): void
    {
        // Get the order articles
        $orderDetails = $order->getDetails();

        foreach ($orderDetails as $orderDetail) {
            // Check if the article is a normal product
            if ($orderDetail->getMode() === 0) {
                $this->basket->sAddArticle(
                    $orderDetail->getArticleNumber(),
                    $orderDetail->getQuantity()
                );
            }
        }

        if (version_compare($this->shopwareVersion, '5.7', '>=')) {
            /** @var CalculationServiceInterface $service */
            $service = Shopware()->Container()->get(CalculationServiceInterface::class);
            $service->recalculateOrderTotals($order);
        } else {
            $order->calculateInvoiceAmount();
        }

        // Refresh the basket
        $this->basket->sRefreshBasket();
    }
}
