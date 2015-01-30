<?php

class MspCustomFieldFilter {
    var $allowed_country_codes_arr;
    var $excluded_country_codes_arr;
    
    function MspCustomFieldFilter(){
        $this->allowed_country_codes_arr = array();
        $this->excluded_country_codes_arr = array();
    }
    
    function AddAllowedPostalArea($country_code) {
        $this->allowed_country_codes_arr[] = $country_code;
    }
    
    function AddExcludedPostalArea($country_code) {
        $this->excluded_country_codes_arr[] = $country_code;
    }
}


?>