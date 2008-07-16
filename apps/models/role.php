<?php
/**
 * @file role.php
 *
 * @description role model
 *
 * @author     Ryan<ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/
require_once 'Abstract.php';
class Role extends Fisma_Model
{
    protected $_name = 'roles';
    protected $_primary = 'id';
}

