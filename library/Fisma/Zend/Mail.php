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
 * @subpackage Fisma_Zend_Mail
 */
class Fisma_Zend_Mail extends Zend_Mail
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
        $this->_charset = 'UTF-8';
        $view = new Fisma_Zend_View();
        $this->_contentTpl = $view->setScriptPath(
            Fisma::getPath('application') . '/modules/default/views/scripts/mail'
        );
        $view->addHelperPath(Fisma::getPath('viewHelper'), 'View_Helper_');
        
        $this->setFrom(Fisma::configuration()->getConfig('sender'), Fisma::configuration()->getConfig('system_name'));
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
        $receiveEmail = $user->email;

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
        $this->_contentTpl->loginLink = Fisma_Url::customUrl("/auth/login");
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
        $this->_contentTpl->host = Fisma_Url::baseUrl();
        $content = $this->_contentTpl->render('sendpassword.phtml');
        $this->setBodyText($content);
        
        try {
            $this->send($this->_getTransport());
        } catch (Exception $excetpion) {
        }
    }
    
    /**
     * Notify users a new incident has been reported
     *
     * @param int $userId id of the user that will receive the email
     * @param int $incidentId id of the incident that the email is referencing 
     * 
     */
    public function IRReport($userId, $incidentId)
    {
        $user = new User();
        $user = $user->getTable()->find($userId);

        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("A new incident has been reported.");
        
        $this->_contentTpl->incidentId = $incidentId;
        
        $content = $this->_contentTpl->render('IRReported.phtml');
        
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());
        } catch (Exception $excetpion) {
        }
    }

    /**
     * Notify users they have been assigned to a new incident
     *
     * @param int $userId id of the user that will receive the email
     * @param int $incidentId id of the incident that the email is referencing 
     * 
     */
    public function IRAssign($userId, $incidentId)
    {
        $user = new User();
        $user = $user->getTable()->find($userId);

        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("You have been assigned to a new incident.");
        
        $this->_contentTpl->incidentId = $incidentId;
        
        $content = $this->_contentTpl->render('IRAssign.phtml');
        
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());
        } catch (Exception $excetpion) {
        }
    }

    /**
     * Notify a user that an incident workflow step has been completed
     *
     * @param int $userId ID of the user that will receive the email
     * @param int $incidentId
     * @param string $workflowStep Description of the completed step
     * @param string $workflowCompletedBy Name of user who completed the step
     */
    public function IRStep($userId, $incidentId, $workflowStep, $workflowCompletedBy)
    {
        $user = new User();
        $user = $user->getTable()->find($userId);

        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("A workflow step has been completed.");
        
        $this->_contentTpl->workflowStep = $workflowStep;
        $this->_contentTpl->workflowCompletedBy = $workflowCompletedBy;
        $this->_contentTpl->incidentId = $incidentId;
        
        $content = $this->_contentTpl->render('IRStep.phtml');
        
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());
        } catch (Exception $excetpion) {
        }
    }

    /**
     * Notify users that a comment has been added to an incident
     *
     * @param int $userId id of the user that will receive the email
     * @param int $incidentId id of the incident that the email is referencing 
     * 
     */
    public function IRComment($userId, $incidentId)
    {
        $user = new User();
        $user = $user->getTable()->find($userId);

        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("A comment has been added to an incident.");
        
        $this->_contentTpl->incidentId = $incidentId;
        
        $content = $this->_contentTpl->render('IRComment.phtml');
        
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());
        } catch (Exception $excetpion) {
        }
    }
    
    /**
     * Notify users that an incident has been resolved
     *
     * @param int $userId id of the user that will receive the email
     * @param int $incidentId id of the incident that the email is referencing 
     * 
     */
    public function IRResolve($userId, $incidentId)
    {
        $user = new User();
        $user = $user->getTable()->find($userId);

        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("An incident has been resolved.");
        
        $this->_contentTpl->incidentId = $incidentId;
        
        $content = $this->_contentTpl->render('IRResolve.phtml');
        
        $this->setBodyText($content);

        try {
            $this->send($this->_getTransport());
        } catch (Exception $excetpion) {
        }
    }
    
    /**
     * Notify users that an incident has been closed
     *
     * @param int $userId id of the user that will receive the email
     * @param int $incidentId id of the incident that the email is referencing 
     * 
     */
    public function IRClose($userId, $incidentId)
    {
        $user = new User();
        $user = $user->getTable()->find($userId);

        $this->addTo($user->email, $user->nameFirst . ' ' . $user->nameLast);
        $this->setSubject("An incident has been closed.");
        
        $this->_contentTpl->incidentId = $incidentId;
        
        $content = $this->_contentTpl->render('IRClose.phtml');
        
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
