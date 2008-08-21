<?php

set_include_path(get_include_path() . PATH_SEPARATOR . 'vendor');

require_once 'PHPUnit2/Framework/TestSuite.php';
require_once 'PHPUnit2/TextUI/TestRunner.php';

/**
 * Main test suite for phpUnderControl.
 */
class phpucAllTests
{
    /**
     * Test suite main method.
     *
     * @return void
     */
    public static function main()
    {
        PHPUnit2_TextUI_TestRunner::run( self::suite() );
    }

    /**
     * Creates the phpunit test suite for this package.
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $suite = new PHPUnit2_Framework_TestSuite( 'phpUnderControl - AllTests' );
        //$suite->addTest( phpucDataAllTest::suite() );
        //$suite->addTestSuite( 'phpucConsoleArgsTest' );
        //$suite->addTestSuite( 'phpucCruiseControlTaskTest' );

        return $suite;
    }
}
