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
 * Listener for the Commentable behavior's associated comment model.
 *
 * @package Fisma
 * @subpackage Fisma_Doctrine_Behavior
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @author Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Doctrine_Behavior_Commentable_Listener extends Doctrine_Record_Listener
{
    protected $_relationName;

    public function __construct($relationName)
    {
        $this->_relationName = $relationName;
    }

    /**
     * When a comment is updated, update the jsonComment field of the parent model.
     *
     * @param Doctrine_Event $event
     * @return void
     */
    public function postInsert(Doctrine_Event $event)
    {
        $comment = $event->getInvoker();
        $object = $comment->{$this->_relationName};
        $object->updateJsonComments();
    }
}
