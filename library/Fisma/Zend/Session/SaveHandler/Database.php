<?php

class Fisma_Zend_Session_SaveHandler_Database implements Zend_Session_SaveHandler_Interface
{
    const LIFETIME          = 'lifetime';
    const OVERRIDE_LIFETIME = 'overrideLifetime';

    /**
     * Session lifetime
     *
     * @var int
     */
    protected $_lifetime = false;

    /**
     * Whether or not the lifetime of an existing session should be overridden
     *
     * @var boolean
     */
    protected $_overrideLifetime = false;

    /**
     * Session save path
     *
     * @var string
     */
    protected $_sessionSavePath;

    /**
     * Session name
     *
     * @var string
     */
    protected $_sessionName;

    /**
     * Constructor
     *
     * $config is an instance of Zend_Config or an array of key/value pairs containing configuration options for
     * Zend_Session_SaveHandler_DbTable and Zend_Db_Table_Abstract. These are the configuration options for
     * Zend_Session_SaveHandler_DbTable:
     *
     * lifetime          => (integer) Session lifetime (optional; default: ini_get('session.gc_maxlifetime'))
     *
     * overrideLifetime  => (boolean) Whether or not the lifetime of an existing session should be overridden
     *      (optional; default: false)
     *
     * @param  Zend_Config|array $config      User-provided configuration
     * @return void
     * @throws Zend_Session_SaveHandler_Exception
     */
    public function __construct($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } else if (!is_array($config)) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            // require_once 'Zend/Session/SaveHandler/Exception.php';

            throw new Zend_Session_SaveHandler_Exception(
                '$config must be an instance of Zend_Config or array of key/value pairs containing '
              . 'configuration options for Zend_Session_SaveHandler_DbTable and Zend_Db_Table_Abstract.');
        }

        foreach ($config as $key => $value) {
            do {
                switch ($key) {
                    case self::LIFETIME:
                        $this->setLifetime($value);
                        break;
                    case self::OVERRIDE_LIFETIME:
                        $this->setOverrideLifetime($value);
                        break;
                    default:
                        // unrecognized options passed to parent::__construct()
                        break 2;
                }
                unset($config[$key]);
            } while (false);
        }

        // init lifetime
        $this->setLifetime($this->getLifetime());
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
        Zend_Session::writeClose();
    }

    /**
     * Set session lifetime and optional whether or not the lifetime of an existing session should be overridden
     *
     * $lifetime === false resets lifetime to session.gc_maxlifetime
     *
     * @param int $lifetime
     * @param boolean $overrideLifetime (optional)
     * @return Zend_Session_SaveHandler_DbTable
     */
    public function setLifetime($lifetime, $overrideLifetime = null)
    {
        if ($lifetime < 0) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            // require_once 'Zend/Session/SaveHandler/Exception.php';
            throw new Zend_Session_SaveHandler_Exception();
        } else if (empty($lifetime)) {
            $this->_lifetime = (int) ini_get('session.gc_maxlifetime');
        } else {
            $this->_lifetime = (int) $lifetime;
        }

        if ($overrideLifetime != null) {
            $this->setOverrideLifetime($overrideLifetime);
        }

        return $this;
    }

    /**
     * Retrieve session lifetime
     *
     * @return int
     */
    public function getLifetime()
    {
        return $this->_lifetime;
    }

    /**
     * Set whether or not the lifetime of an existing session should be overridden
     *
     * @param boolean $overrideLifetime
     * @return Zend_Session_SaveHandler_DbTable
     */
    public function setOverrideLifetime($overrideLifetime)
    {
        $this->_overrideLifetime = (boolean) $overrideLifetime;

        return $this;
    }

    /**
     * Retrieve whether or not the lifetime of an existing session should be overridden
     *
     * @return boolean
     */
    public function getOverrideLifetime()
    {
        return $this->_overrideLifetime;
    }

    /**
     * Open Session
     *
     * @param string $save_path
     * @param string $name
     * @return boolean
     */
    public function open($save_path, $name)
    {
        $this->_sessionSavePath = $save_path;
        $this->_sessionName     = $name;

        return true;
    }

    /**
     * Close session
     *
     * @return boolean
     */
    public function close()
    {
        return true;
    }

    /**
     * Read session data
     *
     * @param string $id
     * @return string
     */
    public function read($id)
    {
        $return = '';

        $row = Doctrine::getTable('PhpSession')->find($this->_getPrimary($id));

        if (!empty($row)) {
            if ($this->_getExpirationTime($row) > time()) {
                $return = $row->data;
            } else {
                $this->destroy($id);
            }
        }

        return $return;
    }

    /**
     * Write session data
     *
     * @param string $id
     * @param string $data
     * @return boolean
     */
    public function write($id, $data)
    {
        $data = array('modified' => time(),
                      'data'     => (string) $data);

        $primary = $this->_getPrimary($id);
        $row = Doctrine::getTable('PhpSession')->find($primary);

        if (!empty($row)) {
            $data['lifetime'] = $this->_getLifetime($row);
        } else {
            $row = new PhpSession();
            $data = array_merge($primary, $data);
            $data['lifetime'] = $this->_lifetime;
        }
        $row->merge($data);
        $row->save();

        return true;
    }

    /**
     * Destroy session
     *
     * @param string $id
     * @return boolean
     */
    public function destroy($id)
    {
        $row = Doctrine::getTable('PhpSession')->find($this->_getPrimary($id));
        if (!empty($row)) {
            $row->delete();
        }

        return true;
    }

    /**
     * Garbage Collection
     *
     * @param int $maxlifetime
     * @return true
     */
    public function gc($maxlifetime)
    {
        //@TODO: convert to doctrine
        //$this->delete($this->getAdapter()->quoteIdentifier($this->_modifiedColumn, true) . ' + '
        //            . $this->getAdapter()->quoteIdentifier($this->_lifetimeColumn, true) . ' < '
        //            . $this->getAdapter()->quote(time()));

        return true;
    }

    /**
     * Retrieve session table primary key values
     *
     * @param string $id
     * @param string $type (optional; default: self::PRIMARY_TYPE_NUM)
     * @return array
     */
    protected function _getPrimary($id, $type = null)
    {
        return array(
            'savePath' => $this->_sessionSavePath,
            'name' => $this->_sessionName,
            'id' => (string) $id
        );
    }

    /**
     * Retrieve session lifetime considering Zend_Session_SaveHandler_DbTable::OVERRIDE_LIFETIME
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @return int
     */
    protected function _getLifetime(PhpSession $row)
    {
        $return = $this->_lifetime;

        if (!$this->_overrideLifetime) {
            $return = (int) $row->lifetime;
        }

        return $return;
    }

    /**
     * Retrieve session expiration time
     *
     * @param Zend_Db_Table_Row_Abstract $row
     * @return int
     */
    protected function _getExpirationTime(PhpSession $row)
    {
        return (int) $row->modified + $this->_getLifetime($row);
    }
}
