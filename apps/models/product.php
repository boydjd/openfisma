<?php
/**
 * @file product.php
 *
 * @description product model
 *
 * @author     Ryan<ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once 'Zend/Db/Table.php';

class Product extends Zend_Db_Table
{
    protected $_name = 'products';
    protected $_primary = 'id';

}

?>
