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

        if (!file_exists(Fisma::getPath('config') . '/install.conf')) {
            print Fisma::getPath('config') . "/install.conf is not exist \n";
            exit;
        }
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
            if (!(in_array($field, $table->getTable()->getFieldNames())) || is_null($value)) {
                continue;
            }
            $table->$field = $value;
        }
        $table->save();
    }

    public function process()
    {
        $this->_checkVersion();
        $this->_rebuildDb();
        $this->copyUser();
        $this->copyProduct();
        $this->copyNetwork();
        $this->copySource();
        $this->copyUserEvent();
        $this->copySystem();
        $this->copyOrganization();
        $this->copyAsset();
        $this->copyAccountLog();
        $this->copyConfiguration();
        $this->copyEmailValidation();
        $this->copyLdapConfig();
        $this->copyUpload();
        $this->copyFinding();
        $this->copyAuditLog();
        $this->copyEvidence();
        $this->copyEvaluation();
        $this->copyFindingEvaluation();
        $this->calcCurrentEvaluationId();
        $this->copyNotification();
        $this->copyUserOrganization();
        $this->copyUserRole();
    }

    public function copyUser()
    {
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
        $this->_printResult('User');
    }

    //@todo need to migrate?
    public function copyProduct()
    {
        $query = self::$_db->select()->from('products', array('count'=>'count(*)'));
        $ret   = self::$_db->fetchRow($query);
        $count = $ret['count'];
        // prevent too many times
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
        $this->_printResult('Product');
    }

    public function copyNetwork()
    {
        $networks = self::$_db->fetchAll('SELECT * FROM networks');
        foreach ($networks as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('Network', $data);
        }
        $this->_printResult('Network');
    }

    public function copySource()
    {
        $data = array();
        $sources = self::$_db->fetchAll('SELECT * FROM sources');
        foreach ($sources as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('Source', $data);
        }
        $this->_printResult('Source');
    }

    public function copyUserEvent()
    {
        $userEvents = self::$_db->fetchAll('SELECT * FROM user_events');
        foreach ($userEvents as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('UserEvent', $data);
        }
        $this->_printResult('UserEvent');
    }

    public function copySystem()
    {
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
        $this->_printResult('System');
    }

    public function copyOrganization()
    {
        $organizations = self::$_db->fetchAll('SELECT * FROM organizations');
        foreach ($organizations as $row) {
            $data = $this->_migrateData($row);
            $data['orgType']    = 'organization';
            $data['createdTs']  = $data['modifiedTs'] = date('Y-m-d H:i:s');
            if ((int)$row['father'] == 0) {
                $this->migrate('Organization', $data);
                $organization = new Organization();
                $treeObject = $organization->getTable()->getTree();
                $treeObject->createRoot($organization->getTable()->find($row['id']));
            } else {
                $organization = new Organization();
                $organization->merge($data);
                $organization->save();
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
            $organization = new Organization();
            $organization->merge($data);
            $organization->save();
            $organization->getNode()->insertAsLastChildOf($organization->getTable()->find($row['oid']));
        }
        $this->_printResult('Organization');
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
        $this->_printResult('Asset');
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
        $this->_printResult('AccountLog');
    }

    public function copyConfiguration()
    {
        $data = array();
        $configurations = self::$_db->fetchAll('SELECT * FROM configurations');
        $configurations[] = array('key'=>'hash_type',
                                  'value'=>'sha1',
                                  'description'=>'The hash algorithm to use for password storage');
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
        $this->_printResult('Configuration');
    }

    public function copyRole()
    {
        $data = array();
        $roles = self::$_db->fetchAll('SELECT * FROM roles');
        foreach ($roles as $row) {
            $data = $this->_migrateData($row);
            $data['createdTs'] = $data['modifiedTs'] = date('Y-m-d H:i:s');
            $data['description'] = $row['desc'];
            $this->migrate('Role', $data);
        }
        $this->_printResult('Role');
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
        $this->_printResult('EmailValidation');
    }

    public function copyPlugin()
    {
        $plugins = self::$_db->fetchAll('SELECT * FROM plugins');
        foreach ($plugins as $row) {
            foreach ($row as $field=>$value) {
                $newField = $this->_makeCamelCase($field);
                $data['description'] = $row['desc'];
                $data[$newField] = $value;
            }
            $this->migrate('Plugin', $data);
        }
        $this->_printResult('Plugin');
    }

    public function copyLdapConfig()
    {
        $ldapConfigs = self::$_db->fetchAll('SELECT * FROM ldap_config');
        foreach ($ldapConfigs as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('LdapConfig', $data);
        }
        $this->_printResult('LdapConfig');
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
        $this->_printResult('Upload');
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
            $data['uploadId']                     = empty($row['upload_id']) ? null : $row['upload_id'];
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
        $this->_printResult('Finding');
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
        $this->_printResult('AuditLog');
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
        $this->_printResult('Evidence');
    }

    public function copyEvaluation()
    {
        $evaluations = self::$_db->fetchAll('SELECT * FROM evaluations order by id DESC');
        foreach ($evaluations as $row) {
            $data = $this->_migrateData($row);
            if ('93' == $data['eventId']) {
                $data['eventId'] = '53';
            }
            $data['approvalGroup'] = strtolower($row['group']);
            $data['precedence']    = $row['precedence_id'];
            $nextId = $row['id'] + 1;
            $result = self::$_db->fetchRow("SELECT * FROM evaluations WHERE id = $nextId AND `group` = '$row[group]'");
            if (!empty($result)) {
                $data['nextId']  =  $nextId;
            }
            $this->migrate('Evaluation', $data);
        }
        $this->_printResult('Evaluation');
    }

    public function copyFindingEvaluation()
    {
        $query = self::$_db->select()->from(array('pe'=>'poam_evaluations'), 'pe.*')
                                     ->joinLeft(array('c'=>'comments'), 'c.poam_evaluation_id = pe.id',
                                                    array('comment'=>'c.content'))
                                     ->join(array('e'=>'evaluations'), 'e.id = pe.eval_id',
                                                    array('group'=>'e.group'));
        $findingEvaluations = self::$_db->fetchAll($query);
        foreach ($findingEvaluations as $row) {
            $data  = $this->_migrateData($row);
            $data['evaluationId'] = $row['eval_id'];
            $data['createdTs']    = $row['date'];
            if ('ACTION' == $row['group']) {
                $data['findingId']    = $row['group_id'];
            } elseif ('EVIDENCE' == $row['group']) {
                $data['evidenceId']   = $row['group_id'];
            }
            $this->migrate('FindingEvaluation', $data);
        }
        $this->_printResult('FindingEvaluation');
    }

    public function calcCurrentEvaluationId()
    {
        $findingIds = Doctrine_Query::create()
                        ->select('f.id')
                        ->from('Finding f')
                        ->execute(array(), Doctrine::HYDRATE_ARRAY);
        foreach ($findingIds as $row) {
            $finding = new Finding();
            $finding = $finding->getTable()->find($row['id']);
            if (in_array($finding->status, array('MSA', 'EA'))) {
                $lastFindingEvaluation = $finding->FindingEvaluations->getLast();
                if ($lastFindingEvaluation->decision == null) {
                    $finding->currentEvaluationId = 1;
                }
                if ('APPROVED' == $lastFindingEvaluation->decision) {
                    $finding->currentEvaluationId = $lastFindingEvaluation->evaluationId + 1;
                }
            }
            $finding->save();
        }
    }

    public function copyNotification()
    {
        $notifications = self::$_db->fetchAll('SELECT * FROM notifications');
        foreach ($notifications as $row) {
            $data = $this->_migrateData($row);
            $data['createdTs'] = $row['timestamp'];
            $data['eventId']   = null;
            $this->migrate('Notification', $data);
        }
        $this->_printResult('Notification');
    }

    public function copyUserOrganization()
    {
        $userOrganizations = self::$_db->fetchAll('SELECT * FROM user_systems');
        foreach ($userOrganizations as $row) {
            $data['userId'] = $row['user_id'];
            $data['organizationId'] = Doctrine::getTable('System')->find($row['system_id'])->Organization[0]->id;
            $this->migrate('UserOrganization', $data);
        }
        $this->_printResult('UserOrganization');
    }

    public function copyUserRole()
    {
        $userRoles = self::$_db->fetchAll('SELECT * FROM user_roles');
        foreach ($userRoles as $row) {
            $data = $this->_migrateData($row);
            $this->migrate('UserRole', $data);
        }
        $this->_printResult('UserRole');
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
     * Check the old db whether is the latest schema version
     */
    private function _checkVersion()
    {
        $latestVersion = 62;
        $row = self::$_db->fetchRow("SELECT schema_version FROM schema_version");
        if ($row['schema_version'] < $latestVersion) {
            print "Please execute the migrate.pl first";
            exit;
        }
    }

    /**
     * rebuild database
     */
    private function _rebuildDb()
    {
        Doctrine::dropDatabases();
        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
        Doctrine::createDatabases();
        Doctrine::createTablesFromModels(Fisma::getPath('model'));
        print "Rebuild Db successfully. \n";

        //load sample data
        Doctrine::loadData(Fisma::getPath('fixture'));
        Doctrine::getTable('User')->findAll()->delete();
        Doctrine::getTable('Source')->findAll()->delete();
        Doctrine::getTable('Evaluation')->findAll()->delete();
        Doctrine::getTable('Configuration')->findAll()->delete();
    }
    
    private function _printResult($table)
    {
        print $table . " is migrated successfully. \n";        
    }
}
