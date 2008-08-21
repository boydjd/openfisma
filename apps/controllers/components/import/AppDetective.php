<?php
/**
 * AppDetective.php
 *
 * AppDetective plugin
 *
 * @package Controller_components_import
 * @author     Ryan ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/
class AppDetective implements ScanResult
{
    
    public function isValid($file)
    {
        $pattern = '/root_header/';
        return preg_match($pattern, file_get_contents($file));
    
    }

    public function parse($data)
    {
        $appName = $data['root']['root_header'][0]['appName'];
        $pos = strpos($appName, ',');
        $asset['address_ip'] = substr($appName, strpos($appName, 'on ')+3,
                            strpos($appName, ',')-strpos($appName, 'on ')-3);
        $asset['address_port'] = substr($appName, strpos($appName, 'port ')+5);
        $discoverTsTmp = $data['root']['root_header'][0]['printDate'];
        $discoverTs = date('Y-m-d H:i:s', strtotime($discoverTsTmp));

        foreach ($data['root'] as $k=>$v) {
            if (substr($k, 0, 21) == 'root_detail_risklevel' && !empty($v)) {
                foreach ($v[0]['data'] as $vv) {
                    if (!empty($vv)) {
                        $risk[]         = nullGet($vv['risk'], '');
                        $findingData[] = 
                                nullGet($vv['details'][0]['vulnDetail'], '');

                        $vulnDesc[] = nullGet($vv['description'], '')
                                     .nullGet($vv['summary'], '');

                        $vulnSolution[] = nullGet($vv['fix'], '');

                        $cveTemp = explode('-', nullGet($vv['cve'], ''));

                        $cve[] = is_numeric(nullGet($cveTemp[1], '')
                                           .nullGet($cveTemp[2], ''))
                                 ?$cveTemp[1].$cveTemp[2]:'';

                        $sbvTemp = explode('-', nullGet($vv['SkyBoxCode'], ''));

                        $sbv[] = !empty($sbvTemp[1])?$sbvTemp[1]:'';
                    }
                }
            }
        }
        $asset['name'] = ':'.$asset['address_ip'].':'.$asset['address_port'];
        $unified = array('product'=>array('meta'=>'', 'vendor'=>'',
                                       'version'=>'','desc'=>''),

                         'asset'=>array('name'=>$asset['name'],
                                       'source'=>'SCAN',
                                       'address_ip'=>$asset['address_ip'],
                                       'address_port'=>$asset['address_port']),

                         'blscr'=>array('code'=>'', 'class'=>'',
                                       'subclass'=>'', 'family'=>'',
                                       'control'=>'', 'guidance'=>'',
                                       'control_level'=>'', 'enhancements'=>'',
                                       'supplement'=>''),

                         'poam'=>array('discover_ts'=>$discoverTs,
                                       'status'=>'NEW',
                                       'finding_data'=>$findingData),

                         'vulnerabilities'=>array('description'=>$vulnDesc,
                                       'sbv'=>$sbv,'severity'=>$risk,
                                       'solution'=>$vulnSolution, 'cve'=>$cve));
        return $unified;
    }
}
