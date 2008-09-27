<?php
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
 * @author    ???
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 *
 * @todo This file needs a LOT of fixing up.
 */

/*
 *this function adjust import data
 *param $datafile schema file
 *return a formatted string
 */
function format_data($dataString){
    $dataString = preg_replace('/\/\*.*\*\//', '', $dataString);
    if (ereg(";$", trim($dataString))) {
        $execute['opt']='execute';
    } else {
        $execute['opt']='incomplete';
    }
    $execute['sql']= $dataString;
    return $execute;    
}

/*
 *this function import data
 *param a formatted array  
 *please make sure comment in one line!
 */
function import_data($db,$dataFile){
    $tmp = "";
    foreach ($dataFile as $elem) {
        $ret = true;
        if ($handle = fopen($elem, 'r')) {
            $dumpline = '';
            while (!feof($handle)&& substr($dumpline, -1)!= "\n") {
                $dumpline = fgets($handle, '4096');
                $dumpline = ereg_replace("\r\n$", "\n", $dumpline);
                $dumpline = ereg_replace("\r$", "\n", $dumpline);
                $dumpline = ereg_replace("--.*\n", "\n", $dumpline);
                $dumpline = trim($dumpline);
                $execute = format_data($dumpline);
                if ($execute['opt']=='incomplete') {
                    $tmp .= $execute['sql'];
                } else {
                    $ret = $db->query($tmp.$execute['sql']);
                    $tmp = '';
                }
                if ( !$ret ) {
                    break;
                }
            }
        } else {
            $ret = false;
        }
        if (!$ret) {
            return $ret;
        }
     }
     return  true; 
}
