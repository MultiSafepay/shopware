<?php

/**
 * Represents a default tax rule
 * 
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_default-tax-rule <default-tax-rule>}
 */
class MspDefaultTaxRule extends MspTaxRule {

    var $shipping_taxed = false;

    /**
     * MspDefaultTaxRule, defaultaxrules for FCO transaction
     */
    function MspDefaultTaxRule($tax_rate, $shipping_taxed = "false") {
        $this->tax_rate = $tax_rate;
        $this->shipping_taxed = $shipping_taxed;

        $this->country_codes_arr = array();
        $this->postal_patterns_arr = array();
        $this->state_areas_arr = array();
        $this->zip_patterns_arr = array();
    }

}

?>