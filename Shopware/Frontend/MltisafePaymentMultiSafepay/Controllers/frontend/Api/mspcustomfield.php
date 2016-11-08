<?php

/**
 * CustomField class for FastCheckout. Creats a custom field that will be visible within the FCO page
 */
class MspCustomField {

    var $standardField = null;
    var $name = null;
    var $type = null;
    var $label = null;
    var $default = null;
    var $savevalue = null;
    var $options = array();
    var $validation = array();
    var $filter = null;
    var $descriptionTop = array();
    var $descriptionRight = array();
    var $descriptionBottom = array();

    /**
     * MspCustomField create custom field for FCO transaction
     */
    function MspCustomField($name = null, $type = null, $label = null) {
        $this->name = $name;
        $this->type = $type;
        $this->label = $label;
    }

    /**
     * add options for FCO custom fields
     */
    function AddOption($value, $label) {
        $this->options[] = new MspCustomFieldOption($value, $label);
    }

    /**
     * add validations for FCO customfields
     */
    function AddValidation($validation) {
        $this->validation[] = $validation;
    }

    /**
     * add restrictions for customfieds for FCO
     */
    function AddRestrictions($filter) {
        $this->filter = $filter;
    }

    /**
     * set standard fields for FCO transaction
     */
    function SetStandardField($name, $optional = false) {
        $this->standardField = $name;
        if ($optional) {
            $this->AddValidation(new MspCustomFieldValidation('regex', ' '));
        }
    }

}

?>