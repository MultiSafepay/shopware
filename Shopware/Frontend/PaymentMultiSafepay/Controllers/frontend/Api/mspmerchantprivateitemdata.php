<?php

  /**
   * Class that represents a merchant-private-item-data.
   * 
   * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-private-item-data <merchant-private-data>}
   */
  class MspMerchantPrivateItemData extends MspMerchantPrivate {
    /**
     * @param mixed $data a string with the data that will go in the 
     *                    merchant-private-item-data tag or an array that will
     *                    be mapped to xml, formatted like:
     *                    array('my-item-id' => 34234,
     *                          'stuff' => array('label' => 'cool',
     *                                           'category' => 'hip stuff'))
     *                    this will map to:
     *                    <my-item-id>
     *                      <stuff>
     *                        <label>cool</label>
     *                        <category>hip stuff</category>
     *                      </stuff>
     *                    </my-item-id>
     */
    function MspMerchantPrivateItemData($data = array()) {
      $this->data = $data;
      $this->type = 'merchant-private-item-data';
    }
  }

  
  ?>