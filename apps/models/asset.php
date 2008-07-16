<?php
/**
 * @file asset.php
 *
 * @description asset model
 *
 * @author     Jim <jimc@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Db/Table.php';

class asset extends Zend_Db_Table
{
    protected $_name = 'assets';
    protected $_primary = 'id';

}

?>
