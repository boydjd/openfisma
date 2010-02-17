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
 * @author    Nathan Harris <nathan.harris@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: IRCategoryController.php 2149 2009-08-25 23:34:02Z nathanrharris $
 * @package   Controller
 */

/**
 * Handles CRUD for incident category objects.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class IrReportController extends SecurityController
{
    /**
     * Check that the user has the privilege to run reports
     */
    public function preDispatch()
    {
        Fisma_Acl::requireArea('incident_report');
    }
    
    /**
     * Breakdown of incidents by category, status (open, resolved, closed), and time frame (week, month, year)
     * 
     * @todo this is copied from the old listAction and probably has unneeded code
     */
    public function categoryAction()
    {        
        $cats  = $this->_getCats();

        /* SETUP CURRENT INCIDENTS ARRAY */
        foreach ($cats as $key => $cat) {
            foreach ($cat['children'] as $key => $subcat) {
                $incidentCurrent['week'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incidentCurrent['week'][$cat['category']][$subcat['id']]['open'] = 0;
                $incidentCurrent['week'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incidentCurrent['week'][$cat['category']][$subcat['id']]['closed'] = 0;
                
                $incidentCurrent['month'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incidentCurrent['month'][$cat['category']][$subcat['id']]['open'] = 0;
                $incidentCurrent['month'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incidentCurrent['month'][$cat['category']][$subcat['id']]['closed'] = 0;
                
                $incidentCurrent['year'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incidentCurrent['year'][$cat['category']][$subcat['id']]['open'] = 0;
                $incidentCurrent['year'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incidentCurrent['year'][$cat['category']][$subcat['id']]['closed'] = 0;
            }
        } 
        /* END SETUP CURRENT INCIDENTS ARRAY */

        /*  GENERATE THIS WEEK'S BREAKDOWN */
        $month = date('m');
        $day   = date('d');
        $year  = date('Y');

        $dayOfWeek = date('w');

        //86400 seconds in a day
        $weekStart = date("Y-m-d", (mktime(0, 0, 0, $month, $day, $year) - ($dayOfWeek * 86400)));
        $weekEnd   = date("Y-m-d", (mktime(0, 0, 0, $month, $day, $year) + ((7-$dayOfWeek) * 86400)));

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $weekStart)
             ->andWhere('i.reportTs <= ?', $weekEnd);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incidentCurrent['week'] as $category => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incidentCurrent['week'][$category][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS WEEK'S BREAKDOWN */

        /*  GENERATE THIS MONTH'S BREAKDOWN */
        $month = date('m');
        $year  = date('Y');
        $lastDay = date('t');
    
        $monthStart = "$year-$month-01";
        $monthEnd   = "$year-$month-$lastDay";

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $monthStart)
             ->andWhere('i.reportTs <= ?', $monthEnd);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incidentCurrent['month'] as $cat => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incidentCurrent['month'][$cat][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS MONTHS'S BREAKDOWN */
        
        /*  GENERATE THIS YEAR'S BREAKDOWN */
        $year  = date('Y');
        $nextYear = $year + 1;
 
        $yearStart = "$year-01-01";
        $yearEnd   = "$nextYear-01-01 00:00:00";

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $yearStart)
             ->andWhere('i.reportTs <= ?', $yearEnd);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incidentCurrent['year'] as $cat => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incidentCurrent['year'][$cat][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS YEAR'S BREAKDOWN */
        
        $this->view->assign('incidentCurrent', $incidentCurrent);

        $weeks = $this->_getWeeks();
        
        /* GENERATE WEEKS BREAKDOWN */ 
        for ($x = 1; $x < 54; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach ($cat['children'] as $key => $subcat) {
                    $incidentWeek[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incidentWeek[$x][$cat['category']][$subcat['id']]['count'] = 0;
                }
            }
      
            $q = Doctrine_Query::create()
                ->select('i.id, i.status, c.name, c.id AS classification')
                ->from('Incident i')
                ->leftJoin('i.Category c')
                 ->whereIn('i.status', array('open','resolved','closed'))
                 ->andWhere('i.reportTs > ?', $weeks[$x]['start'])
                 ->andWhere('i.reportTs <= ?', $weeks[$x]['end']);

            $incidents = $q->execute()->toArray();

            foreach ($incidents as $key => $incident) {
                foreach ($incidentWeek[$x] as $cat => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incidentWeek[$x][$cat][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE WEEKS BREAKDOWN */
    
        $this->view->assign('cats', $cats);
        $this->view->assign('weeks', $weeks);
        $this->view->assign('incidentWeek', $incidentWeek);
 
        /* GENERATE MONTHS BREAKDOWN */ 
        for ($x = 1; $x <= 12; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach ($cat['children'] as $key => $subcat) {
                    $incidentMonth[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incidentMonth[$x][$cat['category']][$subcat['id']]['count'] = 0;
                }
            }
      
            $q = Doctrine_Query::create()
                ->select('i.id, i.status, c.name, c.id AS classification')
                ->from('Incident i')
                ->leftJoin('i.Category c')
                 ->whereIn('i.status', array('open','resolved','closed'))
                 ->andWhere('i.reportTs > ?', '2009-'.$x.'-01')
                 ->andWhere('i.reportTs <= ?', '2009-'.str_pad(($x+1), 2, "0", STR_PAD_LEFT).'-01');

            $incidents = $q->execute()->toArray();

            foreach ($incidents as $key => $incident) {
                foreach ($incidentMonth[$x] as $cat => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incidentMonth[$x][$cat][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE MONTHS BREAKDOWN */
        
        $this->view->assign('incidentMonth', $incidentMonth);
        
        /* GENERATE YEARS BREAKDOWN */ 
        for ($x = 2008; $x <= 2010; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach ($cat['children'] as $key => $subcat) {
                    $incidentYear[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incidentYear[$x][$cat['category']][$subcat['id']]['count'] = 0;
                }
            }
      
            $q = Doctrine_Query::create()
                ->select('i.id, i.status, c.name, c.id AS classification')
                ->from('Incident i')
                ->leftJoin('i.Category c')
                 ->whereIn('i.status', array('open','resolved','closed'))
                 ->andWhere('i.reportTs > ?', $x.'-01-01')
                 ->andWhere('i.reportTs <= ?', ($x+1).'-01-01');

            $incidents = $q->execute()->toArray();

            foreach ($incidents as $key => $incident) {
                foreach ($incidentYear[$x] as $cat => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incidentYear[$x][$cat][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE YEARS BREAKDOWN */
        
        $this->view->assign('incidentYear', $incidentYear);
    }

    public function reportAction() 
    {                
        $startDate = $this->_request->getParam('startDate');
        $endDate   = $this->_request->getParam('endDate');
        $status     = $this->_request->getParam('status');
        $category   = $this->_request->getParam('category');

        $this->view->assign('startDate', $startDate);
        $this->view->assign('endDate', $endDate);
        $this->view->assign('status', $status);

        if (preg_match('/cat/', $category)) {
            $subCats = $this->_getSubCats($category); 
            
            $description = '';   
         
            foreach ($subCats as $key => $val) {
                $cats[] = $val['id'];
                $description .= "{$val['name']}<br />";
            }

            $this->view->assign('category', $description);
        } else {
            $q3 = Doctrine_Query::create()
                  ->select('sc.name')
                  ->from('IrSubCategory sc')
                  ->where('sc.id = ?', $category);
            
            $cat = $q3->execute()->toArray();            

            $this->view->assign('category', $cat[0]['name']);

            $cats = array($category);
        }
        
        if ($status == 'all') {
            $this->view->assign('status', 'open, resolved, & closed');
            $statusArray = array('open', 'resolved', 'closed');
        } else {
            $this->view->assign('status', $status);
            $statusArray = array($status);
        }

        $q = Doctrine_Query::create()
             ->select('i.id, i.status, c.name, c.id AS classification')
             ->from('Incident i')
             ->leftJoin('i.Category c')
             ->where('i.reportTs > ?', $startDate)
             ->andWhere('i.reportTs <= ?', $endDate)
             ->whereIn('i.classification', $cats)
             ->whereIn('i.status', $statusArray)
             ->orderBy('i.reportTs');   
        
        $incidents = $q->execute()->toArray();        
        
        foreach ($incidents as $key => $val) {
            $q2 = Doctrine_Query::create()
                  ->select('sc.name')
                  ->from('IrSubCategory sc')
                  ->where('sc.id = ?', $val['classification']);
            
            $cat = $q2->execute()->toArray();            

            $incidents[$key]['category'] = $cat[0]['name'];

            if ($incidents[$key]['piiInvolved'] == 1) {
                $incidents[$key]['piiInvolved'] = '&#10004;';
            } else {
                $incidents[$key]['piiInvolved'] = '&#10007;';
            }
        }
        
        $this->view->assign('incidents', $incidents);

        $this->render('report');
    }

    /**
     * Breakdown of incidents by category and month for the current year
     * 
     * @todo this is copied from the old listAction and probably has unneeded code
     */
    public function monthAction()
    {        
        $cats  = $this->_getCats();

        /* SETUP CURRENT INCIDENTS ARRAY */
        foreach ($cats as $key => $cat) {
            foreach ($cat['children'] as $key => $subcat) {
                $incidentCurrent['week'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incidentCurrent['week'][$cat['category']][$subcat['id']]['open'] = 0;
                $incidentCurrent['week'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incidentCurrent['week'][$cat['category']][$subcat['id']]['closed'] = 0;
                
                $incidentCurrent['month'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incidentCurrent['month'][$cat['category']][$subcat['id']]['open'] = 0;
                $incidentCurrent['month'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incidentCurrent['month'][$cat['category']][$subcat['id']]['closed'] = 0;
                
                $incidentCurrent['year'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incidentCurrent['year'][$cat['category']][$subcat['id']]['open'] = 0;
                $incidentCurrent['year'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incidentCurrent['year'][$cat['category']][$subcat['id']]['closed'] = 0;
            }
        } 
        /* END SETUP CURRENT INCIDENTS ARRAY */

        /*  GENERATE THIS WEEK'S BREAKDOWN */
        $month = date('m');
        $day   = date('d');
        $year  = date('Y');

        $dayOfWeek = date('w');

        //86400 seconds in a day
        $weekStart = date("Y-m-d", (mktime(0, 0, 0, $month, $day, $year) - ($dayOfWeek * 86400)));
        $weekEnd   = date("Y-m-d", (mktime(0, 0, 0, $month, $day, $year) + ((7-$dayOfWeek) * 86400)));

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $weekStart)
             ->andWhere('i.reportTs <= ?', $weekEnd);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incidentCurrent['week'] as $cat => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incidentCurrent['week'][$cat][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS WEEK'S BREAKDOWN */

        /*  GENERATE THIS MONTH'S BREAKDOWN */
        $month = date('m');
        $year  = date('Y');
        $lastDay = date('t');
    
        $monthStart = "$year-$month-01";
        $monthEnd   = "$year-$month-$lastDay";

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $monthStart)
             ->andWhere('i.reportTs <= ?', $monthEnd);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incidentCurrent['month'] as $cat => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incidentCurrent['month'][$cat][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS MONTHS'S BREAKDOWN */
        
        /*  GENERATE THIS YEAR'S BREAKDOWN */
        $year  = date('Y');
        $nextYear = $year + 1;
 
        $yearStart = "$year-01-01";
        $yearEnd   = "$nextYear-01-01 00:00:00";

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $yearStart)
             ->andWhere('i.reportTs <= ?', $yearEnd);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incidentCurrent['year'] as $cat => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incidentCurrent['year'][$cat][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS YEAR'S BREAKDOWN */
        
        $this->view->assign('incidentCurrent', $incidentCurrent);

        $weeks = $this->_getWeeks();
        
        /* GENERATE WEEKS BREAKDOWN */ 
        for ($x = 1; $x < 54; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach ($cat['children'] as $key => $subcat) {
                    $incidentWeek[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incidentWeek[$x][$cat['category']][$subcat['id']]['count'] = 0;
                }
            }
      
            $q = Doctrine_Query::create()
                ->select('i.id, i.status, c.name, c.id AS classification')
                ->from('Incident i')
                ->leftJoin('i.Category c')
                 ->whereIn('i.status', array('open','resolved','closed'))
                 ->andWhere('i.reportTs > ?', $weeks[$x]['start'])
                 ->andWhere('i.reportTs <= ?', $weeks[$x]['end']);

            $incidents = $q->execute()->toArray();

            foreach ($incidents as $key => $incident) {
                foreach ($incidentWeek[$x] as $cat => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incidentWeek[$x][$cat][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE WEEKS BREAKDOWN */
    
        $this->view->assign('cats', $cats);
        $this->view->assign('weeks', $weeks);
        $this->view->assign('incidentWeek', $incidentWeek);
 
        /* GENERATE MONTHS BREAKDOWN */ 
        for ($x = 1; $x <= 12; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach ($cat['children'] as $key => $subcat) {
                    $incidentMonth[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incidentMonth[$x][$cat['category']][$subcat['id']]['count'] = 0;
                }
            }
      
            $q = Doctrine_Query::create()
                ->select('i.id, i.status, c.name, c.id AS classification')
                ->from('Incident i')
                ->leftJoin('i.Category c')
                 ->whereIn('i.status', array('open','resolved','closed'))
                 ->andWhere('i.reportTs > ?', '2009-'.$x.'-01')
                 ->andWhere('i.reportTs <= ?', '2009-'.str_pad(($x+1), 2, "0", STR_PAD_LEFT).'-01');

            $incidents = $q->execute()->toArray();

            foreach ($incidents as $key => $incident) {
                foreach ($incidentMonth[$x] as $cat => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incidentMonth[$x][$cat][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE MONTHS BREAKDOWN */
        
        $this->view->assign('incidentMonth', $incidentMonth);
        
        /* GENERATE YEARS BREAKDOWN */ 
        for ($x = 2008; $x <= 2010; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach ($cat['children'] as $key => $subcat) {
                    $incidentYear[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incidentYear[$x][$cat['category']][$subcat['id']]['count'] = 0;
                }
            }
      
            $q = Doctrine_Query::create()
                ->select('i.id, i.status, c.name, c.id AS classification')
                ->from('Incident i')
                ->leftJoin('i.Category c')
                 ->whereIn('i.status', array('open','resolved','closed'))
                 ->andWhere('i.reportTs > ?', $x.'-01-01')
                 ->andWhere('i.reportTs <= ?', ($x+1).'-01-01');

            $incidents = $q->execute()->toArray();

            foreach ($incidents as $key => $incident) {
                foreach ($incidentYear[$x] as $cat => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incidentYear[$x][$cat][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE YEARS BREAKDOWN */
        
        $this->view->assign('incidentYear', $incidentYear);
    }

    private function _getCats() 
    {
        /* Get all categories */ 
        $q = Doctrine_Query::create()
             ->select('c.name, c.category')
             ->from('IrCategory c');
        
        $cats = $q->execute()->toArray();        

        /* For each category, get the related subcategories and format them so they will work as a tree */
        foreach ($cats as $key => $val) {
            $cats[$key]['children'] =  ''; 

            $q2 = Doctrine_Query::create()
                  ->select('sc.name')
                  ->from('IrSubCategory sc')
                  ->where('sc.categoryId = ?', $val['id']);  

            $cats[$key]['children'] = $q2->execute()->toArray();
            foreach ($cats[$key]['children'] as $key2 => $val2) {
                $cats[$key]['children'][$key2]['children'] = array();
            }
        }
        
        return $cats;
    }

    private function _getWeeks() 
    {
       //accounting for short first week
       $week[1] = array (
            'start'      => '2009-01-01',
            'end'        => '2009-01-04',
            'start_nice' => 'Jan 1, 2009',
            'end_nice'   => 'Jan 4, 2009'
        ); 

        //604,800 seconds in a week
        for ($x=0; $x<51; $x+=1) {
            $interval1 = $x * 604800;
            $interval2 = ($x + 1) * 604800;

            $week[$x + 2] = array (
                'start'      => date("Y-m-d", (mktime(0, 0, 0, 1, 4, 2009) + $interval1)),
                'end'        => date("Y-m-d", (mktime(0, 0, 0, 1, 4, 2009) + $interval2)),
                'start_nice' => date("M d, Y", (mktime(0, 0, 0, 1, 4, 2009) + $interval1)),
                'end_nice'   => date("M d, Y", (mktime(0, 0, 0, 1, 4, 2009) + $interval2))
            );
        }

        $week[53] = array (
            'start'      => $week[52]['end'],
            'end'        => '2009-12-31',
            'start_nice' => $week[52]['end_nice'],
            'end_nice'   => 'Dec 31, 2009'
        );

        return $week;    
    }

    private function _getSubCats($category) 
    {
        $q1 = Doctrine_Query::create()
             ->select('c.id')
             ->from('IrCategory c')
             ->where('c.category = ?', $category);  

        $cat = $q1->execute()->toArray();

        $q2 = Doctrine_Query::create()
             ->select('sc.id, sc.name')
             ->from('IrSubCategory sc')
             ->where('sc.categoryId = ?', $cat[0]['id'])
             ->orderBy('sc.name');  

        $cats = $q2->execute()->toArray();
        
        return $cats;
    }
}
