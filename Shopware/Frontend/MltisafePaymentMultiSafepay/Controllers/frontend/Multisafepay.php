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

class Shopware_Controllers_Frontend_PaymentMultisafepay extends Shopware_Controllers_Frontend_Payment
{
    private static $pay_to_email;
    private static $secret_word;
    private static $hide_login = 1;
    private static $logo_url;
    private static $recipient_description;
    private static $multisafepay_url;
    private $sid;
    
	
	
	/**
	* This function is called when the order is confirmed.
	*/
    public function indexAction ()
    {
		//Check if selected gateway was in a MultiSafepay method, if not then return to the checkout page.
        switch ($this->getPaymentShortName())
        {
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
			case 'multisafepay_payafter' :
	  
			// If a MultiSafepay gateway was selected then redirect to the gateway action url
			if (preg_match('/multisafepay_(.+)/',$this->getPaymentShortName(), $matches))
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
    public function gatewayAction()
    {
		//Include the MultiSafepay API, used to build the transaction request and start the transaction.
		include('Api/MultiSafepay.combined.php');	
		$msp = new MultiSafepay();

		$router 					= 	$this->Front()->Router();
	
		mt_srand(time());
		$transaction_id 		= 	mt_rand();
    
		$userinfo 				= 	$this->getUser();

		
		if (!$userinfo) // Redirect to payment failed page
			$this->forward('cancel');
	
		$uniquePaymentID 		= 	$this->createPaymentUniqueId();

		//Check if we need the Live or Test environment
		if(Shopware()->Config()->environment){
			$environment		=	 false;
		}else{
			$environment		= 	true;
		}
		$basket 	= 	$this->getBasket();


		/* 
		* Create Transaction Request
		*/
		$msp = new MultiSafepay();

			
		/*
		*	Set the plugin info data
		*/
		$msp->plugin_name					= 	'Shopware '.Shopware()->Config()->get('version');
		$msp->version						= 	'(1.0.0)';
		$msp->plugin['shop']				= 'Shopware';
		$msp->plugin['shop_version']		= Shopware()->Config()->get('version');
		$msp->plugin['plugin_version']		= '1.0.0';



		/* 
		* Merchant Settings
		*/
		$msp->test                         	= 	$environment;
		$msp->merchant['account_id']      	= 	Shopware()->Config()->accountid;
		$msp->merchant['site_id']          	=  	Shopware()->Config()->siteid;
		$msp->merchant['site_code']        	= 	Shopware()->Config()->securecode;
		$msp->merchant['notification_url'] 	= 	$router->assemble(array('action' => 'notify', 'forceSecure' => true, 'appendSession' => true)).'&type=initial';
		$msp->merchant['cancel_url']       	= 	$router->assemble(array('action' => 'cancel', 'forceSecure' => true));
		$msp->merchant['redirect_url'] 	 	= 	$router->assemble(array('action' => 'finish', 'forceSecure' 	=> 	true)) . '?uniquePaymentID=' . $uniquePaymentID . '&transactionID=' . $transaction_id;
		$msp->merchant['close_window']		= 	true;
		
		/* 
		* Customer Details - supply if available
		*/
		$msp->customer['locale']          	= 	Shopware()->System()->sLanguageData[Shopware()->System()->sLanguage]["isocode"];
		$msp->customer['firstname']       	= 	$userinfo["billingaddress"]["firstname"];
		$msp->customer['lastname']         	= 	$userinfo["billingaddress"]["lastname"];
		$msp->customer['zipcode']          	=	$userinfo["billingaddress"]["zipcode"];
		$msp->customer['city']             	=	$userinfo["billingaddress"]["city"];
		$msp->customer['country']          	= 	$userinfo["additional"]["country"]["countryiso"];
		$msp->customer['email']				= 	$userinfo["additional"]["user"]["email"];
		$msp->customer['address1']			=	$userinfo["billingaddress"]["street"];
		$msp->customer['housenumber']		= 	$userinfo["billingaddress"]["streetnumber"];
		$msp->customer['phone']				= 	$userinfo["billingaddress"]["phone"];

		/* 
		 * Transaction Details
		 */
		$msp->transaction['id']            	= 	$transaction_id; // generally the shop's order ID is used here
		$msp->transaction['currency']      	= 	$this->getCurrencyShortName();
		$msp->transaction['amount']        	= 	(float)str_replace(',','.', $this->getAmount())*100;//$this->getAmount() * 100; // cents
		$msp->transaction['description']  	= 	'Order #' . $msp->transaction['id'];
		//$msp->transaction['items']        = 	$items;
		$msp->transaction['var1']			= 	$uniquePaymentID;
		$msp->transaction['gateway']		= 	$this->Request()->payment;

		//request the payment link
		
		if($this->Request()->payment == 'PAYAFTER')
		{
			//For Pay After Delivery we need all cart contents, including fee's, discount, shippingmethod etc. We will store it within the $basket
			$items 	= 	$basket['content'];
			//print_r($items);exit;
			
			//Create a tax array that will contain all used taxes. These will then be added to the transaction request
			$tax_array 							=	 array();

			//add all tax rates to the array
			foreach($items as $product => $data)
			{
				if(isset($data['additional_details']['tax']))
				{
					$tax_array[$data['additional_details']['tax']]	=	$data['additional_details']['tax']/100;
				}elseif(isset($data['tax_rate']))
				{
					$tax_array[$data['tax_rate']]	=	$data['tax_rate']/100;
				}
			}
			
			//Add the taxtables to the request
			foreach($tax_array as $name => $rate)
			{
				$table 						= 	new MspAlternateTaxTable();
				$table->name				=	$name;
				$rule 						= 	new MspAlternateTaxRule($rate);
				$table->AddAlternateTaxRules($rule);
				$msp->cart->AddAlternateTaxTables($table);
			}
		
			//Add all products to the request
			foreach($items as $product => $data)
			{
				//if set then this is a product
				if(isset($data['additional_details']['tax']))
				{
					$c_item = new MspItem($data['additional_details']['articleName'], $data['additional_details']['description'], $data['quantity'], $data['netprice'], $data['additional_details']['sUnit']['unit'], $data['additional_details']['weight'] );
					$msp->cart->AddItem($c_item);
					$c_item->SetMerchantItemId($data['id']);
					$c_item->SetTaxTableSelector($data['additional_details']['tax']);
				}elseif(isset($data['tax_rate']))
				{
					$c_item = new MspItem($data['additional_details']['articleName'], $data['additional_details']['description'], $data['quantity'], $data['netprice'], $data['additional_details']['sUnit']['unit'], $data['additional_details']['weight'] );
					$msp->cart->AddItem($c_item);
					$c_item->SetMerchantItemId($data['id']);
					$c_item->SetTaxTableSelector($data['tax_rate']);

				}

			}


			$url = $msp->startCheckout();
		}else{
			$url = $msp->startTransaction();
		}
	
		/*
		*	Check if there was an error while requesting the payment link
		*/
		if ($msp->error){
			// TODO LOAD FAILED TEMPLATE WITH ERROR CODE
			echo "Error " . $msp->error_code . ": " . $msp->error;
			exit();
		}else{
			//There was no error while requesting the transaction so we received a payment url (because we don't use the direct payment requests for now) so redirect the customer to the payment page.
			$this->redirect($url);
		}
	}
	
     
	 
	/**
	* This is the fail action. This should be called whenever we receive errors when requesting the transaction. This must be implemented.
	*/  
	public function failAction ()
	{
        $this->View()->extendsTemplate('fail.tpl');
	}

   
   
	/**
	* This action is called whenever the customer cancelles the transaction at MultiSafepay or an external acquirer.
	*/  
	public function cancelAction ()
	{
		return $this->redirect(array('controller' => 'checkout'));
	}

    
	
	
	/**
	* This action is called when the customer is redirected back to the shop. This one saves the order and redirects to finish checkout page.
	*/
	public function finishAction ()
	{
		$request 							= 	$this->Request();
		$orderNumber 						= 	$this->saveOrder($request->getParam('transactionID'), $request->getParam('uniquePaymentID'), NULL, true);
		$this->redirect(array('controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $request->getParam('uniquePaymentID')));
	}
	
    
	/**
	* This action is called by the MultiSafepay offline actions system. This one is used to update the 
	*/
	public function notifyAction ()
	{
		include('Api/MultiSafepay.combined.php');	
		$msp 								= 	new MultiSafepay();
		$request 							= 	$this->Request();
		$transactionid						=	$request->getParam('transactionid');
		$type								=	$request->getParam('type');

		if(Shopware()->Config()->environment){
			$environment					= 	false;
		}else{
			$environment					= 	true;
		}
		
		$msp->test                         	= 	$environment;
		$msp->merchant['account_id']       	= 	Shopware()->Config()->accountid;
		$msp->merchant['site_id']          	=  	Shopware()->Config()->siteid;
		$msp->merchant['site_code']        	= 	Shopware()->Config()->securecode;
	
		$msp->transaction['id']            	= 	$transactionid; 
		
		//Get the transaction status
		$status 							= 	$msp->getStatus();	
		
		//Get all transaction details, this can be used for further processing.
		$details 							=	$msp->details;

		
		switch ($status) 
		{
			case "initialized":
				$status_verbose 			= 	'Pending';
				$stat_code 					= 	17;
              	break;

			case "completed":
				$status_verbose 			= 	'Completed';
				$stat_code 					= 	12;
				break;
			case "uncleared":
			 	$status_verbose 			= 	'Pending';
				$stat_code 					= 	17;
                break;
			case "void":
				$status_verbose 			= 	'Cancelled';
				$stat_code 					= 	-1;
				break;
			case "declined":
				$status_verbose 			= 	'Declined';
				$stat_code 					= 	4;
				break;
			case "refunded":
				$status_verbose 			= 	'Refunded';
				$stat_code 					= 	20;
				break;
                        case "partial_refunded":
				$status_verbose 			= 	'Partially Refunded';
				$stat_code 					= 	20;
				break;
			case "expired":
				$status_verbose 			= 	'Expired';
				$stat_code 					= 	4;
				break;
			case "cancelled":
				$status_verbose 			= 	'Cancelled';
				$stat_code 					= 	4;
				break;
			default:
				$status_verbose 			= 	'Pending';
				$stat_code 					= 	4;
			break;
		}	
	
		//if ($status == 'initialized' || $status == 'completed') //TODO ->Check current order status to check if we can/should update.
	   // {
			$this->savePaymentStatus($request->getParam('transactionid'),$details['transaction']['var1'], $stat_code, true);
	   // }

		if($type == 'initial')
		{
			//Setup the url back to the webshop. This one is shown whenever the notification url from within the transaction xml is called. (This is done on the MultiSafepay pages after transaction, if active)
			$router 						= 	$this->Front()->Router();
			$ret_url						=	$router->assemble(array('action' => 'finish', 'forceSecure' 	=> 	true)) . '?uniquePaymentID=' . $details['transaction']['var1'] . '&transactionID=' . $transactionid;
			echo '<a href="'.$ret_url.'">Return to webshop</a>';
		}else{
			//We show OK when everything has gone OK. The status has been updated etc. OK is shown when the configured Notification url is called. This one is different then the notification url within the transaction request.
			echo 'OK';
		}
	}	
}