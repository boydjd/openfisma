<?php
/**
 * finding.php
 *
 * finding model
 *
 * @package Model
 * @author     Ryan ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once MODELS . DS . 'poam.php';

/**
 * @package Model
 * @author     Ryan ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class Finding extends Poam
{
    /**
        count the summary of findings according to certain criteria

        @param $date_range discovery time range
        @param $systems system id those findings belongs to
        @return array of counts
    */
    public function getStatusCount($systems, $date_range=array(),$status=null)
    {
        assert(!empty($systems) && is_array($systems) );
        $criteria = array();
        if( isset($date_range) ){
            // range follows [from, to)
            if( !empty($date_range['from']) ){
                $criteria['created_date_begin'] = $date_range['from'];
            }
            if( !empty($date_range['to']) ){
                $criteria['created_date_end'] = $date_range['to'];
            }
        }
        if( isset($status) ) {
            $criteria = array_merge($criteria, array('status'=>$status) );
            if(is_string($status) ){
                $status = array($status);
            }
            foreach($status as $s ){
                $ret[$s] = 0;
            }
        }else{
            $ret = array('NEW'=>0, 'OPEN'=>0, 'EN'=>0, 'EP'=>0, 'ES'=>0, 'CLOSED'=>0, 'DELETED'=>0);
        }
        $raw = $this->search($systems,array('status'=>'status','count'=>'status'),$criteria);
        foreach($raw as $s) {
            $ret[$s['status']] = $s['count'];
        }
        return $ret;
    }

}

