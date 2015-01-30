<?php


class MspCustomFieldValidation {
    var $type;
    var $data;
    var $error;
    
    function MspCustomFieldValidation($type, $data, $error){
        $this->type  = $type;
        $this->data  = $data;
        $this->error = $error;
    }
}



?>