<?php
/**
 * ZIDS Action Interface. 
 * 
 * Implement this interface for your own actions. 
 *
 * @package    ZIDS
 * @author     Christian Koncilia
 * @copyright  Copyright (c) 2010 Christian Koncilia. (http://www.web-punk.com)
 * @license    New BSD License (see above)
 * @version    V.0.6 
 */

abstract class ZIDS_Plugin_ActionPlugin_Action implements ZIDS_Plugin_ActionPlugin_Interface 
{
	/**
	 * The current request
	 * 
	 * @var Zend_Controller_Request_Abstract 
	 */
	protected $_request;

	/**
	 * The configuration
	 * 
	 * @var array
	 */
	protected $_config;
		
	/**
	 * Injects the current request
	 * 
	 * @param Zend_Controller_Request_Abstract $request the current request
	 * @return ZIDS_Plugin_ActionPlugin_Action
	 */
	final function injectRequest(Zend_Controller_Request_Abstract $request) {
		$this->_request = $request;
		return $this;
	}

	/**
	 * Set the configuration array (usually stems from application.ini)
	 * 
	 * @param array $config
	 * @return ZIDS_Plugin_ActionPlugin_Action
	 */
	final function setConfig(array $config) {
		$this->_config = $config;
		return $this;
	}
	
	/**
	 * Get plugin options defined in application.ini for the current level. If both, 
	 * specific and global parameters have been set, the specific parameter set 
	 * will override the global parameter set.
	 *  
	 * @param string $levelname
	 * @return array
	 */
	final function getOptions($levelname) {
		$specific = (isset($this->_config[$levelname]['option'][$this->getIdentifier()]) ? $this->_config[$levelname]['option'][$this->getIdentifier()] : null);
		$global = (isset($this->_config['*']['option'][$this->getIdentifier()]) ? $this->_config['*']['option'][$this->getIdentifier()] : null);

		if (is_array($specific) && is_array($global))
			// specific overrides global parameters 
	  		return array_merge($global, $specific);
  		else if (is_array($specific))
  			return $specific;
  		else if (is_array($global))
  			return $global;
  		else return null;	
	}  
}