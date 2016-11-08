<?php

/**
 * Represents an alternate tax rule
 * 
 * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_alternate-tax-rule <alternate-tax-rule>}
 */
class MspAlternateTaxRule extends MspTaxRule {

    /**
     * MspAlternateTaxRule, add an alternate tax rule for FCO
     */
    function MspAlternateTaxRule($tax_rate) {
        $this->tax_rate = $tax_rate;

        $this->country_codes_arr = array();
        $this->postal_patterns_arr = array();
        $this->state_areas_arr = array();
        $this->zip_patterns_arr = array();
    }

}

?>