<?php

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

    function MspCustomField($name = null, $type = null, $label = null) {
        $this->name = $name;
        $this->type = $type;
        $this->label = $label;
    }

    function AddOption($value, $label) {
        $this->options[] = new MspCustomFieldOption($value, $label);
    }

    function AddValidation($validation) {
        $this->validation[] = $validation;
    }

    function AddRestrictions($filter) {
        $this->filter = $filter;
    }

    function SetStandardField($name, $optional = false) {
        $this->standardField = $name;
        if ($optional) {
            $this->AddValidation(new MspCustomFieldValidation('regex', ' '));
        }
    }

}

?>