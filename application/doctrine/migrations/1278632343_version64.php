<?php
// @codingStandardsIgnoreFile
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
 * Rebuilds Configuration schema 
 * 
 * @uses Doctrine_Migration_Base
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version64 extends Doctrine_Migration_Base
{
    /**
     * Remove Configuration colums name, value, description
     * Add in all new columns, with existing data migrated
     * 
     * @access public
     * @return void
     */
    public function up()
    {
        // Wipe out Configuration table
        $q = Doctrine_Query::create()
            ->delete('Configuration c')
            ->execute();

		$this->removeColumn('configuration', 'name');
		$this->removeColumn('configuration', 'value');
		$this->removeColumn('configuration', 'description');
		$this->addColumn('configuration', 'account_inactivity_period', 'integer', '1', array(
             'notblank' => '1',
             'unsigned' => '1',
             'default' => '0',
             'comment' => 'Maximum days an account can be inactive before automatic lock',
             ));
		$this->addColumn('configuration', 'failure_threshold', 'integer', '1', array(
             'notblank' => '1',
             'unsigned' => '1',
             'default' => '0',
             'comment' => 'Maximum login attemptes before server locks account',
             ));
		$this->addColumn('configuration', 'session_inactivity_period', 'integer', '2', array(
             'notblank' => '1',
             'unsigned' => '1',
             'default' => '0',
             'comment' => 'Session timeout (seconds)',
             ));
		$this->addColumn('configuration', 'auth_type', 'enum', '', array(
             'notblank' => '1',
             'values' => 
             array(
              0 => 'database',
              1 => 'ldap',
             ),
             'default' => 'database',
             'comment' => 'Authentication type',
             ));
		$this->addColumn('configuration', 'sender', 'string', '255', array(
             'comment' => 'Send email address',
             'default' => '',
             ));
		$this->addColumn('configuration', 'subject', 'string', '255', array(
             'comment' => 'Email subject',
             'default' => '',
             ));
		$this->addColumn('configuration', 'smtp_host', 'string', '255', array(
             'comment' => 'SMTP server name',
             'default' => '',
             ));
		$this->addColumn('configuration', 'smtp_username', 'string', '255', array(
             'comment' => 'Username for SMTP authentication',
             'default' => '',
             ));
		$this->addColumn('configuration', 'smtp_password', 'string', '255', array(
             'comment' => 'Password for SMTP authentication',
             'default' => '',
             ));
		$this->addColumn('configuration', 'send_type', 'enum', '', array(
             'notblank' => '1',
             'values' => 
             array(
              0 => 'sendmail',
              1 => 'smtp',
             ),
             'comment' => 'Notification email send type',
             'default' => 'sendmail',
             ));
		$this->addColumn('configuration', 'smtp_port', 'integer', '2', array(
             'notblank' => '1',
             'unsigned' => '1',
             'comment' => 'SMTP server port',
             'default' => '25',
             ));
		$this->addColumn('configuration', 'smtp_tls', 'boolean', '25', array(
             'notblank' => '1',
             'comment' => 'Use Transport Layer Security (TLS)',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'unlock_enabled', 'boolean', '25', array(
             'notblank' => '1',
             'comment' => 'Enable automated account unlock',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'unlock_duration', 'integer', '2', array(
             'notblank' => '1',
             'unsigned' => '1',
             'comment' => 'Automated account unlock duration (in minutes)',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'contact_name', 'string', '255', array(
             'notblank' => '1',
             'comment' => 'Technical support contact name',
             'default' => '',
             ));
		$this->addColumn('configuration', 'contact_phone', 'string', '255', array(
             'notblank' => '1',
             'comment' => 'Technical support contact phone number',
             'default' => '',
             ));
		$this->addColumn('configuration', 'contact_email', 'string', '255', array(
             'notblank' => '1',
             'email' => 
             array(
              'check_mx' => '',
             ),
             'comment' => 'Technical support contact email address',
             'default' => '',
             ));
		$this->addColumn('configuration', 'contact_subject', 'string', '255', array(
             'notblank' => '1',
             'comment' => 'Technical support email subject text',
             'default' => '',
             ));
		$this->addColumn('configuration', 'use_notification', 'string', '65535', array(
             'notblank' => '1',
             'extra' => 
             array(
              'purify' => 'html',
             ),
             'comment' => 'The warning banner displayed before login',
             'default' => '',
             ));
		$this->addColumn('configuration', 'behavior_rule', 'string', '65535', array(
             'notblank' => '1',
             'extra' => 
             array(
              'purify' => 'html',
             ),
             'comment' => 'Rules of behavior',
             'default' => '',
             ));
		$this->addColumn('configuration', 'privacy_policy', 'string', '65535', array(
             'notblank' => '1',
             'extra' => 
             array(
              'purify' => 'html',
             ),
             'comment' => 'Privacy policy',
             'default' => '',
             ));
		$this->addColumn('configuration', 'system_name', 'string', '255', array(
             'notblank' => '1',
             'comment' => 'System name',
             'default' => '',
             ));
		$this->addColumn('configuration', 'rob_duration', 'integer', '2', array(
             'notblank' => '1',
             'unsigned' => '1',
             'comment' => 'The duration between which the user has to accept the ROB (in days)',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'pass_uppercase', 'boolean', '25', array(
             'notblank' => '1',
             'comment' => 'Require upper case characters',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'pass_lowercase', 'boolean', '25', array(
             'notblank' => '1',
             'comment' => 'Require lower case characters',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'pass_numerical', 'boolean', '25', array(
             'notblank' => '1',
             'comment' => 'Require numerical characters',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'pass_special', 'boolean', '25', array(
             'notblank' => '1',
             'comment' => 'Require special characters',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'pass_min_length', 'integer', '1', array(
             'notblank' => '1',
             'unsigned' => '1',
             'comment' => 'Minimum password length',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'pass_max_length', 'integer', '1', array(
             'notblank' => '1',
             'unsigned' => '1',
             'comment' => 'Maximum password length',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'pass_expire', 'integer', '2', array(
             'notblank' => '1',
             'unsigned' => '1',
             'comment' => 'Password expiration (in days)',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'pass_warning', 'integer', '1', array(
             'notblank' => '1',
             'unsigned' => '1',
             'comment' => 'Number of days before the password will expire that a user begins to receive warnings',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'hash_type', 'enum', '', array(
             'notblank' => '1',
             'values' => 
             array(
              0 => 'sha1',
              1 => 'md5',
              2 => 'sha256',
             ),
             'comment' => 'The hash algorithm to use for password storage',
             'default' => 'sha1',
             ));
		$this->addColumn('configuration', 'host_url', 'string', '255', array(
             'notblank' => '1',
             'comment' => 'This is used to construct self referencing URLs in non-HTTP contexts. The installer should overwrite this value.',
             'default' => '',
             ));
		$this->addColumn('configuration', 'app_version', 'string', '10', array(
             'notblank' => '1',
             'comment' => 'The version of the application',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'yui_version', 'string', '10', array(
             'notblank' => '1',
             'comment' => 'The version of YUI being used by the application',
             'default' => '0',
             ));
		$this->addColumn('configuration', 'default_security_control_catalog_id', 'integer', '1', array(
             'notblank' => '1',
             'unsigned' => '1',
             'comment' => 'The ID of the default security control catalog which is used when the user does not explicitly specify a security control catalog.',
             'default' => '4',
             ));
    }

    /**
     * Irreversible migration 
     * 
     * @return void
     */
    public function down()
    {
       throw new Doctrine_Migration_IrreversibleMigrationException(); 
    }
}
