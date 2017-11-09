<?php

/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License and of our
 * proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Multisafepay
 * @copyright  Copyright (c) 2014, MultiSafepay (http://www.multisafepay.com)
 * @version    $Id$
 * @author     Multisafepay.
 * @author     $Author$
 */
 
class Shopware_Controllers_Frontend_PaymentMultisafepay extends Shopware_Controllers_Frontend_Payment {

    private static $pay_to_email;
    private static $secret_word;
    private static $hide_login = 1;
    private static $logo_url;
    private static $recipient_description;
    private static $multisafepay_url;
    private $sid;
    
    //Shopware\Models\Order\Status
    const PAYMENT_STATE_COMPLETELY_PAID = 12;
    const PAYMENT_STATE_OPEN = 17;
    const PAYMENT_STATE_RE_CREDITING = 20;
    const PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED = 35;

    /**
     * This function is called when the order is confirmed.
     */
    public function indexAction() {
        //Check if selected gateway was in a MultiSafepay method, if not then return to the checkout page.
        switch ($this->getPaymentShortName()) {
            case 'multisafepay_wallet' :
            case 'multisafepay_visa' :
            case 'multisafepay_mastercard' :
            case 'multisafepay_amex' :
            case 'multisafepay_maestro' :
            case 'multisafepay_banktrans' :
            case 'multisafepay_dirdeb' :
            case 'multisafepay_directbank' :
            case 'multisafepay_giropay' :
            case 'multisafepay_ideal' :
            case 'multisafepay_mistercash' :
            case 'multisafepay_paypal' :
            case 'multisafepay_eps' :
            case 'multisafepay_ferbuy' :
            case 'multisafepay_klarna' :
            case 'multisafepay_payafter' :

                // If a MultiSafepay gateway was selected then redirect to the gateway action url
                if (preg_match('/multisafepay_(.+)/', $this->getPaymentShortName(), $matches))
                    $payment_methods = strtoupper($matches[1]);
                return $this->redirect(array('action' => 'gateway', 'payment' => $payment_methods, 'forceSecure' => true));
                break;
            default :
                return $this->redirect(array('controller' => 'checkout'));
        }
    }

    /**
     * 	Gateway action, called when the order is confirmed and the validation is successful within the index function.
     */
    public function gatewayAction() {
        $config = Shopware()->Plugins()->Frontend()->MltisafePaymentMultiSafepay()->Config();


        //Include the MultiSafepay API, used to build the transaction request and start the transaction.
        include('Api/MultiSafepay.combined.php');
        $msp = new MultiSafepay();
        $router = $this->Front()->Router();
        $transaction_id = $this->createPaymentUniqueId();
        $userinfo = $this->getUser();


        if (!$userinfo) // Redirect to payment failed page
            $this->forward('cancel');

        $uniquePaymentID = $this->createPaymentUniqueId();

        //Check if we need the Live or Test environment
        if ($config->get("environment") == 1) {
            $environment = '0';
        } else {
            $environment = '1';
        }
        $basket = $this->getBasket();



        /*
         * Create Transaction Request
         */
        $msp = new MultiSafepay();


        /*
         * 	Set the plugin info data
         */
        $msp->plugin_name = 'Shopware ' . $config->get('version');
        $msp->version = '(1.0.3)';
        $msp->plugin['shop'] = 'Shopware';
        $msp->plugin['shop_version'] = $config->get('version');
        $msp->plugin['plugin_version'] = '1.0.3';



        /*
         * Merchant Settings
         */
        $msp->test = $environment;
        $msp->merchant['account_id'] = $config->get("accountid");
        $msp->merchant['site_id'] = $config->get("siteid");
        $msp->merchant['site_code'] = $config->get("securecode");
        $msp->merchant['notification_url'] = $router->assemble(array('action' => 'notify', 'forceSecure' => true, 'appendSession' => true)) . '&type=initial';
        $msp->merchant['cancel_url'] = $router->assemble(array('action' => 'cancel', 'forceSecure' => true));
        //$msp->merchant['cancel_url'] = $router->assemble(array('action' => 'cancel', 'forceSecure' => true)) . '?uniquePaymentID=' . $uniquePaymentID;
        $msp->merchant['redirect_url'] = $router->assemble(array('action' => 'finish', 'forceSecure' => true)) . '?uniquePaymentID=' . $uniquePaymentID . '&transactionID=' . $transaction_id;
        $msp->merchant['close_window'] = true;

        /*
         * Customer Details - supply if available
         */
        $msp->customer['locale'] = Shopware()->System()->sLanguageData[Shopware()->System()->sLanguage]["isocode"];
        $msp->customer['firstname'] = $userinfo["billingaddress"]["firstname"];
        $msp->customer['lastname'] = $userinfo["billingaddress"]["lastname"];
        $msp->customer['zipcode'] = $userinfo["billingaddress"]["zipcode"];
        $msp->customer['city'] = $userinfo["billingaddress"]["city"];
        $msp->customer['country'] = $userinfo["additional"]["country"]["countryiso"];
        $msp->customer['email'] = $userinfo["additional"]["user"]["email"];



        $addressData = $this->parseCustomerAddress($userinfo["billingaddress"]["street"]);
        if (isset($addressData['housenumber']) && !empty($addressData['housenumber'])) {
            $street = $addressData['address'];
            $housenumber = $addressData['housenumber'];
        } else {
            $street = $userinfo["billingaddress"]["street"];
            $housenumber = $userinfo["billingaddress"]["streetnumber"];
        }


        $msp->customer['address1'] = $street;
        $msp->customer['housenumber'] = $housenumber;
        $msp->customer['phone'] = $userinfo["billingaddress"]["phone"];

        /*
         * Transaction Details
         */
        $msp->transaction['id'] = $transaction_id; // generally the shop's order ID is used here
        $msp->transaction['currency'] = $this->getCurrencyShortName();
        $msp->transaction['amount'] = (float) str_replace(',', '.', $this->getAmount()) * 100; //$this->getAmount() * 100; // cents
        $msp->transaction['description'] = 'Order #' . $msp->transaction['id'];
        //$msp->transaction['items']        = 	$items;
        $msp->transaction['var1'] = $uniquePaymentID;
        $msp->transaction['gateway'] = $this->Request()->payment;

        //request the payment link

        if ($this->Request()->payment == 'PAYAFTER' || $this->Request()->payment == 'KLARNA') {
            //For Pay After Delivery we need all cart contents, including fee's, discount, shippingmethod etc. We will store it within the $basket
            $items = $basket['content'];
            
            //Add none tax table
            $table = new MspAlternateTaxTable();
            $table->name = 'none';
            $rule = new MspAlternateTaxRule('0.00');
            $table->AddAlternateTaxRules($rule);
            $msp->cart->AddAlternateTaxTables($table);
            
            //Add shipping
            if (isset($basket['sShippingcostsNet'])) {
                $diff = $basket['sShippingcostsWithTax'] - $basket['sShippingcostsNet'];
                $cost = ($diff / $basket['sShippingcostsNet']) * 100;
                $shipping_percentage = 1 + round($cost, 0) / 100;
                $shippin_exc_tac_calculated = $basket['sShippingcostsWithTax'] / $shipping_percentage;
                $shipping_percentage = 0 + round($cost, 0) / 100;
                $shipping_cost_orig = $basket['sShippingcostsNet'];

                $table = new MspAlternateTaxTable();
                $table->name = $shipping_percentage;
                $rule = new MspAlternateTaxRule($shipping_percentage);
                $table->AddAlternateTaxRules($rule);
                $msp->cart->AddAlternateTaxTables($table);                

                $c_item = new MspItem('Shipping', 'Shipping', 1, $shippin_exc_tac_calculated, 'KG', 0);
                $c_item->SetMerchantItemId('msp-shipping');
                if (isset($basket['sShippingcostsTax'])) {
                    $c_item->SetTaxTableSelector($shipping_percentage);
                } else {
                    $c_item->SetTaxTableSelector('none');
                }
                $msp->cart->AddItem($c_item);
            }

            //Create a tax array that will contain all used taxes. These will then be added to the transaction request
            $tax_array = array();

            //add all tax rates to the array
            foreach ($items as $product => $data) {
                if (isset($data['additional_details']['tax'])) {
                    $tax_array[$data['additional_details']['tax']] = $data['additional_details']['tax'] / 100;
                } elseif (isset($data['tax_rate'])) {
                    $tax_array[$data['tax_rate']] = $data['tax_rate'] / 100;
                }
            }

            //Add the taxtables to the request
            foreach ($tax_array as $name => $rate) {
                $table = new MspAlternateTaxTable();
                $table->name = $name;
                $rule = new MspAlternateTaxRule($rate);
                $table->AddAlternateTaxRules($rule);
                $msp->cart->AddAlternateTaxTables($table);
            }

            //Add all products to the request
            foreach ($items as $product => $data) {
                //if set then this is a product
                if (isset($data['additional_details']['tax'])) {
                    $c_item = new MspItem($data['additional_details']['articleName'], $data['additional_details']['description'], $data['quantity'], $data['netprice'], $data['additional_details']['sUnit']['unit'], $data['additional_details']['weight']);
                    $msp->cart->AddItem($c_item);
                    $c_item->SetMerchantItemId($data['id']);
                    $c_item->SetTaxTableSelector($data['additional_details']['tax']);
                } elseif (isset($data['tax_rate'])) {
                    $c_item = new MspItem($data['articlename'], $data['additional_details']['description'], $data['quantity'], $data['netprice'], $data['additional_details']['sUnit']['unit'], $data['additional_details']['weight']);
                    $msp->cart->AddItem($c_item);
                    $c_item->SetMerchantItemId($data['id']);
                    $c_item->SetTaxTableSelector($data['tax_rate']);
                }
            }
            $url = $msp->startCheckout();
        } else {
            $url = $msp->startTransaction();
        }

        /*
         * 	Check if there was an error while requesting the payment link
         */
        if ($msp->error) {
            // TODO LOAD FAILED TEMPLATE WITH ERROR CODE
            echo "Error " . $msp->error_code . ": " . $msp->error;
            exit();
        } else {
            //There was no error while requesting the transaction so we received a payment url (because we don't use the direct payment requests for now) so redirect the customer to the payment page.
            //$this->saveOrder($transaction_id, $uniquePaymentID);
            $this->redirect($url);
        }
    }

    /*
     * Parses and splits up an address in street and housenumber
     */

    function parseCustomerAddress($street_address) {
        list($address, $apartment) = $this->parseAddress($street_address);
        $customer['address'] = $address;
        $customer['housenumber'] = $apartment;
        return $customer;
    }

    /*
     * Parses and splits up an address in street and housenumber
     */

    function parseAddress($street_address) {
        $address = $street_address;
        $apartment = "";

        $offset = strlen($street_address);

        while (($offset = $this->rstrpos($street_address, ' ', $offset)) !== false) {
            if ($offset < strlen($street_address) - 1 && is_numeric($street_address[$offset + 1])) {
                $address = trim(substr($street_address, 0, $offset));
                $apartment = trim(substr($street_address, $offset + 1));
                break;
            }
        }

        if (empty($apartment) && strlen($street_address) > 0 && is_numeric($street_address[0])) {
            $pos = strpos($street_address, ' ');

            if ($pos !== false) {
                $apartment = trim(substr($street_address, 0, $pos), ", \t\n\r\0\x0B");
                $address = trim(substr($street_address, $pos + 1));
            }
        }

        return array($address, $apartment);
    }

    /*
     * Parses and splits up an address in street and housenumber
     */

    function rstrpos($haystack, $needle, $offset = null) {
        $size = strlen($haystack);

        if (is_null($offset)) {
            $offset = $size;
        }

        $pos = strpos(strrev($haystack), strrev($needle), $size - $offset);

        if ($pos === false) {
            return false;
        }

        return $size - $pos - strlen($needle);
    }

    /**
     * This is the fail action. This should be called whenever we receive errors when requesting the transaction. This must be implemented.
     */
    public function failAction() {
        $this->View()->extendsTemplate('fail.tpl');
    }

    /**
     * This action is called whenever the customer cancelles the transaction at MultiSafepay or an external acquirer.
     */
    public function cancelAction() {
        //$request = $this->Request();
        //$this->savePaymentStatus($request->getParam('transactionid'), $request->getParam('uniquePaymentID'), self::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED, true);                
        return $this->redirect(array('controller' => 'checkout'));
    }

    /**
     * This action is called when the customer is redirected back to the shop. This one saves the order and redirects to finish checkout page.
     */
    public function finishAction() {
        $request = $this->Request();
        $orderNumber = $this->saveOrder($request->getParam('transactionID'), $request->getParam('uniquePaymentID'), NULL, true);
        $this->redirect(array('controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $request->getParam('uniquePaymentID')));
    }

    /**
     * This action is called by the MultiSafepay offline actions system. This one is used to update the 
     */
    public function notifyAction() {
        
        $config = Shopware()->Plugins()->Frontend()->MltisafePaymentMultiSafepay()->Config();
        include('Api/MultiSafepay.combined.php');
        $msp = new MultiSafepay();

        $request = $this->Request();

        $transactionid = $request->getParam('transactionid');
        
        $timestamp = $request->getParam('timestamp');
        if(!isset($timestamp)) {
            echo 'No timestamp is set so we are stopping the callback';exit;
        }        

        $type = $request->getParam('type');

        //Check if we need the Live or Test environment
        if ($config->get("environment") == 1) {
            $environment = '0';
        } else {
            $environment = '1';
        }

        $msp->test = $environment;
        $msp->merchant['account_id'] = $config->get("accountid");
        $msp->merchant['site_id'] = $config->get("siteid");
        $msp->merchant['site_code'] = $config->get("securecode");

        $msp->transaction['id'] = $transactionid;

        //Get the transaction status
        $status = $msp->getStatus();

        //Get all transaction details, this can be used for further processing.
        $details = $msp->details;


        switch ($status) {
            case "initialized":
                $status_verbose = 'Pending';
                $stat_code = self::PAYMENT_STATE_OPEN;
                break;

            case "completed":
                $status_verbose = 'Completed';
                $stat_code = self::PAYMENT_STATE_COMPLETELY_PAID;
                break;
            case "uncleared":
                $status_verbose = 'Pending';
                $stat_code = self::PAYMENT_STATE_OPEN;
                break;
            case "void":
                $status_verbose = 'Cancelled';
                $stat_code = self::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;
            case "declined":
                $status_verbose = 'Declined';
                $stat_code = self::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;
            case "refunded":
                $status_verbose = 'Refunded';
                $stat_code = self::PAYMENT_STATE_RE_CREDITING;
                break;
            case "partial_refunded":
                $status_verbose = 'Partially Refunded';
                $stat_code = self::PAYMENT_STATE_RE_CREDITING;
                break;
            case "expired":
                $status_verbose = 'Expired';
                $stat_code = self::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;
            case "cancelled":
                $status_verbose = 'Cancelled';
                $stat_code = self::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;
            default:
                $status_verbose = 'Pending';
                $stat_code = self::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;
        }

        //if ($status == 'initialized' || $status == 'completed') //TODO ->Check current order status to check if we can/should update.
        // {
        $this->savePaymentStatus($request->getParam('transactionid'), $details['transaction']['var1'], $stat_code, true);
        // }

        if ($type == 'initial') {
            //Setup the url back to the webshop. This one is shown whenever the notification url from within the transaction xml is called. (This is done on the MultiSafepay pages after transaction, if active)
            $router = $this->Front()->Router();
            $ret_url = $router->assemble(array('action' => 'finish', 'forceSecure' => true)) . '?uniquePaymentID=' . $details['transaction']['var1'] . '&transactionID=' . $transactionid;

            //$request = $this->Request();
            //$this->saveOrder($request->getParam('transactionid'), $details['transaction']['var1'], NULL, true);            
            
            echo '<a href="' . $ret_url . '">Return to webshop</a>';
        } else {
            //We show OK when everything has gone OK. The status has been updated etc. OK is shown when the configured Notification url is called. This one is different then the notification url within the transaction request.
            echo 'OK';
        }
    }

}