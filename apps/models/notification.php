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

    /**
     * Add notification record
     *
     * @param int $eventId the id of the event name
     * @param string $userName
     * @param int or array $record_id such as poam_id, asset_id, system_id and so on.
     */
    public function add($eventId, $userName, $record_id)
    {
        if (is_array($record_id)) {
            $record = implode(",", $record_id);
        } else {
            $record = $record_id;
        }
        $event = new event();
        $eventName = $event->getEventName($eventId);
        
        $event_text = "$eventName (ID ($record) by $userName ";
        $data = array('event_id'=> $eventId,
                      'event_text'=> $event_text,
                      'timestamp'=>Zend_Date::now()->toString("Y-m-d H:i:s"));
        $this->insert($data);
    }
}
