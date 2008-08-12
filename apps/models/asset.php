<?php
/**
 * asset.php
 *
 * asset model
 *
 * @package Model
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */
require_once 'Zend/Db/Table.php';
/**
 * @package Model
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class asset extends Zend_Db_Table
{
    protected $_name = 'assets';
    protected $_primary = 'id';
}
