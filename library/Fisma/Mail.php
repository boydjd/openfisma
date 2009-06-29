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
 * @author    Ryan yang <ryan.yang@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

/**
 * Send mail to user for validate email, account notification etc. 
 *
 * @package    Fisma
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
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
        $this->setSubject(Configuration::getConfig('subject'));
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
        /** @todo english */
        $this->setSubject("Confirm Your E-mail Address");

        $contentTpl->host  = Zend_Controller_Front::getInstance()->getRequest()->getHttpHost();
        $this->_contentTpl->account      = $user->nameLast . ' ' . $user->nameFirst;
        $this->_contentTpl->validateCode = $user->EmailValidation->getLast()->validationCode;
        $this->_contentTpl->userId       = $user->id;

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
        $user   = new User();
        $userId = $notifications[0]->userId;
        $user   = $user->getTable()->find($userId);
        $receiveEmail = $user->notifyEmail ? $user->notifyEmail : $user->email;

        $this->addTo($receiveEmail, $user->nameFirst . $user->nameLast);
        $this->_contentTpl->notifyData = $notifications;
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
     * Return the appropriate Zend_Mail_Transport subclass,
     * based on the system's configuration.
     *
     * @return Zend_Mail_Transport_Smtp|Zend_Mail_Transport_Sendmail
     */
    private function _getTransport()
    {
        $transport = null;
        if ( 'smtp' == Configuration::getConfig('send_type')) {
            $config = array('auth' => 'login',
                'username' => Configuration::getConfig('smtp_username'),
                'password' => Configuration::getConfig('smtp_password'),
                'port' => Configuration::getConfig('smtp_port'));
            $transport = new Zend_Mail_Transport_Smtp(
                Configuration::getConfig('smtp_host'), $config);
        } else {
            $transport = new Zend_Mail_Transport_Sendmail();
        }
        return $transport;
    }


}
