<?php

/**
 * Class for creating filters for the custom fields
 */
class MspCustomFieldFilter {

    var $allowed_country_codes_arr;
    var $excluded_country_codes_arr;

    /**
     * MspCustomFieldFilter, filter for custom fields
     */
    function MspCustomFieldFilter() {
        $this->allowed_country_codes_arr = array();
        $this->excluded_country_codes_arr = array();
    }

    /**
     * add allowed postalarea for FCO shippingmethods
     */
    function AddAllowedPostalArea($country_code) {
        $this->allowed_country_codes_arr[] = $country_code;
    }

    /**
     * add excludedpostalarea for FCO shippingmethods
     */
    function AddExcludedPostalArea($country_code) {
        $this->excluded_country_codes_arr[] = $country_code;
    }

}

?>