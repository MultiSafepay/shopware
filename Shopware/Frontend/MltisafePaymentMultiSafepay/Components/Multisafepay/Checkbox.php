<?php

/**
 * Shopware 4.0
 * Copyright � 2012 shopware AG
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

/**
 * Checkbox class for the MultiSafepay plugin, usage of a checkbox
 */
class Shopware_Components_PaymentMultisafepay_Checkbox extends Zend_Form_Element_Checkbox {

    public $_pluginID;
    public $description;
    public $logoName;
    public $name;
    public $_name;

    /**
     * Constructor for checkbox object
     *
     * @param string $name
     * @param int $pluginID
     */
    public function __construct($name, $pluginID) {
        $this->_type = 'Checkbox';
        $this->_name = strtolower($name);
        $this->name = $this->_name;
        $this->_pluginID = $pluginID;

        parent::__construct(strtolower($name), $options);
    }

    /**
     * Save data from the payment plugins
     *
     */
    /* public function save ()
      {
      $payment = Shopware()->Payments()->fetchRow(array('name=?' => $this->_name));

      if (!$this->logoName)
      $this->logoName = 'multisafepay.gif';

      if (!$payment)
      {
      $payment = Shopware()->Payments()->createRow(array(
      'name' 					=> $this->_name,
      'description' 			=> $this->description,
      'action' 				=> 'payment_multisafepay',
      'active' 				=> $this->getValue(),
      'pluginID' 				=> $this->_pluginID,
      'additionaldescription' =>''
      ));
      }
      else
      {
      $payment->active = $this->getValue() ? 1 : 0;
      }

      $payment->save();
      } */


    /**
     * Delete the payment record
     *
     */
    /* public function deletePayment ()
      {
      $payment = Shopware()->Payments()->fetchRow(array('name=?' => $this->_name));
      if ($payment)
      $payment->delete();
      } */
}
