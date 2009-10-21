<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Fisma
 */

/**
 * Send mail to user for validate email, account notification etc. 
 *
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @package    Fisma
 */
class Fisma_Mail extends Zend_Mail
{
    protected $_contentTpl = null;

    public function __construct()
    {
        $view       = new Zend_View();
        $contentTpl = $view->setScriptPath(Fisma::getPath('application') . '/views/scripts/mail');
        $this->_contentTpl = $contentTpl;
        $this->setFrom(Configuration::getConfig('sender'), Configuration::getConfig('system_name'));
    }

   /**
     * Validate the user's e-mail change.
     *
     * @param object @user User
     * @param string $email the email need to validate
     * @return true|false
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
     * @todo hostUrl can't be get in the CLI script
     *
     * @param array $notifications A group of rows from the notification table
     */
    public function sendNotification($notifications)
    {
        $user = $notifications[0]->User;
        $receiveEmail = empty($user->notifyEmail)
                      ? $user->email
                      : $user->notifyEmail;

        $this->addTo($receiveEmail, $user->nameFirst . $user->nameLast);
        $this->setSubject("Your notifications for " . Configuration::getConfig('system_name'));
        $this->_contentTpl->notifyData = $notifications;
        $this->_contentTpl->user       = $user;
        $content = $this->_contentTpl->render('notification.phtml');
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());    
            print(Fisma::now() . " Email was sent to $receiveEmail\n");
        } catch (Exception $e) {
            print($e->getMessage() . "\n");
            exit();
        }
    }

    /**
     * Send email to a new created user to tell user what the username and password is.
     *
     * @param object $user include the unencrypt password
     * @throw
     */
    public function sendAccountInfo(User $user)
    {
        $systemName = Configuration::getConfig('system_name');
        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("Your new account for $systemName has been created");
        $this->_contentTpl->user = $user;
        $this->_contentTpl->host = Configuration::getConfig('host_url');
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
     * @param object $user include the unencrypt password
     * @return bool
     */
    public function sendPassword(User $user)
    {
        $systemName = Configuration::getConfig('system_name');
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
     * @return Zend_Mail_Transport_Smtp|Zend_Mail_Transport_Sendmail
     */
    private function _getTransport()
    {
        $transport = null;
        if ( 'smtp' == Configuration::getConfig('send_type')) {
            $username = Configuration::getConfig('smtp_username');
            $password = Configuration::getConfig('smtp_password');
            $port     = Configuration::getConfig('smtp_port');
            $tls      = Configuration::getConfig('smtp_tls');
            if (empty($username) && empty($password)) {
                //Un-authenticated SMTP configuration
                $config = array('port' => $port);
            } else {
                $config = array('auth'     => 'login',
                                'port'     => $port,
                                'username' => $username,
                                'password' => $password);
                if ($tls == 1){
                    $config['ssl'] = 'tls';
                }
            }
            $transport = new Zend_Mail_Transport_Smtp(Configuration::getConfig('smtp_host'), $config);
        } else {
            $transport = new Zend_Mail_Transport_Sendmail();
        }
        return $transport;
    }
}
