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

namespace MltisafeMultiSafepayPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder;
use MltisafeMultiSafepayPayment\Components\Factory\Client;
use MltisafeMultiSafepayPayment\Service\CachedConfigService;
use MltisafeMultiSafepayPayment\Service\LoggerService;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware_Controllers_Backend_SwagBackendOrder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Zend_Db_Adapter_Exception;

/**
 * Class OrderBackendController
 *
 * @package MltisafeMultiSafepayPayment\Subscriber
 */
class OrderBackendController implements SubscriberInterface
{
    public $container;
    public $shopwareConfig;
    private $client;
    private $orderRequestBuilder;

    /**
     * OrderBackendController constructor
     *
     * @param Client $client
     * @param OrderRequestBuilder $orderRequestBuilder
     * @param ContainerInterface $container
     */
    public function __construct(
        Client              $client,
        OrderRequestBuilder $orderRequestBuilder,
        ContainerInterface  $container
    ) {
        $this->container = $container;
        $this->shopwareConfig = $this->container->get('config');
        $this->client = $client;
        $this->orderRequestBuilder = $orderRequestBuilder;
    }

    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Modules_Order_SaveOrder_OrderCreated' => 'onGetBackendController'
        ];
    }

    /**
     * Create a payment link when the order is a backend order and is a MultiSafepay payment method
     *
     * @param Enlight_Event_EventArgs $args
     * @throws Zend_Db_Adapter_Exception
     */
    public function onGetBackendController(Enlight_Event_EventArgs $args): void
    {
        // Only when event is triggered from SwagBackendOrder
        // @phpstan-ignore-next-line */
        if (!$args->getSubject() instanceof Shopware_Controllers_Backend_SwagBackendOrder) {
            return;
        }

        /** @var Order $order */
        $order = Shopware()->Models()->getRepository(Order::class)->find($args->get('orderId'));

        $paymentFullName = $order->getPayment()->getName();
        $paymentSplitParts = !empty($paymentFullName) ? explode('_', $paymentFullName) : [];
        $paymentMethodName = $paymentSplitParts[1] ?? '';

        if (empty($paymentMethodName) || (isset($paymentSplitParts[0]) && ($paymentSplitParts[0] !== 'multisafepay'))) {
            return;
        }

        // Retrieve the quoteNumber component
        $quoteNumberComponent = $this->container->get('multisafepay.components.quotenumber');
        if (is_null($quoteNumberComponent)) {
            return;
        }

        $transactionId = $quoteNumberComponent->getNextQuotenumber();
        $order->setTransactionId($transactionId);
        $orderRequest = $this->orderRequestBuilder->buildBackendOrder($order, $paymentMethodName);

        // Retrieve the cached_config_reader component
        [$cachedConfigReader, $shop] = (new CachedConfigService($this->container))->selectConfigReader();
        if (is_null($cachedConfigReader)) {
            (new LoggerService($this->container))->addLog(
                LoggerService::WARNING,
                'Could not load plugin configuration',
                [
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found'
                ]
            );
            return;
        }
        $pluginConfig = $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop);

        try {
            $clientSdk = $this->client->getSdk($pluginConfig);
            $transactionManager = $clientSdk->getTransactionManager();
            $response = $transactionManager->create($orderRequest);
        } catch (ApiException | ClientExceptionInterface $exception) {
            (new LoggerService($this->container))->addLog(
                LoggerService::ERROR,
                'API error occurred while trying to create a transaction',
                [
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Exception' => $exception->getMessage()
                ]
            );
            return;
        }

        Shopware()->Db()->update(
            's_order',
            [
                'transactionID' => $transactionId,
                'temporaryID' => $transactionId
            ],
            'id=' . $order->getId()
        );

        // Retrieve the data_loader component
        $dataLoader = $this->container->get('shopware_attribute.data_loader');
        if (is_null($dataLoader)) {
            return;
        }
        $attributes = $dataLoader->load('s_order_attributes', $order->getId());
        $attributes['multisafepay_payment_link'] = $response->getPaymentUrl();

        // Retrieve the data_persister component
        $dataPersister = $this->container->get('shopware_attribute.data_persister');
        if (is_null($dataPersister)) {
            return;
        }
        $dataPersister->persist($attributes, 's_order_attributes', $order->getId());
    }

    /**
     * Get products from order
     *
     * @param Order $order
     * @return array
     */
    protected function getProducts(Order $order): array
    {
        $cart['items'] = [];
        $products = Shopware()->Models()->getRepository(Detail::class)->findBy(['order' => $order]);
        /** @var Detail $product */
        foreach ($products as $product) {
            $unitPrice = $product->getPrice() - ($product->getPrice() / (100 + $product->getTaxRate()) * $product->getTaxRate());
            $taxInfo = $product->getTax();
            $taxName = is_null($taxInfo) ? '' : $taxInfo->getName();

            $cart['items'][] = [
                'name' => $product->getArticleName(),
                'unit_price' => $unitPrice,
                'quantity' => $product->getQuantity(),
                'merchant_item_id' => $product->getId(),
                'tax_table_selector' => $taxName
            ];
        }

        $cart['items'][] = [
            'name' => 'Shipping',
            'unit_price' => $order->getInvoiceShippingNet(),
            'quantity' => 1,
            'merchant_item_id' => 'msp-shipping',
            'tax_table_selector' => 'shipping-tax'
        ];

        return $cart;
    }
}
