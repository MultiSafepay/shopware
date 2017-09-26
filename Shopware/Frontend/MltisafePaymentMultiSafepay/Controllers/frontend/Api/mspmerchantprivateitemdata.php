<?php

/**
 * Class that represents a merchant-private-item-data.
 * 
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-private-item-data <merchant-private-data>}
 */
class MspMerchantPrivateItemData extends MspMerchantPrivate {

    /**
     * MspMerchantPrivateItemData, private data for FCO items
     */
    function __construct($data = array()) {
        $this->data = $data;
        $this->type = 'merchant-private-item-data';
    }

}

?>