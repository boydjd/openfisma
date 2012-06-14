<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Represents a point of contact.
 *
 * A point of contact is a user who may not have login access to OpenFISMA but can still be referenced
 * by objects within the application. For example, a finding object can reference a point of contact, even if that
 * point of contact does not have a login account on OpenFISMA.
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Poc extends BasePoc implements Fisma_Doctrine_Behavior_AuditLoggable_AuditLogProvider
{
    /**
     * Doctrine hook which is used to set up mutators
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->hasMutator('nameFirst', 'setNameFirst');
        $this->hasMutator('nameLast', 'setNameLast');
        $this->hasMutator('email', 'setEmail');
        $this->hasMutator('type', 'setType');
    }

    /**
     * Implement the audit log provider interface.
     *
     * This represents what a POC will look like when it is rendered in an audit log.
     *
     * @return string
     */
    public function getAuditLogValue()
    {
        return $this->username . ' [' . $this->nameFirst . ' ' . $this->nameLast . ']';
    }

    public function setType($value)
    {
        if ($value === 'User') {
            $this->_set('accountType', 'User');
        } else {
            $this->_set('accountType', 'Contact');
        }
        $this->_set('type', $value);
    }

    /**
     * Update displayName
     *
     * @return void
     */
    protected function _updateDisplayName()
    {
        $displayName = trim($this->nameFirst . ' ' . $this->nameLast);
        if (empty($this->nameFirst) || empty($this->nameLast)) {
            $displayName = trim($displayName. ' <' . $this->email . '>');
        }
        $this->_set('displayName', $displayName);
    }

    /**
     * Update displayName when nameFirst is updated
     *
     * @param string $value
     * @return void
     */
    public function setNameFirst($value)
    {
        $this->_set('nameFirst', $value);
        $this->_updateDisplayName();
    }

    /**
     * Update displayName when nameLast is updated
     *
     * @param string $value
     * @return void
     */
    public function setNameLast($value)
    {
        $this->_set('nameLast', $value);
        $this->_updateDisplayName();
    }

    /**
     * Update displayName when email is updated
     *
     * @param string $value
     * @return void
     */
    public function setEmail($value)
    {
        $this->_set('email', $value);
        $this->_updateDisplayName();
    }
}
