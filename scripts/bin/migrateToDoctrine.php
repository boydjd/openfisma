#!/usr/bin/env php
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
 * @package   Script_Bin
 */

/**
 * Migrate the Zend_Db platform to the Doctrine
 */
$migrate = new Migrate();
$migrate->process();

class Migrate
{
    private static $_db = null;

    public function __construct() 
    {
        require_once(realpath(dirname(__FILE__) . '/../../library/Fisma.php'));

        Fisma::initialize(Fisma::RUN_MODE_COMMAND_LINE);
        Fisma::connectDb();
        Fisma::setNotificationEnabled(false);
        Fisma::setListenerEnabled(false);    

        // Load the old database configuration
        $installConfFile = Fisma::getPath('config') . '/install.conf';
        $conf = new Zend_Config_Ini($installConfFile);
        self::$_db  =  Zend_Db::factory($conf->database);
    }

    /**
     * migrate 
     * @param string $table new table
     * @param array $data 
     */
    public function migrate($tableName, $data)
    {
        $table = new $tableName;
        foreach ($data as $field=>$value) {
            if (!(in_array($field, $table->getTable()->getFieldNames())) || empty($value)) {
                continue;
            }
            $table->$field = $value;
        }
        $table->save();
    }

    public function process()
    {
        $this->copyUser();
        $this->copyProduct();
        $this->copyNetwork();
        $this->copySource();
        $this->copySystem();
        $this->copyOrganization();
        $this->copyAsset();
        $this->copyAccountLog();
        $this->copyConfiguration();
        $this->copyRole();
        $this->copyEmailValidation();
        $this->copyPlugin();
        $this->copyLdapConfig();
        $this->copyUpload();
        $this->copyFinding();
        $this->copyAuditLog();
        $this->copyEvidence();
        $this->copyUserOrganization();
        $this->copyUserRole();
    }

    public function copyUser()
    {
        $this->_emptyTable('User');
        $users = self::$_db->fetchAll('SELECT * FROM users');
        foreach ($users as $row) {
            $data = $this->_migrateData($row);
            $data['username']  = $row['account'];
            $data['hashType'] = $row['hash'];
            $data['locked']   = $row['is_active'] ? false : true;
            $data['lockTs']   = $row['termination_ts'];
            $data['modifiedTs']      = $row['created_ts'];
            $data['oldFailureCount'] = $data['failureCount'];
            $data['currentLoginIp']  = $data['lastLoginIp'];
            $this->migrate('User', $data);
        }
    }

    //@todo need to migrate?
    public function copyProduct()
    {
        $this->_emptyTable('Product');
        $query = self::$_db->select()->from('products', array('count'=>'count(*)'));
        $ret   = self::$_db->fetchRow($query);
        $count = $ret['count'];
        // prevent too many time
        $count = 500;
        $offset = 500;
        $query  = self::$_db->select()->from('products', array('id', 'vendor', 'name', 'version', 'cpe_name'));
        for ($limit=0;$limit<$count;$limit+=$offset) {
            $query->limit($offset, $limit);
            $products = self::$_db->fetchAll($query);
            foreach ($products as $row) {
                $data = $this->_migrateData($row);
                $this->migrate('Product', $data);
            }
        }
    }

    public function copyNetwork()
    {
        $this->_emptyTable('Network');
        $networks = self::$_db->fetchAll('SELECT * FROM networks');
        foreach ($networks as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('Network', $data);
        }
    }

    public function copySource()
    {
        $this->_emptyTable('Source');
        $data = array();
        $sources = self::$_db->fetchAll('SELECT * FROM sources');
        foreach ($sources as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('Source', $data);
        }
    }

    public function copySystem()
    {
        $this->_emptyTable('System');
        $systems = self::$_db->fetchAll('SELECT * FROM systems');
        foreach ($systems as $row) {
            $data = $this->_migrateData($row);
            $data['confidentialityDescription'] = $row['confidentiality_justification'];
            $data['integrityDescription']       = $row['integrity_justification'];
            if ($row['type'] == 'GENERAL SUPPORT SYSTEM') {
                $data['type'] = 'gss';
            } elseif ($row['type'] == 'MINOR APPLICATION') {
                $data['type'] = 'major';
            } else {
                $data['type'] = 'minor';
            }
            $this->migrate('System', $data);
        }
    }

    public function copyOrganization()
    {
        $this->_emptyTable('Organization');
        $organization = new Organization();
        $organizations = self::$_db->fetchAll('SELECT * FROM organizations');
        foreach ($organizations as $row) {
            $data = $this->_migrateData($row);
            $data['orgType']    = 'organization';
            $data['createdTs']  = $data['modifiedTs'] = date('Y-m-d H:i:s');
            $this->migrate('Organization', $data);
            if ((int)$row['father'] == 0) {
                $treeObject = $organization->getTable()->getTree();
                $treeObject->createRoot($organization->getTable()->find($row['id']));
            } else {
                $organization->getNode()->insertAsLastChildOf($organization->getTable()->find($row['father']));
            }
        }
        $orgSystems = self::$_db->fetchAll('SELECT s.*, o.id AS oid FROM systems s JOIN organizations o on o.id = s.organization_id');
        foreach ($orgSystems as $row) {
            $data = $this->_migrateData($row);
            unset($data['id']);
            $data['orgType']    = 'system';
            $data['systemId']   = $row['id'];
            $data['createdTs']  = $data['modifiedTs'] = date('Y-m-d H:i:s');
            $this->migrate('Organization', $data);
            $organization->getNode()->insertAsLastChildOf($organization->getTable()->find($row['oid']));
        }
    }

    public function copyAsset()
    {
        $assets = self::$_db->fetchAll('SELECT * FROM assets');
        $system = new System();
        foreach ($assets as $row) {
            $data = $this->_migrateData($row);
            $data['createdTs'] = $row['create_ts'];
            $data['productId'] = $row['prod_id'];
            $data['orgSystemId'] = $system->getTable()->find($row['system_id'])->Organization[0]->id;
            $data['modifiedTs']  = $row['create_ts'];
            $this->migrate('Asset', $data);
        }
    }

    public function copyAccountLog()
    {
        $logs = self::$_db->fetchAll('SELECT * FROM account_logs');
        foreach ($logs as $row) {
            $data = $this->_migrateData($row);
            $data['createdTs'] = $row['timestamp'];
            switch ($row['event']) {
                case 'ACCOUNT_CREATED':
                    $data['event'] = User::CREATE_USER;
                    break;
                case 'ACCOUNT_MODIFICATION':
                    $data['event'] = User::MODIFY_USER;
                    break;
                case 'ACCOUNT_DELETED':
                    $data['event'] = User::DELETE_USER;
                    break;
                case 'ACCOUNT_LOCKOUT':
                    $data['event'] = User::LOCK_USER;
                    break;
                case 'DISABLING':
                    $data['event'] = User::LOCK_USER;
                    break;
                case 'LOGINFAILURE':
                    $data['event'] = User::LOGIN_FAILURE;
                    break;
                case 'LOGIN':
                    $data['event'] = User::LOGIN;
                    break;
                case 'LOGOUT':
                    $data['event'] = User::LOGOUT;
                    break;
                case 'ROB_ACCEPT':
                    $data['event'] = User::ACCEPT_ROB;
                    break;
                default;
            }
            $this->migrate('AccountLog', $data);
        }
    }

    public function copyConfiguration()
    {
        $this->_emptyTable('Configuration');
        $data = array();
        $configurations = self::$_db->fetchAll('SELECT * FROM configurations');
        foreach ($configurations as $row) {
            $data = $this->_migrateData($row);
            switch ($row['key']) {
                case 'max_absent_time':
                    $row['key'] = 'account_inactivity_period';
                    break;
                case 'expiring_seconds':
                    $row['key'] = 'session_inactivity_period';
                    break;
                case 'pass_min':
                    $row['key'] = 'pass_min_length';
                    break;
                case 'pass_max':
                    $row['key'] = 'pass_max_length';
                    break;
                case 'pass_warningdays':
                    $row['key'] = 'pass_warning';
                    break;
                default;
            }
            $data['name'] = $row['key'];
            $this->migrate('Configuration', $data);
        }
    }

    public function copyRole()
    {
        $this->_emptyTable('Role');
        $data = array();
        $roles = self::$_db->fetchAll('SELECT * FROM roles');
        foreach ($roles as $row) {
            $data = $this->_migrateData($row);
            $data['createdTs'] = $data['modifiedTs'] = date('Y-m-d H:i:s');
            $data['description'] = $row['desc'];
            $this->migrate('Role', $data);
        }
    }

    public function copyEmailValidation()
    {
        $emailvalidations = self::$_db->fetchAll('SELECT * FROM validate_emails');
        foreach ($emailvalidations as $row) {
            foreach ($row as $field=>$value) {
                $newField = $this->_makeCamelCase($field);
                $data['validationCode'] = $row['validate_code'];
                $data[$newField] = $value;
            }
            $this->migrate('EmailValidation', $data);
        }
    }

    public function copyPlugin()
    {
        $this->_emptyTable('Plugin');
        $plugins = self::$_db->fetchAll('SELECT * FROM plugins');
        foreach ($plugins as $row) {
            foreach ($row as $field=>$value) {
                $newField = $this->_makeCamelCase($field);
                $data['description'] = $row['desc'];
                $data[$newField] = $value;
            }
            $this->migrate('Plugin', $data);
        }
    }

    public function copyLdapConfig()
    {
        $ldapConfigs = self::$_db->fetchAll('SELECT * FROM ldap_config');
        foreach ($ldapConfigs as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('LdapConfig', $data);
        }
    }

    public function copyUpload()
    {
        $uploads = self::$_db->fetchAll('SELECT * FROM uploads');
        foreach ($uploads as $row) {
            $data  = $this->_migrateData($row);
            $data['createdTs'] = $row['upload_ts'];
            $data['fileName']  = $row['filename'];
            $this->migrate('Upload', $data);
        }
    }

    public function copyFinding()
    {
        $findings = self::$_db->fetchAll('SELECT * FROM poams');
        foreach ($findings as $row) {
            $data = $this->_migrateData($row);
            if ($row['blscr_id']) {
                $data['securityControlId'] = Doctrine::getTable('SecurityControl')
                                                ->findOneByCode($row['blscr_id'])->id;
            }
            $data['createdTs']                    = $row['create_ts'];
            $data['modifiedTs']                   = $row['modify_ts'];
            $data['discoveredDate']               = $row['discover_ts'];
            $data['closedTs']                     = $row['close_ts'];
            $data['description']                  = $row['finding_data'];
            $data['recommendation']               = $row['action_suggested'];
            $data['mitigationStrategy']           = $row['action_planned'];
            $data['resourcesRequired']            = $row['action_resources'];
            $data['expectedCompletionDate']       = $row['action_est_date'];
            $data['currentEcd']                   = $row['action_current_date'];
            $data['actualcompletiondate']         = $row['action_actual_date'];
            $data['countermeasures']              = $row['cmeasure'];
            $data['countermeasuresEffectiveness'] = $row['cmeasure_effectiveness'];
            $data['threat']                       = $row['threat_source'];
            $data['threatLevel']                  = $row['threat_level'];
            $data['duplicateFindingId']           = $row['duplicate_poam_id'];
            $data['ecdLocked']                    = empty($row['action_actual_date']) ? false : true;
            $data['responsibleOrganizationId']    = Doctrine::getTable('System')->find($row['system_id'])->Organization[0]->id;
            switch ($row['status']) {
                case 'NEW':
                    $data['nextDueDate'] = date("Y-m-d",strtotime("$row[create_ts] + 30day"));
                    break;
                case 'DRAFT':
                    $data['nextDueDate'] = date("Y-m-d",strtotime("$row[create_ts] + 30day"));
                    break;
                case 'MSA':
                    $data['nextDueDate'] = date("Y-m-d",strtotime("$row[mss_ts] + 7day"));
                    break;
                case 'EN':
                    $data['nextDueDate'] = date("Y-m-d",strtotime("$row[action_est_date]"));
                    break;
                case 'EA':
                    $data['nextDueDate'] = date("Y-m-d",strtotime("$row[action_actual_date] + 7day"));
                    break;
            }
            $this->migrate('Finding', $data);
        }
    }

    public function copyAuditLog()
    {
        $auditLogs = self::$_db->fetchAll('SELECT * FROM audit_logs');
        foreach ($auditLogs as $row) {
            $data = $this->_migrateData($row);
            $data['createdTs'] = $row['timestamp'];
            $data['findingId'] = $row['poam_id'];
            $this->migrate('AuditLog', $data);
        }
    }

    public function copyEvidence()
    {
        $evidences = self::$_db->fetchAll('SELECT * FROM evidences');
        foreach ($evidences as $row) {
            $data = $this->_migrateData($row);
            $data['findingId'] = $row['poam_id'];
            $data['filename']  = $row['submission'];
            $data['userId']    = $row['submitted_by'];
            $data['createdTs'] = $row['submit_ts'];
            $this->migrate('Evidence', $data);
        }
    }

    public function copyUserOrganization()
    {
        $userOrganizations = self::$_db->fetchAll('SELECT * FROM user_systems');
        foreach ($userOrganizations as $row) {
            $data['userId'] = $row['user_id'];
            $data['organizationId'] = Doctrine::getTable('System')->find($row['system_id'])->Organization[0]->id;
            $this->migrate('UserOrganization', $data);
        }
    }

    public function copyUserRole()
    {
        $userRoles = self::$_db->fetchAll('SELECT * FROM user_roles');
        foreach ($userRoles as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('UserRole', $data);
        }
    }

    public function copyEvaluation()
    {
        $evaluations = self::$_db->fetchAll('SELECT * FROM evaluations order by id DESC');
        foreach ($evaluations as $row) {
            $data = $this->_migrateData($row);
            $data['approvalGroup'] = strtolower($row['group']);
            $data['precedence']    = $row['precedence_id'];
            $nextId = $row['id'] + 1;
            $result = self::$_db->fetchRow("SELECT * FROM evaluations WHERE id = $nextId AND `group` = '$row[group]'");
            if (!empty($result)) {
                //$data['nextId']  =  null;
            }
            $this->migrate('Evaluation', $data);
        }
    }

    public function copyFindingEvaluation()
    {
        $findingEvaluations = self::$_db->fetchAll('SELECT pe.* FROM poam_evaluations pe JOIN comments c on c.poam_evaluation_id = pe.id');
        foreach ($findingEvaluations as $row) {
            $data  = $this->_migrateData($row);
            $data['evaluationId'] = $row['eval_id'];
            $data['createdTs']    = $row['date'];
            $data['comment']      = $row['comment'];
            $data['findingId']    = $row['group_id'];
            $data['evidenceId']   = $row['group_id'];
            $this->migrate('FindingEvaluation', $data);
        }
    }

    /**
     * change string into a camplCase string
     *
     * @param string @string
     * @return string
     */
    private function _makeCamelCase($string) 
    {
        $upperCase = ucwords(str_replace('_', ' ', $string));
        $camelCase = str_replace(' ', '', $upperCase);
        return strtolower(substr($camelCase, 0, 1)) . substr($camelCase, 1);
    }

    /**
     * generate a new array to migrate
     * 
     * @param array $oldData
     * @return array
     */
    private function _migrateData($oldData)
    {
        $newData = array();
        if (empty($oldData)) {
            return $newData;
        }
        foreach ($oldData as $field=>$value) {
            $newField = $this->_makeCamelCase($field);
            $newData[$newField] = $value;
        }
        return $newData;
    }

    /**
     * empty table data
     *
     * @param string $table
     */
    private function _emptyTable($table)
    {
        Doctrine::getTable($table)->findAll()->delete();        
    }
}
