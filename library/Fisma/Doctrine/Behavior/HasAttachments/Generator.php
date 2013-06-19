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
 * Generator for the attach attachments behavior
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_AuditLoggable
 */
class Fisma_Doctrine_Behavior_HasAttachments_Generator extends Doctrine_Record_Generator
{
    /**
     * Set up the generated class name
     *
     * @return void
     */
    public function initOptions()
    {
        // This will result in class names like 'IncidentAttachment'
        $this->setOption('className', '%CLASS%Attachment');
    }

    /**
     * Overriding to avoid default relation building between the generated model (a joint table) and the host model
     *
     * @return void
     */
    public function buildRelation()
    {
    }

    /**
     * Overriding to avoid default relation building between the generated model (a joint table) and the host model
     *
     * @param Doctrine_Table $table The host table
     * @return mixed
     */
    public function buildForeignKeys(Doctrine_Table $table)
    {
        return array();
    }

    /**
     * Table definition
     *
     * @return void
     */
    public function setTableDefinition()
    {
        // Foreign key to the Upload associated with this Attachment entry
        $this->hasColumn(
            'uploadId',
            'integer',
            null,
            array(
                'comment' => 'The uploaded file',
                'primary' => true
            )
        );

        // Foreign key to the object which this attachment belongs to
        $this->hasColumn(
            'objectId',
            'integer',
            null,
            array(
                'comment' => 'The parent object to which the attachment belongs',
                'primary' => true
            )
        );

    }
}
