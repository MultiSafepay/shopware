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

namespace MltisafeMultiSafepayPayment\Service;

use Shopware\Components\Model\ModelManager;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;

class StockService
{
    private $em;

    public function __construct(ModelManager $modelManager)
    {
        $this->em = $modelManager;
    }

    public function restoreStockByOrder(Order $order)
    {
        foreach ($order->getDetails() as $detail) {
            $this->restoreStockByDetails($detail);
        }
    }

    public function restoreStockByDetails(Detail $detail)
    {
        $detail->setQuantity(0);

        $this->em->persist($detail);
        $this->em->flush($detail);

        $articleDetailRepo = $this->em->getRepository('Shopware\Models\Article\Detail');

        /** @var \Shopware\Models\Article\Detail $article */
        $article = $articleDetailRepo->findOneBy(
            ['number' => $detail->getArticleNumber()]
        );

        $article->setInStock($article->getInStock());
        $this->em->persist($article);
        $this->em->flush($article);
    }
}
