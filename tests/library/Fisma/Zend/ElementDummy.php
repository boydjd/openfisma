<?php
class Test_Library_Fisma_Zend_ElementDummy extends Zend_Form_Element
{
    public $readOnly = false;
    public function __construct()
    {
        parent::__construct('sampleElement');
    }    
}
