<?php
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
 * Represents the steps that must be performed to resolve an instance of an incident
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class IrIncidentWorkflow extends BaseIrIncidentWorkflow
{
    /**
     * Override constructor to set default values
     */
    public function construct()
    {
        // Only operate on new objects (i.e. transient), not persistent objects which are being rehydrated
        $state = $this->state();
        if ($state == Doctrine_Record::STATE_TCLEAN || $state == Doctrine_Record::STATE_TDIRTY) {
        
            $this->status = 'queued';
        }
    }
    
    /**
     * Mark this step as completed and update relevant metadata
     * 
     * @param string $comment The user's comment associated with completing this step
     */
    public function completeStep($comment)
    {
        $this->status = 'completed';
        $this->completeTs = Fisma::now();
        $this->User = CurrentUser::getInstance();
        $this->comments = $comment;
    }
}
