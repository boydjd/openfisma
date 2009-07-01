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
 * @author    Xhorse 
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */



/**
 * @see Zend_Auth_Adapter_Interface
 */
require_once 'Zend/Auth/Adapter/Interface.php';

/**
 * @see Zend_Db_Adapter_Abstract
 */
require_once 'Zend/Db/Adapter/Abstract.php';

/**
 * @see Zend_Auth_Result
 */
require_once 'Zend/Auth/Result.php';


/**
 * Adapte the authentication to Doctrine model 
 * 
 * @category   Fisma
 * @package    Fisma_Auth
 * @copyright  Copyright (c) 2005-2008
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class Fisma_Auth_Adapter_Doctrine implements Zend_Auth_Adapter_Interface
{

    /**
     * The model to be authenticated
     *
     * @var Doctrine_Record
     */
    protected $_identity = null;

    /**
     * $_credential - Credential values
     *
     * @var string
     */

    /**
     * Sets configuration options
     *
     * @param  Doctrine_Record          $identity
     * @param  string                   $credential
     */
    public function __construct(Doctrine_Record $identity, $credential = null) 
    {
        $this->_identity = $identity;
        $this->setCredential($credential);
    }


    /**
     *  set the credential value to be used, 
     *
     * @param  string $credential
     * @return Fisma_Auth_Adapter_Doctrine Provides a fluent interface
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }

    /**
     * defined by Zend_Auth_Adapter_Interface.  This method is called to 
     * attempt an authenication.  Previous to this call, this adapter would have already
     * been configured with all nessissary information to successfully connect to a database
     * table and attempt to find a record matching the provided identity.
     *
     * @throws Zend_Auth_Adapter_Exception if answering the authentication query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        $this->_authenticateSetup();
        $this->_authenticatePolicyCheck();
        if ($this->_identity->login($this->_credential)) {
            /** @todo english */
            $authResult = new Zend_Auth_Result(
                Zend_Auth_Result::SUCCESS, 
                $this->_identity->username,
                array('Authentication successful.')
            );
        } else {
            $authResult = new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE,
                $this->_identity->username,
                array('Supplied credential is invalid.')
            );
        }
        return $authResult;
    }

    /**
     * This method abstracts the steps involved with making sure
     * that this adapter was indeed setup properly with all required peices of information.
     *
     * @throws Zend_Auth_Adapter_Exception - in the event that setup was not done properly
     * @return true
     */
    protected function _authenticateSetup()
    {
        $exception = null;

        if (!$this->_identity instanceof Doctrine_Record) {
            $exception = 'An identity/model must be supplied for '
                    . 'the Fisma_Auth_Adapter_Doctrine authentication adapter.';
        } elseif (is_null($this->_credential) ) {
            $exception = 'A credential value was not provided prior to '
                    . 'authentication with Fisma_Auth_Adapter_Doctrine.';
        }

        if (null !== $exception) {
            throw new Zend_Auth_Adapter_Exception($exception);
        }
        
        $this->_authenticateResultInfo = array(
            'code'     => Zend_Auth_Result::FAILURE,
            'identity' => $this->_identity,
            'messages' => array()
            );
            
        return true;
    }

    /**
     * Check all the login policies 
     * 
     * it throws if any violation is identified.
     */
    protected function _authenticatePolicyCheck()
    {
        $user = $this->_identity;
        // If the account is locked, then check to see
        // what the reason for the lock was and whether it can be unlocked automatically.
        $lockMessage = '';
        $contactEmail = Configuration::getConfig('contact_email');
        $inactivePeriod = new Zend_Date();
        $inactivePeriod->subDay(Configuration::getConfig('account_inactivity_period'));
        $lastLogin = new Zend_Date($user->lastLoginTs, Zend_Date::ISO_8601);

        if (!is_null($user->lastLoginTs)
                && $inactivePeriod->isLater($lastLogin)) {
            $user->lockAccount(User::LOCK_TYPE_INACTIVE);
        } 

        // Check password expiration (for database authentication only)
        $passExpirePeriod = Configuration::getConfig('pass_expire');
        $passExpireTs = new Zend_Date($user->passwordTs, 'Y-m-d');
        $passExpireTs->add($passExpirePeriod, Zend_Date::DAY);
        if ($passExpireTs->isEarlier(new Zend_Date())) {
            $user->lockAccount(User::LOCK_TYPE_EXPIRED);
        }

        if ($user->locked) {
            switch ($user->lockType) {
            case User::LOCK_TYPE_MANUAL:
                $lockMessage = 'Your account has been locked by an Administrator. '
                         . 'Please contact the '
                         . "<a href=\"mailto:$contactEmail\">Administrator</a>.";
                break;
            case User::LOCK_TYPE_PASSWORD:
            // If this system is configured to let accounts unlock automatically,
            // then check whether it can be unlocked now
                if (Configuration::getConfig('unlock_enabled') == 1) {
                    $unlockTs = new Zend_Date($user->lockTs);
                    $unlockTs->add(Configuration::getConfig('unlock_duration'), 
                                    Zend_Date::SECOND);
                    $now = new Zend_Date();
                    if ($now->isEarlier($unlockTs)) {
                        $unlockTs->sub($now);
                        $lockMessage = 'Your user account has been locked due to '
                                     . Configuration::getConfig('failure_threshold')
                                     . ' or more unsuccessful login attempts. Your '
                                     . 'account will be unlocked in '
                                     . ceil($unlockTs->getTimestamp()/60)
                                     . ' minutes. Please try again at that time.<br>'
                                     . ' You may also contact the Administrator for '
                                     . 'further assistance.';
                    } else {
                        $user->unlockAccount();
                    }
                } else {
                    $lockMessage = 'Your user account has been locked due to '
                        . Configuration::getConfig('failure_threshold')
                        . ' or more unsuccessful login attempts. Please '
                        . "contact the <a href=\"mailto:$contactEmail\">Administrator</a>.";
                }
                break;
            case User::LOCK_TYPE_INACTIVE:
                $lockMessage = 'Your account has been locked automatically '
                     .' because you have not logged in over '
                     . Configuration::getConfig('account_inactivity_period') . ' days.';
                break;
            case User::LOCK_TYPE_EXPIRED:
                $lockMessage = 'Your account has been locked automatically '
                     . 'because you have not changed your password in over '
                     . Configuration::getConfig('pass_expire') . ' days.';
                break;
            default:
                $lockMessage = 'Your user account has been locked due to '
                             . Configuration::getConfig('failure_threshold')
                             . ' or more unsuccessful login attempts. Please'
                             . ' contact the <a href="mailto:$contactEmail">Administrator</a>.';
            }
        }
        if (!empty($lockMessage)) {
            throw new Zend_Auth_Adapter_Exception($lockMessage);
        }
    }
}

