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
 * Override parent construct function to call Fisma_Doctrine_Task_BuildAll instead
 *
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Doctrine_Task
 */
class Fisma_Doctrine_Task_BuildAllLoad extends Doctrine_Task_BuildAllLoad
{
    /**
     * Use Fisma_Doctrine_Task_BuildAll so that it can call Fisma_Doctrine_Task_GenerateModelsYaml instead
     *
     * @return void
     */
    public function __construct($dispatcher = null)
    {
        parent::__construct($dispatcher);

        $this->taskName = str_replace(
            '_',
            '-',
            Doctrine_Inflector::tableize(str_replace('Fisma_Doctrine_Task_', '', get_class($this)))
        );

        $this->buildAll = new Fisma_Doctrine_Task_BuildAll($this->dispatcher);
    }

}