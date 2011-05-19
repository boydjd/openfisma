<?php
/**
 * ZIDS Redirect Action 
 * 
 * Implements the action interface. Redirect user to a defined URL. 
 *
 * @package    ZIDS
 * @author     Christian Koncilia
 * @copyright  Copyright (c) 2010 Christian Koncilia. (http://www.web-punk.com)
 * @license    New BSD License (see above)
 * @version    V.0.6 
 */

class ZIDS_Plugin_ActionPlugin_Redirect extends ZIDS_Plugin_ActionPlugin_Action {

    /**
     * plugin identifier
     *
     * @var string
     */
    protected $_identifier = 'redirect';	

    /**
     * Redirect target
     *
     * @var string
     */
    protected $_module = null;
    protected $_controller = null;
    protected $_action = null;

    /**
     * Constructor
     * 
     * @param array $options (optional)
     * @return void
     */
    public function __construct(array $options = null) {
    	if (null != $options) {
    		if (array_key_exists('module', $options) && 
    			array_key_exists('controller', $options) && 
    			array_key_exists('action', $options)) 
    		{
    			$this->_module = $options['module'];
    			$this->_controller = $options['controller'];
    			$this->_action = $options['action'];
    		} else {
    			throw new Exception("Options array has to consist of keys 'module', 'controller' and 'action'");
    		}    		
    	}
    }
    
    /**
     * Required by ZIDS_Plugin_ActionPlugin_Interface
     * Writes output to Zend_Log instance registered
     * 
     * @return void
     */
    public function fires(IDS_Report $ids_result, $impact, $levelname) {
    	// get options for the redirect plugin
		$options = $this->getOptions($levelname);
		
		// set the new request target
		if (null != $this->_module) {
			$this->_request->setModuleName($this->_module);
			$this->_request->setControllerName($this->_controller);
	   		$this->_request->setActionName($this->_action);
		} else {
			$this->_request->setModuleName($options['module']);
			$this->_request->setControllerName($options['controller']);
	   		$this->_request->setActionName($options['action']);
		}
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
    
    /**
     * Set the redirect target
     * @param array $target
     * @return void
     */
    public function setTarget(array $target) {
   		if (array_key_exists('module', $target) && 
   			array_key_exists('controller', $target) && 
   			array_key_exists('action', $target)) 
   		{
   			$this->_module = $target['module'];
   			$this->_controller = $target['controller'];
   			$this->_action = $target['action'];
   		} else {
   			throw new Exception("Target array has to consist of keys 'module', 'controller' and 'action'");
   		}    		
    }

    /**
     * Returns the target as an array 
     * @return array
     */
    public function getTarget() {
    	return array('module' => $this->_module, 
    				 'controller' => $this->_controller, 
    				 'action' => $this->_action);
    }
}