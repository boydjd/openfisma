<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * A generic exception which represents an unexpected error in the application logic
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Mail
 * @version    $Id$
 */
class Fisma_Mail extends Zend_Mail
{
    /**
     * The email content template
     * 
     * @var string
     */
    protected $_contentTpl = null;

    /**
     * Default constructor
     * 
     * @return void
     */
    public function __construct()
    {
        $view       = new Zend_View();
        $contentTpl = $view->setScriptPath(Fisma::getPath('application') . '/views/scripts/mail');
        $this->_contentTpl = $contentTpl;
        $this->setFrom(Fisma::configuration()->getConfig('sender'), Fisma::configuration()->getConfig('system_name'));
    }

   /**
     * Validate the user's e-mail change.
     *
     * @param User $user The specified user
     * @param string $email The email to be validated
     * @return boolean True if the changed email is valid, false otherwise
     */
    public function validateEmail($user, $email)
    {
        $this->addTo($email);
        $this->setSubject("Confirm Your E-mail Address");

        $this->_contentTpl->host  = Zend_Controller_Front::getInstance()->getRequest()->getHttpHost();
        $this->_contentTpl->validateCode = $user->EmailValidation->getLast()->validationCode;
        $this->_contentTpl->user         = $user;

        $content    = $this->_contentTpl->render('validate.phtml');
        $this->setBodyText($content);
        try {
            $this->send($this->_getTransport());
            return true;
        } catch (Exception $excetpion) {
            return false;
        }
    }

    /**
     * Compose and send the notification email for user.
     * 
     * @param array $notifications A group of rows from the notification table
     * @return void
     * @throws Exception if fails to send
     * @todo hostUrl can't be get in the CLI script
     */
    public function sendNotification($notifications)
    {
        $user = $notifications[0]->User;
        $receiveEmail = empty($user->notifyEmail)
                      ? $user->email
                      : $user->notifyEmail;

        $this->addTo($receiveEmail, $user->nameFirst . $user->nameLast);
        $this->setSubject("Your notifications for " . Fisma::configuration()->getConfig('system_name'));
        $this->_contentTpl->notifyData = $notifications;
        $this->_contentTpl->user       = $user;
        $content = $this->_contentTpl->render('notification.phtml');
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());    
            print(Fisma::now() . " Email was sent to $receiveEmail\n");
        } catch (Exception $e) {
            /** @todo how did this come to be? probably need to remove the catch block */
            print($e->getMessage() . "\n");
            throw $e;
        }
    }

    /**
     * Send email to a new created user to tell user what the username and password is.
     *
     * @param User $user The specified newly created user to send email
     * @return void
     * @throws Exception if fails to send
     * @todo exception handling missed
     */
    public function sendAccountInfo(User $user)
    {
        $systemName = Fisma::configuration()->getConfig('system_name');
        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("Your new account for $systemName has been created");
        $this->_contentTpl->user = $user;
        $this->_contentTpl->host = Fisma::configuration()->getConfig('host_url');
        $content = $this->_contentTpl->render('sendaccountinfo.phtml');
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());
        } catch (Exception $excetpion) {
        }
    }

    /**
     * Send the new password to the user
     * 
     * @param User $user The specified user to send the new password
     * @return void
     * @throws Exception if fails to send
     * @todo exception handling missed
     */
    public function sendPassword(User $user)
    {
        $systemName = Fisma::configuration()->getConfig('system_name');
        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("Your password for $systemName has been changed");
        $this->_contentTpl->user = $user;
        $this->_contentTpl->host = Zend_Controller_Front::getInstance()->getRequest()->getHttpHost();
        $content = $this->_contentTpl->render('sendpassword.phtml');
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());
        } catch (Exception $excetpion) {
        }
    }

    /**
     * Return the appropriate Zend_Mail_Transport subclass,
     * based on the system's configuration.
     * 
     * @return Zend_Mail_Transport_Smtp|Zend_Mail_Transport_Sendmail The initialized email sender
     */
    private function _getTransport()
    {
        $transport = null;
        if ( 'smtp' == Fisma::configuration()->getConfig('send_type')) {
            $username = Fisma::configuration()->getConfig('smtp_username');
            $password = Fisma::configuration()->getConfig('smtp_password');
            $port     = Fisma::configuration()->getConfig('smtp_port');
            $tls      = Fisma::configuration()->getConfig('smtp_tls');
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
            $transport = new Zend_Mail_Transport_Smtp(Fisma::configuration()->getConfig('smtp_host'), $config);
        } else {
            $transport = new Zend_Mail_Transport_Sendmail();
        }
        return $transport;
    }
}
