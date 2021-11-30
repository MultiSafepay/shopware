<?php


namespace MltisafeMultiSafepayPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use MltisafeMultiSafepayPayment\Components\API\MspClient;
use MltisafeMultiSafepayPayment\Components\Gateways;
use MltisafeMultiSafepayPayment\Components\Helper;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Tax\Tax;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OrderBackendController implements SubscriberInterface
{
    public $container;
    public $shopwareConfig;

    /**
     * OrderBackendController constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->shopwareConfig = $this->container->get('config');
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
        if (!$args->getSubject() instanceof \Shopware_Controllers_Backend_SwagBackendOrder) {
            return;
        }

        /** @var Order $order */
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->find($args->get('orderId'));

        if (substr($order->getPayment()->getName(), 0, 13) !== "multisafepay_") {
            return;
        }


        $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $order->getShop());
        $transactionId = $this->container->get('multi_safepay_payment.components.quotenumber')->getNextQuotenumber();

        $order->setTransactionId($transactionId);

        list($street, $housenumber) = Helper::parseAddress($order->getBilling()->getStreet(), $order->getBilling()->getAdditionalAddressLine1());
        list($sStreet, $sHousenumber) = Helper::parseAddress($order->getShipping()->getStreet(), $order->getShipping()->getAdditionalAddressLine1());

        $orderData = [
            "type" => 'paymentlink',
            "order_id" => $transactionId,
            "currency" => $order->getCurrency(),
            "amount" => round($order->getInvoiceAmount() * 100),
            "description" => "Order #" . $transactionId,
            "manual" => "false",
            "gateway" => Gateways::getGatewayCode(substr($order->getPayment()->getName(), 13)),
            "seconds_active" => Helper::getSecondsActive($pluginConfig["msp_time_label"], $pluginConfig["msp_time_active"]),
            "payment_options" => [
                "notification_url" => Shopware()->Front()->Router()->assemble([
                    'module' => 'frontend',
                    'controller' => 'MultiSafepayPayment',
                    'action' => 'notify',
                    'forceSecure' => true
                ]),
                "close_window" => "true",
            ],
            "customer" => [
                "locale" => $this->container->get('shop')->getLocale()->getLocale(),
                "first_name" => $order->getBilling()->getFirstName(),
                "last_name" => $order->getBilling()->getLastName(),
                "address1" => $street,
                "address2" => $order->getBilling()->getAdditionalAddressLine2(),
                "house_number" => $housenumber,
                "zip_code" => $order->getBilling()->getZipCode(),
                "city" => $order->getBilling()->getCity(),
                "state" => $order->getBilling()->getState() ? $order->getBilling()->getState()->getShortCode() : null,
                "country" => $order->getBilling()->getCountry()->getIso(),
                "phone" => $order->getBilling()->getPhone(),
                "email" => $order->getCustomer()->getEmail(),
            ],
            "delivery" => [
                "first_name" => $order->getShipping()->getFirstName(),
                "last_name" => $order->getShipping()->getLastName(),
                "address1" => $sStreet,
                "address2" => $order->getShipping()->getAdditionalAddressLine2(),
                "house_number" => $sHousenumber,
                "zip_code" => $order->getShipping()->getZipCode(),
                "city" => $order->getShipping()->getCity(),
                "state" => $order->getShipping()->getState() ? $order->getShipping()->getState()->getShortCode() : null,
                "country" => $order->getShipping()->getCountry()->getIso(),
                "phone" => $order->getShipping()->getPhone(),
                "email" => $order->getCustomer()->getEmail(),
            ],
            "plugin" => [
                "shop" => "Shopware" . ' ' . $this->shopwareConfig->get('version'),
                "shop_version" => $this->shopwareConfig->get('version'),
                "plugin_version" => ' - Plugin ' . Helper::getPluginVersion(),
                "partner" => "MultiSafepay",
            ],
            "shopping_cart" => $this->getProducts($order),
            "checkout_options" => $this->getTaxRates($order),
        ];


        $msp = new MspClient();
        $msp->setApiKey($pluginConfig['msp_api_key']);
        if (!$pluginConfig['msp_environment']) {
            $msp->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $msp->setApiUrl('https://api.multisafepay.com/v1/json/');
        }

        try {
            $msp->orders->post($orderData);
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
        $attributes['multisafepay_payment_link'] = $msp->orders->getPaymentLink();
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
