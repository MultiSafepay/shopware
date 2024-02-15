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

use Exception;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;

/**
 * Class StockService
 *
 * @package MltisafeMultiSafepayPayment\Service
 */
class StockService
{
    /**
     * @var ModelManager
     */
    private $em;

    /**
     * StockService constructor
     *
     * @param ModelManager $modelManager
     */
    public function __construct(ModelManager $modelManager)
    {
        $this->em = $modelManager;
    }

    /**
     * Restore the stock by order
     *
     * @param Order $order
     * @return void
     */
    public function restoreStockByOrder(Order $order): void
    {
        foreach ($order->getDetails() as $detail) {
            $this->restoreStockByDetails($detail, $order);
        }
    }

    /**
     * Restore the stock by detail
     *
     * @param Detail $detail
     * @param Order $order
     * @return void
     */
    public function restoreStockByDetails(Detail $detail, Order $order): void
    {
        $detail->setQuantity(0);
        $container = Shopware()->Container();
        $transactionId = $order->getTransactionId() ?? '';

        try {
            $this->em->persist($detail);
            $this->em->flush($detail);
        } catch (Exception $exception) {
            (new LoggerService($container))->addLog(
                LoggerService::ERROR,
                'Could not save the order detail when restoring the stock',
                [
                    'TransactionId' => $transactionId,
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Exception'     => $exception->getMessage()
                ]
            );
        }

        $articleDetailRepo = $this->em->getRepository(\Shopware\Models\Article\Detail::class);

        /** @var \Shopware\Models\Article\Detail $article */
        $article = $articleDetailRepo->findOneBy(
            ['number' => $detail->getArticleNumber()]
        );

        $article->setInStock($article->getInStock());
        try {
            $this->em->persist($article);
            $this->em->flush($article);
        } catch (Exception $exception) {
            (new LoggerService($container))->addLog(
                LoggerService::ERROR,
                'Could not save the order article when restoring the stock',
                [
                    'TransactionId' => $transactionId,
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Exception'     => $exception->getMessage()
                ]
            );
        }
    }
}
