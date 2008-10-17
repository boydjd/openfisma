<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */
 
/**
 * A scan result injection plugin for injecting AppDetective XML output directly
 * into OpenFISMA
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class Inject_AppDetective implements Inject_Interface
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
                        $risk[]        = isset($vv['risk'])? $vv['risk']:'';
                        $findingData[] = isset($vv['details'][0]['vulnDetail'])?
                            $vv['details'][0]['vulnDetail']:'';

                        $vulnDesc[] =
                            isset($vv['description'])? $vv['description']:''.
                            isset($vv['summary'])? $vv['summary']:'';

                        $vulnSolution[] = isset($vv['fix'])? $vv['fix']:'';

                        $cveTemp = explode('-',
                            isset($vv['cve'])? $vv['cve']:'');

                        $cve[] = is_numeric(isset($cveTemp[1])? $cveTemp[1]:''
                                           .isset($cveTemp[2])? $cveTemp[2]:'')
                                 ?$cveTemp[1].$cveTemp[2]:'';

                        $sbvTemp = explode('-', isset($vv['SkyBoxCode'])?
                            $vv['SkyBoxCode']:'');

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
