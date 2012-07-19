<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * Fisma_Ldap
 *
 * @package Ldap
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Ldap
{
    protected $_configs;

    protected $_resultFields = array(
        'givenname',
        'mail',
        'mobile',
        'sAMAccountName',
        'sn',
        'telephonenumber',
        'title',
        'uid'
    );

    protected $_log = null;

    public function __construct(array $configs)
    {
        if (count($configs) === 0) {
            throw new Fisma_Zend_Exception_User('No LDAP servers defined.');
        }

        $this->_configs = $configs;
        $this->_log = Zend_Registry::get('Zend_Log');
    }

    public function lookup($query)
    {
        if (empty($query)) {
            throw new Fisma_Zend_Exception_User('You did not specified a query.');
        }

        $matchedAccounts = array();
        // Using Zend_Ldap_Filter instead of a string query prevents LDAP injection
        $searchFilter = Zend_Ldap_Filter::orFilter(
            Zend_Ldap_Filter::contains('mail', $query),
            Zend_Ldap_Filter::contains('sn', $query),
            Zend_Ldap_Filter::contains('givenName', $query)
        );

        foreach ($this->_configs as $config) {

            try {
                $ldapServer = new Zend_Ldap($config);
                $result = $ldapServer->search(
                    $searchFilter,
                    null,
                    Zend_Ldap::SEARCH_SCOPE_SUB,
                    $this->_resultFields,
                    'givenname',
                    null,
                    10 // limit 10 results to avoid crushing ldap server
                );
                $matchedAccounts += $result->toArray();
            } catch (Zend_Ldap_Exception $e) {
                $this->_log->err('Problem querying LDAP server.', $e);
            }
        }

        return $matchedAccounts;
    }

    public function match($query)
    {
        if (empty($query)) {
            throw new Fisma_Zend_Exception_User('You did not specified a query.');
        }

        $matchedAccounts = array();
        // Using Zend_Ldap_Filter instead of a string query prevents LDAP injection
        $searchFilter = Zend_Ldap_Filter::orFilter(
            Zend_Ldap_Filter::equals('uid', $query),
            Zend_Ldap_Filter::equals('samaccountname', $query)
        );

        foreach ($this->_configs as $config) {

            try {
                $ldapServer = new Zend_Ldap($config);
                $result = $ldapServer->search(
                    $searchFilter,
                    null,
                    Zend_Ldap::SEARCH_SCOPE_SUB,
                    $this->_resultFields,
                    'givenname',
                    null,
                    10 // limit 10 results to avoid crushing ldap server
                );
                $matchedAccounts += $result->toArray();
            } catch (Zend_Ldap_Exception $e) {
                $this->_log->err('Problem querying LDAP server.', $e);
            }
        }

        return (count($matchedAccounts)) ? $matchedAccounts[0] : null;
    }
}
