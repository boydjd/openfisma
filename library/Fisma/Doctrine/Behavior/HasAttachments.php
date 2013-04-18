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
 * A behavior which provides the ability to has uploaded attachment accessed via the File API
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Behavior_HasAttachments
 */
class Fisma_Doctrine_Behavior_HasAttachments extends Doctrine_Template
{
    /**
     * Overload constructor to plug in the record generator
     *
     * @param array $options The template options
     * @return void
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->_plugin = new Fisma_Doctrine_Behavior_HasAttachments_Generator();
    }

    /**
     * Define a relation to the Upload class
     *
     * @return void
     */
    public function setUp()
    {
        $baseClassName = $this->getTable()->getComponentName();
        $foreignClassName = $baseClassName . 'Attachment';

        $this->hasMany(
            'Upload as Attachments',
            array(
                'local' => 'objectId',
                'foreign' => 'uploadId',
                'refClass' => $foreignClassName
            )
        );

        $this->_plugin->initialize($this->getTable());
    }

    /**
     * A helper method to associate an attachment with the host object
     *
     * @param mixed $file The array mapped from HTTP $_FILES
     * @param string $comment Optional. The comment of the attachment
     * @return void
     */
    public function attach($file, $comment = null)
    {
        $upload = new Upload();
        $upload->description = $comment;
        $upload->instantiate($file);

        $instance = $this->getInvoker();
        $instance->Attachments[] = $upload;
    }
}
