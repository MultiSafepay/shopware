<?php

/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      MultiSafepay <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2018 MultiSafepay, Inc. (http://www.multisafepay.com)
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR 
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN 
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION 
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

use MltisafeMultiSafepayPayment\Components\API\MspClient;
use MltisafeMultiSafepayPayment\Components\Gateways;
use MltisafeMultiSafepayPayment\Components\Helper;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\OptinServiceInterface;
use Shopware\Models\Order\Status;

class Shopware_Controllers_Frontend_MultiSafepayPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    private $shopwareConfig;
    private $pluginConfig;
    private $quoteNumber;

    public function preDispatch()
    {
        $shop = $this->get('shop');
        $this->shopwareConfig = $this->get('config');
        $this->pluginConfig = $this->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $shop);
        $this->quoteNumber = $this->get('multi_safepay_payment.components.quotenumber');
    }

    /**
     * {@inheritdoc}
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'index',
            'gateway',
            'notify',
            'return',
            'cancel',
        ];
    }

    /**
     * Index action method.
     *
     * Forwards to the correct action.
     */
    public function indexAction()
    {
        if (preg_match('/multisafepay_(.+)/', $this->getPaymentShortName(), $matches)) {
            return $this->redirect(array('action' => 'gateway', 'payment' => $matches[1], 'forceSecure' => true));
        } else {
            return $this->redirect(['controller' => 'checkout']);
        }
    }

    /**
     * Gateway action method.
     *
     * Collects the payment information and transmit it to MultiSafepay.
     */
    public function gatewayAction()
    {
        $router = $this->Front()->Router();
        $userinfo = $this->getUser();
        $basket = $this->getBasket();

        $msp = new MspClient();
        $msp->setApiKey($this->pluginConfig['msp_api_key']);
        if (!$this->pluginConfig['msp_environment']) {
            $msp->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $msp->setApiUrl('https://api.multisafepay.com/v1/json/');
        }

        $checkoutData = $this->getCheckoutData($basket);
        $shoppingCart = $checkoutData["shopping_cart"];
        $checkoutData = $checkoutData["checkout_options"];        

        list($street, $housenumber) = Helper::parseAddress($userinfo["billingaddress"]["street"], $userinfo["billingaddress"]["additionalAddressLine1"]);
        list($shipping_street, $shipping_housenumber) = Helper::parseAddress($userinfo["shippingaddress"]["street"], $userinfo["shippingaddress"]["additionalAddressLine1"]);

        $billing_data = array(
            "locale" => Shopware()->Container()->get('shop')->getLocale()->getLocale(),
            "ip_address" => Helper::getRemoteIP(),
            "forwarded_ip" => Helper::getForwardedIP(),
            "first_name" => $userinfo["billingaddress"]["firstname"],
            "last_name" => $userinfo["billingaddress"]["lastname"],
            "address1" => $street,
            "address2" => $userinfo["billingaddress"]["additionalAddressLine1"],
            "house_number" => $housenumber,
            "zip_code" => $userinfo["billingaddress"]["zipcode"],
            "city" => $userinfo["billingaddress"]["city"],
            "state" => $userinfo["billingaddress"]["state"],
            "country" => $userinfo["additional"]["country"]["countryiso"],
            "phone" => $userinfo["billingaddress"]["phone"],
            "email" => $userinfo["additional"]["user"]["email"],
        );

        $delivery_data = array(
            "first_name" => $userinfo["shippingaddress"]["firstname"],
            "last_name" => $userinfo["shippingaddress"]["lastname"],
            "address1" => $shipping_street,
            "address2" => $userinfo["shippingaddress"]["additionalAddressLine1"],
            "house_number" => $shipping_housenumber,
            "zip_code" => $userinfo["shippingaddress"]["zipcode"],
            "city" => $userinfo["shippingaddress"]["city"],
            "state" => $userinfo["shippingaddress"]["state"],
            "country" => $userinfo["additional"]["countryShipping"]["countryiso"],
            "phone" => $userinfo["shippingaddress"]["phone"],
            "email" => $userinfo["additional"]["user"]["email"],
        );

        $order_id = $this->quoteNumber->getNextQuotenumber();

        $items = "<ul>\n";
        foreach ($basket['content'] as $item => $data) {
            $items .= "<li>" . ($data['quantity'] * 1) . " x : " . $data['articlename'] . "</li>\n";
        }
        $items .= "</ul>\n";

        if($this->container->has('shopware.components.optin_service')){
            /** Since Shopware 5.5.7 removed append sessions. We use optinService*/
            $optinService = $this->container->get('shopware.components.optin_service');
            $hash = $optinService->add(
                OptinServiceInterface::TYPE_CUSTOMER_LOGIN_FROM_BACKEND,
                Helper::getSecondsActive($this->pluginConfig["msp_time_label"], $this->pluginConfig["msp_time_active"]),
                ["sessionId" => Shopware()->Session()->get("sessionId")]
            );
            $paymentOptions = [
                "notification_url" => $router->assemble(['action' => 'notify', 'forceSecure' => true, 'hash' => $hash]),
                "redirect_url" => $router->assemble(['action' => 'return', 'forceSecure' => true, 'hash' => $hash]),
                "cancel_url" => $router->assemble(['action' => 'cancel', 'forceSecure' => true, 'hash' => $hash]),
                "close_window" => "true",
            ];
        }else{
            $paymentOptions = [
                "notification_url" => $router->assemble(['action' => 'notify', 'forceSecure' => true, 'appendSession' => true]) . '&type=initial',
                "redirect_url" => $router->assemble(['action' => 'return', 'forceSecure' => true, 'appendSession' => true]),
                "cancel_url" => $router->assemble(['action' => 'cancel', 'forceSecure' => true, 'appendSession' => true]),
                "close_window" => "true",
                ];
        }


        $order_data = array(
            "type" => Gateways::getGatewayType($this->Request()->payment),
            "order_id" => $order_id,
            "currency" => $this->getCurrencyShortName(),
            "amount" => round($this->getAmount() * 100),
            "description" => "Order #" . $order_id,
            "items" => $items,
            "manual" => "false",
            "gateway" => Gateways::getGatewayCode($this->Request()->payment),
            "seconds_active" => Helper::getSecondsActive($this->pluginConfig["msp_time_label"], $this->pluginConfig["msp_time_active"]),
            "payment_options" => $paymentOptions,
            "customer" => $billing_data,
            "delivery" => $delivery_data,
            "plugin" => array(
                "shop" => "Shopware" . ' ' . $this->shopwareConfig->get('version'),
                "shop_version" => $this->shopwareConfig->get('version'),
                "plugin_version" => ' - Plugin ' . Helper::getPluginVersion(),
                "partner" => "MultiSafepay",
            ),
            "gateway_info" => array(
                "issuer_id" => $this->get('session')->get('ideal_issuer'),
            ),
            "shopping_cart" => $shoppingCart,
            "checkout_options" => $checkoutData,
        );

        if ($order_data['gateway'] == 'IDEAL' && !$order_data['gateway_info']['issuer_id']) {
            $order_data['type'] = 'redirect';
        }

        try {
            $msp->orders->post($order_data);
        } catch (\Exception $e) {
            $this->redirect(['controller' => 'checkout', 'action' => 'shippingPayment', 'multisafepay_error_message' => $e->getMessage()]);
            return;
        }

        $result = $msp->orders->getResult();

        if (!$result->success) {
            $message = "There was an error processing your transaction request, please try again with another payment method.<br />";
            $message .= "Error: " . "{$result->error_code} : {$result->error_info}";
            $this->redirect([
                'controller' => 'checkout',
                'action' => 'shippingPayment',
                'multisafepay_error_message' => urlencode($message)
            ]);
            return;
        }

        $this->redirect($msp->orders->getPaymentLink());
    }

    public function notifyAction()
    {
        $transactionid = $this->Request()->getParam('transactionid');

        $sessionId = $this->getSessionId();

        $this->restoreSession($sessionId);

        $msp = new MspClient();
        $msp->setApiKey($this->pluginConfig['msp_api_key']);
        if (!$this->pluginConfig['msp_environment']) {
            $msp->setApiUrl('https://testapi.multisafepay.com/v1/json/');
        } else {
            $msp->setApiUrl('https://api.multisafepay.com/v1/json/');
        }

        $msporder = $msp->orders->get($endpoint = 'orders', $transactionid);
        $status = $msporder->status;

        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $transactionid]);

        switch ($status) {
            case "initialized":
                $create_order = false;
                $update_order = false;
                break;
            case "expired":
                $create_order = false;
                $update_order = true;
                $payment_status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;                
            case "completed":
                if (is_null($order)) {
                    $create_order = true;
                    $update_order = false;
                } elseif (Helper::orderHasClearedDate($order)) {
                    $create_order = false;
                    $update_order = false;
                } else {
                    $create_order = false;
                    $update_order = true;
                }
                $payment_status = Status::PAYMENT_STATE_COMPLETELY_PAID;
                break;
            case "uncleared":
                $create_order = true;
                $update_order = false;
                $payment_status = Status::PAYMENT_STATE_REVIEW_NECESSARY;
                break;
            case "declined":
                $create_order = false;
                $update_order = true;
                $payment_status = Status::PAYMENT_STATE_NO_CREDIT_APPROVED;
                break;
            case "refunded":
                $create_order = false;
                $update_order = false;

                if ($this->pluginConfig['msp_update_refund_active'] &&
                    is_int($this->pluginConfig['msp_update_refund']) &&
                    $this->pluginConfig['msp_update_refund'] > 0
                ) {
                    $payment_status = $this->pluginConfig['msp_update_refund'];
                    $update_order = true;
                }
                break;
            default:
                $create_order = false;
                $update_order = false;
                break;
        }

        if ($create_order) {
            $this->saveOrder($transactionid, $transactionid, $payment_status, true);
        }

        if ($update_order) {
            $this->savePaymentStatus($transactionid, $transactionid, $payment_status, true);
        }

        if (!Helper::orderHasClearedDate($order) && $payment_status == Status::PAYMENT_STATE_COMPLETELY_PAID) {
            $this->setClearedDate($transactionid);
        }

        exit("OK");
    }

    /**
     * Return action method
     */
    public function returnAction()
    {
        $sessionId = $this->getSessionId();

        $this->restoreSession($sessionId);
        $this->saveOrder($this->Request()->transactionid, $this->Request()->transactionid, null, true);
        $this->redirect(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $this->Request()->transactionid]);
    }

    /**
     * Cancel action method
     */
    public function cancelAction()
    {
        $this->redirect(['controller' => 'checkout']);
    }

    private function restoreSession($sessionId)
    {
        \Enlight_Components_Session::writeClose();
        \Enlight_Components_Session::setId($sessionId);
        \Enlight_Components_Session::start();
    }

    private function getCheckoutData($basket)
    {
        $alternateTaxRates = array();
        $shoppingCart = array();
        $rates = array();
        $items = $basket['content'];

        foreach ($items as $product => $data) {
            $rate = $data['tax_rate'] + 0;
            $rates[$rate] = $rate;

            $shoppingCart['shopping_cart']['items'][] = array(
                "name" => $data['articlename'],
                "description" => $data['additional_details']['description'],
                "unit_price" => $data['netprice'],
                "quantity" => $data['quantity'],
                "merchant_item_id" => $data['ordernumber'],
                "tax_table_selector" => (string) number_format($rate, 2),
                "weight" => array(
                    "unit" => $data['additional_details']['sUnit']['unit'],
                    "value" => $data['additional_details']['weight'],
                )
            );
        }

        //Add shipping line item
        $shipping_rate = $basket['sShippingcostsTax'] + 0;
        $rates[$shipping_rate] = $shipping_rate;
        $shipping_info = $this->get('session')->sOrderVariables->sDispatch;
        $shipping_name = !empty($shipping_info['name']) ? $shipping_info['name'] : 'Shipping';
        $shipping_descr = !empty($shipping_info['description']) ? $shipping_info['description'] : 'Shipping';

        $shoppingCart['shopping_cart']['items'][] = array(
            "name" => $shipping_name,
            "description" => $shipping_descr,
            "unit_price" => $basket['sShippingcostsNet'],
            "quantity" => "1",
            "merchant_item_id" => "msp-shipping",
            "tax_table_selector" => (string) number_format($shipping_rate, 2),
            "weight" => array(
                "unit" => "KG",
                "value" => "0",
            )
        );

        //Add alternate tax rates
        foreach ($rates as $index => $rate){
            $alternateTaxRates['tax_tables']['alternate'][] = array(
                 "standalone" => "true",
                 "name" => (string) number_format($rate, 2),
                 "rules" => array(
                     array("rate" => $rate / 100)
                 ),
             );
         }        

        $checkoutData["shopping_cart"] = $shoppingCart['shopping_cart'];
        $checkoutData["checkout_options"] = $alternateTaxRates;
        return $checkoutData;
    }

    private function setClearedDate($transactionid)
    {
        $order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(['transactionId' => $transactionid]);

        //Check if date has not been set yet
        if(!Helper::orderHasClearedDate($order)){
            $order->setClearedDate(new \DateTime());
            $this->container->get('models')->flush($order);
        }
    }

    private function getSessionId()
    {
        if($this->container->has('shopware.components.optin_service') &&
            !empty($this->Request()->getParam('hash'))
        ) {
            $optinService = $this->container->get('shopware.components.optin_service');
            $hashArray = $optinService->get(
                OptinServiceInterface::TYPE_CUSTOMER_LOGIN_FROM_BACKEND,
                $this->Request()->getParam('hash')
            );
            return $hashArray['sessionId'];
        }

        $shop = $this->Request()->getParam('__shop');
        return $this->Request()->getParam('session-' . $shop);

    }
}
