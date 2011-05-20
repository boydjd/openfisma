<?php
/**
 * ZIDS Ignore Action 
 * 
 * Implements the action interface. Task: do nothing... 
 *
 * @package    ZIDS
 * @author     Christian Koncilia
 * @copyright  Copyright (c) 2010 Christian Koncilia. (http://www.web-punk.com)
 * @license    New BSD License (see above)
 * @version    V.0.6 
 */

class ZIDS_Plugin_ActionPlugin_Ignore extends ZIDS_Plugin_ActionPlugin_Action {

    /**
     * plugin identifier
     *
     * @var string
     */
    protected $_identifier = 'ignore';	
	
    /**
     * Required by ZIDS_Plugin_ActionPlugin_Interface
     * 
     * @return void
     */
    public function fires(IDS_Report $ids_result, $impact, $levelname) {
		// do nothing
    }

    /**
     * Required by ZIDS_Plugin_ActionPlugin_Interface
     * Returns the unique identifier
     *
     * @return string
     */
    public function getIdentifier() {
    	return $this->_identifier;	
    }
}