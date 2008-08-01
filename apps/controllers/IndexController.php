<?php
/**
 * IndexController.php
 *
 * Index Controller
 *
 * @package Controller
 * @author     chriszero chriszero at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/
class IndexController extends Zend_Controller_Action
{
    /**
     * The default action - show the home page
     */
    public function indexAction ()
    {    
        $this->_forward('index','Panel');
    }
}
