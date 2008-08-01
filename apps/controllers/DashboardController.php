<?PHP
/**
 * DashboardController.php
 *
 * Dashboard Controller
 *
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Controller/Action.php';
require_once CONTROLLERS . DS . 'SecurityController.php';
require_once MODELS . DS . 'poam.php';
require_once MODELS . DS . 'system.php';

/**
 * DashboardController is responsible for all dashboard creation
 *
 * The dashboard works in this way:
 *      The view displays the flash chart plugins, which request individual XML data files
 *      from the server and in the end display pie, bars chart.
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class DashboardController extends SecurityController
{
    protected $_poam = null;
    protected $_all_systems = null;

    function init()
    {
        parent::init();
        $sys = new System();
        $this->_all_systems = $this->me->systems;
    }

    function preDispatch()
    {
        parent::preDispatch();
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        $contextSwitch->addActionContext('totalstatus', 'xml')
                      ->addActionContext('totaltype','xml')
                      ->initContext();
        if( !isset($this->_poam) ){
            $this->_poam = new Poam();
        }
    }


    public function indexAction()
    {
        $open_count = $this->_poam->search($this->_all_systems,
                        array('count'=>'count(*)'),
                        array('status'=>array('OPEN')));
        $en_count = $this->_poam->search($this->_all_systems,
                        array('count'=>'count(*)'),
                        array('status'=>'EN','est_date_begin'=> parent::$now ));
        $eo_count = $this->_poam->search($this->_all_systems, 
                        array('count'=>'count(*)'),
                        array('status'=>'EN',
                              'est_date_end'=> parent::$now ));
        $total = $this->_poam->search($this->_all_systems, array('count'=>'count(*)'));
        $alert = array();
        $alert['TOTAL'] = $total;
        $alert['OPEN'] = $open_count;
        $alert['EN'] = $en_count;
        $alert['EO'] = $eo_count;
        $url=burl().'/panel/remediation/sub/searchbox/s/search/status/';
        $this->view->url=$url;
        $this->view->alert = $alert;
        $this->render();
    }

    public function totalstatusAction()
    {
        $poam = $this->_poam;
        $req = $this->getRequest();
        $type = $req->getParam('type','pie');

        if( !in_array($type, array('3d column','pie')) ) {
            $type = 'pie';
        }
        $ret = $poam->search($this->_all_systems,
                        array('count'=>'status', 'status'));
        $eo_count = $poam->search($this->_all_systems, 
                        array('count'=>'count(*)'),
                        array('status'=>'EN',
                              'est_date_end'=> parent::$now ));
        $this->view->summary = array( 'NEW'=>0,'OPEN'=>0,'EN'=>0,'EP'=>0,
                                      'ES'=>0,'CLOSED'=>0) ;
        foreach($ret as $s){
            $this->view->summary["{$s['status']}"] = $s['count'];
        }
        $this->view->summary["EO"] = $eo_count;
        $this->view->chart_type = $type;
        $this->render($type);
    }

    public function totaltypeAction()
    {
        $ret = $this->_poam->search($this->_all_systems, array('count'=>'type', 'type') );
        $this->view->summary = array('NONE'=>0,'CAP'=>0,'FP'=>0,'AR'=>0);
        foreach($ret as $s){
            $this->view->summary["{$s['type']}"] = $s['count'];
        }
        $this->render();
    }

}

?>
