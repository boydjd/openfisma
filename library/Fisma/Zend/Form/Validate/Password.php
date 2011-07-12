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
 * Given the password validator
 * 
 * @author     Ryan Yang <ryan@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Form
 */
class Fisma_Zend_Form_Validate_Password extends Zend_Validate_Abstract
{
    /**
     * Constant message key 'pass_min'
     */
    const PASS_MIN = "pass_min";
    
    /**
     * Constant message key 'pass_max'
     */
    const PASS_MAX = "pass_max";
    
    /**
     * Constant message key 'pass_uppercase'
     */
    const PASS_UPPERCASE = "pass_uppercase";
    
    /**
     * Constant message key 'pass_lowercase'
     */
    const PASS_LOWERCASE = "pass_lowercase";
    
    /**
     * Constant message key 'pass_numerical'
     */
    const PASS_NUMERICAL = "pass_numerical";
    
    /**
     * Constant message key 'pass_special'
     */
    const PASS_SPECIAL   = "pass_special";
    
    /**
     * Constant message key 'pass_include'
     */
    const PASS_INCLUDE   = "pass_include";
    
    /**
     * Constant message key 'pass_history'
     */
    const PASS_HISTORY   = "pass_history";
    
    /**
     * Constant message key 'pass_notsameold'
     */
    const PASS_NOTSAMEOLD = "pass_notsameold";
    
    /**
     * Constant message key 'pass_notconfirm'
     */
    const PASS_NOTCONFIRM = "pass_notconfirm";
    
    /**
     * Constant message key 'pass_notincorrect'
     */
    const PASS_NOTINCORRECT = "pass_notincorrect";
    
    /** 
     * Check whether the password matchs the specified password complexity criterias
     * 
     * @param string $pass The password to be validate
     * @param array|null $context The specified password complexity criterias
     * @return boolean True if match, false otherwise
     */
    public function isValid($pass, $context=null)
    {
        $this->_messageTemplates = array(
            self::PASS_MIN => 'must be at least ' 
                            . Fisma::configuration()->getConfig('pass_min_length') 
                            . ' characters long',
            self::PASS_UPPERCASE=>'must contain at least 1 uppercase letter (A-Z)',
            self::PASS_LOWERCASE=>'must contain at least 1 lowercase letter (a-z)',
            self::PASS_NUMERICAL=>'must contain at least 1 numeric digit (0-9)',
            self::PASS_SPECIAL  =>'must contain at least 1 special character (!@#$%^&*-=+~`_)',
            self::PASS_INCLUDE  =>'The new password can not include your first name or last name',
            self::PASS_HISTORY  =>'Your password must be different from the last three passwords you have used.'
                                  .' Please pick a different password',
            self::PASS_NOTSAMEOLD =>'must not be the same as your old password.',
            self::PASS_NOTCONFIRM =>'mismatch.'
        );
        
        $errno = 0;
        $this->_setValue($pass);

        if (isset($context['confirmPassword']) && $pass != $context['confirmPassword']) {
            $errno++;
            $this->_error(self::PASS_NOTCONFIRM);
        }
        if (strlen($pass) < Fisma::configuration()->getConfig('pass_min_length')) {
            $errno++;
            $this->_error(self::PASS_MIN);
        }
        if (true == Fisma::configuration()->getConfig('pass_uppercase')) {
            if ( false == preg_match("/[A-Z]+/", $pass)) {
                $errno++;
                $this->_error(self::PASS_UPPERCASE);
            }
        }
        if (true == Fisma::configuration()->getConfig('pass_lowercase')) {
            if ( false == preg_match("/[a-z]+/", $pass) ) {
                $errno++;
                $this->_error(self::PASS_LOWERCASE);
            }
        }
        if ( true == Fisma::configuration()->getConfig('pass_numerical')) {
            if ( false == preg_match("/[0-9]+/", $pass) ) {
                $errno++;
                $this->_error(self::PASS_NUMERICAL);
            }
        }
        if ( true == Fisma::configuration()->getConfig('pass_special')) {
            if ( false == preg_match("/[^0-9a-zA-Z]+/", $pass) ) {
                $errno++;
                $this->_error(self::PASS_SPECIAL);
            }
        }

        $user = CurrentUser::getInstance();
        // password change
        $nameincluded = true;
        // check last name
        if (empty($user->nameLast)
            || strpos($pass, $user->nameLast) === false) {
            $nameincluded = false;
        }
        if (!$nameincluded) {
            // check first name
            if (empty($user->nameFirst)
                || strpos($pass, $user->nameFirst) === false) {
                $nameincluded = false;
            } else {
                $nameincluded = true;
            }
        }
        if ($nameincluded) {
            $errno++;
            $this->_error(self::PASS_INCLUDE);
        }

        if ($errno > 0) {
            return false;
        } else {
            return true;
        }
    }
}
