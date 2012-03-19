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
 * An evaluation is either an approval or denial of a particular item, such as a mitigation
 * strategy or evidence artifact.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Model
 */
class Evaluation extends BaseEvaluation
{
    /**
     * Mutator for nickname to update associated findings' denormalizedStatus
     *
     * $param String $newValue
     *
     * @return void
     */
    public function setNickname($newValue)
    {
        $this->_set('nickname', $newValue);
        $this->save();
        $findings = Doctrine_Query::create()
            ->from('Finding f')
            ->leftJoin('f.CurrentEvaluation e')
            ->where('e.id = ?', $this->id)
            ->execute();
        if (count($findings) > 0) {
            foreach ($findings as $f) {
                $f->setStatus($f->status); //because updateDenormalizedStatus is not public)
                $f->save();
            }
        }
    }

    /**
     * Mutator for daysUntilDue to update associated findings' nextDueDate
     *
     * @param int $newValue
     *
     * @return void
     */
    public function setDaysUntilDue($newValue)
    {
        $this->_set('daysUntilDue', $newValue);
        $this->save();
        $findings = Doctrine_Query::create()
            ->from('Finding f')
            ->leftJoin('f.CurrentEvaluation e')
            ->where('e.id = ?', $this->id)
            ->execute();
        if (count($findings) > 0) {
            foreach ($findings as $f) {
                $f->setStatus($f->status); //because updateDueDate is not public)
                $f->save();
            }
        }
    }
}
