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
 */
 
/**
 * An object which represents an OpenFISMA configuration item.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Config extends FismaModel
{
    const MAX_ABSENT    = 'max_absent_time';
    const AUTH_TYPE     = 'auth_type';
    const F_THRESHOLD   = 'failure_threshold';
    const EXPIRING_TS   = 'expiring_seconds';
    const UNLOCK_ENABLED = 'unlock_enabled';
    const UNLOCK_DURATION = 'unlock_duration';

    const CONTACT_NAME  = 'contact_name';
    const CONTACT_PHONE = 'contact_phone';
    const CONTACT_EMAIL = 'contact_email';
    const CONTACT_SUBJECT = 'contact_subject';

    const USE_NOTIFICATION = 'use_notification';
    const BEHAVIOR_RULE    = 'behavior_rule';
    const PRIVACY_POLICY   = 'privacy_policy';
    
    const SENDER    = 'sender';
    const SUBJECT     = 'subject';
    const SMTP_HOST   = 'smtp_host';
    const SMTP_USERNAME   = 'smtp_username';
    const SMTP_PASSWORD   = 'smtp_password';
    
    protected $_name = 'configurations';
    protected $_primary = 'id';
    protected $_ldaps = array('name'=>'ldap_config',
                              'primary'=>'id');
    protected $_mapLdap = array(
            'host' => 'host',
            'port' => 'port',
            'username' => 'username',
            'password' => 'password',
            'useSsl' => 'use_ssl',
            'bindRequiresDn' => 'bind_requires_dn',
            'baseDn' => 'basedn',
            'accountFilterFormat' => 'account_filter',
            'accountCanonicalForm' => 'account_canonical',
            'accountDomainNameShort' => 'domain_short',
            'accountDomainName' => 'domain_name'
    );



    /**
     *  Retrive the ldap configuration(s)
     *
     *  @param numeric $id default null the group id of ldap config
     *  @return array all the configurations of LDAP servers. One configuration 
     *      if the $id is specified. 
     */
    public function getLdap($id=null)
    {
        $ldapConfig = new FismaModel($this->_ldaps);
        if (isset($id) && !is_array($id)) {
            $id = array($id);
        }
        $ret = $ldapConfig->getList($this->_mapLdap, $id);
        /*
        $qry = $ldapConfig->select()->from($ldapConfig, $this->_mapLdap);
        if (!empty($id)) {
            $qry->where("id=$id");
        }
        $ret = $ldapConfig->fetchAll($qry);
        */
        return $ret;
    }

    /**
     *  Save/Add LDAP configuration
     *
     *  @param array $value data to be saved/added
     */
     public function saveLdap($values,$id=null)
     {
         $revVal = array_flip($this->_mapLdap);
         $values = directMap($revVal, $values);
         $ldapConfig = new FismaModel($this->_ldaps);
         if (empty($id)) {
             $ret = $ldapConfig->insert($values);
         } else {
             $ret = $ldapConfig->update($values, "id=$id");
         }
         return $ret;
     }

    /**
     *  Delete LDAP configuration
     *
     *  @param numeric $id the key of the configuration
     */
     public function delLdap($id)
     {
        assert(is_numeric($id));
        $ldapConfig = new FismaModel($this->_ldaps);
        return $ldapConfig->delete("id=$id");
     }
}

