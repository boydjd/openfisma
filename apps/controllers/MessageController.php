<?php
/**
 * MessageController.php
 *
 * Message Controller
 *
 * @package Controller
 * @author     Ryan   ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Controller/Action.php';

/**
 * show result message when handling some action
 *
 * @package Controller
 * @author     Ryan   ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class MessageController extends Zend_Controller_Action
{
    const M_NOTICE = 'notice';
    const M_WARNING= 'warning';

    /**
     *  Routine to show messages to UI by ajax
     */
    public function message( $msg , $model ){
        assert(in_array($model, array(self::M_NOTICE, self::M_WARNING) ));
        $this->view->msg = $msg;
        $this->view->model= $model;
        $this->_helper->viewRenderer->renderScript('message.tpl');
    }
}
?>
