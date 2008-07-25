<?php
/**
 * ReportController.php
 *
 * Report Controller
 *
 * @package Controller
 * @author     Rayn ryan at sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once CONTROLLERS . DS . 'PoamBaseController.php';
require_once CONTROLLERS . DS . 'RiskAssessment.class.php';
require_once 'Pager.php';

/**
 * Poams Report
 * @package Controller
 * @author     Rayn ryan at sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class ReportController extends PoamBaseController
{

    public function init()
    {
        parent::init();
        $swCtx = $this->_helper->contextSwitch();
        if(!$swCtx->hasContext('pdf')){
            $swCtx->addContext('pdf',array('suffix'=>'pdf',
                    'headers'=>array('Content-Disposition'=>'attachement;filename="export.pdf"', 
                    'Content-Type'=>'application/pdf')) );
        }
        if(!$swCtx->hasContext('xls')){
            $swCtx->addContext('xls',array('suffix'=>'xls') );
        }
    }

    public function preDispatch()
    {
        parent::preDispatch();
        $this->req = $this->getRequest();
        $swCtx = $this->_helper->contextSwitch();
        $swCtx->addActionContext('poam', array('pdf','xls') )
             ->addActionContext('fisma', array('pdf','xls') )
             ->addActionContext('blscr',array('pdf','xls') )
             ->addActionContext('fips',array('pdf','xls') )
             ->addActionContext('prods',array('pdf','xls') )
             ->addActionContext('swdisc',array('pdf','xls') )
             ->addActionContext('total',array('pdf','xls') )
             ->addActionContext('overdue', array('pdf','xls') )
             ->initContext();
    }

    public function fismaAction()
    {
        $req = $this->getRequest();
        $criteria['year']      = $req->getParam('y');
        $criteria['quarter']   = $req->getParam('q');
        $criteria['system_id'] = $system_id = $req->getParam('system');
        $criteria['startdate'] = $req->getParam('startdate');
        $criteria['enddate']   = $req->getParam('enddate');
        $this->view->assign('system_list', $this->_system_list);
        $this->view->assign('criteria',$criteria);
        $date_begin='';
        $date_end='';
        if('search' == $req->getParam('s')
            || 'pdf' == $req->getParam('format')
            || 'xls' == $req->getParam('format')){
            if(!empty($criteria['startdate']) && !empty($criteria['enddate'])){
                $date_begin = new Zend_Date($criteria['startdate'],Zend_Date::DATES);
                $date_end   = new Zend_Date($criteria['enddate'],Zend_Date::DATES);
            }
            if(!empty($criteria['year'])){
                if(!empty($criteria['quarter'])){
                    switch($criteria['quarter']){
                        case 1:
                            $startdate = $criteria['year'].'-01-01';
                            $enddate   = $criteria['year'].'-03-31';
                            break;
                        case 2:
                            $startdate = $criteria['year'].'-04-01';
                            $enddate   = $criteria['year'].'-06-30';
                            break;
                        case 3:
                            $startdate = $criteria['year'].'-07-01';
                            $enddate   = $criteria['year'].'-09-30';
                            break;
                        case 4:
                            $startdate = $criteria['year'].'-10-01';
                            $enddate   = $criteria['year'].'-12-31';
                            break;
                    } 
                }else{
                    $startdate = $criteria['year'].'-01-01';
                    $enddate   = $criteria['year'].'-12-31';
                }
                $date_begin = new Zend_Date($startdate,Zend_Date::DATES);
                $date_end   = new Zend_Date($enddate,Zend_Date::DATES);
            }
            $system_array = array('system_id'=>$system_id);
            $aaw_array    = array('created_date_end'=>$date_begin,
                                  'closed_date_begin'=>$date_end);//or close_ts is null
            $baw_array    = array('created_date_end'=>$date_end,
                                  'est_date_end'=>$date_end,
                                  'actual_date_begin'=>$date_begin,
                                  'action_date_end'=>$date_end);
            $caw_array    = array('created_date_end'=>$date_end,
                                  'est_date_begin'=>$date_end);// and actual_date_begin is null
            $daw_array    = array('est_date_end'=>$date_end,
                                  'actual_date_begin'=>$date_end);//or action_actual_date is null
            $eaw_array    = array('created_date_begin'=>$date_begin,
                                  'created_date_end'=>$date_end);
            $faw_array    = array('created_date_end'=>$date_end,
                                  'closed_date_begin'=>$date_end);//or close_ts is null
            
            $criteria_aaw = array_merge($system_array,$aaw_array);
            $criteria_baw = array_merge($system_array,$baw_array);
            $criteria_caw = array_merge($system_array,$caw_array);
            $criteria_daw = array_merge($system_array,$daw_array);
            $criteria_eaw = array_merge($system_array,$eaw_array);
            $criteria_faw = array_merge($system_array,$faw_array);
            $summary = array('AAW'=>0,'AS'=>0,'BAW'=>0,'BS'=>0,'CAW'=>0,'CS'=>0,'DAW'=>0,'DS'=>0,
                             'EAW'=>0,'ES'=>0,'FAW'=>0,'FS'=>0);
            $summary['AAW'] = $this->_poam->search($this->me->systems,array('count'=>'count(*)'),$criteria_aaw);
            $summary['BAW'] = $this->_poam->search($this->me->systems,array('count'=>'count(*)'),$criteria_baw);
            $summary['CAW'] = $this->_poam->search($this->me->systems,array('count'=>'count(*)'),$criteria_caw);
            $summary['DAW'] = $this->_poam->search($this->me->systems,array('count'=>'count(*)'),$criteria_daw);
            $summary['EAW'] = $this->_poam->search($this->me->systems,array('count'=>'count(*)'),$criteria_eaw);
            $summary['FAW'] = $this->_poam->search($this->me->systems,array('count'=>'count(*)'),$criteria_faw);
            $this->view->assign('summary',$summary);
        }
        $this->render();
    }

    public function poamAction()
    {
        $req = $this->getRequest();
        $params = array( 'system_id'=>'system_id',
                         'source_id'=>'source_id',
                         'type'     =>'type',
                         'year'     =>'year',
                         'status'   =>'status');
        $criteria = $this->retrieveParam($req, $params); 
        $this->view->assign('source_list',$this->_source_list);
        $this->view->assign('system_list',$this->_system_list);
        $this->view->assign('network_list',$this->_network_list);
        $this->view->assign('criteria',$criteria);
        $is_export = $req->getParam('format');
        if('search' == $req->getParam('s') || isset($is_export)){
            $this->_paging_base_path .= '/panel/report/sub/poam/s/search';
            if(isset($is_export)){
                $this->_paging['currentPage']=$this->_pagging['perPage']=null;
            }
            $this->makeUrl($criteria);
            if(!empty($criteria['year'])){
                $criteria['created_date_begin'] = new Zend_Date($criteria['year'],Zend_Date::YEAR);
                $criteria['created_date_end']   = clone $criteria['created_date_begin'];
                $criteria['created_date_end']->add(1,Zend_Date::YEAR);   
                unset($criteria['year']);
            }
            $list = &$this->_poam->search($this->me->systems, array('id',
                                                         'finding_data',
                                                         'system_id',
                                                         'network_id',
                                                         'source_id',
                                                         'asset_id',
                                                         'type',
                                                         'ip',
                                                         'port',
                                                         'status',
                                                         'action_suggested',
                                                         'action_planned',
                                                         'threat_level',
                                                         'action_est_date',
                                                         'count'=>'count(*)') ,$criteria,
                                        $this->_paging['currentPage'],
                                        $this->_paging['perPage']);
            $total = array_pop($list); 
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
            $pager = &Pager::factory($this->_paging);
            $this->view->assign('poam_list', $list);
            $this->view->assign('links', $pager->getLinks());
        }
        $this->render();
    }

    public function overdueAction()
    {
        $req = $this->getRequest();
        $params = array( 'system_id'=>'system_id',
                         'source_id'=>'source_id',
                         'overdue_type'  =>'overdue_type', 
                         'overdue_day'  =>'overdue_day',  
                         'year'     =>'year');
        $criteria = $this->retrieveParam($req, $params); 
        $this->view->assign('source_list',$this->_source_list);
        $this->view->assign('system_list',$this->_system_list);
        $this->view->assign('criteria',$criteria);
        $is_export = $req->getParam('format');
        if('search' == $req->getParam('s') || isset($is_export) ){
            $this->_paging_base_path .= '/panel/report/sub/overdue/s/search';
            if( isset($is_export) ) {
                $this->_paging['currentPage']= $this->_paging['perPage'] = null;
            }
            $this->makeUrl($criteria);
            $this->view->assign('url',$this->_paging_base_path); 
            if( isset($criteria['overdue_type'] ))  {
                $criteria['overdue']['type'] = $criteria['overdue_type'];
            }
            if( isset($criteria['overdue_day'] )) {
                $criteria['overdue']['day'] = $criteria['overdue_day'];
            }
            if(!empty($criteria['year'])){
                $criteria['created_date_begin'] = new Zend_Date($criteria['year'],Zend_Date::YEAR);
                $criteria['created_date_end']   = clone $criteria['created_date_begin'];
                $criteria['created_date_end']->add(1,Zend_Date::YEAR);   
                unset($criteria['year']);
            }

            if(!empty($criteria['overdue'])){
                $date = clone self::$now;
                $date->sub(($criteria['overdue']['day']-1)*30,Zend_Date::DAY);
                $criteria['overdue']['end_date'] = clone $date;
                $date->sub(30,Zend_Date::DAY);
                $criteria['overdue']['begin_date'] = $date;
                if( $criteria['overdue']['day']==5 ) { ///@todo hardcode greater than 120
                    unset($criteria['overdue']['begin_date'] );
                }
            }
            $list = &$this->_poam->search($this->me->systems, array('id',
                                                         'finding_data',
                                                         'system_id',
                                                         'network_id',
                                                         'source_id',
                                                         'asset_id',
                                                         'type',
                                                         'ip',
                                                         'port',
                                                         'status',
                                                         'action_suggested',
                                                         'action_planned',
                                                         'threat_level',
                                                         'action_est_date',
                                                         'count'=>'count(*)') ,$criteria,
                                        $this->_paging['currentPage'],
                                        $this->_paging['perPage']);
            $total = array_pop($list); 
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_paging_base_path}/p/%d";
            $pager = &Pager::factory($this->_paging);
            $this->view->assign('poam_list', $list);
            $this->view->assign('links', $pager->getLinks());
        }
        $this->render();
    }

    public function generalAction()
    {
        $req = $this->getRequest();
        $type = $req->getParam('type','');
        $this->view->assign('type',$type);
        $this->render();
        if(!empty($type) && ('search' == $req->getParam('s'))){
            $REPORT_GEN_BLSCR  = 1;   // NIST Baseline Security Controls Report
            $REPORT_GEN_FIPS   = 2;   // FIPS 199 Category Breakdown
            $REPORT_GEN_PRODS  = 3;   // Products with Open Vulnerabilities
            $REPORT_GEN_SWDISC = 4;   // Software Discovered Through Vulnerability Assessments
            $REPORT_GEN_TOTAL  = 5;   // Total # Systems with Open Vulnerabilitie
            if($REPORT_GEN_BLSCR == $type){
                $this->_forward('blscr');
            }
            if($REPORT_GEN_FIPS == $type) {
                $this->_forward('fips');
            }
            if($REPORT_GEN_PRODS == $type) {
                $this->_forward('prods');
            }
            if($REPORT_GEN_SWDISC == $type) {
                $this->_forward('swdisc');
            }
            if($REPORT_GEN_TOTAL == $type) {
                $this->_forward('total');
            }
        }
    }

    public function blscrAction()
    {
        $db = $this->_poam->getAdapter();
        $system = new system();
        $rpdata = array();
        $query = $db->select()->from(array('p'=>'poams'),array('num'=>'count(p.id)'))
                    ->join(array('b'=>'blscrs'),'b.code = p.blscr_id',array('blscr'=>'b.code'))
                    ->where("b.class = 'MANAGEMENT'")
                    ->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $query->reset();
        $query = $db->select()->from(array('p'=>'poams'),array('num'=>'count(p.id)'))
                    ->join(array('b'=>'blscrs'),'b.code = p.blscr_id',array('blscr'=>'b.code'))
                    ->where("b.class = 'OPERATIONAL'")
                    ->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $query->reset();
        $query = $db->select()->from(array('p'=>'poams'),array('num'=>'count(p.id)'))
                    ->join(array('b'=>'blscrs'),'b.code = p.blscr_id',array('blscr'=>'b.code'))
                    ->where("b.class = 'TECHNICAL'")
                    ->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $this->view->assign('rpdata',$rpdata);
        $this->render();
    }

    public function fipsAction()
    {
        $system = new system();
        $systems = $system->getList(array('name'=>'name','type'=>'type','conf'=>'confidentiality',
                                          'avail'=>'availability','integ'=>'availability'));
        $fips_totals = array();
        $fips_totals['LOW'] = 0;
        $fips_totals['MODERATE'] = 0;
        $fips_totals['HIGH']     = 0;
        $fips_totals['n/a'] = 0;
        foreach($systems as $sid=>&$system){
            if(strtolower($system['conf']) != 'none'){
                $risk_obj = new RiskAssessment($system['conf'],$system['avail'],$system['integ'],null,null,null);
                $fips199 = $risk_obj->get_data_sensitivity();
            }else{
                $fips199 = 'n/a';
            }
            $qry = $this->_poam->select()->from('poams',array('last_update'=>'MAX(modify_ts)'))
                                         ->where('poams.system_id = ?',$sid);
            $result = $this->_poam->fetchRow($qry);
            if(!empty($result)){
                $ret = $result->toArray();
                $system['last_update'] = $ret['last_update'];
            }
            $system['fips'] = $fips199;
            $fips_totals[$fips199] += 1;
            $system['crit'] = $system['avail'];
        }
        $rpdata = array();
        $rpdata[] = $systems;
        $rpdata[] = $fips_totals;
        $this->view->assign('rpdata',$rpdata);
        $this->render();
    }

    public function prodsAction()
    {
        $db = $this->_poam->getAdapter();
        $query = $db->select()->from(array('prod'=>'products'),
                                     array('Vendor'=>'prod.vendor','Product'=>'prod.name',
                                           'Version'=>'prod.version','NumoOV'=>'count(prod.id)'))
                    ->join(array('p'=>'poams'),'p.status IN ("OPEN","EN","UP","ES")',array())
                    ->join(array('a'=>'assets'),'a.id = p.asset_id AND a.prod_id = prod.id',array())
                    ->group("prod.vendor")
                    ->group("prod.name")
                    ->group("prod.version");
        $rpdata = $db->fetchAll($query);
        $this->view->assign('rpdata',$rpdata);
        $this->render();
    }

    public function swdiscAction()
    {
        $db = $this->_poam->getAdapter();                   
        $query = $db->select()->from(array('p'=>'products'),
                                   array('Vendor'=>'p.vendor','Product'=>'p.name',
                                           'Version'=>'p.version'))
                    ->join(array('a'=>'assets'),'a.source = "SCAN" AND a.prod_id = p.id',array());
        $rpdata = $db->fetchAll($query);
        $this->view->assign('rpdata',$rpdata);
        $this->render();
    }
    
    public function totalAction()
    {
        $db = $this->_poam->getAdapter();
        $system = new system();
        $rpdata = array();
        $query = $db->select()->from(array('sys'=>'systems'),array('sysnick'=>'sys.nickname',
                                                                   'vulncount'=>'count(sys.id)'))
                    ->join(array('p'=>'poams'),'p.type IN ("CAP","AR","FP") AND
                           p.status IN ("OPEN","EN","EP","ES") AND p.system_id = sys.id',array())
                    ->join(array('a'=>'assets'),'a.id = p.asset_id',array())
                    ->group("p.system_id");
        $sys_vulncounts = $db->fetchAll($query);
        $sys_nicks = $system->getList('nickname');
        $system_totals = array();
        foreach($sys_nicks as $nickname){
            $system_nick = $nickname;
            $system_totals[$system_nick] = 0;
        }

        $total_open = 0;
        foreach((array)$sys_vulncounts as $sv_row){
            $system_nick = $sv_row['sysnick'];
            $system_totals[$system_nick] = $sv_row['vulncount'];
            $total_open++;
        }
        $system_total_array = array();
        foreach(array_keys($system_totals) as $key){
            $val = $system_totals[$key];
            $this_row = array();
            $this_row['nick'] = $key;
            $this_row['num'] = $val;
            array_push($system_total_array,$this_row);
        }
        array_push($rpdata,$total_open);
        array_push($rpdata,$system_total_array);
        $this->view->assign('rpdata',$rpdata);
        $this->render();
    }

    /**
     *  Batch generate RAFS report per system and all those PDF files would be packed in tgz
     *
     *  It reuses the PDF generation part of RemediationController::rafAction()
     */
    public function rafsAction()
    {
        require_once 'Archive/Tar.php';
        $sid = $this->_req->getParam('system_id');
        $this->view->assign('system_list',$this->_system_list);
        if( !empty($sid) ) {
            $query = $this->_poam->select()->from($this->_poam,array('id') )
                           ->where('system_id=?',$sid)
                           ->where('threat_level IS NOT NULL AND threat_level != \'NONE\'')
                           ->where('cmeasure_effectiveness IS NOT NULL AND 
                                    cmeasure_effectiveness != \'NONE\'');
            $poam_ids = $this->_poam->getAdapter()->fetchCol($query);
            $count = count($poam_ids);
            if( $count > 0 ) {
                $this->_helper->layout->disableLayout(true);
                $fname = tempnam('/tmp/', "RAFs");
                @unlink($fname);
                $rafs = new Archive_Tar($fname,true);
                $this->view->assign('source_list',$this->_source_list);
                $path = $this->_helper->viewRenderer->getViewScript('raf',
                        array('controller'=>'remediation',
                              'suffix'    =>'pdf.tpl') );
                foreach( $poam_ids as $id ) {
                    $poam_detail = &$this->_poam->getDetail($id);
                    $this->view->assign('poam',$poam_detail);
                    $rafs->addString("raf_{$id}.pdf",$this->view->render($path));
                }
                header("Content-type: application/octetstream");
                header('Content-Length: '.filesize($fname));
                header("Content-Disposition: attachment; filename=RAFs.tgz");
                header("Content-Transfer-Encoding: binary");
                header("Expires: 0");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: public");
                echo file_get_contents($fname);
                @unlink($fname);
            }else{
                $this->render();
            }
        }else{
            $this->render();
        }
    }
}
