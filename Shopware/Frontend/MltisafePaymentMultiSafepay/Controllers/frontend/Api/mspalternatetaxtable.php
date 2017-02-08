<?php

/**
 * Represents an alternate tax table
 * 
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_alternate-tax-table <alternate-tax-table>}
 */
class MspAlternateTaxTable {

    var $name;
    var $tax_rules_arr;
    var $standalone;

    /**
     * MspAlternateTaxTable, add an alternative taxtable for FCO
     */
    function __construct($name = "", $standalone = "false") {
        if ($name != "") {
            $this->name = $name;
            $this->tax_rules_arr = array();
            $this->standalone = $standalone;
        }
    }

    /**
     * AddAlternateTaxRules, add an alternative TAX Rule for FCO
     */
    function AddAlternateTaxRules($rules) {
        $this->tax_rules_arr[] = $rules;
    }

}

?>