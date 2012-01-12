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
 * Override parent construct function to call Fisma_Doctrine_Task_DropDb and Fisma_Doctrine_Task_BuildAll instead
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Task
 */
class Fisma_Doctrine_Task_RebuildDb extends Doctrine_Task_RebuildDb
{
    /**
     * Use Fisma_Doctrine_Task_DropDb so that it can detect auto-yes/auto-no argument,
     * use Fisma_Doctrine_Task_BuildAll so that it can call Fisma_Doctrine_Task_GenerateModelsYaml instead
     *
     * @return void
     */
    public function __construct($dispatcher = null)
    {
        parent::__construct($dispatcher);

        $this->taskName = Fisma_Doctrine_Task::getDoctrineTaskName($this);

        $this->dropDb = new Fisma_Doctrine_Task_DropDb($this->dispatcher);
        $this->buildAll = new Fisma_Doctrine_Task_BuildAll($this->dispatcher);
    }
}