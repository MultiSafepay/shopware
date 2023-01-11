<?php declare(strict_types=1);
namespace MltisafeMultiSafepayPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder;
use MltisafeMultiSafepayPayment\Components\Factory\Client;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Tax\Tax;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderBackendController implements SubscriberInterface
{
    public $container;
    public $shopwareConfig;
    private $client;
    private $orderRequestBuilder;

    /**
     * OrderBackendController constructor.
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
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SaveOrder_OrderCreated' => 'onGetBackendController',
        ];
    }

    /**
     * Create a payment link when the order is a backend order and is a MultiSafepay payment method
     *
     * @param \Enlight_Event_EventArgs $args
     * @throws \Zend_Db_Adapter_Exception
     */
    public function onGetBackendController(\Enlight_Event_EventArgs $args)
    {
        // Only when event is triggered from SwagBackendOrder
        // @phpstan-ignore-next-line */
        if (!$args->getSubject() instanceof \Shopware_Controllers_Backend_SwagBackendOrder) {
            return;
        }

        /** @var Order $order */
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($args->get('orderId'));

        if (substr($order->getPayment()->getName(), 0, 13) !== "multisafepay_") {
            return;
        }

        $transactionId = $this->container->get('multi_safepay_payment.components.quotenumber')->getNextQuotenumber();

        $order->setTransactionId($transactionId);
        $orderRequest = $this->orderRequestBuilder->buildBackendOrder($order);
        $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $order->getShop());

        try {
            $response = $this->client->getSdk($pluginConfig)->getTransactionManager()->create($orderRequest);
        } catch (\Exception $e) {
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


        $attributes = $this->container->get('shopware_attribute.data_loader')->load('s_order_attributes', $order->getId());
        $attributes['multisafepay_payment_link'] = $response->getPaymentUrl();
        $this->container->get('shopware_attribute.data_persister')->persist($attributes, 's_order_attributes', $order->getId());
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getProducts(Order $order)
    {
        $cart['items'] = [];
        $products = Shopware()->Models()->getRepository(Detail::class)->findBy(['order' => $order]);
        /** @var Detail $product */
        foreach ($products as $product) {
            $unitPrice = $product->getPrice() - ($product->getPrice() / (100 + $product->getTaxRate()) * $product->getTaxRate());
            $cart['items'][] = [
                "name" => $product->getArticleName(),
                "unit_price" => $unitPrice,
                "quantity" => $product->getQuantity(),
                'merchant_item_id' => $product->getId(),
                'tax_table_selector' => $product->getTax()->getName()
            ];
        }

        $cart['items'][] = [
            "name" => 'Shipping',
            "unit_price" => $order->getInvoiceShippingNet(),
            "quantity" => 1,
            'merchant_item_id' => 'msp-shipping',
            'tax_table_selector' => 'shipping-tax'
        ];

        return $cart;
    }

    /**
     * @param Order $order
     * @return array
     */
    protected function getTaxRates(Order $order)
    {
        $taxTables['tax_tables']['alternate'] = [];
        $taxRates = Shopware()->Models()->getRepository(Tax::class)->findAll();
        foreach ($taxRates as $taxRate) {
            $taxTables['tax_tables']['alternate'][] = [
                'name' => $taxRate->getName(),
                'rules' => [[
                    'rate' => $taxRate->getTax() / 100
                ]]
            ];
        }

        $taxTables['tax_tables']['alternate'][] = [
            'name' => 'shipping-tax',
            'rules' => [
                ['rate' => $order->getInvoiceShippingTaxRate() / 100]
            ]
        ];

        return $taxTables;
    }
}
