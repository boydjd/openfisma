<?php
/**
 * PoamBaseController.php
 *
 * PoamBase Controller
 *
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once MODELS . DS . 'poam.php';
require_once MODELS . DS . 'system.php';
require_once MODELS . DS . 'source.php';
require_once MODELS . DS . 'network.php';
require_once CONTROLLERS . DS . 'SecurityController.php';

/**
 *  A basic business unit for poam centeric controllers
 *
 *  It hold some common works for those controllers, such as paging initialization, 
 *  regular modules initialization.
 * @package Controller
 * @author     Xhorse   xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class PoamBaseController extends SecurityController 
{
    protected $_system_list =null;
    protected $_source_list =null;
    protected $_network_list =null;
    protected $_poam =null;
    
    /**
     * The requestor object. 
     * To save the labor to initialize it every time.
     */
    protected $_req = null;
    
    /**
     * Param names that need to be sanitized
     */
    protected $_rawData = null;
    /**
     * rules to sanity check the data
     */
    protected $_validator = null;
    protected $_paging = array('mode'        =>'Sliding',
                             'append'      =>false,
                             'urlVar'      =>'p',
                             'path'        =>'',
                             'currentPage' =>1,
                             'perPage'     =>20);

    public function init()
    {
        parent::init();
        $this->_poam = new Poam();
        $src = new Source();
        $net = new Network();
        $sys = new System();
        
        $this->_source_list  = $src->getList('name');
        $tmp_list = $sys->getList(array('name','nickname'),$this->me->systems, 'nickname');
        $this->_network_list = $net->getList('name');
        foreach($tmp_list as $k => $v){
            $this->_system_list[$k] = "({$v['nickname']}) {$v['name']}";
        }
    }

    /**
     * To validate input(post, get) data and pass them to error handler when voilation ocurrs.
     * 
     * Override this function to get different behavior
     * 
     * @return Zend_Validator validate result
     */
    public function validate()
    {
        return null;
    }
    
    public function preDispatch()
    {
        parent::preDispatch();
        $this->_req = $this->getRequest();
        $req = $this->_req;
        $this->_paging_base_path = $req->getBaseUrl();
        $this->_paging['currentPage'] = $req->getParam('p',1);
        $input = $this->validate();
        if(isset($input) && ($input->hasInvalid()||$input->hasMissing())  ) {
            $this->_forward('inputerror','error',null,array('inputerror'=>$input->getMessages()));
        }
    }

    public function makeUrl($criteria)
    {
        foreach($criteria as $key=>$value){
            if(!empty($value) ){
                if($value instanceof Zend_Date){
                    $this->_paging_base_path .='/'.$key.'/'.$value->toString('Ymd').'';
                }else{
                    $this->_paging_base_path .='/'.$key.'/'.$value.'';
                }
            }
        }
    }
}
