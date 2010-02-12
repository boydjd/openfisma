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
     * Breakdown of incidents by category, status (open, resolved, closed), and time frame (week, month, year)
     * 
     * @todo this is copied from the old listAction and probably has unneeded code
     */
    public function categoryAction()
    {
        Fisma_Acl::requirePrivilege('irreport', 'read');

        $cats  = $this->_getCats();

        /* SETUP CURRENT INCIDENTS ARRAY */
        foreach ($cats as $key => $cat) {
            foreach($cat['children'] as $key => $subcat) {
                $incident_current['week'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incident_current['week'][$cat['category']][$subcat['id']]['open'] = 0;
                $incident_current['week'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incident_current['week'][$cat['category']][$subcat['id']]['closed'] = 0;
                
                $incident_current['month'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incident_current['month'][$cat['category']][$subcat['id']]['open'] = 0;
                $incident_current['month'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incident_current['month'][$cat['category']][$subcat['id']]['closed'] = 0;
                
                $incident_current['year'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incident_current['year'][$cat['category']][$subcat['id']]['open'] = 0;
                $incident_current['year'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incident_current['year'][$cat['category']][$subcat['id']]['closed'] = 0;
            }
       } 
        /* END SETUP CURRENT INCIDENTS ARRAY */


        /*  GENERATE THIS WEEK'S BREAKDOWN */
        $month = date('m');
        $day   = date('d');
        $year  = date('Y');

        $day_of_week = date('w');

        //86400 seconds in a day
        $week_start = date("Y-m-d", (mktime(0, 0, 0, $month, $day, $year) - ($day_of_week * 86400)));
        $week_end   = date("Y-m-d", (mktime(0, 0, 0, $month, $day, $year) + ((7-$day_of_week) * 86400)));

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $week_start)
             ->andWhere('i.reportTs <= ?', $week_end);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incident_current['week'] as $CAT => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incident_current['week'][$CAT][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS WEEK'S BREAKDOWN */

        /*  GENERATE THIS MONTH'S BREAKDOWN */
        $month = date('m');
        $year  = date('Y');
        $last_day = date('t');
    
        $month_start = "$year-$month-01";
        $month_end   = "$year-$month-$last_day";

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $month_start)
             ->andWhere('i.reportTs <= ?', $month_end);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incident_current['month'] as $CAT => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incident_current['month'][$CAT][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS MONTHS'S BREAKDOWN */
        
        /*  GENERATE THIS YEAR'S BREAKDOWN */
        $year  = date('Y');
        $next_year = $year + 1;
 
        $year_start = "$year-01-01";
        $year_end   = "$next_year-01-01 00:00:00";

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $year_start)
             ->andWhere('i.reportTs <= ?', $year_end);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incident_current['year'] as $CAT => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incident_current['year'][$CAT][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS YEAR'S BREAKDOWN */
        
        $this->view->assign('incident_current', $incident_current);

        $weeks = $this->_getWeeks();
        
        /* GENERATE WEEKS BREAKDOWN */ 
        for($x = 1; $x < 54; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach($cat['children'] as $key => $subcat) {
                    $incident_week[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incident_week[$x][$cat['category']][$subcat['id']]['count'] = 0;
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
                foreach ($incident_week[$x] as $CAT => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incident_week[$x][$CAT][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE WEEKS BREAKDOWN */
   
 
        $this->view->assign('cats', $cats);
        $this->view->assign('weeks', $weeks);
        $this->view->assign('incident_week', $incident_week);
 
        /* GENERATE MONTHS BREAKDOWN */ 
        for($x = 1; $x <= 12; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach($cat['children'] as $key => $subcat) {
                    $incident_month[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incident_month[$x][$cat['category']][$subcat['id']]['count'] = 0;
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
                foreach ($incident_month[$x] as $CAT => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incident_month[$x][$CAT][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE MONTHS BREAKDOWN */
        
        $this->view->assign('incident_month', $incident_month);
        
        /* GENERATE YEARS BREAKDOWN */ 
        for($x = 2008; $x <= 2010; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach($cat['children'] as $key => $subcat) {
                    $incident_year[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incident_year[$x][$cat['category']][$subcat['id']]['count'] = 0;
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
                foreach ($incident_year[$x] as $CAT => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incident_year[$x][$CAT][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE YEARS BREAKDOWN */
        
        $this->view->assign('incident_year', $incident_year);
    }


    public function reportAction() 
    {
        Fisma_Acl::requirePrivilege('irreport', 'read');
        
        $start_date = $this->_request->getParam('start_date');
        $end_date   = $this->_request->getParam('end_date');
        $status     = $this->_request->getParam('status');
        $category   = $this->_request->getParam('category');

        $this->view->assign('start_date', $start_date);
        $this->view->assign('end_date', $end_date);
        $this->view->assign('status', $status);

        if(preg_match('/CAT/',$category)) {
            $subCats = $this->_getSubCats($category); 
            
            $description = '';   
         
            foreach($subCats as $key => $val) {
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
            $status_arr = array('open','resolved','closed');
        } else {
            $this->view->assign('status', $status);
            $status_arr = array($status);
        }

        $q = Doctrine_Query::create()
             ->select('i.id, i.status, c.name, c.id AS classification')
             ->from('Incident i')
             ->leftJoin('i.Category c')
             ->where('i.reportTs > ?',     $start_date)
             ->andWhere('i.reportTs <= ?', $end_date)
             ->whereIn('i.classification', $cats)
             ->whereIn('i.status', $status_arr)
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
        Fisma_Acl::requirePrivilege('irreport', 'read');

        $cats  = $this->_getCats();

        /* SETUP CURRENT INCIDENTS ARRAY */
        foreach ($cats as $key => $cat) {
            foreach($cat['children'] as $key => $subcat) {
                $incident_current['week'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incident_current['week'][$cat['category']][$subcat['id']]['open'] = 0;
                $incident_current['week'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incident_current['week'][$cat['category']][$subcat['id']]['closed'] = 0;
                
                $incident_current['month'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incident_current['month'][$cat['category']][$subcat['id']]['open'] = 0;
                $incident_current['month'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incident_current['month'][$cat['category']][$subcat['id']]['closed'] = 0;
                
                $incident_current['year'][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                $incident_current['year'][$cat['category']][$subcat['id']]['open'] = 0;
                $incident_current['year'][$cat['category']][$subcat['id']]['resolved'] = 0;
                $incident_current['year'][$cat['category']][$subcat['id']]['closed'] = 0;
            }
       } 
        /* END SETUP CURRENT INCIDENTS ARRAY */


        /*  GENERATE THIS WEEK'S BREAKDOWN */
        $month = date('m');
        $day   = date('d');
        $year  = date('Y');

        $day_of_week = date('w');

        //86400 seconds in a day
        $week_start = date("Y-m-d", (mktime(0, 0, 0, $month, $day, $year) - ($day_of_week * 86400)));
        $week_end   = date("Y-m-d", (mktime(0, 0, 0, $month, $day, $year) + ((7-$day_of_week) * 86400)));

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $week_start)
             ->andWhere('i.reportTs <= ?', $week_end);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incident_current['week'] as $CAT => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incident_current['week'][$CAT][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS WEEK'S BREAKDOWN */

        /*  GENERATE THIS MONTH'S BREAKDOWN */
        $month = date('m');
        $year  = date('Y');
        $last_day = date('t');
    
        $month_start = "$year-$month-01";
        $month_end   = "$year-$month-$last_day";

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $month_start)
             ->andWhere('i.reportTs <= ?', $month_end);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incident_current['month'] as $CAT => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incident_current['month'][$CAT][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS MONTHS'S BREAKDOWN */
        
        /*  GENERATE THIS YEAR'S BREAKDOWN */
        $year  = date('Y');
        $next_year = $year + 1;
 
        $year_start = "$year-01-01";
        $year_end   = "$next_year-01-01 00:00:00";

        $q = Doctrine_Query::create()
            ->select('i.id, i.status, c.name, c.id AS classification')
            ->from('Incident i')
            ->leftJoin('i.Category c')
             ->whereIn('i.status', array('open','resolved','closed'))
             ->andWhere('i.reportTs > ?', $year_start)
             ->andWhere('i.reportTs <= ?', $year_end);

        $incidents = $q->execute()->toArray();

        foreach ($incidents as $key => $incident) {
            foreach ($incident_current['year'] as $CAT => $subcats) {
                foreach ($subcats as $id => $subcat) {
                    if ($id == $incident['classification']) {
                        $incident_current['year'][$CAT][$id][$incident['status']] += 1;
                    } 
                }
            }          
        }

        /*  END THIS YEAR'S BREAKDOWN */
        
        $this->view->assign('incident_current', $incident_current);

        $weeks = $this->_getWeeks();
        
        /* GENERATE WEEKS BREAKDOWN */ 
        for($x = 1; $x < 54; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach($cat['children'] as $key => $subcat) {
                    $incident_week[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incident_week[$x][$cat['category']][$subcat['id']]['count'] = 0;
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
                foreach ($incident_week[$x] as $CAT => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incident_week[$x][$CAT][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE WEEKS BREAKDOWN */
   
 
        $this->view->assign('cats', $cats);
        $this->view->assign('weeks', $weeks);
        $this->view->assign('incident_week', $incident_week);
 
        /* GENERATE MONTHS BREAKDOWN */ 
        for($x = 1; $x <= 12; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach($cat['children'] as $key => $subcat) {
                    $incident_month[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incident_month[$x][$cat['category']][$subcat['id']]['count'] = 0;
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
                foreach ($incident_month[$x] as $CAT => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incident_month[$x][$CAT][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE MONTHS BREAKDOWN */
        
        $this->view->assign('incident_month', $incident_month);
        
        /* GENERATE YEARS BREAKDOWN */ 
        for($x = 2008; $x <= 2010; $x += 1) {
            foreach ($cats as $key => $cat) {
                foreach($cat['children'] as $key => $subcat) {
                    $incident_year[$x][$cat['category']][$subcat['id']]['name'] = $subcat['name'];
                    $incident_year[$x][$cat['category']][$subcat['id']]['count'] = 0;
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
                foreach ($incident_year[$x] as $CAT => $subcats) {
                    foreach ($subcats as $id => $subcat) {
                        if ($id == $incident['classification']) {
                            $incident_year[$x][$CAT][$id]['count'] += 1;
                        } 
                    }
                }          
            }
        } 
        /*  END GENERATE YEARS BREAKDOWN */
        
        $this->view->assign('incident_year', $incident_year);
    }

    private function _getCats() {
        /* Get all categories */ 
        $q = Doctrine_Query::create()
             ->select('c.name, c.category')
             ->from('IrCategory c');
        
        $cats = $q->execute()->toArray();        

        /* For each category, get the related subcategories and format them so they will work as a tree */
        foreach($cats as $key => $val) {
            $cats[$key]['children'] =  ''; 

            $q2 = Doctrine_Query::create()
                  ->select('sc.name')
                  ->from('IrSubCategory sc')
                  ->where('sc.categoryId = ?', $val['id']);  

            $cats[$key]['children'] = $q2->execute()->toArray();
            foreach($cats[$key]['children'] as $key2 => $val2) {
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
        for($x=0; $x<51; $x+=1) {
            $interval_1 = $x * 604800;
            $interval_2 = ($x + 1) * 604800;

            $week[$x + 2] = array (
                'start'      => date("Y-m-d", (mktime(0, 0, 0, 1, 4, 2009) + $interval_1)),
                'end'        => date("Y-m-d", (mktime(0, 0, 0, 1, 4, 2009) + $interval_2)),
                'start_nice' => date("M d, Y", (mktime(0, 0, 0, 1, 4, 2009) + $interval_1)),
                'end_nice'   => date("M d, Y", (mktime(0, 0, 0, 1, 4, 2009) + $interval_2))
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

    private function _getSubCats($category) {
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
?>
