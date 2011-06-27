<?php
/** @codingStandardsIgnoreFile
 * 
 *
 * This helper was originally created by Sean P. O. MacCath-Moran and has been 
 * slightly modified for use in OpenFISMA. Mr. MacCath-Moran granted OpenFISMA use
 * of his code on October 24, 2009, via e-mail to Mark E. Haase:
 *
 * "Please do use the Zend Priority Messenger code in whatever project you
 * wish, and please consider this my release to use any of the code on my
 * site free of any restrictions, stated or implied, with the following
 * two reservations:
 *
 * 1. Please attribute the source of any of my code you use,
 *
 * 2. and if you should update the code to make it better, please
 * consider making it available for general use (either through my site,
 * by submitting to Zend, or wherever)."
 *
 * These reservations do not conflict with the GLPv3, and as such, this code should comply with the provisions of GPLv3.
 * 
 * @author     Sean P. O. MacCath-Moran <zendcode@emanaton.com>
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    View_Helper
 */
 class View_Helper_PriorityMessenger extends Zend_View_Helper_Abstract {

    /**
     * Zend_Session storage object
     * 
     * @var Zend_Session_Namespace
     */
    static protected $_session = null;

    /**
     * To execute different operation to the page messages array in the session storage object 
     * based on the specified message and severity
     * 
     * Add a message to the collection of priority messages or retrieve the
     * priority messages. If $messages is left null then all the messages are
     * returned, unless $severity is set (as a string or array), causing just
     * the indicated messages to be returned; in either case, the returned
     * messages are cleared from the session cache. If a message or messages
     * are provided, then store them in the indicated severity; $messages may
     * be an array of string all to be stored in the indicated severity OR it
     * may be an associative array of severity-to-message pairs. In any case,
     * if $severity is not set but message is, then 'info' is the assumed
     * default.
     * 
     * @param string|array|null $message  The specified message
     * @param string|array|null $severity  The specified severity
     * @return array The message array
     */
    public function priorityMessenger($message = null, $severity = null) {
        $session = $this->_getSession();

        if (!isset($session->page_messages)) {
            $this->_resetMessageArray();
        }

        if (is_null($message)) {
            return $this->_resetMessageArray($severity);
        } else {
            $severity = !is_null($severity) ? $severity : 'info';
        }

        if (is_array($severity)) {
            reset($severity);
            $severity = $severity[key($severity)];
        }

        if (!isset($session->page_messages[$severity])) {
            $session->page_messages[$severity] = array();
        }

        if (is_array($message)) {
            foreach ($message as $sev=>$mes) {
                $this->priorityMessenger($mes, $sev);
            }
        } else {
            $cleanMessage = Fisma_String::escapeJsString(Fisma_String::textToHtml($message));
            $session->page_messages[$severity][] = $cleanMessage;
        }
    }

    /**
     * To initialize, obtain or set the message array with the specified severity 
     * 
     * Reset the session object's collection of messages. If severity provided, then return that severity and clear
     * only that severity. If an array of severities are provided, then return an array in the form of 
     * $severity=>$messages.
     * 
     * @param string|array $severity The specified severity to obtain or set
     * @return array The returned messages in array
     */
    private function _resetMessageArray($severity = null) {
        $messages = array();

        if (is_null($severity)) {
            $messages = $this->_getSession()->page_messages;
            $this->_getSession()->page_messages = array();
        } elseif (is_string($severity) && isset($this->_getSession()->page_messages[$severity])) {
            $messages = $this->_getSession()->page_messages[$severity];
            unset($this->_getSession()->page_messages[$severity]);
        } elseif (is_array($severity)) {
            foreach($severity as $sev) {
                $messages[$sev] = $this->_resetMessageArray($sev);
            }
        }

        return $messages;
    }

    /**
     * Return the static session object, initiating it if necessary
     * 
     * @return Zend_Session_Namespace The initialized static session object
     */
    private function _getSession() {
        if (!self::$_session instanceof Zend_Session_Namespace) {
            $className = get_class($this);
            $className = (strpos($className, '_') !== false) ? ltrim(strrchr($className, '_'), '_') : $className;
            self::$_session = new Zend_Session_Namespace($className);
        }

        return self::$_session;
    }
}
