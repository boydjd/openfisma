<?php
/**
 * Copyright (c) 2009 Endeavor Systems, Inc.
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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 * @version   $Id$
 * @package   Model
 */

/**
 * System configuration items, such as authentication policy, account management policy, etc.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 */
class Configuration extends BaseConfiguration
{
    const SYSTEM_NAME   = 'system_name';
    const MAX_ABSENT    = 'account_inactivity_period';
    const AUTH_TYPE     = 'auth_type';
    const F_THRESHOLD   = 'failure_threshold';
    const EXPIRING_TS   = 'session_inactivity_period';
    const UNLOCK_ENABLED = 'unlock_enabled';
    const UNLOCK_DURATION = 'unlock_duration';

    const CONTACT_NAME  = 'contact_name';
    const CONTACT_PHONE = 'contact_phone';
    const CONTACT_EMAIL = 'contact_email';
    const CONTACT_SUBJECT = 'contact_subject';

    const USE_NOTIFICATION = 'use_notification';
    const BEHAVIOR_RULE    = 'behavior_rule';
    const ROB_DURATION     = 'rob_duration';
    const PRIVACY_POLICY   = 'privacy_policy';
    
    const SENDER    = 'sender';
    const SUBJECT     = 'subject';
    const SMTP_HOST   = 'smtp_host';
    const SMTP_USERNAME   = 'smtp_username';
    const SMTP_PASSWORD   = 'smtp_password';

    const PASS_EXPIRE      = 'pass_expire';
    const PASS_WARNINGDAYS = 'pass_warning';
    const PASS_UPPERCASE  = 'pass_uppercase';
    const PASS_LOWERCASE  = 'pass_lowercase';
    const PASS_NUMERICAL  = 'pass_numerical';
    const PASS_SPECIAL    = 'pass_special';
    const PASS_MIN        = 'pass_min';
    const PASS_MAX        = 'pass_max';
    
    /**
     * Get a configuration item from the configuration table. This static function is merely a convenience
     * function to make this common task easier to perform.
     * 
     * @param string $name
     * @return string|int $value
     */
    public static function getConfig($name) {
        $config = Doctrine::getTable('Configuration')->findOneByName($name);
        if (!empty($config)) {
            return $config->value;
        }
        if (Zend_Registry::isRegistered($name)) {
            return Zend_Registry::get($name);
        }
        throw new Fisma_Exception_Config("Invalid configuration name: $name");
    }
    
    /**
     * Set a configuration item in the configuration table. This static function is merely a convenience
     * function to make this common task easier to perform.
     * 
     * @param string $name
     * @param string|int $value
     */
    public static function setConfig($name, $value) {
        Fisma_Acl::requirePrivilege('area', 'configuration');
        
        $config = Doctrine::getTable('Configuration')->findOneByName($name);
        $config->value = $value;
        $config->save();
    }
}
