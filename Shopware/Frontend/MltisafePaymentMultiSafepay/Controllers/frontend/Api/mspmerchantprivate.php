<?php

/**
 * Abstract class that represents the merchant-private-data.
 * 
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_merchant-private-data <merchant-private-data>}
 */
class MspMerchantPrivate {

    var $data;
    var $type = "Abstract";

    /**
     * unused MspMerchantPrivate function for FCO transaction
     */
    function MspMerchantPrivate() {
        
    }

    /**
     * AddMerchantPrivateToXML for FCO transaction
     */
    function AddMerchantPrivateToXML(&$xml_data) {
        if (is_array($this->data)) {
            $xml_data->Push($this->type);
            $this->_recursiveAdd($xml_data, $this->data);
            $xml_data->Pop($this->type);
        } else {
            $xml_data->Element($this->type, (string) $this->data);
        }
    }

    /**
     * _recursiveAdd xml for transaction
     * @access private
     */
    function _recursiveAdd(&$xml_data, $data) {
        foreach ($data as $name => $value) {
            if (is_array($value)) {
                $xml_data->Push($name);
                $this->_recursiveAdd($xml_data, $name);
                $xml_data->Pop($name);
            } else {
                $xml_data->Element($name, (string) $value);
            }
        }
    }

}

?>