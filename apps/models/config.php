<?php
/**
 * config.php
 *
 * config model
 *
 * @package Model
 * @author     Ryan ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once MODELS . DS . 'Abstract.php';
/**
 * @package Model
 * @author     Ryan ryan at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class Config extends Fisma_Model
{
    const MAX_ABSENT    = 'max_absent_time';
    const AUTH_TYPE     = 'auth_type';
    const F_THRESHOLD   = 'failure_threshold';
    const EXPIRING_TS   = 'expiring_seconds';

    protected $_name = 'configurations';
    protected $_primary = 'id';
    protected $_ldaps = array('name'=>'ldap_config',
                              'primary'=>'id');



    /**
     *  Retrive the ldap configuration(s)
     *
     *  @param numeric $id default null the group id of ldap config
     *  @return array all the configurations of LDAP servers. One configuration 
     *      if the $id is specified. 
     */
    public function getLdap($id=null)
    {
        $ldapConfig = new Fisma_Model($this->_ldaps);
        if (!empty($id)) {
            $ret = $ldapConfig->find($id);
        } else {
            $ret = $ldapConfig->fetchAll();
        }
        return $ret->toArray();
    }

    /**
     *  Save/Add LDAP configuration
     *
     *  @param array $value data to be saved/added
     */
     public function saveLdap($values)
     {
        $ldapConfig = new Fisma_Model($this->_ldaps);
        if (empty($values['id'])) {
            $ret = $ldapConfig->insert($values);
        } else {
            $id = $values['id'];
            unset($values['id']);
            $ret = $ldapConfig->update($values, "id=$id");
        }
        return $ret;
     }

    /**
     *  Delete LDAP configuration
     *
     *  @param numeric $id the key of the configuration
     */
     public function delLdap($id)
     {
        assert(is_numeric($id));
        $ldapConfig = new Fisma_Model($this->_ldaps);
        return $ldapConfig->delete("id=$id");
     }
}

