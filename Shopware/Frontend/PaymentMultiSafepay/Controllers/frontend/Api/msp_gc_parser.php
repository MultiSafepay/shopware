<?php

/**
 * Classes used to parse xml data
 */
class msp_gc_xmlparser {

    var $params = array(); //Stores the object representation of XML data
    var $root = NULL;
    var $global_index = -1;
    var $fold = false;

    /* Constructor for the class
     * Takes in XML data as input( do not include the <xml> tag
     */

    function msp_gc_xmlparser($input, $xmlParams = array(XML_OPTION_CASE_FOLDING => 0)) {

        // XML PARSE BUG: http://bugs.php.net/bug.php?id=45996
        $input = str_replace('&amp;', '[msp-amp]', $input);
        //

        $xmlp = xml_parser_create();
        foreach ($xmlParams as $opt => $optVal) {
            switch ($opt) {
                case XML_OPTION_CASE_FOLDING:
                    $this->fold = $optVal;
                    break;
                default:
                    break;
            }
            xml_parser_set_option($xmlp, $opt, $optVal);
        }

        if (xml_parse_into_struct($xmlp, $input, $vals, $index)) {
            $this->root = $this->_foldCase($vals[0]['tag']);
            $this->params = $this->xml2ary($vals);
        }
        xml_parser_free($xmlp);
    }

    function _foldCase($arg) {
        return( $this->fold ? strtoupper($arg) : $arg);
    }

    /*
     * Credits for the structure of this function
     * http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
     * 
     * Adapted by Ropu - 05/23/2007 
     * 
     */

    function xml2ary($vals) {

        $mnary = array();
        $ary = &$mnary;
        foreach ($vals as $r) {
            $t = $r['tag'];
            if ($r['type'] == 'open') {
                if (isset($ary[$t]) && !empty($ary[$t])) {
                    if (isset($ary[$t][0])) {
                        $ary[$t][] = array();
                    } else {
                        $ary[$t] = array($ary[$t], array());
                    }
                    $cv = &$ary[$t][count($ary[$t]) - 1];
                } else {
                    $cv = &$ary[$t];
                }
                $cv = array();
                if (isset($r['attributes'])) {
                    foreach ($r['attributes'] as $k => $v) {
                        $cv[$k] = $v;
                    }
                }

                $cv['_p'] = &$ary;
                $ary = &$cv;
            } else if ($r['type'] == 'complete') {
                if (isset($ary[$t]) && !empty($ary[$t])) { // same as open
                    if (isset($ary[$t][0])) {
                        $ary[$t][] = array();
                    } else {
                        $ary[$t] = array($ary[$t], array());
                    }
                    $cv = &$ary[$t][count($ary[$t]) - 1];
                } else {
                    $cv = &$ary[$t];
                }
                if (isset($r['attributes'])) {
                    foreach ($r['attributes'] as $k => $v) {
                        $cv[$k] = $v;
                    }
                }
                $cv['VALUE'] = (isset($r['value']) ? $r['value'] : '');

                // XML PARSE BUG: http://bugs.php.net/bug.php?id=45996
                $cv['VALUE'] = str_replace('[msp-amp]', '&amp;', $cv['VALUE']);
                //
            } elseif ($r['type'] == 'close') {
                $ary = &$ary['_p'];
            }
        }

        $this->_del_p($mnary);
        return $mnary;
    }

    // _Internal: Remove recursion in result array
    function _del_p(&$ary) {
        foreach ($ary as $k => $v) {
            if ($k === '_p') {
                unset($ary[$k]);
            } else if (is_array($ary[$k])) {
                $this->_del_p($ary[$k]);
            }
        }
    }

    /* Returns the root of the XML data */

    function GetRoot() {
        return $this->root;
    }

    /* Returns the array representing the XML data */

    function GetData() {
        return $this->params;
    }

}

?>