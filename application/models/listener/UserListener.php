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
        
        Doctrine_Manager::connection()->beginTransaction();

        $modifyValues = $user->getModified();
        if (array_key_exists('email', $modifyValues)) {
            $user->emailValidate = false;
            $emailValidation     = new EmailValidation();
            $emailValidation->email          = $modifyValues['email'];
            $emailValidation->validationCode = md5(rand());
            $emailValidation->User           = $user;
            $user->EmailValidation[]         = $emailValidation;
        }

        if (array_key_exists('password', $modifyValues)) {
            $user->password        = $user->hash($modifyValues['password']);
            $user->passwordTs      = Zend_Date::now()->toString('Y-m-d H:i:s');
            $user->passwordHistory = $user->_generatePwdHistory();

            if ($user->id == $user->currentUser()->id) {
                /** @todo english  also see follow */
                $user->log("Password changed");
            }
        } else {
            if ($user->id == $user->currentUser()->id && !empty($modifyValues)) {
                $user->log("Profile changed");
            }
        }
    }
    
    public function preDelete(Doctrine_Event $event)
    {
         Doctrine_Manager::connection()->beginTransaction();       
    }

    public function postInsert(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        
        Notification::notify(Notification::ACCOUNT_CREATED, $user, User::currentUser());
        Doctrine_Manager::connection()->commit();

        Fisma_Lucene::updateIndex('account', $user);
    }

    public function postUpdate(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        
        Notification::notify(Notification::ACCOUNT_MODIFIED, $user, User::currentUser());
        Doctrine_Manager::connection()->commit();

        Fisma_Lucene::updateIndex('account', $user);
    }

    public function postDelete(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        
        Notification::notify(Notification::ACCOUNT_DELETED, $user, User::currentUser());
        Doctrine_Manager::connection()->commit();

        Fisma_Lucene::deleteIndex('account', $user->id);
    }    
}
