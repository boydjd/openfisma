<?PHP
/**
 * metainfoController.php
 *
 * metainfo Controller
 *
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once CONTROLLERS . DS . 'PoamBaseController.php';
require_once MODELS . DS . 'blscr.php';

/**
 *  Provide yellow book inquery for meta information such as types, decisions. 
 *
 *  Usually it render a select element.
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class metainfoController extends PoamBaseController
{
    public function init()
    {
        parent::init();
        $ajaxContext = $this->_helper->getHelper('AjaxContext');
        $ajaxContext->addActionContext('list', 'html')
             ->initContext();
    }

    public function listAction()
    {
        $req = $this->getRequest();
        $module = $req->getParam('o');
        $this->view->selected = $req->getParam('value','');
        if( $module == 'system' ) {
            $list = &$this->_system_list;
        }
        if( $module == 'blscr' ) {
            $m = new Blscr();
            $list = $m->getList('class');
            $list = array_keys($list);
            $list = array_combine($list,$list);
        }
        if( in_array($module,array('threat_level',
                                   'cmeasure_effectiveness' )) ){
            $list = array("NONE"=>"NONE","LOW"=>"LOW","MODERATE"=>"MODERATE","HIGH"=>"HIGH");
        }
        if( $module == 'decision' ) {
            $list = array("APPROVED"=>"APPROVED","DENIED"=>"DENIED");
        }
        if( $module == 'type' ) {
   
            $list = array("CAP"=>"(CAP) Corrective Action Plan",
                           "AR"=>"(AR) Accepted Risk",
                           "FP"=>"(FP) False Positive");
            $this->view->selected = nullGet($list[$this->view->selected],'CAP');   
        }
        $this->view->list = $list;
        $this->render();
    }
}
