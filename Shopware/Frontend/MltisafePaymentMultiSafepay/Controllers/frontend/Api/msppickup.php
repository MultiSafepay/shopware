<?php

/**
 * Used as a shipping option in which neither a carrier nor a ship-to 
 * address is specified
 * 
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_pickup} <pickup>
 */
class MspPickUp {

    var $price;
    var $name;
    var $type = "pickup";

    /**
     * MspPickUp For FCO shipping method
     * @param string $name the name of this shipping option
     * @param double $price the handling cost (if there is one)
     */
    function __construct($name, $price) {
        $this->price = $price;
        $this->name = $name;
    }

}

?>