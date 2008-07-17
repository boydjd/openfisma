<?php
/**
 * @file:AppDetective.php
 *
 * AppDetective plugin
 *
 * @author     Ryan<ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/
class AppDetective implements ScanResult
{
    
    public function isValid($file)
    {
        $pattern = '/root_header/';
        return preg_match($pattern,file_get_contents($file));
    
    }

    public function parse($data)
    {
        $appName = $data['root']['root_header'][0]['appName'];
        $pos = strpos($appName,',');
        $asset['address_ip'] = substr($appName,strpos($appName,'on ')+3,strpos($appName,',')-strpos($appName,'on ')-3);
        $asset['address_port'] = substr($appName,strpos($appName,'port ')+5);
        $discover_ts_tmp = $data['root']['root_header'][0]['printDate'];
        $discover_ts = date('Y-m-d H:i:s',strtotime($discover_ts_tmp));
        foreach($data['root'] as $k=>$v){
            if(substr($k,0,21) == 'root_detail_risklevel' && !empty($v)){
                foreach($v[0]['data'] as $vv){
                    if(!empty($vv)){
                        $risk[]         = $vv['risk'];
                        $finding_data[] = $vv['details'][0]['vulnDetail'];
                        $vuln_desc[] = $vv['description'].$vv['summary'];
                        $vuln_solution[] = $vv['fix'];
                        $cve_temp = explode('-',$vv['cve']);
                        $cve[] = is_numeric($cve_temp[1].$cve_temp[2])?$cve_temp[1].$cve_temp[2]:'';
                        $sbv_temp = explode('-',$vv['SkyBoxCode']);
                        $sbv[] = !empty($sbv_temp[1])?$sbv_temp[1]:'';
                    }
                }
            }
        }
        $asset['name'] = ':'.$asset['address_ip'].':'.$asset['address_port'];
        $current_time = date('Y-m-d H:i:s');
        $unified = array('product'=>array('meta'=>'','vendor'=>'','version'=>'','desc'=>''),
                         'asset'=>array('name'=>$asset['name'],
                                        'source'=>'SCAN','address_ip'=>$asset['address_ip'],
                                        'address_port'=>$asset['address_port']),
                         'blscr'=>array('code'=>'','class'=>'','subclass'=>'','family'=>'','control'=>'',
                                        'guidance'=>'','control_level'=>'','enhancements'=>'',
                                        'supplement'=>''),
                         'poam'=>array('discover_ts'=>$discover_ts,'status'=>'NEW',
                                        'finding_data'=>$finding_data),
                         'vulnerabilities'=>array('description'=>$vuln_desc,'sbv'=>$sbv,'severity'=>$risk,
                                                  'solution'=>$vuln_solution,'cve'=>$cve));
        return $unified;
    }
}
