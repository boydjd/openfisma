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
 */
 
/**
 * A listener for the User model
 *
 * @package   Listener
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 */
class UserListener extends Doctrine_Record_Listener
{
    public function preSave(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        
        $modifyValues = $user->getModified();
        if (empty($modifyValues)) {
            return;
        }
        extract($modifyValues);

        if ($user->id == User::currentUser()->id) {
            if ($email || $notifyEmail) {
                $user->emailValidate = false;
                $emailValidation  = new EmailValidation();
                $emailValidation->email          = !empty($email) ? $email : $notifyEmail;
                $emailValidation->validationCode = md5(rand());
                $emailValidation->User           = $user;
                $user->EmailValidation[]         = $emailValidation;
            }

            if ($password) {
                $user->password        = $user->hash($modifyValues['password']);
                $user->passwordTs      = Zend_Date::now()->toString('Y-m-d H:i:s');
                // Generate user's password history
                $pwdHistory = $user->passwordHistory;
                if (3 == substr_count($pwdHistory, ':')) {
                    $pwdHistory = substr($pwdHistory, 0, -strlen(strrchr($pwdHistory, ':')));
                }
                $user->passwordHistory = ':' . $user->password . $pwdHistory;

                /** @todo english */
                $user->log("Password changed");
            }

        }
    }
    
    public function postInsert(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        Notification::notify(Notification::ACCOUNT_CREATED, $user, User::currentUser());
        Fisma_Lucene::updateIndex('account', $user);
    }

    public function postUpdate(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        Notification::notify(Notification::ACCOUNT_MODIFIED, $user, User::currentUser());
        Fisma_Lucene::updateIndex('account', $user);
    }

    public function postDelete(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        Notification::notify(Notification::ACCOUNT_DELETED, $user, User::currentUser());
        Fisma_Lucene::deleteIndex('account', $user->id);
    }    

}
