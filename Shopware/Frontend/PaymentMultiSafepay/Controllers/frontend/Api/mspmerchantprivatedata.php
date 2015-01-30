<?php

  
  /**
   * Class that represents the merchant-private-data.
   * 
   * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-private-data <merchant-private-data>}
   */
  class MspMerchantPrivateData extends MspMerchantPrivate {
    /**
     * @param mixed $data a string with the data that will go in the 
     *                    merchant-private-data tag or an array that will
     *                    be mapped to xml, formatted like (e.g.):
     *                    array('my-order-id' => 34234,
     *                          'stuff' => array('registered' => 'yes',
     *                                           'category' => 'hip stuff'))
     *                    this will map to:
     *                    <my-order-id>
     *                      <stuff>
     *                        <registered>yes</registered>
     *                        <category>hip stuff</category>
     *                      </stuff>
     *                    </my-order-id>
     */
    function MspMerchantPrivateData($data = array()) {
      $this->data = $data;
      $this->type = 'merchant-private-data';
    }
  }
  ?>