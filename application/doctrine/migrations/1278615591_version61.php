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
 * Load metadata for security controls and catalogs from YAML fixture files
 * 
 * @codingStandardsIgnoreFile
 * 
 * @package Migration
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version61 extends Doctrine_Migration_Base
{
    /**
     * Load security control metadata from YAML fixture files
     * 
     * This is a slight pain. The easiest way to do it is to copy the YAML files that contain the data into a new,
     * separate folder by themselves. Then you can just load that folder. Loading the files one at a time will cause
     * errors because of the references between the files.
     */
    public function up()
    {
        // rebuild the models so we can use them to load the data
        $configuration = Zend_Registry::get('doctrine_config');
        $modelOptions = $configuration['generate_models_options'];
        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'), $modelOptions);

        $tempDir = Fisma::getPath('temp') . '/version59data.' . uniqid();

        if (!mkdir($tempDir)) {
            throw new Fisma_Zend_Exception("Could not create temp directory: $tempDir");
        }

        $basePath = Fisma::getPath('fixture');
        
        if (!(   copy($basePath . '/SecurityControl.yml', $tempDir . '/SecurityControl.yml')
              && copy($basePath . '/SecurityControlCatalog.yml', $tempDir . '/SecurityControlCatalog.yml')
              && copy($basePath . '/SecurityControlEnhancement.yml', $tempDir . '/SecurityControlEnhancement.yml'))) {
            throw new Fisma_Zend_Exception("Could not copy fixtures into temp directory: $tempDir");
        }
        
        Doctrine::loadData($tempDir);
        
        unlink($tempDir . '/SecurityControl.yml');
        unlink($tempDir . '/SecurityControlCatalog.yml');
        unlink($tempDir . '/SecurityControlEnhancement.yml');
        
        rmdir($tempDir);
    }
    
    /**
     * Delete security control metadata
     */
    public function down()
    {
        $deleteCategoryQuery = Doctrine_Query::create()->delete('SecurityControl');
        $deleteCategoryQuery->execute();
        
        $deleteStepsQuery = Doctrine_Query::create()->delete('SecurityControlCatalog');
        $deleteStepsQuery->execute();

        $deleteWorkflowQuery = Doctrine_Query::create()->delete('SecurityControlEnhancement');
        $deleteWorkflowQuery->execute();
    }
}
