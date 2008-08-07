<?php
    if (!defined('DS')) {
        define('DS', DIRECTORY_SEPARATOR);
    }

    if (!defined('URL_BASE') ){
        define('URL_BASE', 'http://192.168.0.115/of/zfentry.php/');
    }

    if (!defined('ROOT')) {
        define('ROOT', dirname(dirname(__FILE__)));
    }
    
    require_once( ROOT . DS . 'paths.php');
    require_once( APPS . DS . 'basic.php');
    import(LIBS, VENDORS, VENDORS.DS.'Pear');

    require_once 'Zend/Controller/Front.php';
    require_once 'Zend/Layout.php';
    require_once 'Zend/Registry.php';
    require_once 'Zend/Config.php';
    require_once 'Zend/Config/Ini.php';
    require_once 'Zend/Db.php';
    require_once 'Zend/Db/Table.php';
    require_once MODELS . DS . 'Abstract.php';
    require_once 'Zend/Controller/Plugin/ErrorHandler.php';
    require_once ( CONFIGS . DS . 'setting.php');
    $target = $config->database->toArray();
    $target['params']['dbname'] = 'legacy_fisma';
    $srcconfig = new Zend_Config($target);//from setting.php
    Zend_Registry::set('legacy_datasource', $srcconfig); 

    $table_name=  array(
         //     'BLSCR', 
              'NETWORKS', 
              'PRODUCTS',
              'FINDING_SOURCES', 
              'SYSTEM_GROUP_SYSTEMS','SYSTEMS',
              'SYSTEM_GROUPS',
              'ROLES',
              'FUNCTIONS','ROLE_FUNCTIONS',
              'ASSETS',
              'USER_ROLES',
              'USERS',
              'USER_SYSTEM_ROLES',
              'FINDINGS',
              'POAMS',
              'VULN_PRODUCTS',
              'VULNERABILITIES',
              'FINDING_VULNS',
              'POAM_EVIDENCE', 
              'POAM_COMMENTS',
              'AUDIT_LOG'
              );

    $db_target = Zend_DB::factory(Zend_Registry::get('datasource'));
    $db_src    = Zend_DB::factory(Zend_Registry::get('legacy_datasource'));

    $clean_table_name=array('roles','functions','role_functions');
    foreach($clean_table_name as $table)
    {
        $db_target->delete($table);
    }
    
    $delta = 1000;
    echo "start to migrate \n";
    $sql = "CREATE TABLE IF NOT EXISTS poam_tmp (
`legacy_finding_id` int(10) unsigned NOT NULL default '0',
`asset_id` int(10) unsigned NOT NULL default '0',
`source_id` int(10) unsigned NOT NULL default '0',
`system_id` int(10) unsigned NOT NULL default '0',
`blscr_id` varchar(5) default NULL,
`create_ts` datetime NOT NULL default '0000-00-00 00:00:00',
`discover_ts` datetime NOT NULL default '0000-00-00 00:00:00',
`status` enum('NEW','OPEN','EN','EP','ES','CLOSED','DELETED') NOT NULL default 'NEW',
`finding_data` text NOT NULL) ";
    $db_target->query($sql);
    foreach( $table_name as $table ) 
    {
        echo "$table\n";

        try{

        if($table == 'POAMS'){
            poam_conv($db_src, $db_target);
            continue;
        }
        $qry = $db_src->select()->from($table,'count(*)');
        //Get count
        $count = $db_src->fetchRow($qry);
        $count=$count['count(*)'] ;
        $qry = $db_src->select()->from($table)->limit(0,$delta);
        $rc = 0;
        for($i=0;$i<$count+$delta ; $i+=$delta )
        {
            $qry->reset(Zend_Db_Select::LIMIT_COUNT)
               ->reset(Zend_Db_Select::LIMIT_OFFSET);
            $qry->limit($delta,$i);
            $rows = $db_src->fetchAll($qry);
            $rc += count($rows);
            foreach($rows as &$data) {
                convert($db_src, $db_target, $table,$data);
            }
        }
        echo " ( $rc ) successfully\n";
        }catch(Zend_Exception $e){
            echo "skip \n\t", $e->getMessage() . "\n";
            continue;
        }
    }

    $db_target->query(' INSERT INTO `poams` 
        (`legacy_finding_id`, `asset_id`, `source_id`, 
        `system_id`, `blscr_id`, `create_ts`, `discover_ts`, 
        `status`, `finding_data`) SELECT * from poam_tmp');





function convert($db_src, $db_target, $table,&$data)
{
    switch($table)
    {
    case 'BLSCR':
         blscr_conv($db_src, $db_target, $data);
         break;

    case 'NETWORKS':
         networks_conv($db_src, $db_target, $data);
         break;

    case 'PRODUCTS':
         products_conv($db_src, $db_target, $data);
         break;

    case 'FINDING_SOURCES':
         sources_conv($db_src, $db_target, $data);
         break;

    case 'SYSTEM_GROUP_SYSTEMS':
         systemgroup_systems_conv($db_src, $db_target, $data);
         break;

    case 'SYSTEMS':
         system_conv($db_src, $db_target, $data);
         break;
   
    case 'SYSTEM_GROUPS':
         system_groups_conv($db_src, $db_target, $data);
         break;

    case 'FUNCTIONS':
         functions_conv($db_src, $db_target, $data);
         break;

    case 'ROLES':
         roles_conv($db_src, $db_target, $data);
         break;

    case 'ROLE_FUNCTIONS':
         role_functions_conv($db_src, $db_target, $data);
         break;

    case 'USER_ROLES':
         user_roles_conv($db_src, $db_target, $data);
         break;

    case 'USERS':
         users_conv($db_src, $db_target, $data);
         break;

    case 'USER_SYSTEM_ROLES':
         user_system_conv($db_src, $db_target, $data);
         break;

    case 'VULN_PRODUCTS':
         vuln_products_conv($db_src, $db_target, $data);
         break;

    case 'FINDING_VULNS':
         poam_vulns_conv($db_src, $db_target, $data);
         break;

    case 'POAM_EVIDENCE':
         poam_evidence_conv($db_src, $db_target, $data);
         break;
   
    case 'AUDIT_LOG':
         audit_log_conv($db_src, $db_target, $data);
         break; 



/////////////////////////////////////////////////////  

    case 'ASSETS':
         assets_conv($db_src, $db_target, $data);
         break;

    case 'FINDINGS':
         finding_conv($db_src, $db_target, $data);
         break;

    case 'VULNERABILITIES':
         vulnerabilities_conv($db_src, $db_target, $data);
         break;

    case 'POAM_COMMENTS':
         poam_comments_conv($db_src, $db_target, $data);
         break;

    default:
            assert(false);
    }
}

function blscr_conv($db_src, $db_target, $data)
{
    if($data['blscr_low']==1&&$data['blscr_moderate']==1&&$data['blscr_high']==1 ){
        $level= 'low';
    } else if($data['blscr_low']==0&&$data['blscr_moderate']==1&&$data['blscr_high']==1 ) {
        $level='moderate';    
    }else if($data['blscr_low']==0&&$data['blscr_moderate']==0&&$data['blscr_high']==1 ) {
        $level='high';
    }else if($data['blscr_low']==0&&$data['blscr_moderate']==0&&$data['blscr_high']==0 ) {
        $level='none';
    }else {
        echo "{$data['blscr_id']} level error";
    }
         
    if( empty($data['blscr_enhancements']) || 
        $data['blscr_enhancements'] == '.' ) {
        $data['blscr_enhancements'] = 'N/A';
    }
    if( $data['blscr_supplement'] == '.' 
        || empty($data['blscr_supplement']) ) {
        $data['blscr_supplement'] = 'N/A';
    }
    $tmparray=array('code'=>$data['blscr_number'] ,
                    'class'=>$data['blscr_class']  ,
                    'subclass'=>$data['blscr_subclass']  ,
                    'family'=>$data['blscr_family']  ,
                    'control'=>$data['blscr_control']  , 
                    'guidance'=> $data['blscr_guidance'] , 
                    'control_level'=>$level,
                    'enhancements'=>$data['blscr_enhancements']  ,
                    'supplement'=> $data['blscr_supplement']);
    $db_target->insert('blscrs',$tmparray);
}


function networks_conv($db_src, $db_target, $data)
{
    $tmparray=array('id'=>$data['network_id'] ,
                  'name'=>$data['network_name']  ,
              'nickname'=>$data['network_nickname']  ,
                  'desc'=>$data['network_desc'] );
    $db_target->insert('networks',$tmparray);
}

function products_conv($db_src, $db_target, &$data)
{
    $tmparray=array('id'=>$data['prod_id'] ,
           'nvd_defined'=>$data['prod_nvd_defined']  ,
                  'meta'=>$data['prod_meta']  ,
                'vendor'=>$data['prod_vendor'],
                  'name'=>$data['prod_name'] ,
               'version'=>$data['prod_version'],
                  'desc'=>$data['prod_desc'] );
    $db_target->insert('products',$tmparray);
    unset($tmparray);
}

function sources_conv($db_src, $db_target, &$data)
{
    $tmparray=array('id'=>$data['source_id'] ,
                  'name'=>$data['source_name'],
              'nickname'=>$data['source_nickname'] ,
                  'desc'=>$data['source_desc'] );
    $db_target->insert('sources',$tmparray);
    unset($tmparray);
}

function systemgroup_systems_conv($db_src, $db_target, &$data)
{
    $tmparray=array('sysgroup_id'=>$data['sysgroup_id'] ,
                      'system_id'=>$data['system_id']);
    $db_target->insert('systemgroup_systems',$tmparray);
    unset($tmparray);
}

function system_conv($db_src, $db_target, $data)
{
    $tmparray=array('id'=>$data['system_id'] ,
                  'name'=>$data['system_name'],
              'nickname'=>$data['system_nickname'],
                  'desc'=>$data['system_desc'],
                  'type'=>$data['system_type'],
        'primary_office'=>$data['system_primary_office'],
       'confidentiality'=>$data['system_confidentiality'],
             'integrity'=>$data['system_integrity'],
          'availability'=>$data['system_availability'],
                  'tier'=>$data['system_tier'],
    'criticality_justification'=>$data['system_criticality_justification'],
    'sensitivity_justification'=>$data['system_sensitivity_justification'],
           'criticality'=>$data['system_criticality']);
     $db_target->insert('systems',$tmparray);
     unset($tmparray);
}

function system_groups_conv($db_src, $db_target, $data)
{
    $tmparray=array('id'=>$data['sysgroup_id'] ,
                  'name'=>$data['sysgroup_name'],
              'nickname'=>$data['sysgroup_nickname'],
           'is_identity'=>$data['sysgroup_is_identity']);
    $db_target->insert('system_groups',$tmparray);
    unset($tmparray);
}

function functions_conv($db_src, $db_target, $data)
{
    $tmparray=array('id'=>$data['function_id'] ,
                  'name'=>$data['function_name'],
                'screen'=>$data['function_screen'],
                'action'=>$data['function_action'],
                  'desc'=>$data['function_desc'],
                  'open'=>$data['function_open']);
    $db_target->insert('functions',$tmparray);
    unset($tmparray);
}

function roles_conv($db_src, $db_target, $data)
{
    $tmparray=array('id'=>$data['role_id'] ,
                  'name'=>$data['role_name'],
              'nickname'=>$data['role_nickname'],
                  'desc'=>$data['role_desc']);
    $db_target->insert('roles',$tmparray);
    unset($tmparray);
}

function role_functions_conv($db_src, $db_target, $data)
{
    $tmparray=array('role_id'=>$data['role_id'] ,
               'function_id'=>$data['function_id']);
    try{
        $db_target->insert('role_functions',$tmparray);
    }catch(Zend_Exception $e){
        return;
    }
}


function user_roles_conv($db_src, $db_target, $data)
{
    $tmparray=array('user_id'=>$data['user_id'] ,
                    'role_id'=>$data['role_id']);
    $db_target->insert('user_roles',$tmparray);
    unset($tmparray);
}

function user_system_conv($db_src, $db_target, $data)
{
    $tmparray=array('user_id'=>$data['user_id'] ,
                    'system_id'=>$data['system_id']);
    $db_target->insert('user_systems',$tmparray);
    unset($tmparray);
}

function users_conv($db_src, $db_target, $data)
{
    if(isset($data['extra_role']))
    {
        $auto_role=$data['extra_role'];
    } else {
        $auto_role=$data['user_name'].'_r';
    }
    
    if(empty($data['failure_count']))
    {
        $data['failure_count']=0;
    }

    $tmparray=array('id'=>$data['user_id'] ,
               'account'=>$data['user_name'],
              'password'=>$data['user_password'],
                 'title'=>$data['user_title'],
             'name_last'=>$data['user_name_last'],
           'name_middle'=>$data['user_name_middle'],
            'name_first'=>$data['user_name_first'],
            'created_ts'=>$data['user_date_created'],
           'password_ts'=>$data['user_date_password'],
      'history_password'=>$data['user_history_password'],
         'last_login_ts'=>$data['user_date_last_login'],
        'termination_ts'=>$data['user_date_deleted'],
             'is_active'=>$data['user_is_active'],
         'failure_count'=>$data['failure_count'],
          'phone_office'=>$data['user_phone_office'],
          'phone_mobile'=>$data['user_phone_mobile'],
                 'email'=>$data['user_email'],
             'auto_role'=>$auto_role);
    $db_target->insert('users',$tmparray);
    unset($tmparray);
    try{
        user_roles_conv($db_src, $db_target, $data);
    }catch(Zend_Exception $e){
        return;
    }
    
}

function vuln_products_conv($db_src, $db_target, $data)
{
    $tmparray=array('vuln_seq'=>$data['vuln_seq'] ,
                   'vuln_type'=>$data['vuln_type'] ,
                     'prod_id'=>$data['prod_id']);
    $db_target->insert('vuln_products',$tmparray);
}

/////////////////////////////////////

function assets_conv($db_src, $db_target,$data)
{    
    $qry=$db_src->select()->from('SYSTEM_ASSETS' ,array('system_id'=>'system_id'))->where('asset_id=?',$data['asset_id']);
    $system_id=$db_src->fetchRow($qry);
    if(empty($system_id)) 
        $system_id=0;
    else
        $system_id=$system_id['system_id'];
    
    $qry=null;
    $qry=$db_src->select()->from('FINDINGS' ,array('finding_id'=>'finding_id'))->where('asset_id=?',$data['asset_id']);
    $finding_id=$db_src->fetchRow($qry);
    $finding_id=$finding_id['finding_id'];
    if(empty($finding_id))
        $is_virgin=1;
    else
        $is_virgin=0;        
     
    $qry=null;
    $qry=$db_src->select()->from('ASSET_ADDRESSES' ,
                        array('network_id'=>'network_id',
                              'address_ip'=>'address_ip',
                            'address_port'=>'address_port'))
                          ->where('asset_id=?',$data['asset_id']);
    $network=$db_src->fetchRow($qry);
    if(empty($network))
    {
        $network_id=0;
        $address_ip=0;
        $address_port=0;
    }else
    {
        $network_id=$network['network_id'];
        $address_ip=$network['address_ip'];
        $address_port=$network['address_port'];
    }

    $tmparray=array('id'=>$data['asset_id'] ,
               'prod_id'=>$data['prod_id'],
                  'name'=>$data['asset_name'],
             'create_ts'=>$data['asset_date_created'],
                'source'=>$data['asset_source'],
             'system_id'=>$system_id,
             'is_virgin'=>$is_virgin,
            'network_id'=>$network_id,
            'address_ip'=>$address_ip,
          'address_port'=>$address_port);
    try {
        $db_target->insert('assets',$tmparray);
    } catch(Zend_Exception $e) {
        echo "error in assets_conv() for asset_id {$data['asset_id']}: ", $e->getMessage() . "\n";
    }
    unset($tmparray);
}

function finding_conv($db_src, $db_target, $data)
{
    $qry = $db_src->select();
    $poam_data = $db_src->fetchAll($qry->from('POAMS')->where('finding_id=?',$data['finding_id']));
    $qry->reset();
 
    $asset_data = $db_src->fetchAll(
                  $qry->from(array('as'=>'ASSETS'))->where('as.asset_id=?',$data['asset_id'])
                      ->join(array('sys'=>'SYSTEM_ASSETS'),'sys.asset_id = as.asset_id') );
    if(empty($asset_data)) {
        echo "asset {$data['asset_id']} missing for finding[{$data['finding_id']}] \n";
    }
    if(empty($poam_data)){
        $tmp = array(
                     'legacy_finding_id'=> $data['finding_id'],
                     'asset_id'=>$data['asset_id'],
                     'source_id'=>$data['source_id'],
                     'system_id'=> isset($asset_data[0]['system_id'])?$asset_data[0]['system_id']:0,
                     'create_ts'=>$data['finding_date_created'],
                     'finding_data'=>$data['finding_data'],
                     'discover_ts'=>$data['finding_date_discovered'],
                     'status'=>'NEW'
                     );
        $db_target->insert('poam_tmp',$tmp);
        return;
    }else{
        $poam_data = $poam_data[0];
    }

    if($data['finding_id'] != $poam_data['finding_id']){
        echo "{$data['finding_id']} is inconsistent between finding_id and poam.finding_id \n";
    }

    if($poam_data['poam_status']=='OPEN' && $poam_data['poam_type']=='NONE')
    {
        $poam_data['poam_status']='NEW';
    }

    $tmp = array('id'=> $poam_data['poam_id'],
                 'legacy_finding_id'=> $data['finding_id'],
                 'asset_id'=>$data['asset_id'],
                 'source_id'=>$data['source_id'],
                 'system_id'=>$poam_data['poam_action_owner'],
                 'blscr_id'=>$poam_data['poam_blscr'],
                 'create_ts'=>$data['finding_date_created'],
                 'finding_data'=>$data['finding_data'],
                 'discover_ts'=>$data['finding_date_discovered'],
                 'modify_ts'=>$poam_data['poam_date_modified'],
                 'close_ts'=>$poam_data['poam_date_closed'],
                 'type'=>$poam_data['poam_type'],
                 'status'=>$poam_data['poam_status'],
                 'is_repeat'=>$poam_data['poam_is_repeat'],
                 'previous_audits'=>$poam_data['poam_previous_audits'],
                 'created_by'=>$poam_data['poam_created_by'],
                 'modified_by'=>$poam_data['poam_modified_by'],
                 'closed_by'=>$poam_data['poam_closed_by'],
                 'action_suggested'=>$poam_data['poam_action_suggested'],
                 'action_planned'=>$poam_data['poam_action_planned'],
                 'action_status'=>$poam_data['poam_action_status'],
                 'action_approved_by'=>$poam_data['poam_action_approved_by'],
                 'action_resources'=>$poam_data['poam_action_resources'],
                 'action_est_date'=>$poam_data['poam_action_date_est'],
                 'action_actual_date'=>$poam_data['poam_action_date_actual'],
                 'cmeasure'=>$poam_data['poam_cmeasure'],
                 'cmeasure_effectiveness'=>$poam_data['poam_cmeasure_effectiveness'],
                 'cmeasure_justification'=>$poam_data['poam_cmeasure_justification'],
                 'threat_source'=>$poam_data['poam_threat_source'],
                 'threat_level'=>$poam_data['poam_threat_level'],
                 'threat_justification'=>$poam_data['poam_threat_justification']);
     $db_target->insert('poams',$tmp);
}


function poam_conv( $db_src, $db_target)
{
    $qry = $db_src->select();
    $data = $db_src->fetchAll(
            "SELECT *
            FROM POAMS p
            WHERE NOT
            EXISTS (

            SELECT finding_id
            FROM FINDINGS f
            WHERE f.finding_id = p.finding_id
            )");
    foreach($data as $poam_data){

    $tmp = array('id'=> $poam_data['poam_id'],
                 'legacy_finding_id'=> 0,
                 'asset_id'=>0,
                 'source_id'=>0,
                 'system_id'=>$poam_data['poam_action_owner'],
                 'blscr_id'=>$poam_data['poam_blscr'],
                 'create_ts'=>$poam_data['poam_date_created'],
                 'modify_ts'=>$poam_data['poam_date_modified'],
                 'close_ts'=>$poam_data['poam_date_closed'],
                 'type'=>$poam_data['poam_type'],
                 'status'=>$poam_data['poam_status'],
                 'is_repeat'=>$poam_data['poam_is_repeat'],
                 'previous_audits'=>$poam_data['poam_previous_audits'],
                 'created_by'=>$poam_data['poam_created_by'],
                 'modified_by'=>$poam_data['poam_modified_by'],
                 'closed_by'=>$poam_data['poam_closed_by'],
                 'action_suggested'=>$poam_data['poam_action_suggested'],
                 'action_planned'=>$poam_data['poam_action_planned'],
                 'action_status'=>$poam_data['poam_action_status'],
                 'action_approved_by'=>$poam_data['poam_action_approved_by'],
                 'action_resources'=>$poam_data['poam_action_resources'],
                 'action_est_date'=>$poam_data['poam_action_date_est'],
                 'action_actual_date'=>$poam_data['poam_action_date_actual'],
                 'cmeasure'=>$poam_data['poam_cmeasure'],
                 'cmeasure_effectiveness'=>$poam_data['poam_cmeasure_effectiveness'],
                 'cmeasure_justification'=>$poam_data['poam_cmeasure_justification'],
                 'threat_source'=>$poam_data['poam_threat_source'],
                 'threat_level'=>$poam_data['poam_threat_level'],
                 'threat_justification'=>$poam_data['poam_threat_justification']);
     $db_target->insert('poams',$tmp);
    }
}


function  vulnerabilities_conv($db_src, $db_target, $data)
{
    $description=NULL;
    if($data['vuln_desc_primary'])
        $description.="Primary:".$data['vuln_desc_primary'];
    if($data['vuln_desc_secondary'])
        $description.="\nSecondary:".$data['vuln_desc_secondary'];
    $stmt = $db_src->query("SELECT vi.imp_desc, 
                                   vi.imp_source,
                                   vr.ref_name,
                                   vr.ref_source,
                                   vr.ref_url,
                                   vr.ref_is_advisory,
                                   vr.ref_has_tool_sig,
                                   vr.ref_has_patch,
                                   vs.sol_desc,
                                   vs.sol_source
                              FROM VULNERABILITIES v
                         LEFT JOIN VULN_IMPACTS vi ON v.vuln_seq = vi.vuln_seq and v.vuln_type = vi.vuln_type
                         LEFT JOIN VULN_REFERENCES vr ON v.vuln_seq = vr.vuln_seq and v.vuln_type = vr.vuln_type
                         LEFT JOIN VULN_SOLUTIONS vs ON v.vuln_seq = vs.vuln_seq and v.vuln_type = vs.vuln_type
                             WHERE v.vuln_seq = '{$data['vuln_seq']}'
                               AND v.vuln_type = '{$data['vuln_type']}'
                             LIMIT 1");
    $row = $stmt->fetch();

    $references=NULL;
    if($row['ref_name'])
        $references.="Name:".$row['ref_name']; 
    if($row['ref_source'])
        $references.="\nSource:".$row['ref_source'];           
    if($row['ref_url'])
        $references.="\nUrl:".$row['ref_url'];    
    if($row['ref_is_advisory'])
        $references.="\nIs_advisory".$row['ref_is_advisory'];    
    if($row['ref_has_tool_sig'])
        $references.="\nHas_tool_sig".$row['ref_has_tool_sig'];    
    if($row['ref_has_patch'])
        $references.="\nHas_patch".$row['ref_has_patch'];

    $impact=NULL;
    if($row['imp_desc'])
        $impact.="Description:".$row['imp_desc'];
    if($row['imp_source'])     
        $impact.="\nSource:".$row['imp_source'];

    $solutions=NUll;
    if($row['sol_desc'])
        $solutions.="Description:".$row['sol_desc'];
    if($row['sol_source'])
        $solutions.="\nSource:".$row['sol_source'];

    $tmparray=array('seq'=>$data['vuln_seq'],
                   'type'=>$data['vuln_type'],
            'description'=>$description,
              'modify_ts'=>$data['vuln_date_modified'],
             'publish_ts'=>$data['vuln_date_published'],
               'severity'=>$data['vuln_severity'],
                 'impact'=>$impact,
              'reference'=>$references,
               'solution'=>$solutions
     );
    $db_target->insert('vulnerabilities',$tmparray); 
    unset($tmparray);
}

/* need to edit

function  vulnerabilities_conv($db_src, $db_target, $data)
{   
    $description="Primary:".$data['vuln_desc_primary'].
                  "Secondary:".$data['vuln_desc_secondary'];
    
    $QRY=$DB_SRC->SELECT()->FROM('VULNERABILITIES',
                                 array('imp_desc'=>'vi.imp_desc',
                                     'imp_source'=>'vi.imp_source',
                                       'ref_name'=>>'vr.ref_name',
                                     'ref_source'=>'vr.ref_source',
                                        'ref_url'=>'vr.ref_url',
                                'ref_is_advisory'=>'vr.ref_is_advisory',
                               'ref_has_tool_sig'=>'vr.ref_has_tool_sig',
                                  'ref_has_patch'=>'vr.ref_has_patch',   
                                       'sol_desc'=>'vs.sol_desc',
                                     'sol_source'=>'vs.sol_source'))
                          ->where('vuln_seq=?',$data['vuln_seq'])
                          ->where('vuln_type=?',$data['vuln_type']);
                          echo $qry;die("xxxxxx");
    $impact=$db_src->fetchRow($qry);
    $impact="Description:".$impact['imp_desc']."Source:".$impact['imp_source'];
    $qry=null;

    $qry=$db_src->select()->from('VULN_REFERENCES',
                           array('ref_name'=>'ref_name',
                               'ref_source'=>'ref_source',
                                  'ref_url'=>'ref_url',
                          'ref_is_advisory'=>'ref_is_advisory',
                         'ref_has_tool_sig'=>'ref_has_tool_sig',
                            'ref_has_patch'=>'ref_has_patch'))
                          ->where('vuln_seq=?',$data['vuln_seq'])
                          ->where('vuln_type=?',$data['vuln_type']);
    $references=$db_src->fetchRow($qry);
    $references="Name:".$references['ref_name'].
              "Source:".$references['ref_source'].
                 "Url:".$references['ref_url'].
          "Is_advisory".$references['ref_is_advisory'].
         "Has_tool_sig".$references['ref_has_tool_sig'].
            "Has_patch".$references['ref_has_patch'];
    $qry=null;

    $qry=$db_src->select()->from('VULN_SOLUTIONS',
                           array('sol_desc'=>'sol_desc',
                               'sol_source'=>'sol_source'))
                          ->where('vuln_seq=?',$data['vuln_seq'])
                          ->where('vuln_type=?',$data['vuln_type']);
    $solutions=$db_src->fetchRow($qry);
    $solutions="Description:".$solutions['sol_desc']."Source:".$solutions['sol_source'];

    $tmparray=array('seq'=>$data['vuln_seq'],
                   'type'=>$data['vuln_type'],
            'description'=>$description,
              'modify_ts'=>$data['vuln_date_modified'],
             'publish_ts'=>$data['vuln_date_published'],
               'severity'=>$data['vuln_severity'],
                 'impact'=>$impact,
              'reference'=>$references,
               'solution'=>$solutions
     );
    $db_target->insert('vulnerabilities',$tmparray); 
    unset($tmparray);
    
}
*/

function poam_vulns_conv($db_src, $db_target, $data)
{
    $qry=$db_src->select()->from('POAMS',array('poam_id'=>'poam_id'))
                          ->where('finding_id=?',$data['finding_id']);
    $poam_id=$db_src->fetchRow($qry);
    $poam_id=$poam_id['poam_id'];                      
    if(!empty($poam_id)){
        $tmparray=array('poam_id'=>$poam_id,
                       'vuln_seq'=>$data['vuln_seq']  ,
                      'vuln_type'=>$data['vuln_type'] );
        $db_target->insert('poam_vulns',$tmparray);
    }else{
        echo "INSERT INTO poam_vulns( `poam_id` , `vuln_seq` , `vuln_type` ) SELECT p.id, v.seq, v.type FROM poams p, vulnerabilities v WHERE p.legacy_finding_id = '{$data['finding_id']}' AND v.seq = '{$data['vuln_seq']}' AND v.type = '{$data['vuln_type']}';\n" ; 
    }
}

function insert_ev_eval($db_target,$ev_id,$eval_id,$decision,$date)
{
        if(!$date){
            $date='0000-00-00';
        }
        if(in_array($decision, array('EXCLUDED','NONE'))){
            echo "evidence($ev_id) $decision is discarded by design\n";
            return;
        }
        $tmparray=array('group_id'=>$ev_id,
                      'eval_id'=>$eval_id, 
                     'decision'=>$decision,
                         'date'=>$date );
        $db_target->insert('poam_evaluations',$tmparray);
}

function poam_evidence_conv($db_src, $db_target, $data)
{
    $tmparray=array('id'=>$data['ev_id'],
               'poam_id'=>$data['poam_id'],
            'submission'=>$data['ev_submission'],
          'submitted_by'=>$data['ev_submitted_by'],
             'submit_ts'=>$data['ev_date_submitted']);
    $db_target->insert('evidences',$tmparray);

    if($data['ev_sso_evaluation']){
        $eval_id=1;
        $decision=$data['ev_sso_evaluation'];
        $date=$data['ev_date_sso_evaluation'];
        insert_ev_eval($db_target,$data['ev_id'],$eval_id,$decision,$date);
    }

    if($data['ev_fsa_evaluation']){
        $eval_id=2;
        $decision=$data['ev_fsa_evaluation'];
        $date=$data['ev_date_fsa_evaluation'];
        insert_ev_eval($db_target,$data['ev_id'],$eval_id,$decision,$date);
    }
     
    if($data['ev_ivv_evaluation']){
        $eval_id=3;
        $decision=$data['ev_ivv_evaluation'];
        $date=$data['ev_date_ivv_evaluation'];
        insert_ev_eval($db_target,$data['ev_id'],$eval_id,$decision,$date);
    }
}

function insert_poam_eval($db_target,$poam_id,$eval_id,$decision,$date)
{
        if(!$date){
            $date='0000-00-00';
        }
        if(!in_array($decision, array('APPROVED','DENIED','EST_CHANGED'))){
            echo "evaluation($poam_id) $decision is wrong!!\n";
            return;
        }
        $tmparray=array('group_id'=>$poam_id,
                      'eval_id'=>$eval_id, 
                     'decision'=>$decision,
                         'date'=>$date );
        $db_target->insert('poam_evaluations',$tmparray);
        return $db_target->lastInsertId();
}

function poam_comments_conv($db_src, $db_target, $data)
{
    $evals=array('EV_SSO'=> 1,
                 'EV_FSA'=> 2,
                 'EV_IVV'=> 3,
                 'EST'  => 4,
                 'SSO' => 5);
    $eval_id = 0;
    if(isset($evals[$data['comment_type']])){
        $eval_id = $evals[$data['comment_type']];
    }else if( isset($evals[$data['comment_log']]) ){
        $eval_id = $evals[$data['comment_log']];
        $data['comment_type'] = $data['comment_log'];
        $data['comment_log'] = $data['comment_body'];
        $data['comment_body'] = $data['comment_topic'];
        $data['comment_topic'] = '';
    }else{
       // return;need to insert comment too
    }

    if($eval_id == 4 )
    {
        assert( $data['poam_id']);
        $ev_evaluation_id = insert_poam_eval($db_target, $data['poam_id'],$eval_id,'EST_CHANGED',$data['comment_date']);
    }else if($eval_id == 5){
        $ev_evaluation_id = insert_poam_eval($db_target, $data['poam_id'],$eval_id,'DENIED',$data['comment_date']);

    }else if($eval_id < 4 && $eval_id > 0){
        assert( $data['ev_id']);
        $qry=$db_target->select()->from('poam_evaluations',array('id'))
                                 ->where('group_id=?',$data['ev_id'])
                                 ->where('eval_id=?',$eval_id);
        $ev_evaluation_id=$db_target->fetchRow($qry);
        if(!empty($ev_evaluation_id))
        {
            $ev_evaluation_id=$ev_evaluation_id['id'];
        }else{
            $ev_evaluation_id=0;
        }
    }
    if(!empty($ev_evaluation_id) )
    {
        $tmparray=array('id'=>$data['comment_id'],
        'poam_evaluation_id'=>$ev_evaluation_id,
                   'user_id'=>$data['user_id'],
                      'date'=>$data['comment_date'],
                     'topic'=>$data['comment_topic'],
                   'content'=>$data['comment_body'].$data['comment_log']);
        $db_target->insert('comments',$tmparray);
    }
    $description="";
    if($data['comment_topic'])
        $description=$data['comment_topic'];
    if($data['comment_body'])
        $description.="\n".$data['comment_body'];
    if($data['comment_log'])
        $description.="\n".$data['comment_log'];
    $tmparray=array('poam_id'=>$data['poam_id'],
                    'user_id'=>$data['user_id'],
                  'timestamp'=>$data['comment_date'],
                      'event'=>"MODIFICATION",
                'description'=>$description);
    $db_target->insert('audit_logs',$tmparray);
}

function audit_log_conv($db_src, $db_target, $data)
{
    $qry=$db_target->select()->from('poams','id')
                        ->where('legacy_finding_id=?',$data['finding_id']);
    $poam_id=$db_target->fetchRow($qry);
    if(empty($poam_id))
    {
        echo "Missing poam_id from finding_id: ".$data['finding_id']."\n";
    }else{
        $poam_id=$poam_id['id'];    
        $tmparray=array('poam_id'=>$poam_id,
                        'user_id'=>$data['user_id'],
                      'timestamp'=>date('Y-m-d H:i:s',$data['date']),
                          'event'=>'MODIFICATION',
                    'description'=>$data['description']);
        $db_target->insert('audit_logs',$tmparray);
    }
}

?>
