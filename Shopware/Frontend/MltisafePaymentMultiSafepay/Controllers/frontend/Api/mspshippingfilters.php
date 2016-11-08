<?php

/**
 * 
 * Shipping restrictions contain information about particular areas where
 * items can (or cannot) be shipped.
 * 
 * More info:
 * {@link http://code.google.com/apis/checkout/developer/index.html#tag_shipping-restrictions}
 * 
 * Address filters identify areas where a particular merchant-calculated 
 * shipping method is available or unavailable. Address filters are applied 
 * before Google Checkout sends a <merchant-calculation-callback> to the 
 * merchant. Google Checkout will not ask you to calculate the cost of a 
 * particular shipping method for an address if the address filters in the 
 * Checkout API request indicate that the method is not available for the 
 * address.
 * 
 * More info:
 * {@link http://code.google.com/apis/checkout/developer/index.html#tag_address-filters}
 */
class MspShippingFilters {

    var $allow_us_po_box = true;
    var $allowed_restrictions = false;
    var $excluded_restrictions = false;
    var $allowed_world_area = false;
    var $allowed_country_codes_arr;
    var $allowed_postal_patterns_arr;
    var $allowed_country_area;
    var $allowed_state_areas_arr;
    var $allowed_zip_patterns_arr;
    var $excluded_country_codes_arr;
    var $excluded_postal_patterns_arr;
    var $excluded_country_area;
    var $excluded_state_areas_arr;
    var $excluded_zip_patterns_arr;

    /**
     * MspShippingFilters, add filters for FCO shippingmethods
     */
    function MspShippingFilters() {
        $this->allowed_country_codes_arr = array();
        $this->allowed_postal_patterns_arr = array();
        $this->allowed_state_areas_arr = array();
        $this->allowed_zip_patterns_arr = array();

        $this->excluded_country_codes_arr = array();
        $this->excluded_postal_patterns_arr = array();
        $this->excluded_state_areas_arr = array();
        $this->excluded_zip_patterns_arr = array();
    }

    /**
     * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_allow-us-po-box <allow-us-po-box>}
     * 
     * @param bool $allow_us_po_box whether to allow delivery to PO boxes in US,
     * defaults to true
     */
    function SetAllowUsPoBox($allow_us_po_box = true) {
        $this->allow_us_po_box = $allow_us_po_box;
    }

    /**
     * Set the world as allowed delivery area.
     * 
     * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_world-area <world-area>}
     * 
     * @param bool $world_area Set worldwide allowed shipping, defaults to true
     */
    function SetAllowedWorldArea($world_area = true) {
        $this->allowed_restrictions = true;
        $this->allowed_world_area = $world_area;
    }

    // Allows
    /**
     * Add a postal area to be allowed for delivery.
     * 
     * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_postal-area <postal-area>}
     * 
     * @param string $country_code 2-letter iso country code
     * @param string $postal_pattern Pattern that matches the postal areas to
     * be allowed, as defined in {@link http://code.google.com/apis/checkout/developer/index.html#tag_postal-code-pattern}
     */
    function AddAllowedPostalArea($country_code, $postal_pattern = "") {
        $this->allowed_restrictions = true;
        $this->allowed_country_codes_arr[] = $country_code;
        $this->allowed_postal_patterns_arr[] = $postal_pattern;
    }

    /**
     * Add a us country area to be allowed for delivery.
     * 
     * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-country-area <us-country-area>}
     * 
     * @param string $country_area the area to allow, one of "CONTINENTAL", 
     * "FULL_50_STATES" or "ALL"
     * 
     */
    function SetAllowedCountryArea($country_area) {
        switch ($country_area) {
            case "CONTINENTAL_48":
            case "FULL_50_STATES":
            case "ALL":
                $this->allowed_country_area = $country_area;
                $this->allowed_restrictions = true;
                break;
            default:
                $this->allowed_country_area = "";
                break;
        }
    }

    /**
     * Allow shipping to areas specified by state.
     * 
     * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-state-area <us-state-area>}
     * 
     * @param array $areas Areas to be allowed
     */
    function SetAllowedStateAreas($areas) {
        $this->allowed_restrictions = true;
        $this->allowed_state_areas_arr = $areas;
    }

    /**
     * Allow shipping to areas specified by state.
     * 
     * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-state-area <us-state-area>}
     * 
     * @param string $area Area to be allowed
     */
    function AddAllowedStateArea($area) {
        $this->allowed_restrictions = true;
        $this->allowed_state_areas_arr[] = $area;
    }

    /**
     * Allow shipping to areas specified by zip patterns.
     * 
     * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-zip-area <us-zip-area>}
     * 
     * @param array $zips
     */
    function SetAllowedZipPatterns($zips) {
        $this->allowed_restrictions = true;
        $this->allowed_zip_patterns_arr = $zips;
    }

    /**
     * Allow shipping to area specified by zip pattern.
     * 
     * GC tag: {@link http://code.google.com/apis/checkout/developer/index.html#tag_us-zip-area <us-zip-area>}
     * 
     * @param string 
     */
    function AddAllowedZipPattern($zip) {
        $this->allowed_restrictions = true;
        $this->allowed_zip_patterns_arr[] = $zip;
    }

    /**
     * Exclude postal areas from shipping.
     * 
     * @see AddAllowedPostalArea
     */
    function AddExcludedPostalArea($country_code, $postal_pattern = "") {
        $this->excluded_restrictions = true;
        $this->excluded_country_codes_arr[] = $country_code;
        $this->excluded_postal_patterns_arr[] = $postal_pattern;
    }

    /**
     * Exclude state areas from shipping.
     * 
     * @see SetAllowedStateAreas
     */
    function SetExcludedStateAreas($areas) {
        $this->excluded_restrictions = true;
        $this->excluded_state_areas_arr = $areas;
    }

    /**
     * Exclude state area from shipping.
     * 
     * @see AddAllowedStateArea
     */
    function AddExcludedStateArea($area) {
        $this->excluded_restrictions = true;
        $this->excluded_state_areas_arr[] = $area;
    }

    /**
     * Exclude shipping to area specified by zip pattern.
     * 
     * @see SetAllowedZipPatterns
     */
    function SetExcludedZipPatternsStateAreas($zips) {
        $this->excluded_restrictions = true;
        $this->excluded_zip_patterns_arr = $zips;
    }

    /**
     * Exclude shipping to area specified by zip pattern.
     * 
     * @see AddExcludedZipPattern
     */
    function SetAllowedZipPatternsStateArea($zip) {
        $this->excluded_restrictions = true;
        $this->excluded_zip_patterns_arr[] = $zip;
    }

    /**
     * Exclude shipping to country area
     * 
     * @see SetAllowedCountryArea
     */
    function SetExcludedCountryArea($country_area) {
        switch ($country_area) {
            case "CONTINENTAL_48":
            case "FULL_50_STATES":
            case "ALL":
                $this->excluded_country_area = $country_area;
                $this->excluded_restrictions = true;
                break;

            default:
                $this->excluded_country_area = "";
                break;
        }
    }

}

?>