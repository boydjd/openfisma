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

require_once(realpath(dirname(__FILE__) . '/../Case/Unit.php'));

/**
 * Tests for the Fisma facade class
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma
 */
class Test_Library_Fisma extends Test_Case_Unit
{
    /**
     * Test the ability to globally set the enabled state of a Fisma_Doctrine_Record_Listener.
     * 
     * This covers the logic for loading listeners if they haven't been loaded when the enabled state is set.
     * 
     * This test isn't ideal because it relies on knowledge of a known Fisma_Doctrine_Record_Listener subclass in order
     * to test the logic in the Fisma class, but it's a necessary evil to get coverage of this rather important
     * function.
     *
     * @return void
     */
    public function testGloballySetListenerEnabledState()
    {
        Fisma::setListenerEnabled(true);
        $this->assertTrue(IndexListener::getEnabled());
        
        Fisma::setListenerEnabled(false);
        $this->assertFalse(IndexListener::getEnabled());
    }
    
    /**
     * Test the getListenerEnabled() wrapper
     *
     * This test also relies on the knowledge of the IndexListener static object
     *
     * @return void
     */
    public function testGetListenerEnabledState()
    {
        $this->assertEquals(IndexListener::getEnabled(), Fisma::getListenerEnabled());
    }

    /**
     * Test set/get notification state
     *
     * not ideal but can't get access to private member
     *
     * @return void
     */
    public function testNotificationEnabled()
    {
        Fisma::setNotificationEnabled(true);
        $this->assertTrue(Fisma::getNotificationEnabled());
        Fisma::setNotificationEnabled(false);
        $this->assertFalse(Fisma::getNotificationEnabled());
    }

    /**
     * Test set app config array
     *
     * @return void
     */
    public function testSetAppConfig()
    {
        $sampleArray = array('environment' => 'production', 'version' => '0.0.0.1');
        Fisma::setAppConfig($sampleArray);
        $this->assertEquals($sampleArray, Fisma::$appConf);
    }

    /**
     * Test htmlentities wrapper
     *
     * @return void
     */
    public function testHtmlEntities()
    {
        $originalString = '<c> + a&b';
        $htmlFriendlyString = '&lt;c&gt; + a&amp;b';
        $this->assertEquals($htmlFriendlyString, Fisma::htmlentities($originalString));
    }

    /**
     * Test configuration() method
     *
     * @return void
     * @backupStaticAttributes disabled
     */
    public function testConfiguration()
    {
        try {
            Fisma::configuration();
            $this->markTestSkipped('This test must be run alone without static backups.');
        } catch (Fisma_Zend_Exception $e) {
            $sampleConfig=new Fisma_Configuration_Array();
            Fisma::setConfiguration($sampleConfig, true);
            $this->assertEquals($sampleConfig, Fisma::configuration());
            $this->assertNotNull(Fisma::configuration());
        }
    }

    /**
     * Test debug() method
     *
     * @return void
     * @backupStaticAttributes disabled
     */
    public function testDebug()
    {
        try {
            Fisma::debug();
            $this->markTestSkipped('This test must be run alone without static backups.');
        } catch (Fisma_Zend_Exception $e) {
            Fisma::initialize(3);
            $this->assertFalse(Fisma::debug());
        
            Fisma::setAppConfig(array('debug' => 1));
            $this->assertTrue(Fisma::debug());
        }
    }

    /**
     * Test getPath() method
     *
     * knowing $_applicationPath['application']='application'
     * and the structure of the array $_appConf['includePaths']
     *
     * @return void
     * @backupStaticAttributes disable
     */
    public function testGetPath()
    {
        $builtinKey='application';
        $builtinPath='application';

        try {
            //use the builtinKey to avoid "No path found for key" exception
            Fisma::getPath($builtinKey);
            $this->markTestSkipped('This test must be run alone without static backups.');
        } catch(Fisma_Zend_Exception $e) {
            Fisma::initialize(3);

            //use the assertContains instead of assertEquals to cancel unknown environment root directory
            $this->assertContains($builtinPath, Fisma::getPath($builtinKey));

            $userdefinedKey='unittest';
            $userdefinedPath='tests/unit';
            Fisma::setAppConfig(array('includePaths' => array($userdefinedKey => $userdefinedPath)));
            $this->assertEquals($userdefinedPath, Fisma::getPath($userdefinedKey));
        }
    }

    /**
     * Test getPath() method with undefined key for exception
     *
     * @return void
     */
    public function testGetUnknownPath()
    {
        Fisma::initialize(3);
        $undefinedKey='rawsource';
        $this->setExpectedException('Fisma_Zend_Exception', 'No path found for key: "'.$undefinedKey.'"');
        Fisma::getPath($undefinedKey);
    }

    /**
     * Test setConfiguration() method
     *
     * use configuration() method to access private member $_configuration
     * knowing Fisma_Configuration_Array class implement Fisma_Configuration_Interface
     *
     * @return void
     */
    public function testSetConfiguration()
    {
        $sampleConfig=new Fisma_Configuration_Array();
        Fisma::setConfiguration($sampleConfig, true);
        $this->assertEquals($sampleConfig, Fisma::configuration());

        $sampleConfig2=new Fisma_Configuration_Array();
        $sampleConfig2->setConfig('sampleKey', 'sampleValue');        
        Fisma::setConfiguration($sampleConfig2, true);
        $this->assertEquals($sampleConfig2, Fisma::configuration());

        $this->setExpectedException('Fisma_Zend_Exception', 'Configuration already exists');
        Fisma::setConfiguration($sampleConfig, false);
    }
}
