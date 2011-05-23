<?php
/**
 * ZIDS Log Action 
 * 
 * Implements the action interface. Takes the IDS_Report object and writes result to a Zend_Log instance. 
 *
 * @package    ZIDS
 * @author     Christian Koncilia
 * @copyright  Copyright (c) 2010 Christian Koncilia. (http://www.web-punk.com)
 * @license    New BSD License (see above)
 * @version    V.0.6 
 */

class ZIDS_Plugin_ActionPlugin_Log extends ZIDS_Plugin_ActionPlugin_Action {

    /**
     * plugin identifier
     *
     * @var string
     */
    protected $_identifier = 'log';	

    /**
     * The log
     *
     * @var Zend_Log
     */
    protected $_log = null;

    private $_logitems;
    
    /**
     * Constructor
     * 
     * @param Zend_Log $log (optional)
     * @return void
     */
    public function __construct(Zend_Log $log = null) {
    	if (null != $log) {
    		$this->setLog($log);
    	}
    }
    
    /**
     * Required by ZIDS_Plugin_ActionPlugin_Interface
     * Writes output to Zend_Log instance registered
     * 
     * @return void
     */
    public function fires(IDS_Report $ids_result, $impact, $levelname) {
    	// get options for the log plugin
		$options = $this->getOptions($levelname);		

		// parse log->items parameters
		$this->_logitems = explode(',', $options['items']);
		array_walk($this->_logitems, create_function('&$arr','$arr=trim($arr);'));
		
    	if (null == $this->_log) {
			throw new Exception('ZIDS cannot use the log action unless you register a Zend_Log instance.');
		}
		
		$this->_log->log($this->getNotificationString($impact, $ids_result, $levelname), $options['loglevel']);
    }

    /**
     * Assembles the notification string
     * @param int $impact Impact of the potential attack
     * @param IDS_Report $result the result of PHPIDSs check
     * @param string $level the level of the potential attack
     * @return string the assembled notification
     */
    private function getNotificationString($impact, IDS_Report $result, $level) {
    	$retstr = "ZIDS detected a potential attack! ZIDS LEVEL: " . $level;
    	foreach ($this->_logitems as $item) {
 		   	switch ($item) {
    			case "ip":
        			$retstr .= " from IP: " . $_SERVER['REMOTE_ADDR'];
        			break;
    			case "impact":
        			$retstr .= " Impact: " . $impact;
        			break;
    			case "tags":
        			$retstr .= " Tags: " . implode(',', $result->getTags());
        			break;
    			case "variables":
        			$retstr .= " Variables: ";
        			foreach ($result->getIterator() as $event) {
        				$retstr .= $event->getName() . " (Tags: " . $event->getTags() . "; Value: " . $event->getValue() . "; Impact: " . $event->getImpact() . ") ";
        			}
        			break;
 		   	}
    	}
    	return $retstr;
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
     * Register a Zend_Log instance
     * @param Zend_Log $log
     * @return void
     */
    public function setLog(Zend_Log $log) {
    	$this->_log = $log;
    }
    
    /**
     * Returns the registered Zend_Log instance 
     * @return Zend_Log
     */
    public function getLog() {
    	return $this->_log;
    }
}