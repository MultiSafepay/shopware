<?php

/*
 * Copyright (C) 2007 Google Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */
/**
 * Classes used to represent shipping types
 * @version $Id: googleshipping.php 1234 2007-09-25 14:58:57Z ropu $
 */
 
  /**
   * Class that represents flat rate shipping
   * 
   * info:
   * {@link http://code.google.com/apis/checkout/developer/index.html#tag_flat-rate-shipping}
   * {@link http://code.google.com/apis/checkout/developer/index.html#shipping_xsd}
   *  
   */
  class MspFlatRateShipping {

    var $price;
    var $name;
    var $type = "flat-rate-shipping";
    var $shipping_restrictions;

    
	/**
	* MspFlatRateShipping, ADD flatrate shipping for FCO transaction
    * @param string $name a name for the shipping
    * @param double $price the price for this shipping
    */
    function MspFlatRateShipping($name, $price) {
      $this->name = $name;
      $this->price = $price;
    }

   
	/**
    * Adds a restriction to this shipping.
    * 
    * @param GoogleShippingFilters $restrictions the shipping restrictions
    */
    function AddShippingRestrictions($restrictions) {
      $this->shipping_restrictions = $restrictions;
    }
  }
  
  ?>