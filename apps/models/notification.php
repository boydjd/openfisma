<?php
/**
 * notification.php
 *
 * notification model
 *
 * @package Model
 * @author     chris chris users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once 'Zend/Db/Table/Abstract.php';
require_once (MODELS . DS . 'event.php');
/*
 * @package Model
 * @author     chris chris users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class Notification extends Fisma_Model
{
    /**
     * The default table name 
     */
    protected $_name = 'notifications';
    protected $_primary = 'id';
    
    /**
     * Notification events name
     *
     */
    const FINDING_CREATED = 1;
    const FINDING_IMPORT = 2;
    const FINDING_INJECT = 3;
    const ASSET_MODIFIED = 4;
    const ASSET_CREATED = 5;
    const ASSET_DELETED = 6;
    const UPDATE_COURSE_OF_ACTION = 7;
    const UPDATE_FINDING_ASSIGNMENT = 8;
    const UPDATE_CONTROL_ASSIGNMENT = 9;
    const UPDATE_COUNTERMEASURES = 10;
    const UPDATE_THREAT = 11;
    const UPDATE_FINDING_RECOMMENDATION = 12;
    const UPDATE_FINDING_RESOURCES = 13;
    const UPDATE_EST_COMPLETION_DATE = 14;
    const MITIGATION_STRATEGY_APPROVED = 15;
    const POAM_CLOSED = 16;
    const EVIDENCE_UPLOAD = 17;
    const EVIDENCE_APPROVAL_1ST = 18;
    const EVIDENCE_APPROVAL_2ND = 19;
    const EVIDENCE_APPROVAL_3RD = 20;
    const ACCOUNT_MODIFIED = 21;
    const ACCOUNT_DELETED = 22;
    const ACCOUNT_CREATED = 23;
    const SYSGROUP_DELETED = 24;
    const SYSGROUP_MODIFIED = 25;
    const SYSGROUP_CREATED = 26;
    const SYSTEM_DELETED = 27;
    const SYSTEM_MODIFIED = 28;
    const SYSTEM_CREATED = 29;
    const PRODUCT_CREATED = 30;
    const PRODUCT_MODIFIED = 31;
    const PRODUCT_DELETED = 32;
    const ROLE_CREATED = 33;
    const ROLE_DELETED = 34;
    const ROLE_MODIFIED = 35;
    const SOURCE_CREATED = 36;
    const SOURCE_MODIFIED = 37;
    const SOURCE_DELETED = 38;
    const NETWORK_MODIFIED = 39;
    const NETWORK_CREATED = 40;
    const NETWORK_DELETED = 41;
    const CONFIGURATION_MODIFIED = 42;
    const ACCOUNT_LOGIN_SUCCESS = 43;
    const ACCOUNT_LOGIN_FAILURE = 44;
    const ACCOUNT_LOGOUT = 45;

    /**
     * Add notification record
     *
     * @param int $eventId the id of the event name
     * @param string $userName
     * @param int or array $record_id such as poam_id, asset_id,
     * system_id and so on.
     */
    public function add($eventId, $userName, $recordId)
    {
        if (is_array($recordId)) {
            $record = implode(",", $recordId);
        } else {
            $record = $recordId;
        }

        $event = new Event();
        $ret = $event->find($eventId);
        if (empty($ret)) {
            throw new fisma_Exception('Event name is not exist');
        }
        $eventName = $ret->current()->name;

        $query = $this->_db->select()->from('users','id')
                       ->where('account = ?', $userName);
        $result = $this->_db->fetchRow($query);
        $userId = $result['id'];

        $eventText = "$eventName by $userName($record) ";
        $data = array('event_id'=> $eventId,
                      'user_id' => $userId,
                      'event_text'=> $eventText,
                      'timestamp'=>Zend_Date::now()->toString("Y-m-d H:i:s"));
        $this->insert($data);
    }

    /**
     * Get event record which user interested.
     *
     * the result excludes those events caused by the observer himself.
     * i.e. $userID. 
     *
     * @param int $userId
     * @param string $beforTime
     * @param string $afterTime
     * @return array event records
     */
    public function getEventData($userId, $afterTime, $beforTime=null)
    {
        if (!isset($beforTime)) {
            $beforTime = Zend_Date::now()->toString('Y-m-d H:i:s');
        }

        $query = $this->_db->select()
                           ->from(array('n'=>'notifications'), 'n.*')
                           ->join(array('e'=>'events'), 'e.id = n.event_id',
                                array('event_name'=>'e.name'))
                           ->join(array('ue'=>'user_events'),
                                'ue.event_id = e.id', array())
                           ->where('ue.user_id = ?', $userId)
                           ->where('n.user_id != ?', $userId)
                           ->where('n.timestamp >= ?', $afterTime)
                           ->where('n.timestamp < ?', $beforTime)
                           ->order('n.user_id')
                           ->order('e.name')
                           ->order('n.timestamp');
        $ret = $this->_db->fetchAll($query);
        return $ret;
    }
}
