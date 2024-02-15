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

namespace MltisafeMultiSafepayPayment\Components\Documents;

use Enlight_Event_Exception;
use Enlight_Exception;
use Shopware\Models\Order\Order;
use Shopware_Components_Document;

/**
 * Class Invoice
 *
 * @package MltisafeMultiSafepayPayment\Components\Documents
 */
class Invoice
{
    /**
     * @var int
     */
    public const INVOICE_DOCUMENT_TYPE = 1;

    /**
     * Create the invoice document
     *
     * @param Order $order
     *
     * @return void
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     */
    public function create(Order $order): void
    {
        $documentId = self::INVOICE_DOCUMENT_TYPE;
        $orderId = $order->getId();
        $orderDocument = Shopware_Components_Document::initDocument(
            $orderId,
            $documentId,
            $this->getDocumentOptions($order)
        );

        $orderDocument->render();
    }

    /**
     * Get the document options
     *
     * @param Order $order
     *
     * @return array
     */
    private function getDocumentOptions(Order $order): array
    {
        $orderTime = $order->getOrderTime();
        $formattedOrderTime = $orderTime ? $orderTime->format('d.m.Y') : null;

        return [
            'netto' => false,
            'bid' => '',
            'voucher' => null,
            'date' => $formattedOrderTime,
            'delivery_date' => null,
            'shippingCostsAsPosition' => true,
            '_renderer' => 'pdf',
            '_preview' => false,
            '_previewForcePagebreak' => null,
            '_previewSample' => null,
            'docComment' => '',
            'forceTaxCheck' => false
        ];
    }
}
