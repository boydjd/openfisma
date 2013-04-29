<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * InformationDataTypeCatalog
 *
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @author Duy K. Bui <duy.bui@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class InformationDataTypeCatalog extends BaseInformationDataTypeCatalog
{
    /**
     * This model uses a combined "manage" privilege in place of usual CRUD
     *
     * @var bool
     */
    const IS_MANAGED = true;

    /**
     * Update denormalized counters
     */
    public function updateDenormalizedCounters()
    {
        $this->loadReference('InformationDataTypes');
        $this->denormalizedTotalCount = $this->InformationDataTypes->count();
        $this->denormalizedPublishedCount = count(array_filter(
            $this->InformationDataTypes->toKeyValueArray('id', 'published'),
            function($value) {
                return $value;
            }
        ));
    }
}
