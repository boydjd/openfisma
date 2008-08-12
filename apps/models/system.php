<?php
/**
 * system.php
 *
 * system model
 *
 * @author     Ryan ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once 'Abstract.php';
/**
 * @package Model
 * @author     Ryan ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class System extends Fisma_Model
{
    protected $_name = 'systems';
    protected $_primary = 'id';
    /**
     * getList
     *
     * The system class overrides getList in order to format the system list in
     * a specific way.
     *
     * The system list should be ordered by system nickname and should display
     * the nickname in parentheses and then the system name, e.g.:
     *
     * (SN) System Name
     *
     * TODO temporarily this function patches through to the parent implementation
     * when the parameters are set to anything but the default values.
     * ideally it wouldn't do this, but for backwards compatibility this is the
     * quickest way to implement it without breaking numerous other pieces.
     */
    public function getList ($fields = '*', $primary_key = null, $order = null)
    {
        if (($fields === '*') && ($primary_key === null) && ($order === null)) {
            $system_list = array();
            $query = $this->select(array($this->_primary , 'nickname' , 'name'))->distinct()->from($this->_name)->order('nickname');
            $result = $this->fetchAll($query);
            foreach ($result as $row) {
                $system_list[$row->id] = array('name' => ('(' . $row->nickname . ') ' . $row->name));
            }
            return $system_list;
        } else {
            return parent::getList($fields, $primary_key, $order);
        }
    }
}
