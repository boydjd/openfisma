<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Fisma_Zend_Controller_Plugin_CsrfProtect
 *
 * A controller plugin for protecting forms from CSRF
 *
 * Works by looking at the response and adding a hidden element to every
 * form, which contains an automatically generated key that is checked
 * on the next request against a key stored in the session
 *
 * @uses Zend_Controller_Plugin_Abstract
 * @package Fisma_Zend_Controller_Plugin
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Jani Hartikainen <firstname at codeutopia net>
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Controller_Plugin_CsrfProtect extends Zend_Controller_Plugin_Abstract
{
    /**
     * Session storage
     * @var Zend_Session_Namespace
     */
    protected $_session = null;

    /**
     * The name of the form element which contains the key
     * @var string
     */
    protected $_keyName = 'csrf';

    /**
     * The session's token, set by _initializeToken
     * @var string
     */
    protected $_token = '';

    /**
     * __construct
     *
     * @param array $params
     */
    public function __construct(array $params = array())
    {
        if (isset($params['keyName'])) {
            $this->setKeyName($params['keyName']);
        }

        $this->_session = new Zend_Session_Namespace('CsrfProtect');

        $this->_initializeTokens();
    }

    /**
     * Set the name of the csrf form element
     * @param string $name
     * @return CU_Controller_Plugin_CsrfProtect implements fluent interface
     */
    public function setKeyName($name)
    {
        $this->_keyName = $name;
        return $this;
    }

    /**
     * Performs CSRF protection checks before dispatching occurs
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($request->isPost() && $this->getRequest()->getControllerName() != "error") {
            if (empty($this->_token))
                throw new Fisma_Zend_Exception_User('A possible CSRF attack detected: no token received.');

            $value = $request->getPost($this->_keyName);
            if (!$this->isValidToken($value))
                throw new Fisma_Zend_Exception_User('A possible CSRF attack detected: tokens do not match.');
        }
    }

    /**
     * Check if a token is valid for the previous request
     * @param string $value
     * @return bool
     */
    public function isValidToken($value)
    {
        if ($value != $this->_token)
            return false;

        return true;
    }

    /**
     * Return the CSRF token for this request
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * Adds protection to forms
     */
    public function dispatchLoopShutdown()
    {
        $token = $this->getToken();

        $response = $this->getResponse();

        $headers = $response->getHeaders();

        foreach ($headers as $header) {
            //Do not proceed if content-type is not html/xhtml or such
            if($header['name'] == 'Content-Type' && strpos($header['value'], 'html') === false)
                return;
        }

        $element = sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            $this->_keyName,
            $token
        );

        $body = $response->getBody();

        //Find all forms and add the csrf protection element to them
        $body = preg_replace('/<form[^>]*>/i', '$0' . $element, $body);

        $response->setBody($body);
    }

    /**
     * Initializes a new token if a token isn't already set in the session
     */
    protected function _initializeTokens()
    {
        if (!isset($this->_session->key)) {
            $newKey = sha1(microtime() . mt_rand());
            $this->_session->key = $newKey;
            $this->_token = $newKey;
        } else {
            $this->_token = $this->_session->key;
        }
    }
}
