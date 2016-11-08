<?php

/**
 * Class that represents the merchant-private-data.
 * 
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-private-data <merchant-private-data>}
 */
class MspMerchantPrivateData extends MspMerchantPrivate {

    /**
     * 	MspMerchantPrivateData, for transaction request
     */
    function MspMerchantPrivateData($data = array()) {
        $this->data = $data;
        $this->type = 'merchant-private-data';
    }

}

?>