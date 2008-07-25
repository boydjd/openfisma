<?php 
/**
 * scanreport_interface.php
 *
 * Plugin Interface
 *
 * @package Controller_components_import
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

interface ScanResult 
{
    /** 
     * To decide wether or not the file can be parsed
     *
     * @return boolean
     */
    public function isValid($file);
    /** 
     * Convert the file to an iteratable data which can be directly injected into database
     *
     * @return mixed
     */
    public function parse($data);
}
