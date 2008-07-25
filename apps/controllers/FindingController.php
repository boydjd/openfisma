<?PHP
/**
 * FindingController.php
 *
 * Finding Controller
 *
 * @package Controller
 * @author     Ryan rayn at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */

require_once(CONTROLLERS . DS . 'PoamBaseController.php');
require_once(MODELS . DS . 'finding.php');
require_once(MODELS . DS . 'asset.php');
require_once(MODELS . DS . 'product.php');
require_once(MODELS . DS . 'plugin.php');
require_once('Pager.php');
define('TEMPLATE_NAME', "OpenFISMA_Injection_Template.xls"); 

/**
 * Finding Controller
 * Create new Finding 
 * @package Controller
 * @author     Ryan rayn at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */

class FindingController extends PoamBaseController
{
    /**
        Provide searching capability of findings
        Data is limited in legal systems.
     */
    protected function _search($criteria)
    {
        $fields = array('id',
                       'legacy_finding_id',
                       'ip',
                       'port',
                       'status',
                       'source_id',
                       'system_id',
                       'discover_ts',
                       'count'=>'count(*)');
        if( $criteria['status'] == 'REMEDIATION' ) {
            $criteria['status'] = array('OPEN','EN','EP','ES');
        }
        $result = $this->_poam->search($this->me->systems, $fields, $criteria, 
                    $this->_paging['currentPage'],$this->_paging['perPage']);
        $total = array_pop($result);

        $this->_paging['totalItems'] = $total ;
        $pager = &Pager::factory($this->_paging);

        $this->view->assign('findings',$result);
        $this->view->assign('links',$pager->getLinks());
        $this->render('search');
    }

    /**
       Get finding detail infomation
    */
    public function viewAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id',0);
        assert($id);

        $this->view->assign('id',$id);

        if(isAllow('finding','read')){
            $sys = new System();
            $poam = new Poam();
            $detail = $poam->find($id)->current();
            $this->view->finding = $poam->getDetail($id);
            $this->view->finding['system_name'] = $this->_system_list[$this->view->finding['system_id']];
            $this->render();
        } else {
            /// @todo Add a new Excption page to indicate Access denial
            $this->render();
        }
    }

    /**
      Edit finding infomation
    */
    public function editAction(){
        $req = $this->getRequest();
        $id = $req->getParam('id');
        assert($id);
        $finding = new Finding();
        $do = $req->getParam('do');
        if($do == 'update'){
           $status = $req->getParam('status');
           $db = Zend_Registry::get('db');
           $result = $db->query("UPDATE FINDINGS SET finding_status = '$status' WHERE finding_id = $id");
           if($result){
               $this->view->assign('msg',"Finding updated successfully");
           }
           else {
               $this->view->assign('msg',"Failed to update the finding");
           }
        }
        $this->view->assign('act','edit');
        $this->_forward('view','Finding');
    }

    /**
     *  Spreadsheet upload
     *
     *  The spreadsheet should be a CSV file in fact. It parse the valid data and leave the
     *  remaining to the user.
    */
    public function injectionAction(){
        $this->_helper->actionStack('header','Panel');
        if(isAllow('finding','create')){
            $csvFile = isset($_FILES['csv'])?$_FILES['csv']:array();
            if(!empty($csvFile)){
                if($csvFile['size'] < 1 ){
                    $err_msg = 'Error: Empty file.';
                }else{
                    if($csvFile['size'] > 1048576 ){
                        $err_msg = 'Error: File is too big.';
                    }
                    if(preg_match('/\x00|\xFF/',file_get_contents($csvFile['tmp_name']))){
                        $err_msg = 'Error: Binary file.';
                    }
                    if($csvFile['error']){
                        $err_msg = 'Encountered an unknown error while processing the file';
                    }
                }
            }
            if(!empty($err_msg)){
                $this->message($err_msg,self::M_WARNING);
                $this->render();
                return;
            }
            if(!empty($csvFile)){
                $fileName = $csvFile['name'];
                $tempFile = $csvFile['tmp_name'];
                $fileSize = $csvFile['size'];

                $failedArray = $succeedArray = array();
                $handle = fopen($tempFile,'r');
                $data = fgetcsv($handle,1000,",",'"'); //skip the first line
                $data = fgetcsv($handle,1000,",",'"'); //skip the second line
                $row = 0;
                while($data = fgetcsv($handle,1000,",",'"')) {
                    if(implode('',$data)!=''){
                        $row++;
                        $ret = $this->insertCsvRow($data);
                        if(empty($ret) ){
                            $failedArray[] = $data;
                        }else{
                            $succeedArray[] = $data;
                        }
                    }
                }
                fclose($handle);
                $summary_msg = "You have uploaded a CSV file which contains $row line(s) of data.<br />";
                if(count($failedArray) > 0){
                    $temp_file = 'temp/csv_'.date('YmdHis').'_'.rand(10,99).'.csv';
                    $fp = fopen($temp_file,'w');
                    foreach($failedArray as $fail) {
                        fputcsv($fp,$fail);
                    }
                    fclose($fp);
                    $summary_msg .= count($failedArray)." line(s) cannot be parsed successfully. This is likely due to an unexpected datatype or the use of a datafield which is not currently in the database. Please ensure your csv file matches the data rows contained <a href='/$temp_file'>here</a> in the spreadsheet template. Please update your CSV file and try again.<br />";
                }
                if(count($succeedArray)>0){
                    $summary_msg .= count($succeedArray)." line(s) parsed and injected successfully. <br />";
                }
                if(count($succeedArray)==$row){
                    $summary_msg .= " Congratulations! All of the lines contained in the CSV were parsed and injected successfully.";
                }
                $this->view->assign('error_msg',$summary_msg);
            }
            $this->render();
        }
    }

    /**
     *  Create a finding manually
    */
    public function createAction()
    {
        $req = $this->getRequest();
        $do = $req->getParam('is','view');
        if("new" == $do){
            try{
                $data = array();
                $data['source_id'] = $req->getParam('source');
                $data['asset_id'] = $req->getParam('asset_list');
                if(!empty($data['asset_id'])){
                    $asset = new asset();
                    $ret = $asset->find($data['asset_id'])->toArray();
                    $data['system_id'] = $ret[0]['system_id'];
                }
                $data['status'] = 'NEW';
                $discover_ts = new Zend_Date($req->getParam('discovereddate'),Zend_Date::DATES);
                $data['discover_ts'] = $discover_ts->toString("Y-m-d");
                $data['finding_data'] = $req->getParam('finding_data');

                $data['create_ts'] = self::$now->toString("Y-m-d H:i:s");
                $data['created_by'] = $this->me->id;

                $this->_poam->insert($data);
                $message="Finding created successfully";
                $model=self::M_NOTICE;
            }catch(Zend_Exception $e){
                $message= "Failed to create the finding";
                $model=self::M_WARNING;
            }
            $this->message($message,$model);
        }

        $this->view->assign('system',$this->_system_list);
        $this->view->assign('source',$this->_source_list);
        $this->render();
    }
    
    /**
     *  Delete findings
    */
    public function deleteAction(){
        $req = $this->getRequest();
        $post = $req->getPost();
        $errno = 0;
        $successno = 0;
        $poam = new poam();
        foreach($post as $key=>$id){
            if(substr($key,0,3) == 'id_'){
                $res = $poam->update(array('status'=>'DELETED'),'id = '.$id);
                if($res){
                    $successno++;
                }else{
                    $errno++;
                }
            }
        }
        $msg = 'Delete '.$successno.' Findings Successfully,'.$errno.' Failed!';
        $this->message($msg,self::M_NOTICE);
        $this->_forward('searchbox','finding',null,array('s'=>'search'));

    }

    /** 
     *  Insert a row of data into database. 
     */
    protected function insertCsvRow($row){
        $asset = new asset();
        $poam = new poam();
        if (!is_array($row) || (count($row)<7)){
            return false;
        }
        if (strlen($row[3])>63 || (!is_numeric($row[4]) && !empty($row[4]))){
            return false;
        }
        if (in_array('', array($row[0],$row[1],$row[2],$row[5],$row[6]))){
            return false;
        }
        $row[2] = date('Y-m-d',strtotime($row[2]));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$row[2])){
            return false;
        }
        $db = Zend_Registry::get('db');
        $query = $db->select()->from('systems','id')->where('nickname = ?',$row[0]);
        $result = $db->fetchRow($query);
        $row[0] = !empty($result)?$result['id']:false;
        $query->reset();
        $query = $db->select()->from('networks','id')->where('nickname = ?',$row[1]);
        $result = $db->fetchRow($query);
        $row[1] = !empty($result)?$result['id']:false;
        $query->reset();
        $query = $db->select()->from('sources','id')->where('nickname = ?',$row[5]);
        $result = $db->fetchRow($query);        
        $row[5] = !empty($result)?$result['id']:false;
        if (!$row[0] || !$row[1] || !$row[5]) {
            return false;
        }
        $asset_name = ':'.$row[3].':'.$row[4];
        $query = $asset->select()->from($asset,'id')
                                 ->where('system_id = ?',$row[0])
                                 ->where('network_id = ?',$row[1])
                                 ->where('address_ip = ?',$row[3])
                                 ->where('address_port = ?',$row[4]);
        $result = $asset->fetchRow($query);
        if(!empty($result)){
            $data = $result->toArray();
            $asset_id = $data['id'];
        }else{
            $asset_data = array('name'=>$asset_name,'create_ts'=>$row[2],'source'=>'SCAN',
                                'system_id'=>$row[0],'network_id'=>$row[1],'address_ip'=>$row[3],
                                'address_port'=>$row[4]);
            $asset_id = $asset->insert($asset_data);
        }
        $poam_data = array('asset_id'=>$asset_id,'source_id'=>$row[5],'system_id'=>$row[0],
                              'status'=>'NEW','create_ts'=>self::$now->toString('Y-m-d h:i:s') ,
                              'discover_ts'=>$row[2],'finding_data'=>$row[6]);
        $ret =  $poam->insert($poam_data);
        return $ret;
    }

    /** 
     * Downloading a excel file which is used as a template for uploading findings.
     * systems, networks and sources are extracted from the database dynamically.
    */
    public function templateAction() 
    {
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        $contextSwitch->addContext('xls', array(
                    'suffix'=>'xls',
                    'headers'=>array('Content-type'=>'application/vnd.ms-excel',
                                     'Content-Disposition'=>'filename='.TEMPLATE_NAME)
                ));
        $contextSwitch->addActionContext('template', 'xls')->initContext('xls');

        $resp = $this->getResponse();

        $src = new System();
        $this->view->systems = $src->getList('nickname') ;
        $src = new Network();
        $this->view->networks = $src->getList('nickname');
        $src = new Source();
        $this->view->sources = $src->getList('nickname');
        $this->render();
    }

    /** 
     *  Scan result import
     */
    public function importAction()
    {
        $this->_helper->actionStack('header','Panel');
        $req = $this->getRequest();
        $db = $this->_poam->getAdapter();
        $plugin = new Plugin();
        $plugin_list = $plugin->getList('name');
        $this->view->assign('plugin_list',$plugin_list);
        $this->view->assign('system_list',$this->_system_list);
        $this->view->assign('network_list',$this->_network_list);
        $this->view->assign('source_list',$this->_source_list);
        $msg = '';
        if(isset($_FILES['upload_file'])){
            $plugin_id = $req->getParam('plugin');
            $ret = $plugin->find($plugin_id)->toArray();
            if(!empty($ret)){
                $plugin_class = $ret[0]['classname'];
            }else{
                $this->message('post plugin is not found',self::M_WARNING);
                $this->render();
                return;
            }
            if($_FILES['upload_file']['type'] != 'text/xml'){
                $this->message('It is not xml file',self::M_WARNING);
                $this->render();
                return;
            }
            require_once( CONTROLLERS . DS . 'components' . DS . 'import' . DS . 'interface.php');
            require_once( CONTROLLERS . DS . 'components' . DS . 'import' . DS . $plugin_class.'.php');
            require_once('parseXml.class.php');
            $assets['system_id'] = $req->getParam('system');
            $assets['source_id'] = $req->getParam('source');
            $assets['network_id'] = $req->getParam('network');
            $tmpfile = $_FILES['upload_file']['tmp_name'];
            $ret = null;
            $parser = new $plugin_class();
            if( $parser->isValid($tmpfile) ) {
                $xmlObj    = new XmlToArray(file_get_contents($tmpfile)); 
                $xmlData = $xmlObj->createArray();
                $unified_data = $parser->parse($xmlData);
                foreach($unified_data as $k=>$v){
                    if('product' == $k && !empty($v['meta'])){
                        $product = new product();
                        $qry = $product->select()->from('products',array('id'=>'id'))
                                        ->where('meta = ?',$v['meta'])
                                        ->where('vendor = ?',$v['vendor'])
                                        ->where('version = ?',$v['version']);
                        $ret = $db->fetchRow($qry);
                        if(!empty($ret)){
                            $prod_id = $ret['id'];
                        }else{
                            $prod_id = $product->insert($v);
                        }
                    }
                    if('asset' == $k && !empty($v['name'])){
                        $asset = new asset();
                        $v['prod_id'] = isset($prod_id)?$prod_id:'';
                        $v['system_id'] = $assets['system_id'];
                        $v['network_id'] = $assets['network_id'];
                        $v['create_ts'] = self::$now->toString('Y-m-d H:i:s');
                        $qry = $asset->select()->from('assets',array('id'=>'id'))
                                      ->where('prod_id = ?',$v['prod_id'])
                                      ->where('name = ?',$v['name']);
                        $ret = $db->fetchRow($qry);
                        if(!empty($ret['id'])){
                            $asset_id = $ret['id'];
                        }else{
                            $asset_id = $asset->insert($v);
                        }
                    }
                    if('blscr' == $k && !empty($v['code'])){
                        $blscr = new blscr();
                        $blscr_id = $blscr->insert($v);
                    }
                    if('poam' == $k){
                        foreach($v['finding_data'] as $row){
                            if(!empty($row)){
                                $data = array('asset_id'=>$asset_id,'source_id'=>$assets['source_id'],
                                              'system_id'=>$assets['system_id'],
                                              'blscr_id'=>isset($blscr_id)?$blscr_id:'',
                                              'create_ts'=>self::$now->toString('Y-m-d H:i:s'),
                                              'discover_ts'=>$v['discover_ts'],'created_by'=>$this->me->id,
                                              'status'=>'NEW','finding_data'=>$row);
                                $poam_id[] = $this->_poam->insert($data);
                            }
                        }
                    }
                    if('vulnerabilities' == $k && !empty($v)){
                        $severity_int = array('Low'=>20,'Medium'=>'55','High'=>85,'Default'=>50);
                        foreach($v['description'] as $i=>$row){
                            if(!empty($row)){
                                $qry = $db->select()->from('vulnerabilities', array('id'=>'seq'));
                                if(!empty($v['cve'][$i])){
                                    $vuln_data['type'] = 'CVE';
                                    $vuln_data['severity'] = $severity_int[$v['severity'][$i]];
                                    $qry->where('type = ?','CVE')
                                        ->where('severity = ?',$severity_int[$v['severity'][$i]]);
                                }else{
                                    if(!empty($v['sbv'][$i])){
                                        $vuln_data['type'] = 'APP';
                                        $vuln_data['severity'] = $severity_int[$v['severity'][$i]];
                                        $qry->where('type = ?','APP')
                                            ->where('severity = ?',$severity_int[$v['severity'][$i]]);
                                    }
                                }
                                $qry->where('description = ?',$row)
                                    ->where('solution = ?',$v['solution'][$i]);
                                $ret = $db->fetchRow($qry);
                                if(!empty($ret)){
                                    $vuln_id[] = $ret['id'];
                                }else{
                                    $vuln_data['description'] = $row;
                                    $vuln_data['solution'] = $v['solution'][$i];
                                    $db->insert('vulnerabilities',$vuln_data);
                                    unset($vuln_data);
                                    $vuln_id[] = $db->LastInsertId();
                                }
                            }
                        }
                    }
                }
                foreach($poam_id as $i=>$id){
                    $data = array('poam_id'=>$id,'vuln_seq'=>$vuln_id[$i],'vuln_type'=>'APP');
                    $db->insert('poam_vulns',$data);
                }
                foreach($unified_data['vulnerabilities']['cve'] as $i=>$v){
                    if(!empty($v)){
                        $poam_vulns = array('poam_id'=>$poam_id[$i],'vuln_seq'=>$v,'vuln_type'=>'CVE');
                        $db->insert('poam_vulns',$poam_vulns);
                    }
                }
                $msg = "Injection complete.";
                $this->message($msg,self::M_NOTICE);
            }else{
                $msg = 'Upload file is not valid';
                $this->message($msg,self::M_WARNING);
            }
        }
        $this->render();
    }
}
