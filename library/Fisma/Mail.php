<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 *  This is generic encapsulation of mail in OpenFISMA.
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Mail
 */
class Fisma_Mail
{
    /**
     * Array for Fisma_Mail
     * 
     * @var array
     */
    private $_mail = array();

    /**
     * Constructor
     * 
     * @param array $data
     * @param string $template
     * @param array $options 
     * @return void
     */
    public function __construct($data, $template, $options = array())
    {
        if (!empty($data['recipient'])) {
            $this->_mail['recipient'] = $data['recipient'];
        } else {
            throw new Fisma_Zend_Exception_User('the recipient address cannot be empty');
        }

        $this->_mail['recipientName'] = $data['recipientName'];
        $this->_mail['subject']       = $data['subject'];

        if (!empty($data['sender'])) {
            $this->_mail['sender'] = $data['sender'];
        } else {
            $this->_mail['sender'] = Fisma::configuration()->getConfig('sender');
        }

        if (!empty($data['senderName'])) {
            $this->_mail['senderName'] = $data['senderName'];
        } else {
            $this->_mail['senderName'] = Fisma::configuration()->getConfig('system_name');
        }

        $view = Zend_Layout::getMvcInstance()->getView();
        $this->_mail['body'] = $view->partial("mail/{$template}.phtml", 'default', $options);
    }

    /**
     * Set mail variable
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->_mail[$key] = $value;
    }

    /**
     * Get mail variable
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_mail[$key])) {
            return $this->_mail[$key];
        }

        return null;
    }
}

