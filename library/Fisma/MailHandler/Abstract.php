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
 * A base class for implementing mail handler object
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_MailHandler
 */
abstract class Fisma_MailHandler_Abstract
{
    /**
     * A Fisma_Mail instance
     * 
     * @var Fisma_Mail
     */
    private $_mail;

    /**
     * Set mail object.
     *
     * @param Fisma_Mail $mail
     * @return this
     */
    public function setMail(Fisma_Mail $mail)
    {
        $this->_mail = $mail;

        return $this;
    }

    /**
     * Return the mail object
     */
    public function getMail()
    {
        return $this->_mail;
    }

    /**
     * Send mail store to mail table or send email immediately
     */
    public function send()
    {
        $this->_send();
    }

    /**
     * Subclasses must implement this method to do their work
     * 
     * @return void
     */
    abstract protected function _send();

    /**
     * Return the appropriate Zend_Mail_Transport subclass, based on the system's configuration
     * 
     * @param  Zend_Mail_Transport_Abstract $transport
     * @return Zend_Mail_Transport_Smtp|Zend_Mail_Transport_Sendmail The initialized email sender
     */
    public function getTransport(Zend_Mail_Transport_Abstract $transport = null)
    {
        if ($transport) {
            return $transport;
        } else {
            if ('smtp' == Fisma::configuration()->getConfig('send_type')) {
                $username = Fisma::configuration()->getConfig('smtp_username');
                $password = Fisma::configuration()->getConfig('smtp_password');
                $port     = Fisma::configuration()->getConfig('smtp_port');
                $tls      = Fisma::configuration()->getConfig('smtp_tls');
                $host     = Fisma::configuration()->getConfig('smtp_host');
                if (empty($username) && empty($password)) {
                    //Un-authenticated SMTP configuration
                    $config = array('port' => $port);
                } else {
                    $config = array('auth'     => 'login',
                                    'port'     => $port,
                                    'username' => $username,
                                    'password' => $password);
                    if ($tls) {
                        $config['ssl'] = 'tls';
                    }
                }
                $transport = new Zend_Mail_Transport_Smtp($host, $config);
            } else {
                $transport = new Zend_Mail_Transport_Sendmail();
            }
        }

        return $transport;
    }
}

