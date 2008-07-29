<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php'; 
require_once 'Testing/Selenium.php'; 

class WebTest extends PHPUnit_Extensions_SeleniumTestCase

{    
	protected function setUp()    
	{
		$this->setBrowser('*firefox /usr/lib/firefox/firefox-bin');
		$this->setBrowserUrl('http://www.example.com/');
	}     
	
	public function testTitle()    
	{
		$this->open('http://www.example.com/');
        $this->assertTitleEquals('Example Web Page');    
	}
}

?>