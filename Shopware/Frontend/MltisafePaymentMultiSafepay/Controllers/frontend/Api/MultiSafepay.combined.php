<?php

include('msptaxrule.php');
include('msp_gc_parser.php');
include('msp_gc_xmlbuilder.php');
include('mspalternatetaxrule.php');
include('mspalternatetaxtable.php');
include('mspcart.php');
include('mspcustomfield.php');
include('mspcustomfields.php');
include('mspcustomfieldfilter.php');
include('mspcustomfieldoption.php');
include('mspcustomfieldvalidation.php');
include('mspdefaulttaxrule.php');
include('mspflatrateshipping.php');
include('mspitem.php');
include('mspmerchantprivate.php');
include('mspmerchantprivatedata.php');
include('mspmerchantprivateitemdata.php');
include('msppickup.php');
include('mspshippingfilters.php');

/**
 * MultiSafepay class with functions to create a MultiSafepay transaction
 */
class MultiSafepay {

    var $plugin_name = '';
    var $version = '';
    // test or live api
    var $test = false;
    var $custom_api;
    var $extravars = '';
    var $use_shipping_xml;
    var $use_shipping_notification = false;
    // merchant data
    var $merchant = array(
        'account_id' => '', // required
        'site_id' => '', // required
        'site_code' => '', // required
        'notification_url' => '',
        'cancel_url' => '',
        'redirect_url' => '',
        'close_window' => '',
    );
    // customer data
    var $customer = array(
        'locale' => '', // advised
        'ipaddress' => '',
        'forwardedip' => '',
        'firstname' => '',
        'lastname' => '',
        'address1' => '',
        'address2' => '',
        'housenumber' => '',
        'zipcode' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'phone' => '',
        'email' => '', // advised
        'accountid' => '',
        'accountholdername' => '',
        'accountholdercity' => '',
        'accountholdercountry' => '',
        'user_agent' => '',
        'referrer' => '',
        'bankaccount' => '',
        'birthday' => ''
    );
    // customer-delivery data
    var $delivery = array(
        'firstname' => '',
        'lastname' => '',
        'address1' => '',
        'address2' => '',
        'housenumber' => '',
        'zipcode' => '',
        'city' => '',
        'state' => '',
        'country' => '',
        'phone' => '',
        'email' => '',
    );
    // transaction data
    var $transaction = array(
        'id' => '', // required
        'currency' => '', // required
        'amount' => '', // required
        'description' => '', // required
        'var1' => '',
        'var2' => '',
        'var3' => '',
        'items' => '',
        'manual' => 'false',
        'gateway' => '',
        'daysactive' => '',
        'invoice_id' => '',
        'shipdate' => '',
        'special' => '',
    );
    var $gatewayinfo = array(
        'user_agent' => '',
        'referrer' => '',
        'bankaccount' => '',
        'birthday' => '',
        'phone' => '',
        'email' => '',
        'issuer' => ''
    );
    var $plugin = array(
        'shop' => '',
        'shop_version' => '',
        'plugin_version' => '',
        'partner' => '',
        'shop_root_url' => ''
    );
    var $ganalytics = array(
        'account' => '',
        'domainName' => 'none',
    );
    var $cart;
    var $fields;
    // signature
    var $cart_xml;
    var $fields_xml;
    var $signature;
    // return vars
    var $api_url;
    var $request_xml;
    var $reply_xml;
    var $payment_url;
    var $status;
    var $error_code;
    var $error;
    var $details;
    var $parsed_xml;
    var $parsed_root;

    /**
     * MulitiSafepy construct, setup the cart and customfields
     */
    function __construct() {
        $this->cart = new MspCart();
        $this->fields = new MspCustomFields();
    }

    /**
     * startDirectXMLTransaction function creates transactions that skip the MultiSafepay pages.
     */
    function startDirectXMLTransaction() {
        $this->checkSettings();

        $this->setIp();
        $this->createSignature();

        // create request
        $this->request_xml = $this->createDirectXMLTransactionRequest();

        // post request and get reply
        $this->api_url = $this->getApiUrl();
        $this->reply_xml = $this->xmlPost($this->api_url, $this->request_xml);

        // communication error
        if (!$this->reply_xml)
            return false;

        // parse xml
        $rootNode = $this->parseXmlResponse($this->reply_xml);
        if (!$rootNode)
            return false;

        // return payment url
        //print_r($rootNode);exit;
        $this->payment_url = $this->xmlUnescape($rootNode['gatewayinfo']['redirecturl']['VALUE']);
        return $this->payment_url;
    }

    /**
     * startDirectBankTransfer function creates a banktransfer transaction, customer should redirect to thank you page. Customer doesn't see the payment page with the payment details but will receive an email containing the details
     */
    function startDirectBankTransfer() {
        $this->checkSettings();

        $this->setIp();
        $this->createSignature();

        // create request
        $this->request_xml = $this->createDirectBankTransferTransactionRequest();

        // post request and get reply
        $this->api_url = $this->getApiUrl();
        $this->reply_xml = $this->xmlPost($this->api_url, $this->request_xml);

        // communication error
        if (!$this->reply_xml)
            return false;

        // parse xml
        $rootNode = $this->parseXmlResponse($this->reply_xml);
        if (!$rootNode)
            return false;

        // return payment url
        //print_r($rootNode);exit;
        $this->payment_url = ''; //$this->xmlUnescape($rootNode['gatewayinfo']['redirecturl']['VALUE']);
        return $this->payment_url;
    }

    /**
     * Set the account settins before using them
     */
    function checkSettings() {
        // trim any spaces
        $this->merchant['account_id'] = trim($this->merchant['account_id']);
        $this->merchant['site_id'] = trim($this->merchant['site_id']);
        $this->merchant['site_code'] = trim($this->merchant['site_code']);
    }

    /**
     * getIdealIssuers function requests the ideal issuers list from MultiSafepay
     */
    public function getIdealIssuers() {
        $this->request_xml = $this->createIdealIssuersRequest();
        $this->api_url = $this->getApiUrl();
        $this->reply_xml = $this->xmlPost($this->api_url, $this->request_xml);
        $issuers = $this->parseXmlResponse($this->reply_xml);


        return $issuers;
    }

    /**
     * createIdealIssuersRequest, create the issuer xml request to send to MultiSafepay to request the iDEAL issuers
     */
    function createIdealIssuersRequest() {
        $request = '<?xml version="1.0" encoding="UTF-8"?>
		<idealissuers ua="iDeal Issuers Request">
			<merchant>
				<account>' . $this->xmlEscape($this->merchant['account_id']) . '</account>
				<site_id>' . $this->xmlEscape($this->merchant['site_id']) . '</site_id>
				<site_secure_code>' . $this->xmlEscape($this->merchant['site_code']) . '</site_secure_code>
			</merchant>
		</idealissuers>';
        return $request;
    }

    /**
     * startTransaction will create a Connect payment transaction request to request a transaction payment link
     */
    function startTransaction() {
        $this->checkSettings();

        $this->setIp();
        $this->createSignature();
        // Referer
        $this->SetRef();

        // create request
        $this->request_xml = $this->createTransactionRequest();

        // post request and get reply
        $this->api_url = $this->getApiUrl();
        $this->reply_xml = $this->xmlPost($this->api_url, $this->request_xml);

        // communication error
        if (!$this->reply_xml)
            return false;

        // parse xml
        $rootNode = $this->parseXmlResponse($this->reply_xml);
        if (!$rootNode)
            return false;

        // return payment url
        $this->payment_url = $this->xmlUnescape($rootNode['transaction']['payment_url']['VALUE']);
        return $this->payment_url;
    }

    /**
     * startCheckout function creates a FastCheckout transaction request.
     */
    function startCheckout() {
        $this->checkSettings();

        $this->setIp();
        $this->createSignature();

        // create request
        $this->request_xml = $this->createCheckoutRequest();

        // post request and get reply
        $this->api_url = $this->getApiUrl();
        $this->reply_xml = $this->xmlPost($this->api_url, $this->request_xml);

        // communication error
        if (!$this->reply_xml)
            return false;

        // parse xml
        $rootNode = $this->parseXmlResponse($this->reply_xml);
        if (!$rootNode)
            return false;

        // return payment url
        if (isset($rootNode['transaction']['payment_url']['VALUE'])) {
            $this->payment_url = $this->xmlUnescape($rootNode['transaction']['payment_url']['VALUE']);
            return $this->payment_url;
        } else {
            return false;
        }
    }

    /**
     * getStatus request the current status of a transaction, returns the current status.
     */
    function getStatus() {
        $this->checkSettings();

        // generate request
        $this->request_xml = $this->createStatusRequest();

        // post request and get reply
        $this->api_url = $this->getApiUrl();
        $this->reply_xml = $this->xmlPost($this->api_url, $this->request_xml);

        // communication error
        if (!$this->reply_xml)
            return false;

        // parse xml
        $rootNode = $this->parseXmlResponse($this->reply_xml);
        if (!$rootNode)
            return false;

        // parse all the order details
        $details = $this->processStatusReply($rootNode);
        $this->details = $details;

        // return status
        $this->status = $rootNode['ewallet']['status']['VALUE'];
        return $this->status;
    }

    /**
     * updateTransaction is used to update transaction information at MultiSafepay. For example, set the transactions order status to shipped for Pay After Delivery
     */
    function updateTransaction() {
        $this->checkSettings();

        // generate request
        $this->request_xml = $this->createUpdateTransactionRequest();

        // post request and get reply
        $this->api_url = $this->getApiUrl();
        $this->reply_xml = $this->xmlPost($this->api_url, $this->request_xml);

        // communication error
        if (!$this->reply_xml)
            return false;

        // parse xml
        $rootNode = $this->parseXmlResponse($this->reply_xml);
        if (!$rootNode)
            return false;

        // parse all the order details
        $details = $this->processStatusReply($rootNode);
        $this->details = $details;

        return true;
    }

    /**
     * _isXmlSectionEmpty, check if section has a value.
     */
    function _isXmlSectionEmpty($section) {
        return isset($section['VALUE']);
    }

    /**
     * processStatusReply will handle the status request answer
     */
    function processStatusReply($rootNode) {
        $xml = $rootNode;
        $result = array();

        $copy = array('ewallet', 'customer', 'customer-delivery', 'transaction', 'paymentdetails');

        foreach ($copy as $section) {
            if (isset($xml[$section]) && !$this->_isXmlSectionEmpty($xml[$section])) {
                foreach ($xml[$section] as $k => $v) {
                    $result[$section][$k] = $this->xmlUnescape($v['VALUE']);
                }
            }
        }

        if (isset($xml['checkoutdata']['shopping-cart']['items']['item'])) {
            $returnCart = array();

            if (!isset($xml['checkoutdata']['shopping-cart']['items']['item'][0]))
                $xml['checkoutdata']['shopping-cart']['items']['item'] = array($xml['checkoutdata']['shopping-cart']['items']['item']);

            foreach ($xml['checkoutdata']['shopping-cart']['items']['item'] as $item) {
                $returnItem = array();

                foreach ($item as $k => $v) {
                    if ($k == 'merchant-private-item-data') {
                        $returnItem[$k] = $v;
                        continue;
                    }

                    if ($k == 'unit-price')
                        $returnItem['currency'] = $v['currency'];

                    $returnItem[$k] = $v['VALUE'];
                }

                $returnCart[] = $returnItem;
            }

            $result['shopping-cart'] = $returnCart;
        }

        if (!empty($xml['checkoutdata']['order-adjustment']['shipping'])) {
            $returnShipping = array();

            foreach ($xml['checkoutdata']['order-adjustment']['shipping'] as $type => $shipping) {
                $returnShipping['type'] = $type;
                $returnShipping['name'] = $shipping['shipping-name']['VALUE'];
                $returnShipping['cost'] = $shipping['shipping-cost']['VALUE'];
                $returnShipping['currency'] = $shipping['shipping-cost']['currency'];
            }

            $result['shipping'] = $returnShipping;
        }

        if (!empty($xml['checkoutdata']['order-adjustment']['total-tax'])) {
            $returnAddjustment = array();

            $returnAddjustment['total'] = $xml['checkoutdata']['order-adjustment']['total-tax']['VALUE'];
            $returnAddjustment['currency'] = $xml['checkoutdata']['order-adjustment']['total-tax']['currency'];

            $result['total-tax'] = $returnAddjustment;
        }

        if (!empty($xml['checkoutdata']['order-adjustment']['adjustment-total'])) {
            $returnAddjustment = array();

            $returnAddjustment['total'] = $xml['checkoutdata']['order-adjustment']['adjustment-total']['VALUE'];
            $returnAddjustment['currency'] = $xml['checkoutdata']['order-adjustment']['adjustment-total']['currency'];

            $result['adjustment-total'] = $returnAddjustment;
        }

        if (!empty($xml['checkoutdata']['order-total'])) {
            $returnTotal = array();

            $returnTotal['total'] = $xml['checkoutdata']['order-total']['VALUE'];
            $returnTotal['currency'] = $xml['checkoutdata']['order-total']['currency'];

            $result['order-total'] = $returnTotal;
        }

        if (!empty($xml['checkoutdata']['custom-fields']) && !$this->_isXmlSectionEmpty($xml['checkoutdata']['custom-fields'])) {
            $result['custom-fields'] = array();

            foreach ($xml['checkoutdata']['custom-fields'] as $k => $v) {
                $result['custom-fields'][$k] = $v['VALUE'];
            }
        }

        return $result;
    }

    /**
     * Returns an associative array with the ids and the descriptions of the available gateways
     * TODO-> Check error logs. This function gate an error on a private server. Research this problem or ask the merchants error log.
     */
    function getGateways() {
        $this->checkSettings();

        // generate request
        $this->request_xml = $this->createGatewaysRequest();

        // post request and get reply
        $this->api_url = $this->getApiUrl();
        $this->reply_xml = $this->xmlPost($this->api_url, $this->request_xml);

        // communication error
        if (!$this->reply_xml)
            return false;

        // parse xml
        $rootNode = $this->parseXmlResponse($this->reply_xml);
        if (!$rootNode)
            return false;

        // fix for when there's only one gateway
        $xml_gateways = $rootNode['gateways']['gateway'];
        if (!isset($xml_gateways[0])) {
            $xml_gateways = array($xml_gateways);
            $rootNode['gateways']['gateway'] = $xml_gateways;
        }

        // get gatesways
        $gateways = array();
        foreach ($rootNode['gateways']['gateway'] as $xml_gateway) {
            $gateway = array();
            $gateway['id'] = $xml_gateway['id']['VALUE'];
            $gateway['description'] = $xml_gateway['description']['VALUE'];

            // issuers
            if (isset($xml_gateway['issuers'])) {
                $issuers = array();

                foreach ($xml_gateway['issuers']['issuer'] as $xml_issuer) {
                    $issuer = array();
                    $issuer['id'] = $xml_issuer['id']['VALUE'];
                    $issuer['description'] = $xml_issuer['description']['VALUE'];
                    $issuers[$issuer['id']] = $issuer;
                }

                $gateway['issuers'] = $issuers;
            }

            $gateways[$gateway['id']] = $gateway;
        }

        // return
        return $gateways;
    }

    /**
     * Create the transaction request xml
     */
    function createTransactionRequest() {
        // issuer attribute
        $issuer = "";
        if (!empty($this->issuer)) {
            $issuer = ' issuer="' . $this->xmlEscape($this->issuer) . '"';
        }

        $request = '<?xml version="1.0" encoding="UTF-8"?>
		<redirecttransaction ua="' . $this->plugin_name . ' ' . $this->version . '">
		  <merchant>
			<account>' . $this->xmlEscape($this->merchant['account_id']) . '</account>
			<site_id>' . $this->xmlEscape($this->merchant['site_id']) . '</site_id>
			<site_secure_code>' . $this->xmlEscape($this->merchant['site_code']) . '</site_secure_code>
			<notification_url>' . $this->xmlEscape($this->merchant['notification_url']) . '</notification_url>
			<cancel_url>' . $this->xmlEscape($this->merchant['cancel_url']) . '</cancel_url>
			<redirect_url>' . $this->xmlEscape($this->merchant['redirect_url']) . '</redirect_url>
			<close_window>' . $this->xmlEscape($this->merchant['close_window']) . '</close_window>
		  </merchant>
		   <plugin>
			<shop>' . $this->xmlEscape($this->plugin['shop']) . '</shop>
			<shop_version>' . $this->xmlEscape($this->plugin['shop_version']) . '</shop_version>
			<plugin_version>' . $this->xmlEscape($this->plugin['plugin_version']) . '</plugin_version>
			<partner>' . $this->xmlEscape($this->plugin['partner']) . '</partner>
			<shop_root_url>' . $this->xmlEscape($this->plugin['shop_root_url']) . '</shop_root_url>
		  </plugin>
		  <customer>
			<locale>' . $this->xmlEscape($this->customer['locale']) . '</locale>
			<ipaddress>' . $this->xmlEscape($this->customer['ipaddress']) . '</ipaddress>
			<forwardedip>' . $this->xmlEscape($this->customer['forwardedip']) . '</forwardedip>
			<firstname>' . $this->xmlEscape($this->customer['firstname']) . '</firstname>
			<lastname>' . $this->xmlEscape($this->customer['lastname']) . '</lastname>
			<address1>' . $this->xmlEscape($this->customer['address1']) . '</address1>
			<address2>' . $this->xmlEscape($this->customer['address2']) . '</address2>
			<housenumber>' . $this->xmlEscape($this->customer['housenumber']) . '</housenumber>
			<zipcode>' . $this->xmlEscape($this->customer['zipcode']) . '</zipcode>
			<city>' . $this->xmlEscape($this->customer['city']) . '</city>
			<state>' . $this->xmlEscape($this->customer['state']) . '</state>
			<country>' . $this->xmlEscape($this->customer['country']) . '</country>
			<phone>' . $this->xmlEscape($this->customer['phone']) . '</phone>
			<email>' . $this->xmlEscape($this->customer['email']) . '</email>
			<referrer>' . $this->xmlEscape($this->customer['referrer']) . '</referrer>
			<user_agent>' . $this->xmlEscape($this->customer['user_agent']) . '</user_agent>
		  </customer>
				<customer-delivery>
					<firstname>' . $this->xmlEscape($this->delivery['firstname']) . '</firstname>
					<lastname>' . $this->xmlEscape($this->delivery['lastname']) . '</lastname>
					<address1>' . $this->xmlEscape($this->delivery['address1']) . '</address1>
					<address2>' . $this->xmlEscape($this->delivery['address2']) . '</address2>
					<housenumber>' . $this->xmlEscape($this->delivery['housenumber']) . '</housenumber>
					<zipcode>' . $this->xmlEscape($this->delivery['zipcode']) . '</zipcode>
					<city>' . $this->xmlEscape($this->delivery['city']) . '</city>
					<state>' . $this->xmlEscape($this->delivery['state']) . '</state>
					<country>' . $this->xmlEscape($this->delivery['country']) . '</country>
					<phone>' . $this->xmlEscape($this->delivery['phone']) . '</phone>
					<email>' . $this->xmlEscape($this->delivery['email']) . '</email>
				</customer-delivery>
		  <transaction>
			<id>' . $this->xmlEscape($this->transaction['id']) . '</id>
			<currency>' . $this->xmlEscape($this->transaction['currency']) . '</currency>
			<amount>' . $this->xmlEscape($this->transaction['amount']) . '</amount>
			<description>' . $this->xmlEscape($this->transaction['description']) . '</description>
			<var1>' . $this->xmlEscape($this->transaction['var1']) . '</var1>
			<var2>' . $this->xmlEscape($this->transaction['var2']) . '</var2>
			<var3>' . $this->xmlEscape($this->transaction['var3']) . '</var3>
			<items>' . $this->xmlEscape($this->transaction['items']) . '</items>
			<manual>' . $this->xmlEscape($this->transaction['manual']) . '</manual>
			<daysactive>' . $this->xmlEscape($this->transaction['daysactive']) . '</daysactive>
			<gateway' . $issuer . '>' . $this->xmlEscape($this->transaction['gateway']) . '</gateway>
		  </transaction>
		  <signature>' . $this->xmlEscape($this->signature) . '</signature>
		</redirecttransaction>';

        return $request;
    }

    /**
     * Create a DirectXmltransaction request xml
     */
    function createDirectXMLTransactionRequest() {
        $issuer = "";
        if (!empty($this->issuer)) {
            $issuer = ' issuer="' . $this->xmlEscape($this->issuer) . '"';
        }
        if ($this->extravars != '') {
            $gatewayinfo = '<gatewayinfo>
							<issuerid>' . $this->extravars . '</issuerid>	
						</gatewayinfo>';
        } else {
            $gatewayinfo = '';
        }

        $request = '<?xml version="1.0" encoding="UTF-8"?>
		<directtransaction ua="' . $this->plugin_name . ' ' . $this->version . '">
			<transaction>
				<id>' . $this->xmlEscape($this->transaction['id']) . '</id>
				<currency>' . $this->xmlEscape($this->transaction['currency']) . '</currency>
				<amount>' . $this->xmlEscape($this->transaction['amount']) . '</amount>
				<description>' . $this->xmlEscape($this->transaction['description']) . '</description>
				<var1>' . $this->xmlEscape($this->transaction['var1']) . '</var1>
				<var2>' . $this->xmlEscape($this->transaction['var2']) . '</var2>
				<var3>' . $this->xmlEscape($this->transaction['var3']) . '</var3>
				<items>' . $this->xmlEscape($this->transaction['items']) . '</items>
				<manual>' . $this->xmlEscape($this->transaction['manual']) . '</manual>
				<daysactive>' . $this->xmlEscape($this->transaction['daysactive']) . '</daysactive>
				<gateway' . $issuer . '>' . $this->xmlEscape($this->transaction['gateway']) . '</gateway>
			</transaction>
		  <merchant>
			<account>' . $this->xmlEscape($this->merchant['account_id']) . '</account>
			<site_id>' . $this->xmlEscape($this->merchant['site_id']) . '</site_id>
			<site_secure_code>' . $this->xmlEscape($this->merchant['site_code']) . '</site_secure_code>
			<notification_url>' . $this->xmlEscape($this->merchant['notification_url']) . '</notification_url>
			<cancel_url>' . $this->xmlEscape($this->merchant['cancel_url']) . '</cancel_url>
			<redirect_url>' . $this->xmlEscape($this->merchant['redirect_url']) . '</redirect_url>
			<close_window>' . $this->xmlEscape($this->merchant['close_window']) . '</close_window>
		  </merchant>
		   <plugin>
		<shop>' . $this->xmlEscape($this->plugin['shop']) . '</shop>
		<shop_version>' . $this->xmlEscape($this->plugin['shop_version']) . '</shop_version>
		<plugin_version>' . $this->xmlEscape($this->plugin['plugin_version']) . '</plugin_version>
		<partner>' . $this->xmlEscape($this->plugin['partner']) . '</partner>
		<shop_root_url>' . $this->xmlEscape($this->plugin['shop_root_url']) . '</shop_root_url>
	  </plugin>
		  <customer>
			<locale>' . $this->xmlEscape($this->customer['locale']) . '</locale>
			<ipaddress>' . $this->xmlEscape($this->customer['ipaddress']) . '</ipaddress>
			<forwardedip>' . $this->xmlEscape($this->customer['forwardedip']) . '</forwardedip>
			<firstname>' . $this->xmlEscape($this->customer['firstname']) . '</firstname>
			<lastname>' . $this->xmlEscape($this->customer['lastname']) . '</lastname>
			<address1>' . $this->xmlEscape($this->customer['address1']) . '</address1>
			<address2>' . $this->xmlEscape($this->customer['address2']) . '</address2>
			<housenumber>' . $this->xmlEscape($this->customer['housenumber']) . '</housenumber>
			<zipcode>' . $this->xmlEscape($this->customer['zipcode']) . '</zipcode>
			<city>' . $this->xmlEscape($this->customer['city']) . '</city>
			<state>' . $this->xmlEscape($this->customer['state']) . '</state>
			<country>' . $this->xmlEscape($this->customer['country']) . '</country>
			<phone>' . $this->xmlEscape($this->customer['phone']) . '</phone>
			<email>' . $this->xmlEscape($this->customer['email']) . '</email>
			<referrer>' . $this->xmlEscape($this->customer['referrer']) . '</referrer>
			<user_agent>' . $this->xmlEscape($this->customer['user_agent']) . '</user_agent>
		  </customer>
				<customer-delivery>
					<firstname>' . $this->xmlEscape($this->delivery['firstname']) . '</firstname>
					<lastname>' . $this->xmlEscape($this->delivery['lastname']) . '</lastname>
					<address1>' . $this->xmlEscape($this->delivery['address1']) . '</address1>
					<address2>' . $this->xmlEscape($this->delivery['address2']) . '</address2>
					<housenumber>' . $this->xmlEscape($this->delivery['housenumber']) . '</housenumber>
					<zipcode>' . $this->xmlEscape($this->delivery['zipcode']) . '</zipcode>
					<city>' . $this->xmlEscape($this->delivery['city']) . '</city>
					<state>' . $this->xmlEscape($this->delivery['state']) . '</state>
					<country>' . $this->xmlEscape($this->delivery['country']) . '</country>
					<phone>' . $this->xmlEscape($this->delivery['phone']) . '</phone>
					<email>' . $this->xmlEscape($this->delivery['email']) . '</email>
				</customer-delivery>
			' . $gatewayinfo . '
		  <signature>' . $this->xmlEscape($this->signature) . '</signature>
		</directtransaction>';

        return $request;
    }

    /**
     * Create the diectBankTransfterTransaction request xml
     */
    function createDirectBankTransferTransactionRequest() {
        $issuer = "";
        if (!empty($this->issuer)) {
            $issuer = ' issuer="' . $this->xmlEscape($this->issuer) . '"';
        }
        $request = '<?xml version="1.0" encoding="UTF-8"?>
		<directtransaction ua="' . $this->plugin_name . ' ' . $this->version . '">
			<transaction>
				<id>' . $this->xmlEscape($this->transaction['id']) . '</id>
				<currency>' . $this->xmlEscape($this->transaction['currency']) . '</currency>
				<amount>' . $this->xmlEscape($this->transaction['amount']) . '</amount>
				<description>' . $this->xmlEscape($this->transaction['description']) . '</description>
				<var1>' . $this->xmlEscape($this->transaction['var1']) . '</var1>
				<var2>' . $this->xmlEscape($this->transaction['var2']) . '</var2>
				<var3>' . $this->xmlEscape($this->transaction['var3']) . '</var3>
				<items>' . $this->xmlEscape($this->transaction['items']) . '</items>
				<manual>' . $this->xmlEscape($this->transaction['manual']) . '</manual>
				<daysactive>' . $this->xmlEscape($this->transaction['daysactive']) . '</daysactive>
				<gateway' . $issuer . '>' . $this->xmlEscape($this->transaction['gateway']) . '</gateway>
			</transaction>
		  <merchant>
			<account>' . $this->xmlEscape($this->merchant['account_id']) . '</account>
			<site_id>' . $this->xmlEscape($this->merchant['site_id']) . '</site_id>
			<site_secure_code>' . $this->xmlEscape($this->merchant['site_code']) . '</site_secure_code>
			<notification_url>' . $this->xmlEscape($this->merchant['notification_url']) . '</notification_url>
			<cancel_url>' . $this->xmlEscape($this->merchant['cancel_url']) . '</cancel_url>
			<redirect_url>' . $this->xmlEscape($this->merchant['redirect_url']) . '</redirect_url>
			<close_window>' . $this->xmlEscape($this->merchant['close_window']) . '</close_window>
		  </merchant>
		   <plugin>
		<shop>' . $this->xmlEscape($this->plugin['shop']) . '</shop>
		<shop_version>' . $this->xmlEscape($this->plugin['shop_version']) . '</shop_version>
		<plugin_version>' . $this->xmlEscape($this->plugin['plugin_version']) . '</plugin_version>
		<partner>' . $this->xmlEscape($this->plugin['partner']) . '</partner>
		<shop_root_url>' . $this->xmlEscape($this->plugin['shop_root_url']) . '</shop_root_url>
	  </plugin>
		  <customer>
			<locale>' . $this->xmlEscape($this->customer['locale']) . '</locale>
			<ipaddress>' . $this->xmlEscape($this->customer['ipaddress']) . '</ipaddress>
			<forwardedip>' . $this->xmlEscape($this->customer['forwardedip']) . '</forwardedip>
			<firstname>' . $this->xmlEscape($this->customer['firstname']) . '</firstname>
			<lastname>' . $this->xmlEscape($this->customer['lastname']) . '</lastname>
			<address1>' . $this->xmlEscape($this->customer['address1']) . '</address1>
			<address2>' . $this->xmlEscape($this->customer['address2']) . '</address2>
			<housenumber>' . $this->xmlEscape($this->customer['housenumber']) . '</housenumber>
			<zipcode>' . $this->xmlEscape($this->customer['zipcode']) . '</zipcode>
			<city>' . $this->xmlEscape($this->customer['city']) . '</city>
			<state>' . $this->xmlEscape($this->customer['state']) . '</state>
			<country>' . $this->xmlEscape($this->customer['country']) . '</country>
			<phone>' . $this->xmlEscape($this->customer['phone']) . '</phone>
			<email>' . $this->xmlEscape($this->customer['email']) . '</email>
			<referrer>' . $this->xmlEscape($this->customer['referrer']) . '</referrer>
			<user_agent>' . $this->xmlEscape($this->customer['user_agent']) . '</user_agent>
		  </customer>
				<customer-delivery>
					<firstname>' . $this->xmlEscape($this->delivery['firstname']) . '</firstname>
					<lastname>' . $this->xmlEscape($this->delivery['lastname']) . '</lastname>
					<address1>' . $this->xmlEscape($this->delivery['address1']) . '</address1>
					<address2>' . $this->xmlEscape($this->delivery['address2']) . '</address2>
					<housenumber>' . $this->xmlEscape($this->delivery['housenumber']) . '</housenumber>
					<zipcode>' . $this->xmlEscape($this->delivery['zipcode']) . '</zipcode>
					<city>' . $this->xmlEscape($this->delivery['city']) . '</city>
					<state>' . $this->xmlEscape($this->delivery['state']) . '</state>
					<country>' . $this->xmlEscape($this->delivery['country']) . '</country>
					<phone>' . $this->xmlEscape($this->delivery['phone']) . '</phone>
					<email>' . $this->xmlEscape($this->delivery['email']) . '</email>
				</customer-delivery>
				<gatewayinfo>
					<accountid>' . $this->xmlEscape($this->customer['accountid']) . '</accountid>
					<accountholdername>' . $this->xmlEscape($this->customer['accountholdername']) . '</accountholdername>
					<accountholdercity>' . $this->xmlEscape($this->customer['accountholdercity']) . '</accountholdercity>
					<accountholdercountry>' . $this->xmlEscape($this->customer['accountholdercountry']) . '</accountholdercountry>
				</gatewayinfo>
		  <signature>' . $this->xmlEscape($this->signature) . '</signature>
		</directtransaction>';

        return $request;
    }

    /**
     * Create the checkout request xml
     */
    function createCheckoutRequest() {
        $this->cart_xml = $this->cart->GetXML();
        $this->fields_xml = $this->fields->GetXML();

        $ganalytics = "";
        if (!empty($this->ganalytics['account'])) {
            $ganalytics .= '<google-analytics>';
            $ganalytics .= '  <account>' . $this->xmlEscape($this->ganalytics['account']) . '</account>';
            $ganalytics .= '</google-analytics>';
        }

        //JB:if setting $use_shipping_notification is true, add extra element
        if ($this->use_shipping_notification) {
            $use_shipping_xml = "<checkout-settings>
    									<use-shipping-notification>true</use-shipping-notification>
    							</checkout-settings>";
        } else {
            $use_shipping_xml = "";
        }


        if ($this->transaction['special'] != "") {
            $trans_type = 'directtransaction';
        } elseif ($this->transaction['gateway'] != "") {

            $trans_type = 'redirecttransaction';
        } else {

            $trans_type = 'checkouttransaction';
        }

        $request = '<?xml version="1.0" encoding="UTF-8"?>
		<' . $trans_type . ' ua="' . $this->plugin_name . ' ' . $this->version . '">
			<merchant>
        <account>' . $this->xmlEscape($this->merchant['account_id']) . '</account>
        <site_id>' . $this->xmlEscape($this->merchant['site_id']) . '</site_id>
        <site_secure_code>' . $this->xmlEscape($this->merchant['site_code']) . '</site_secure_code>
				<notification_url>' . $this->xmlEscape($this->merchant['notification_url']) . '</notification_url>
				<cancel_url>' . $this->xmlEscape($this->merchant['cancel_url']) . '</cancel_url>
				<redirect_url>' . $this->xmlEscape($this->merchant['redirect_url']) . '</redirect_url>
				<close_window>' . $this->xmlEscape($this->merchant['close_window']) . '</close_window>
			</merchant>
			 <plugin>
		<shop>' . $this->xmlEscape($this->plugin['shop']) . '</shop>
		<shop_version>' . $this->xmlEscape($this->plugin['shop_version']) . '</shop_version>
		<plugin_version>' . $this->xmlEscape($this->plugin['plugin_version']) . '</plugin_version>
		<partner>' . $this->xmlEscape($this->plugin['partner']) . '</partner>
		<shop_root_url>' . $this->xmlEscape($this->plugin['shop_root_url']) . '</shop_root_url>
	  </plugin>
			<customer>
				<locale>' . $this->xmlEscape($this->customer['locale']) . '</locale>
				<ipaddress>' . $this->xmlEscape($this->customer['ipaddress']) . '</ipaddress>
				<forwardedip>' . $this->xmlEscape($this->customer['forwardedip']) . '</forwardedip>
				<firstname>' . $this->xmlEscape($this->customer['firstname']) . '</firstname>
				<lastname>' . $this->xmlEscape($this->customer['lastname']) . '</lastname>
				<address1>' . $this->xmlEscape($this->customer['address1']) . '</address1>
				<address2>' . $this->xmlEscape($this->customer['address2']) . '</address2>
				<housenumber>' . $this->xmlEscape($this->customer['housenumber']) . '</housenumber>
				<zipcode>' . $this->xmlEscape($this->customer['zipcode']) . '</zipcode>
				<city>' . $this->xmlEscape($this->customer['city']) . '</city>
				<state>' . $this->xmlEscape($this->customer['state']) . '</state>
				<country>' . $this->xmlEscape($this->customer['country']) . '</country>
				<phone>' . $this->xmlEscape($this->customer['phone']) . '</phone>
				<email>' . $this->xmlEscape($this->customer['email']) . '</email>
				<referrer>' . $this->xmlEscape($this->customer['referrer']) . '</referrer>
				<user_agent>' . $this->xmlEscape($this->customer['user_agent']) . '</user_agent>
				<birthday>' . $this->xmlEscape($this->customer['birthday']) . '</birthday>
				<bankaccount>' . $this->xmlEscape($this->customer['bankaccount']) . '</bankaccount>

			</customer>
			<customer-delivery>
				<firstname>' . $this->xmlEscape($this->delivery['firstname']) . '</firstname>
				<lastname>' . $this->xmlEscape($this->delivery['lastname']) . '</lastname>
				<address1>' . $this->xmlEscape($this->delivery['address1']) . '</address1>
				<address2>' . $this->xmlEscape($this->delivery['address2']) . '</address2>
				<housenumber>' . $this->xmlEscape($this->delivery['housenumber']) . '</housenumber>
				<zipcode>' . $this->xmlEscape($this->delivery['zipcode']) . '</zipcode>
				<city>' . $this->xmlEscape($this->delivery['city']) . '</city>
				<state>' . $this->xmlEscape($this->delivery['state']) . '</state>
				<country>' . $this->xmlEscape($this->delivery['country']) . '</country>
				<phone>' . $this->xmlEscape($this->delivery['phone']) . '</phone>
				<email>' . $this->xmlEscape($this->delivery['email']) . '</email>
			</customer-delivery>
			' . $this->cart_xml . '
			' . $this->fields_xml . '
			' . $ganalytics . '		
			' . $use_shipping_xml . ' 
			<gatewayinfo>
				<referrer>' . $this->xmlEscape($this->gatewayinfo['referrer']) . '</referrer>
				<user_agent>' . $this->xmlEscape($this->gatewayinfo['user_agent']) . '</user_agent>
				<birthday>' . $this->xmlEscape($this->gatewayinfo['birthday']) . '</birthday>
				<bankaccount>' . $this->xmlEscape($this->gatewayinfo['bankaccount']) . '</bankaccount>
				<phone>' . $this->xmlEscape($this->gatewayinfo['phone']) . '</phone>
				<email>' . $this->xmlEscape($this->gatewayinfo['email']) . '</email>
				<issuerid>' . $this->xmlEscape($this->gatewayinfo['issuer']) . '</issuerid>
			</gatewayinfo>
			<transaction>
				<id>' . $this->xmlEscape($this->transaction['id']) . '</id>
				<currency>' . $this->xmlEscape($this->transaction['currency']) . '</currency>
				<amount>' . $this->xmlEscape($this->transaction['amount']) . '</amount>
				<description>' . $this->xmlEscape($this->transaction['description']) . '</description>
				<var1>' . $this->xmlEscape($this->transaction['var1']) . '</var1>
				<var2>' . $this->xmlEscape($this->transaction['var2']) . '</var2>
				<var3>' . $this->xmlEscape($this->transaction['var3']) . '</var3>
				<items>' . $this->xmlEscape($this->transaction['items']) . '</items>
				<manual>' . $this->xmlEscape($this->transaction['manual']) . '</manual>
        <gateway>' . $this->xmlEscape($this->transaction['gateway']) . '</gateway>
			</transaction>
			<signature>' . $this->xmlEscape($this->signature) . '</signature>
		</' . $trans_type . '>';

        return $request;
    }

    /**
     * Create the status request xml
     */
    function createStatusRequest() {
        $request = '<?xml version="1.0" encoding="UTF-8"?>
		<status ua="' . $this->plugin_name . ' ' . $this->version . '">
			<merchant>
				<account>' . $this->xmlEscape($this->merchant['account_id']) . '</account>
				<site_id>' . $this->xmlEscape($this->merchant['site_id']) . '</site_id>
				<site_secure_code>' . $this->xmlEscape($this->merchant['site_code']) . '</site_secure_code>
			</merchant>
			<transaction>
				<id>' . $this->xmlEscape($this->transaction['id']) . '</id>
			</transaction>
		</status>';

        return $request;
    }

    /**
     * Create the gateway request xml
     */
    function createGatewaysRequest() {
        $request = '<?xml version="1.0" encoding="UTF-8"?>
		<gateways ua="' . $this->plugin_name . ' ' . $this->version . '">
			<merchant>
				<account>' . $this->xmlEscape($this->merchant['account_id']) . '</account>
				<site_id>' . $this->xmlEscape($this->merchant['site_id']) . '</site_id>
				<site_secure_code>' . $this->xmlEscape($this->merchant['site_code']) . '</site_secure_code>
			</merchant>
			<customer>
				<country>' . $this->xmlEscape($this->customer['country']) . '</country>
			</customer>
		</gateways>';

        return $request;
    }

    /**
     * Create the update transaction request xml
     */
    function createUpdateTransactionRequest() {
        $request = '<?xml version="1.0" encoding="UTF-8"?>
		<updatetransaction>
			<merchant>
				<account>' . $this->xmlEscape($this->merchant['account_id']) . '</account>
				<site_id>' . $this->xmlEscape($this->merchant['site_id']) . '</site_id>
				<site_secure_code>' . $this->xmlEscape($this->merchant['site_code']) . '</site_secure_code>
			</merchant>
			<transaction>
				<id>' . $this->xmlEscape($this->transaction['id']) . '</id>
				<invoiceid>' . $this->xmlEscape($this->transaction['invoice_id']) . '</invoiceid>
				<shipdate>' . $this->xmlEscape($this->transaction['shipdate']) . '</shipdate>
			</transaction>
		</updatetransaction>';

        return $request;
    }

    /**
     * Creates the signature used the check the transaction
     */
    function createSignature() {
        $this->signature = md5(
                $this->transaction['amount'] .
                $this->transaction['currency'] .
                $this->merchant['account_id'] .
                $this->merchant['site_id'] .
                $this->transaction['id']
        );
    }

    /**
     * Sets the customers ip variables
     */
    function setIp() {

        $ip = $_SERVER['REMOTE_ADDR'];
        $isValid = filter_var($ip, FILTER_VALIDATE_IP);
        
        if($isValid) {
            $this->customer['ipaddress'] = $isValid;
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            $isValid = filter_var($ip, FILTER_VALIDATE_IP);

            if ($isValid) {
                $this->customer['forwardedip'] = $isValid;
            } else {
                $this->customer['forwardedip'] = '127.0.0.1';
            }
        }
    }

    /**
     * set the customers Referer
     */
    function SetRef() {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->customer['referer'] = $_SERVER['HTTP_REFERER'];
        } else {
            $this->customer['referer'] = '';
        }
    }

    /**
     * Parses and sets customer address
     */
    function parseCustomerAddress($street_address) {
        list($address, $apartment) = $this->parseAddress($street_address);
        $this->customer['address1'] = $address;
        $this->customer['housenumber'] = $apartment;
    }

    /**
     * Parses and sets delivery address
     */
    function parseDeliveryAddress($street_address) {
        list($address, $apartment) = $this->parseAddress($street_address);
        $this->delivery['address1'] = $address;
        $this->delivery['housenumber'] = $apartment;
    }

    /**
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

    /**
     * set the default tax zones for FCO
     */
    function setDefaultTaxZones($globalRate = true, $shippingTaxed = true) {
        $shippingTaxed = ($shippingTaxed) ? 'true' : 'false';

        if ($globalRate) {
            $rule = new MspDefaultTaxRule('0.21', $shippingTaxed);
            $this->cart->AddDefaultTaxRules($rule);
        }

        $table = new MspAlternateTaxTable('BTW21', 'true');
        $rule = new MspAlternateTaxRule('0.21');
        $table->AddAlternateTaxRules($rule);
        $this->cart->AddAlternateTaxTables($table);

        $table = new MspAlternateTaxTable('BTW6', 'true');
        $rule = new MspAlternateTaxRule('0.06');
        $table->AddAlternateTaxRules($rule);
        $this->cart->AddAlternateTaxTables($table);

        $table = new MspAlternateTaxTable('BTW0', 'true');
        $rule = new MspAlternateTaxRule('0.00');
        $table->AddAlternateTaxRules($rule);
        $this->cart->AddAlternateTaxTables($table);
    }

    /**
     * Returns the api url
     */
    function getApiUrl() {
        if ($this->custom_api) {
            return $this->custom_api;
        }

        if ($this->test) {
            return "https://testapi.multisafepay.com/ewx/";
        } else {
            return "https://api.multisafepay.com/ewx/";
        }
    }

    /**
     * Parse an xml response
     */
    function parseXmlResponse($response) {
        // strip xml line
        $response = preg_replace('#</\?xml[^>]*>#is', '', $response);

        // parse
        $parser = new msp_gc_xmlparser($response);
        $this->parsed_xml = $parser->GetData();
        $this->parsed_root = $parser->GetRoot();
        $rootNode = $this->parsed_xml[$this->parsed_root];

        // check if valid response?
        // check for error
        $result = $this->parsed_xml[$this->parsed_root]['result'];
        if ($result != "ok") {
            $this->error_code = $rootNode['error']['code']['VALUE'];
            $this->error = $rootNode['error']['description']['VALUE'];
            return false;
        }

        return $rootNode;
    }

    /**
     * Returns the string escaped for use in XML documents
     */
    function xmlEscape($str) {
        //$ts = array("/[�-�]/", "/�/", "/�/", "/[�-�]/", "/[�-�]/", "/�/", "/�/", "/[�-��]/", "/�/", "/[�-�]/", "/[�-�]/", "/[�-�]/", "/�/", "/�/", "/[�-�]/", "/[�-�]/", "/�/", "/�/", "/[�-��]/", "/�/", "/[�-�]/", "/[�-�]/");
        //$tn = array("A", "AE", "C", "E", "I", "D", "N", "O", "X", "U", "Y", "a", "ae", "c", "e", "i", "d", "n", "o", "x", "u", "y");

        //$str = preg_replace($ts, $tn, $str);
        return htmlspecialchars($str, ENT_COMPAT, "UTF-8");
    }

    /**
     * Returns the string with all XML escaping removed
     */
    function xmlUnescape($str) {
        return html_entity_decode($str, ENT_COMPAT, "UTF-8");
    }

    /**
     * Post the supplied XML data and return the reply
     */
    function xmlPost($url, $request_xml, $verify_peer = false) {
        $curl_available = extension_loaded("curl");

        // generate request
        $header = array();

        if (!$curl_available) {
            $url = parse_url($url);

            if (empty($url['port'])) {
                $url['port'] = $url['scheme'] == "https" ? 443 : 80;
            }

            $header[] = "POST " . $url['path'] . "?" . $url['query'] . " HTTP/1.1";
            $header[] = "Host: " . $url['host'] . ":" . $url['port'];
            $header[] = "Content-Length: " . strlen($request_xml);
        }

        $header[] = "Content-Type: text/xml";
        $header[] = "Connection: close";

        // issue request
        if ($curl_available) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request_xml);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_peer);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_HEADER, true);
            //curl_setopt($ch, CURLOPT_HEADER_OUT,     true);

            $reply_data = curl_exec($ch);
        } else {
            $request_data = implode("\r\n", $header);
            $request_data .= "\r\n\r\n";
            $request_data .= $request_xml;
            $reply_data = "";

            $errno = 0;
            $errstr = "";

            $fp = fsockopen(($url['scheme'] == "https" ? "ssl://" : "") . $url['host'], $url['port'], $errno, $errstr, 30);

            if ($fp) {
                if (function_exists("stream_context_set_params")) {
                    stream_context_set_params($fp, array(
                        'ssl' => array(
                            'verify_peer' => $verify_peer,
                            'allow_self_signed' => $verify_peer
                        )
                    ));
                }

                write($fp, $request_data);
                fflush($fp);

                while (!feof($fp)) {
                    $reply_data .= fread($fp, 1024);
                }

                fclose($fp);
            }
        }

        // check response
        if ($curl_available) {
            if (curl_errno($ch)) {
                $this->error_code = -1;
                $this->error = "curl error: " . curl_errno($ch);
                return false;
            }

            $reply_info = curl_getinfo($ch);
            curl_close($ch);
        } else {
            if ($errno) {
                $this->error_code = -1;
                $this->error = "connection error: " . $errno;
                return false;
            }

            $header_size = strpos($reply_data, "\r\n\r\n");
            $header_data = substr($reply_data, 0, $header_size);
            $header = explode("\r\n", $header_data);
            $status_line = explode(" ", $header[0]);
            $content_type = "application/octet-stream";

            foreach ($header as $header_line) {
                $header_parts = explode(":", $header_line);
                if (strtolower($header_parts[0]) == "content-type") {
                    $content_type = trim($header_parts[1]);
                    break;
                }
            }

            $reply_info = array(
                'http_code' => (int) $status_line[1],
                'content_type' => $content_type,
                'header_size' => $header_size + 4
            );
        }

        if ($reply_info['http_code'] != 200) {
            $this->error_code = -1;
            $this->error = "http error: " . $reply_info['http_code'];
            return false;
        }

        if (strstr($reply_info['content_type'], "/xml") === false) {
            $this->error_code = -1;
            $this->error = "content type error: " . $reply_info['content_type'];
            return false;
        }

        // split header and body    
        $reply_header = substr($reply_data, 0, $reply_info['header_size'] - 4);
        $reply_xml = substr($reply_data, $reply_info['header_size']);

        if (empty($reply_xml)) {
            $this->error_code = -1;
            $this->error = "received empty response";
            return false;
        }

        return $reply_xml;
    }

    /**
     * From http://www.php.net/manual/en/function.strrpos.php#78556
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

}
