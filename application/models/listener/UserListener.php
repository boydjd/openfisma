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
 * <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: index.php 1793 2009-06-19 17:49:33Z mehaase $
 * @package   Listener
 */
 
/**
 * A listener for the User model
 *
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 * @package   Listener
 */
class UserListener extends Doctrine_Record_Listener
{
    public function preSave(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        
        $modified = $user->getModified();
        
        if (isset($modified['email']) || isset($modified['notifyEmail'])) {
            $user->emailValidate = false;
            $emailValidation  = new EmailValidation();
            if (!empty($modified['email'])) {
                $emailValidation->email = $modified['email'];
            } elseif (!empty($modified['notifyEmail'])) {
                $emailValidation->email = $modified['notifyEmail'];
            }
            $emailValidation->validationCode = md5(rand());
            $emailValidation->User           = $user;
            $user->EmailValidation[]         = $emailValidation;
        }
        
        if (isset($modified['password'])) {
            $user->generateSalt();
            $user->password        = $user->hash($modified['password']);
            $user->passwordTs      = Fisma::now();

            /** @todo where is the password policy enforcement ? */
            
            // Generate user's password history
            $pwdHistory = $user->passwordHistory;
            if (3 == substr_count($pwdHistory, ':')) {
                $pwdHistory = substr($pwdHistory, 0, -strlen(strrchr($pwdHistory, ':')));
            }
            $user->passwordHistory = ':' . $user->password . $pwdHistory;
            // if the user only changed the password, then we think this is a change pwd event
            if ($user == User::currentUser($user) && count($modified) == 1) {
                $user->log(User::CHANGE_PASSWORD, "Password changed");
            }
        }
        
        if (isset($modified['lastRob'])) {
            $user->log(User::ACCEPT_ROB, "Accepted Rules of Behavior");
        }
    }

    public function preInsert(Doctrine_Event $event) {
        $user = $event->getInvoker();
        
        $user->passwordTs = Fisma::now();
        $user->hashType = Configuration::getConfig('hash_type');
        $user->log(User::CREATE_USER, "create user: $user->nameFirst $user->nameLast");
    }

    /**
     * Send an email to a new created user, tell he/she how to log in the system
     * Create the finding lucene index
     */
    public function postInsert(Doctrine_Event $event) 
    {
        $user     = $event->getInvoker();
        $modified = $user->getModified($old=true, $last=true);
        $user->password = $modified['password'];
        $mail = new Fisma_Mail();
        $mail->sendAccountInfo($user);
    }
    
    /**
     * Send an email to tell user what the new password is
     * Update finding lucene index
     */
    public function postUpdate(Doctrine_Event $event)
    {
        $user     = $event->getInvoker();
        $modified = $user->getModified($old=true, $last=true);
        if (isset($modified['password']) && $modified['password']) {
            $user->password = $modified['password'];
            $mail = new Fisma_Mail();
            $mail->sendPassword($user);
        }
    }

    public function preDelete(Doctrine_Event $event)
    {
        $user    = $event->getInvoker();
        $user->log(User::DELETE_USER, "delete user: $user->nameFirst $user->nameLast");
    }
}
