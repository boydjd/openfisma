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

require_once(realpath(dirname(__FILE__) . '/../../../../../FismaUnitTest.php'));

/**
 * Tests for the AttachArtifacts behavior generator
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Doctrine_Behavior_AttachArtifacts_Generator extends Test_FismaUnitTest
{
    /**
     * Test the blacklist function against a mimetype
     * 
     * @expectedException Fisma_Zend_Exception_User
     */
    public function testMimeTypeBlackList()
    {
        $incident = new Incident();
        
        $file = array('name' => 'Artifact.txt', 'type' => 'application/x-javascript');
        
        $comment = "Inane comment goes here";

        $incident->getArtifacts()->attach($file, $comment);
    }

    /**
     * Test the blacklist function against a file extension
     * 
     * @expectedException Fisma_Zend_Exception_User
     */
    public function testExtensionBlackList()
    {
        $incident = new Incident();
        
        $file = array('name' => 'dangerous.exe');
        
        $comment = "Inane comment goes here";
        
        $incident->getArtifacts()->attach($file, $comment);
    }
}
