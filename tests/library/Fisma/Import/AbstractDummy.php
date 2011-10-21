<?php
/**
 ** Dummy class to wrap Fisma_Import_Abstract
 ** @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 ** @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 ** @license    http://www.openfisma.org/content/license GPLv3
 ** @package    Test
 ** @subpackage Test_Library
 **/

class Test_Library_Fisma_Import_AbstractDummy extends Fisma_Import_Abstract
{
    //Publicize everything to test
    public $_errors = array();
    public $_orgSystemId;
    public $_networkId;
    public $_filePath;
    public $_numImported = 0;
    public $_numSuppressed = 0;
    //lazy constructor
    public function __construct($values = null)
    {
        if(isset($values)) {
            parent::__construct($values);
        } else {
            $this->_orgSystemId = 0;
            $this->_networkId = 0;
            $this->_filePath = '';
        }
    }
    public function _setError($err)
    {
        parent::_setError($err);
    }
    public function parse()
    {
        return true;
    }
}
