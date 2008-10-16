<?PHP
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Ryan Yang <ryanyang@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * ???
 *
 * @package    Controller
 * @subpackage Controller_Subpackage
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo This class needs to be refactored, and then the /include directory
 * can be removed entirely.
 */
class XmlToArray
{
    var $xml=''; 
    
    /** 
    * Default Constructor 
    * @param $xml = xml data 
    * @return none 
    */ 
    
    function XmlToArray($xml) 
    { 
       $this->xml = $xml;    
    } 
    
    /** 
    * _struct_to_array($values, &$i) 
    * 
    * This is adds the contents of the return xml into the array 
    * for easier processing. 
    * Recursive, Static 
    * 
    * @access    private 
    * @param    array  $values this is the xml data in an array 
    * @param    int    $i  this is the current location in the array 
    * @return    Array 
    */ 
    
    function _struct_to_array($values, &$i) 
    { 
        $child = array(); 
        if (isset($values[$i]['value'])) {
            array_push($child, $values[$i]['value']);
        }
        
        while ($i++ < count($values)) {
            switch ($values[$i]['type']) {
                case 'cdata':
                    array_push($child, $values[$i]['value']);
                    break;
                
                case 'complete':
                    $name = $values[$i]['tag'];
                    if (!empty($name)) {
                        $child[$name] = isset($values[$i]['value'])?
                            $values[$i]['value']:'';
                        if (isset($values[$i]['attributes'])) {
                            $child[$name] = $values[$i]['attributes'];
                        }
                    }
                    break;
                
                case 'open':
                    $name = $values[$i]['tag'];
                    $size = isset($child[$name]) ? sizeof($child[$name]) : 0;
                    if ( array_key_exists($name, $child) ) {
                        $child[$name][] = $child[$name];
                        $child[$name][] = $this->_struct_to_array($values, $i);

                    } else {
                        $child[$name][$size] = 
                            $this->_struct_to_array($values, $i);
                    }
                    break; 
                
                case 'close': 
                    return $child; 
                    break; 
            } 
        }
        return $child; 
    }
    
    /** 
    * createArray($data) 
    * 
    * This is adds the contents of the return xml into the array
    * for easier processing. 
    * 
    * @access    public 
    * @param    string    $data this is the string of the xml data 
    * @return    Array 
    */ 
    function createArray() 
    { 
        $xml    = $this->xml; 
        $values = array(); 
        $index  = array(); 
        $array  = array(); 
        $parser = xml_parser_create(); 
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); 
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); 
        xml_parse_into_struct($parser, $xml, $values, $index); 
        xml_parser_free($parser); 
        $i = 0; 
        $name = $values[$i]['tag']; 
        $array[$name] =
            isset($values[$i]['attributes'])? $values[$i]['attributes']:'';
        $array[$name] = $this->_struct_to_array($values, $i);
        return $array; 
    }
}
