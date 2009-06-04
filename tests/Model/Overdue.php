<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Woody <woody712@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Test
 */

/**
 * @ignore
 * Run the application bootstrap in command line mode
 */


require_once(realpath(dirname(__FILE__) . '/../FismaUnitTest.php'));

require_once('Fisma/Controller/Action/Helper/OverdueStatistic.php');

class Test_Model_Overdue extends Test_FismaUnitTest
{
    // data for testing
    private $_data = array(0 => array('id' => 1, 'system_id' => 1, 'source_id' => 1, 'type' => 'CAP',
                                      'status' => 'EP_SSO', 'duetime' => '2009-04-23', 'system_nickname' => 'SysA',
                                      'system_name' => 'SystemA', 'oStatus' => 'EA'),
                                      
                           1 => array('id' => 2, 'system_id' => 1, 'source_id' => 1, 'type' => 'NONE',
                                      'status' => 'NEW', 'duetime' => '2009-02-01', 'system_nickname' => 'SysA',
                                      'system_name' => 'SystemA', 'oStatus' => 'NEW'),
                                     
                           2 => array('id' => 3, 'system_id' => 1, 'source_id' => 1, 'type' => 'NONE',
                                      'status' => 'NEW', 'duetime' => '2009-04-23', 'system_nickname' => 'SysA',
                                      'system_name' => 'SystemA', 'oStatus' => 'NEW'),
                                     
                           3 => array('id' => 4, 'system_id' => 1, 'source_id' => 1, 'type' => 'CAP',
                                      'status' => 'MP_SSO', 'duetime' => '2009-04-23 06:01:12', 'system_nickname' => 'SysA',
                                      'system_name' => 'SystemA', 'oStatus' => 'MSA'),
                                     
                           4 => array('id' => 6, 'system_id' => 1, 'source_id' => 1, 'type' => 'NONE',
                                      'status' => 'NEW', 'duetime' => '2009-03-26', 'system_nickname' => 'SysA',
                                      'system_name' => 'SystemA', 'oStatus' => 'NEW'),
                                     
                           7 => array('id' => 26, 'system_id' => 1, 'source_id' => 1, 'type' => 'CAP',
                                      'status' => 'EP_SSO', 'duetime' => '2009-04-23', 'system_nickname' => 'SysA',
                                      'system_name' => 'SystemA', 'oStatus' => 'EA'));
    /**
     * test the statistic of overdue report
     *
     */
    public function testOverdueReport()
    {
        $overdueHelper = new Fisma_Controller_Action_Helper_OverdueStatistic();
        $result = $overdueHelper->overdueStatistic($this->_fixOverdueData());
        
        foreach ($result as $v) {
            if ($v['systemName'] == 'SysA - SystemA') {
                if ($v['type'] == 'Mitigation Strategy'){
                    $this->assertEquals($v['lessThan30'], 0);
                    $this->assertEquals($v['moreThan30'], 1);
                    $this->assertEquals($v['moreThan60'], 1);
                    $this->assertEquals($v['moreThan90'], 1);
                    $this->assertEquals($v['moreThan120'], 1);
                    $this->assertEquals($v['total'], 4);
                    $this->assertEquals($v['average'], 81);
                    $this->assertEquals($v['max'], 130);
                } elseif ($v['type'] == 'Corrective Action') {
                    $this->assertEquals($v['lessThan30'], 1);
                    $this->assertEquals($v['moreThan30'], 0);
                    $this->assertEquals($v['moreThan60'], 0);
                    $this->assertEquals($v['moreThan90'], 0);
                    $this->assertEquals($v['moreThan120'], 1);
                    $this->assertEquals($v['total'], 2);
                    $this->assertEquals($v['average'], 157);
                    $this->assertEquals($v['max'], 300);
                }
            }
        }
    }
    
    /**
     * fix the offset of duetime by current date,
     *
     * @return array
     */
    private function _fixOverdueData()
    {
        // the duetime columns in $_data are useless,
        // it is only for holding the place
        $offset = array(14, 95, 33, 65, 130, 300);
        $date = new Zend_Date(null, Zend_Date::ISO_8601);
        foreach ($this->_data as &$val) {
            if (isset($val['duetime'])) {
                $tmpDate = clone $date;
                $tmpDate->subDay(array_shift($offset)-1);
                $val['duetime'] = date('Y-m-d', $tmpDate->get());
            }
        }
        return $this->_data;
    }
    
}

?>