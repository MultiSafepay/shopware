<?php

class MspCustomFields {
    var $fields = array();
    var $fields_xml_extra = '';
    
    function AddField($field){
        $this->fields[] = $field;
    }
    
    function SetRaw($xml){
        $this->fields_xml_extra = $xml;
    }
    
    function GetXml(){
        $xml_data = new msp_gc_XmlBuilder();
        $xml_data->xml = '';
        
        //$xml_data->Push('custom-fields');
        foreach($this->fields as $field){
            $xml_data->Push('field');
            
            if ($field->standardField){
                $xml_data->Element('standardtype', $field->standardField);
            }
            
            if ($field->name){
                $xml_data->Element('name', $field->name);
            }
            if ($field->type){
                $xml_data->Element('type', $field->type);
            }
            if ($field->default){
                $xml_data->Element('default', $field->default);
            }
            if ($field->savevalue){
                $xml_data->Element('savevalue', $field->savevalue);
            }
            if ($field->label){
                $this->_GetXmlLocalized($xml_data, 'label', $field->label);
            }
            
            if (!empty($field->descriptionTop)){
                $xml_data->Push('description-top');
                if (!empty($field->descriptionTop['style'])){
                    $xml_data->Element('style', $field->descriptionTop['style']);
                }
                $this->_GetXmlLocalized($xml_data, 'value', $field->descriptionTop['value']);
                $xml_data->Pop('description-top');
            }
            
            if (!empty($field->descriptionRight)){
                $xml_data->Push('description-right');
                if (!empty($field->descriptionRight['style'])){
                    $xml_data->Element('style', $field->descriptionRight['style']);
                }
                $this->_GetXmlLocalized($xml_data, 'value', $field->descriptionRight['value']);
                $xml_data->Pop('description-right');
            }
            
            if (!empty($field->descriptionBottom)){
                $xml_data->Push('description-bottom');
                if (!empty($field->descriptionBottom['style'])){
                    $xml_data->Element('style', $field->descriptionBottom['style']);
                }
                $this->_GetXmlLocalized($xml_data, 'value', $field->descriptionBottom['value']);
                $xml_data->Pop('description-bottom');
            }
            
            if (!empty($field->options)){
                $xml_data->Push('options');
                foreach($field->options as $option){
                    $xml_data->Push('option');
                    $xml_data->Element('value', $option->value);
                    $this->_GetXmlLocalized($xml_data, 'label', $option->label);
                    $xml_data->Pop('option');
                }
                $xml_data->Pop('options');
            }
            
            if (!empty($field->validation)){
                foreach($field->validation as $validation){
                    $xml_data->Push('validation');
                    $xml_data->Element($validation->type, $validation->data);
                    $this->_GetXmlLocalized($xml_data, 'error', $validation->error);
                    $xml_data->Pop('validation');
                }
            }
            
            if ($field->filter){
                $xml_data->Push('field-restrictions');
                
                if (!empty($field->filter->allowed_country_codes_arr)){
                    $xml_data->Push('allowed-areas');
                    foreach($field->filter->allowed_country_codes_arr as $country_code){
                        $xml_data->Push('postal-area');
                        $xml_data->Element('country-code', $country_code);
                        $xml_data->Pop('postal-area');
                    }
                    $xml_data->Pop('allowed-areas');
                }
                
                if (!empty($field->filter->excluded_country_codes_arr)){
                    $xml_data->Push('excluded-areas');
                    foreach($field->filter->excluded_country_codes_arr as $country_code){
                        $xml_data->Push('postal-area');
                        $xml_data->Element('country-code', $country_code);
                        $xml_data->Pop('postal-area');
                    }
                    $xml_data->Pop('excluded-areas');
                }
                
                $xml_data->Pop('field-restrictions');
            }

            $xml_data->Pop('field');
        }
        //$xml_data->Pop('custom-fields');
        
        return '<custom-fields>' . $xml_data->GetXML() . $this->fields_xml_extra . '</custom-fields>';  
    }
    
    function _GetXmlLocalized(&$xml_data, $field, $value){
        if(is_array($value)){
            foreach($value as $lang => $text){
                $xml_data->Element($field, $text, array('xml:lang' => $lang));
            }
        }else{
            $xml_data->Element($field, $value);
        }
    }
}


?>