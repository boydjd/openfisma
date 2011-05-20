<?php
/**
 * ZIDS Email Action 
 * 
 * Implements the action interface. Task: Send Email to admin in case of a possible attack 
 *
 * @package    ZIDS
 * @author     Christian Koncilia
 * @copyright  Copyright (c) 2010 Christian Koncilia. (http://www.web-punk.com)
 * @license    New BSD License (see above)
 * @version    V.0.6 
 */

class ZIDS_Plugin_ActionPlugin_Email extends ZIDS_Plugin_ActionPlugin_Action {

    /**
     * plugin identifier
     *
     * @var string
     */
    protected $_identifier = 'email';	

    /**
     * The email
     *
     * @var Zend_Mail
     */
    protected $_email = null;

    /**
     * Constructor
     * 
     * @param Zend_Mail $email (optional)
     * @return void
     */
    public function __construct(Zend_Mail $email = null) {
    	if (null != $email) {
    		$this->setEmail($email);
    	}
    }
        
    /**
     * Required by ZIDS_Plugin_ActionPlugin_Interface
     * 
     * @return void
     */
    public function fires(IDS_Report $ids_result, $impact, $levelname) {
    	// get options for the email plugin
		$options = $this->getOptions($levelname);

		// send the email
    	$this->sendMail($this->assembleEmailText($impact, $ids_result, $levelname, $options), $options);
    }

    /**
     * Sends an email notification to the admin in case of a potential attack
     * @param string $notification the emails text
     * @param array $options email options (usually defined in application.ini)
     * @return void
     */
    private function sendMail($notification, $options) {
    	// if email has not been set using the constructor, 
    	// try to fetch parameters defined in options
    	if (null == $this->_email) {
			$config = array(
					'ssl' => $options['smtp']['ssl'], 
					'port' => $options['smtp']['port'],
					'auth' => $options['smtp']['auth'],
    	            'username' => $options['smtp']['username'],
	                'password' => $options['smtp']['password']);
			$transport = new Zend_Mail_Transport_Smtp(
							$options['smtp']['host'],
							$config);
			$mail = new Zend_Mail('UTF-8');

			// define sender and recipient		
			$mail->setFrom($options['from'], 'ZIDS Notification');
			$mail->addTo($options['to']);
    	} else {
    		$mail = $this->_email;
    	}

    	// email text & subject
		$mail->setBodyHtml( $notification );
		$mail->setBodyText( strip_tags($notification) );
		$mail->setSubject( (isset($options['subject']) ? $options['subject'] : 'ZIDS Notification: potential attack on your website') );
    	
		$mail->send( (isset($transport)?$transport:null) );
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
     * Register a Zend_Mail instance
     * @param Zend_Mail $email
     * @return void
     */
    public function setEmail(Zend_Mail $email) {
    	$this->_email = $email;
    }
    
    /**
     * Returns the registered Zend_Email instance 
     * @return Zend_Email 
     */
    public function getEmail() {
    	return $this->_email;
    }

    /**
     * Assembles the HTML notification string for the email plugin
     * @param int $impact Impact of the potential attack
     * @param IDS_Report $result the result of PHPIDSs check
     * @param string $level the level of the potential attack
     * @param array $options options usually defined in application.ini
     * @return string the assembled notification
     */
    private function assembleEmailText($impact, IDS_Report $result, $level, $options) {
    	$retstr = "ZIDS detected a potential attack! ZIDS LEVEL: " . $level . "<br><br>";

    	// parse email items parameters
		$items = explode(',', (isset($options['items']) ? $options['items'] : 'ip, impact, tags, variables'));
		array_walk($items, create_function('&$arr','$arr=trim($arr);'));
    	
    	foreach ($items as $item) {
 		   	switch ($item) {
    			case "ip":
        			$retstr .= " from IP: " . $_SERVER['REMOTE_ADDR'] . '<br>';
        			break;
    			case "impact":
        			$retstr .= " Impact: " . $impact . '<br>';
        			break;
    			case "tags":
        			$retstr .= " Tags: " . implode(',', $result->getTags()) . '<br>';
        			break;
    			case "variables":
        			$retstr .= " Variables: ";
        			foreach ($result->getIterator() as $event) {
        				$retstr .= $event->getName() . " (Tags: " . $event->getTags() . "; Value: " . $event->getValue() . "; Impact: " . $event->getImpact() . ")<br>";
        			}
        			break;
 		   	}
    	}
    	return $retstr;
    }        
}