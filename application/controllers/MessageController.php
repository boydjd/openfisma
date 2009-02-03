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
 */

/**
 * Displays warnings or informational messages to the user via DHTML.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo This doesn't appear to be a controller... why is it called MessageController??
 */
class MessageController extends Zend_Controller_Action
{
    const M_NOTICE = 'notice';
    const M_WARNING = 'warning';
    /**
     *  Routine to show messages to UI by ajax
     */
    public function message($msg, $model) {
        assert(in_array($model, array(
            self::M_NOTICE,
            self::M_WARNING
        )));
        $msg = str_replace("\n", '', $msg);
        $msg = addslashes($msg);
        $this->view->msg = $msg;
        $this->view->model = $model;
        $this->_helper->viewRenderer->renderScript('message.phtml');
        // restore the auto rendering
        $this->_helper->viewRenderer->setNoRender(false);
    }

    /**
     * _emailvalidate() - Validate the user's e-mail change.
     *
     * @todo Cleanup this method: comments and formatting
     * @todo This function is named incorrectly
     */
    public function emailvalidate($userId, $email, $type, $accountInfo = null)
    {
        $mail = new Zend_Mail();

        $mail->setFrom(Config_Fisma::readSysConfig('sender'), Config_Fisma::readSysConfig('system_name'));
        $mail->addTo($email);
        $mail->setSubject("Confirm Your E-mail Address");

        $validateCode = md5(rand());
        
        $data = array('user_id'=>$userId, 'email'=>$email,
            'validate_code'=>$validateCode);
        $db = Zend_Registry::get('db');
        $db->insert('validate_emails', $data);

        $contentTpl = $this->view->setScriptPath(Config_Fisma::getPath('application') . '/views/scripts/mail');
        $contentTpl = $this->view;

        if (!empty($accountInfo)) {
            $contentTpl->account = $accountInfo['account'];
            $contentTpl->password = $accountInfo['password'];
        }

        $contentTpl->actionType = $type;
        $contentTpl->validateCode = $validateCode;
        $contentTpl->userId = $userId;
        $contentTpl->hostUrl = Config_Fisma::readSysConfig('hostUrl');
        $content = $contentTpl->render('validate.phtml');
        $mail->setBodyText($content);
        $mail->send($this->_getTransport());
    }

    /**
     * Send the new password to the user whom password has been changed by administrator
     *
     * @param int $userId a special user id
     * @param string $password new password for this user
     */
    public function sendPassword($userId, $password)
    {
        $user = new User();
        $ret = $user->find($userId)->current();

        $sender = Config_Fisma::readSysConfig('sender');
        $systemName = Config_Fisma::readSysConfig('system_name');

        $mail = new Zend_Mail();

        $mail->setFrom($sender, $systemName);
        $mail->addTo($ret->email);
        $mail->setSubject("Your password for $systemName has been changed");

        $contentTpl = $this->view->setScriptPath(Config_Fisma::getPath('application') . '/views/scripts/mail');
        $contentTpl = $this->view;
        $contentTpl->hostUrl = Config_Fisma::readSysConfig('hostUrl');
        $contentTpl->password = $password;
        $content = $contentTpl->render('sendpassword.phtml');
        $mail->setBodyText($content);
        $mail->send($this->_getTransport());
    }



    /**
     * _getTransport() - Return the appropriate Zend_Mail_Transport subclass,
     * based on the system's configuration.
     *
     * @return Zend_Mail_Transport_Smtp|Zend_Mail_Transport_Sendmail
     */
    private function _getTransport()
    {
        $transport = null;
        if ( 'smtp' == Config_Fisma::readSysConfig('send_type')) {
            $config = array('auth' => 'login',
                'username' => Config_Fisma::readSysConfig('smtp_username'),
                'password' => Config_Fisma::readSysConfig('smtp_password'),
                'port' => Config_Fisma::readSysConfig('smtp_port'));
            $transport = new Zend_Mail_Transport_Smtp(
                Config_Fisma::readSysConfig('smtp_host'), $config);
        } else {
            $transport = new Zend_Mail_Transport_Sendmail();
        }
        return $transport;
    }

}
