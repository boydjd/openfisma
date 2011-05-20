<?php
/*
		-------------------------------------------------------
		!   ZIDS = Zend Framework Intruder Detection System   !
		-------------------------------------------------------

Requirements: Zend Framework (tested with version 1.10)
			  PHP-IDS (tested with version 0.6.4)
						  

						  Copyright (c) 2010
						 by Christian KONCILIA

						http://www.web-punk.com

All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.

    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * ZIDS (Zend Framework Intruder Detection System). Uses PHPIDS to detect intruders on your
 * website developed with Zend Framework. 
 * 
 * See README for a brief documentation and how-to-use
 *
 * @package    ZIDS
 * @author     Christian Koncilia
 * @copyright  Copyright (c) 2010 Christian Koncilia. (http://www.web-punk.com)
 * @license    New BSD License (see above)
 * @version    V.0.6.1
 */
class ZIDS_Plugin_Ids extends Zend_Controller_Plugin_Abstract 
{
    /**
     * Contains all registered plugins
     *
     * @var array
     */
    private $_plugins = array();

    /**
     * Contains all levels and level options
     *
     * @var array
     */
    private $_levels;
    
    /**
     * true, if ZIDS should aggregate all impacts in the session
     *
     * @var boolean
     */    
	private $_aggregate = false;

    /**
     * contains the configurtion (usually specified in application.ini)
     *
     * @var array
     */    
	private $_config;
	
	/**
	 * Constructor
	 *
	 * @param array|Zend_Config $config  
	 * @return void
	 */
	public function __construct(array $config) {
		$this->_config = $config;

		// get all defined levels including their options
		$this->_levels = $config['level'];

		// should ZIDS aggregate all impacts in the session
		$this->_aggregate = $config['aggregate_in_session']; 
	}

	/**
	 * Register ZIDS plugin in the pre-Dispatch phase. 
	 * @param Zend_Controller_Request_Abstract $request
	 */
	public function routeShutdown(Zend_Controller_Request_Abstract $request)
	{
		// should ZIDS ignore this request?
   		if (isset($this->_config['ignore'])) {
			foreach ($this->_config['ignore']['requests']['module'] as $i => $module) {
				// if module, controller and action have been specified, all three parameters have to match
				if (isset($this->_config['ignore']['requests']['controller'][$i]) && 
				 	isset($this->_config['ignore']['requests']['action'][$i])) {
					if ($request->getModuleName() == $module && 
						$request->getControllerName() == $this->_config['ignore']['requests']['controller'][$i] &&
						$request->getActionName() == $this->_config['ignore']['requests']['action'][$i])
							return $request;
				// if only module and controller have been specified, both parameters have to match (action is being ignored)
	   		 	} else if (isset($this->_config['ignore']['requests']['controller'][$i])) {
					if ($request->getModuleName() == $module && 
						$request->getControllerName() == $this->_config['ignore']['requests']['controller'][$i])
							return $request;
				// if only module has been specified, module has to match (controller & action are being ignored)
	   		 	} else if ($request->getModuleName() == $module) {
				 	return $request;
				}
			}
		}
   	
		// init and start PHP IDS
		require_once 'IDS/Init.php';
		$input = array ('REQUEST' => $_REQUEST, 
						'GET' => $_GET, 
						'POST' => $_POST, 
						'COOKIE' => $_COOKIE );
		$init = IDS_Init::init ( $this->_config['phpids']['config'] );
		
		// set PHPIDS options
		if (isset($this->_config['phpids']['general']['base_path'])) {
			$init->config['General']['base_path'] = $this->_config['phpids']['general']['base_path'];
		}
		if (isset($this->_config['phpids']['general']['use_base_path'])) {
			$init->config['General']['use_base_path'] = $this->_config['phpids']['general']['use_base_path'];
		}
		if (isset($this->_config['phpids']['general']['tmp_path'])) {
			$init->config['General']['tmp_path'] = $this->_config['phpids']['general']['tmp_path'];
		}
		if (isset($this->_config['phpids']['general']['filter_path'])) {
			$init->config['General']['filter_path'] = $this->_config['phpids']['general']['filter_path'];
    	}
		if (isset($this->_config['phpids']['logging']['path'])) {
			$init->config['Logging']['path'] = $this->_config['phpids']['logging']['path'];
		}
		if (isset($this->_config['phpids']['caching']['path'])) {
			$init->config['Caching']['path'] = $this->_config['phpids']['caching']['path'];
		}
		
		// html preparation
    	if (isset($this->_config['phpids']['general']['html'])) {
    		if (is_array($this->_config['phpids']['general']['html'])) {
    			foreach ($this->_config['phpids']['general']['html'] AS $html) {
    				$init->config['General']['html'][] = $html;
    			}
    		} else {
				$init->config['General']['html'][] = $this->_config['phpids']['general']['html'];
    		}
		}
		// json options
    	if (isset($this->_config['phpids']['general']['json'])) {
    		if (is_array($this->_config['phpids']['general']['json'])) {
    			foreach ($this->_config['phpids']['general']['json'] AS $json) {
    				$init->config['General']['json'][] = $json;
    			}
    		} else {
				$init->config['General']['json'][] = $this->_config['phpids']['general']['json'];
    		}
		}
		// exceptions (POST,GET,COOKIE)
    	if (isset($this->_config['phpids']['general']['exceptions'])) {
    		if (is_array($this->_config['phpids']['general']['exceptions'])) {
    			foreach ($this->_config['phpids']['general']['exceptions'] AS $exceptions) {
    				$init->config['General']['exceptions'][] = $exceptions;
    			}
    		} else {
				$init->config['General']['exceptions'][] = $this->_config['phpids']['general']['exceptions'];
    		}
		}
		
		$ids = new IDS_Monitor ( $input, $init );
		$result = $ids->run ();

		// deal with the result of PHP IDS
		if (! $result->isEmpty ()) {
			// get PHP-IDS impact
			$impact = $result->getImpact();
			
			// check, if ZIDS should aggregate all impacts in the session			
			if ($this->_aggregate) {
				$session = new Zend_Session_Namespace('ZIDS');
				$impact += $session->impact;
				$session->impact = $impact;
			}

			// find corresponding ZIDS level of attack
			foreach ($this->_levels as $lvlname => $currlevel) {
				if (!in_array(strtolower($lvlname), array('*','all')))
					if (isset($currlevel['upto'])) { 
						if ($impact <= $currlevel['upto']) {
							$level = $lvlname;
							break;
						}
					} else {
						$level = $lvlname;
						break;
					}
			}
			if(!isset($level))
				throw new Exception('ZIDS could not find a corresponding level for impact value ' . $impact . '! Please, check your ZIDS configuration in application.ini!');
						
			// which actions should ZIDS perform?
			$actions = $this->_levels[$level]['action'];
			// make sure to trim each action, e.g. ' email' => 'email'
			array_walk($actions, create_function('&$arr','$arr=trim($arr);')); 
			
			// do we have to ignore this (potential) attack?
			if (!in_array('ignore', $actions)) {

				// fire all defined actions
				foreach ($actions as $action) {
					$plugin = $this->getPlugin($action);
					if (!$plugin) {
						throw new Exception('ZIDS cannot find a plugin with name ' . $action);
					}
					$plugin->injectRequest($request)
						   ->fires($result, $impact, $level);
				}
			}			
		}
		return $request;
	}
    
    /**
     * Register a new action plugin
     *
     * @param ZIDS_Plugin_ActionPlugin_Interface $plugin The plugin to register
     * @return ZIDS_Plugin_Ids
     */
    public function registerPlugin(ZIDS_Plugin_ActionPlugin_Interface $plugin)
    {
    	$plugin->setConfig($this->_config['level']);    	
        $this->_plugins[$plugin->getIdentifier()] = $plugin;
        return $this;
    }

    /**
     * Unregister an action plugin
     *
     * @param string $identifier identifier of the plugin to unregister
     * @return ZIDS_Plugin_Ids
     */
    public function unregisterPlugin($identifier)
    {
        $id = strtolower($identifier);
        if (isset($this->_plugins[$id])) {
            unset($this->_plugins[$id]);
        }
        return $this;
    }
    
    /**
     * Return a registered action plugin
     *
     * @param string $identifier identifier of the action plugin
     * @return ZIDS_Plugin_ActionPlugin_Interface or false if plugin not found
     */
    public function getPlugin($identifier)
    {
        $id = strtolower($identifier);
        if (isset($this->_plugins[$id])) {
            return $this->_plugins[$id];
        }
        return false;
    }
}
