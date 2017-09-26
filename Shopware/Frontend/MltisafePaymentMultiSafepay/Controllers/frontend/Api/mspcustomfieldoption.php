<?php

/**
 * Custom field option class. Add options to the customer field
 */
class MspCustomFieldOption {

    var $value;
    var $label;

    /**
     * MspCustomFieldOtion for FCO transaction
     */
    function __construct($value, $label) {
        $this->value = $value;
        $this->label = $label;
    }

}

?>