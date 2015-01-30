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
 * @subpackage MultisafepayPayment
 * @copyright  Copyright (c) 2014, MultiSafepay (http://www.multisafepay.com
 * @version    $Id$
 * @author     Multisafepay
 * @author     $Author$
 */
class Shopware_Plugins_Frontend_MltisafePaymentMultiSafepay_Bootstrap extends Shopware_Components_Plugin_Bootstrap {
    /*
     * 	Array containing all supporte payment methods by MultiSafepay. You can add more gateways using the MultiSafepay gateway codes.
     */

    public static $paymentMethods = array(
        'WALLET' => 'Wallet',
        'VISA' => 'VISA',
        'MASTERCARD' => 'MASTERCARD',
        'AMEX' => 'AMERICAN EXPRESS',
        'MAESTRO' => 'MAESTRO',
        'BANKTRANS' => 'Bank Transfer',
        'DIRDEB' => 'Direct Debit',
        'DIRECTBANK' => 'Sofort Banking',
        'GIROPAY' => 'GiroPay',
        'IDEAL' => 'iDEAL',
        'MISTERCASH' => 'MisterCash',
        'PAYPAL' => 'PayPal',
        'PAYAFTER' => 'Betaal na Ontvangst',
        'BABYGIFTCARD' => 'Babygiftcard',
        'BOEKENBON' => 'Boekenbon',
        'DEGROTESPEELGOEDWINKEL' => 'De grote speelgoed winkel',
        'EBON' => 'Ebon',
        'EROTIEKBON' => 'Erotiekbon',
        'FASHIONCHEQUE' => 'Fashioncheque',
        'GEZONDHEIDSBON' => 'Gezondheidsbon',
        'LIEF' => 'Lief',
        'PARFUMCADEAUKAART' => 'Parfumcadeaukaart',
        'PARFUMNL' => 'Parfumnl',
        'WEBSHOPGIFTCARD' => 'Webshop giftcard',
    );

    /*
     * 	Array containing the images files for the corresponding gateways. Not yet fully implemented	
     */
    public static $logos = array(
        'WALLET' => 'wallet.gif',
        'VISA' => 'visa.gif',
        'MASTERCARD' => 'mastercard.gif',
        'AMEX' => 'amex.gif',
        'MAESTRO' => 'maestro.gif',
        'BANKTRANS' => 'banktrans.gif',
        'DIRDEB' => 'dirdeb.gif',
        'DIRECTBANK' => 'directbank.gif',
        'GIROPAY' => 'giropay.gif',
        'IDEAL' => 'ideal.gif',
        'MISTERCASH' => 'mistercash.gif',
        'PAYPAL' => 'paypal.gif',
        'PAYAFTER' => 'payafter.gif',
        'BOEKENBON' => 'boekenbon.gif',
        'DEGROTESPEELGOEDWINKEL' => 'degrotespeelgoedwinkel.gif',
        'EBON' => 'ebon.gif',
        'EROTIEKBON' => 'erotiekbon.gif',
        'FASHIONCHEQUE' => 'fashioncheque.gif',
        'GEZONDHEIDSBON' => 'gezondheidsbon.gif',
        'LIEF' => 'lief.gif',
        'PARFUMCADEAUKAART' => 'parfumcadeaukaart.gif',
        'PARFUMNL' => 'parfumnl.gif',
        'WEBSHOPGIFTCARD' => 'webshopgiftcard.gif',
    );

    /**
     * onSaveForm function for adding the seperate gateways
     */
    public static function onSaveForm(Enlight_Hook_HookArgs $args) {
        //Register the namespace to that the MultiSafepay checkboxes work
        Shopware()->Loader()->registerNamespace('Shopware_Components_PaymentMultisafepay', dirname(__FILE__) . '/Components/Multisafepay/');
        $class = $args->getSubject();
        $request = $class->Request();
        $pluginId = (int) $request->id;
        $elements = $request->getPost('elements');

        // Start adding the seperate gateways
        foreach ($elements as $element_id => $element_data) {
            foreach (self::$paymentMethods as $pAbbrMethod => $pMethod) {
                if ($element_data['name'] != 'multisafepay_' . strtolower($pAbbrMethod)) {
                    continue;
                }

                $pMethodElement = new Shopware_Components_PaymentMultisafepay_Checkbox('multisafepay_' . strtolower($pAbbrMethod), $pluginId);
                $pMethodElement->setValue($element_data['values'][0]['value']);
                $pMethodElement->description = 'Multisafepay ' . $pMethod;
                if (self::$logos[$pAbbrMethod]) {
                    $pMethodElement->logoName = self::$logos[$pAbbrMethod];
                }
                $pMethodElement->save();
            }
        }
    }

    /**
     * createPayments function addts the paymentmethods
     */
    protected function createPayments() {
        $payment = Shopware()->Payments()->fetchRow(array('name=?' => 'multisafepay'));

        if (!$payment) {
            Shopware()->Payments()->createRow(array('name' => 'multisafepay', 'description' => 'Multisafepay', 'action' => 'payment_multisafepay', 'active' => 1, 'pluginID' => $this->getId(), 'additionaldescription' => '<div id="multisafepay_desc">
						MultiSafepay offers innovative and solid payment products and solutions for small business and large corporations.
With MultiSafepay you can offer specific local payment options for Germany, The Netherlands and Belgium and a wide variety of creditcards for all other countries.
				    </div>'))->save();
        }
    }

    /**
     * 	Called on install. This add's events for the plugin and creates the payment methods and form
     */
    public function install() {
        //Register the namespace to that the MultiSafepay checkboxes work
        Shopware()->Loader()->registerNamespace('Shopware_Components_PaymentMultisafepay', dirname(__FILE__) . '/Components/Multisafepay/');

        $event = $this->createEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
        $this->subscribeEvent($event);

        $event = $this->createEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentMultisafepay', 'onGetControllerPath');
        $this->subscribeEvent($event);

        $event = $this->createEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentMultisafepay', 'onGetControllerPathFrontend');
        $this->subscribeEvent($event);

        $this->createPayments();
        $this->createForm();
        return true;
    }

    /**
     * Uninstall function to remove plugin 
     */
    public function uninstall() {
        //Register the namespace to that the MultiSafepay checkboxes work
        Shopware()->Loader()->registerNamespace('Shopware_Components_PaymentMultisafepay', dirname(__FILE__) . '/Components/Multisafepay/');

        if ($payment = $this->Payment()) {
            $payment->delete();
        }

        $form = $this->Form();

        // Uninstall all gateways
        foreach (self::$paymentMethods as $pAbbrMethod => $pMethod) {
            $pMethodElement = $form->getElement('multisafepay_' . strtolower($pAbbrMethod));
            if (!$pMethodElement) {
                continue;
            }

            $pMethodNew = new Shopware_Components_PaymentMultisafepay_Checkbox('multisafepay_' . strtolower($pAbbrMethod), $this->getId());
            $pMethodNew->deletePayment();
        }
        return parent::uninstall();
    }

    /**
     * Enable function enables the paymentmethod
     */
    public function enable() {
        $payment = $this->Payment();
        if ($payment !== null) {
            $payment->active = 1;
        }
        return true;
    }

    /**
     * Disable function disables the paymentmethod
     */
    public function disable() {
        $payment = $this->Payment();
        if ($payment !== null) {
            $payment->active = 0;
        }
        return true;
    }

    /**
     * Payment function fetches the pm
     */
    public function Payment() {
        return Shopware()->Payments()->fetchRow(array('name=?' => 'multisafepay'));
    }

    /**
     * onGetControllerPatch function, add template dir, return controller
     */
    public static function onGetControllerPath(Enlight_Event_EventArgs $args) {
        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
        return dirname(__FILE__) . '/Controllers/frontend/Multisafepay.php';
    }

    /**
     * onPostDispatch function, handle responses
     */
    public static function onPostDispatch(Enlight_Event_EventArgs $args) {
        $request = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();
        $view = $args->getSubject()->View();

        if ($request->getActionName() == 'saveForm' && $request->getModuleName() == 'backend' && $request->getControllerName() == 'config') {
            self::onSaveForm($args);
            return;
        }

        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');

        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
            return;
        }
    }

    /**
     * createForm function creates the admin config form
     */
    public function createForm() {
        // Create to configurations form
        $form = $this->Form();
        $form->setElement('text', 'accountid', array('label' => 'Account ID', 'required' => true));
        $form->setElement('text', 'siteid', array('label' => 'Site ID', 'required' => true));
        $form->setElement('text', 'securecode', array('label' => 'Site Secure Code', 'required' => true));
        $form->setElement('text', 'apikey', array('label' => 'API Key', 'required' => false));
        $form->setElement('checkbox', 'environment', array('label' => 'Live transactions', 'value' => true));

        foreach (self::$paymentMethods as $pAbbrMethod => $pMethod) {
            $pMethodElement = new Shopware_Components_PaymentMultisafepay_Checkbox('multisafepay_' . $pAbbrMethod, $this->getId());
            $pMethodElement->setLabel($pMethod);
            $pMethodElement->description = 'Multisafepay ' . $pMethod;
            if (self::$logos[$pAbbrMethod]) {
                $pMethodElement->logoName = self::$logos[$pAbbrMethod];
            }
            $pMethodElement->setValue(false);
            $pMethodElement->save();

            $form->setElement('checkbox', $pMethodElement->name, array('label' => $pMethodElement->description, 'value' => false));
        }
        /* All checkboxes must be replaced by the following code whenever we can save/load multiple option fields
         * in Shopware 4.0
         *
         * $multisafepaymultiple = new Shopware_Components_Multisafepay_Multiselect('paymentMethods');
         * $multisafepaymultiple->setLabel('Payment methods');
         * $multisafepaymultiple->addMultiOptions($paymentMethods);
         * $form->addElement($multisafepaymultiple);
         *
         * */
    }

    /**
     * getVersion returns the currenct plugin version
     */
    public function getVersion() {
        return '1.0.0';
    }

    /**
     * getInfo function, returns an array of information about the plugin.
     */
    public function getInfo() {
        return array(
            'version' => $this->getVersion(),
            'autor' => 'Multisafepay',
            'label' => 'MultiSafepay',
            'source' => 'MultiSafepay',
            'description' => 'MultiSafepay offers innovative and solid payment products and solutions for small business and large corporations.
With MultiSafepay you can offer specific local payment options for Germany, The Netherlands and Belgium and a wide variety of creditcards for all other countries.',
            'license' => '',
            'support' => 'http://support.multisafepay.com',
            'link' => 'http://www.multisafepay.com/',
            'changes' => '[changelog]',
            'revision' => '[revision]');
    }

}
