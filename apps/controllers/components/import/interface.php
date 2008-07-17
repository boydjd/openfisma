<?php 
/**
 * @file:scanreport_interface.php
 *
 * Plugin Interface
 *
 * @author     Jim Chen<jimc@reyosoft.com>
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
