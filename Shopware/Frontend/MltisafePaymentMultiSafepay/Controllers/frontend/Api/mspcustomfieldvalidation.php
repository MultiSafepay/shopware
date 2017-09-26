<?php

/**
 * Class for adding customfield validations to a custom field
 */
class MspCustomFieldValidation {

    var $type;
    var $data;
    var $error;

    /**
     * MspCustomFieldValidation add customfieldvalidation for FCO custom field
     */
    function __construct($type, $data, $error) {
        $this->type = $type;
        $this->data = $data;
        $this->error = $error;
    }

}

?>