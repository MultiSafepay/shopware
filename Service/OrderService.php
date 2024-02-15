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
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OrderService
 *
 * @package MltisafeMultiSafepayPayment\Service
 */
class OrderService
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ModelManager
     */
    private $em;

    /**
     * OrderService constructor
     *
     * @param ModelManager $modelManager
     * @param $container
     */
    public function __construct(ModelManager $modelManager, $container)
    {
        $this->em = $modelManager;
        $this->container = $container;
    }

    /**
     * Cancel the order
     *
     * @param Order $order
     * @return void
     */
    public function cancelOrder(Order $order): void
    {
        $getModels = $this->container->get('models');
        /** @var Status $statusCanceled */
        $statusCanceled = $getModels ? $getModels
            ->getRepository(Status::class)
            ->find(Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED)
            : null;

        // Move the order to the canceled status
        if (!is_null($statusCanceled)) {
            $order->setPaymentStatus($statusCanceled);
            // Save order status
            try {
                $this->em->persist($order);
                $this->em->flush();
            } catch (Exception $exception) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::ERROR,
                    'Could not save the order status after canceling the order',
                    [
                        'TransactionId' => $order->getTransactionId(),
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Action' => 'cancelOrder',
                        'Exception' => get_class($exception) . ': ' . $exception->getMessage()
                    ]
                );
            }
        }
    }
}
