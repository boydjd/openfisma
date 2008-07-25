<?php
/**
 * sysgroup.php
 *
 * system_group model
 *
 * @package Model
 * @author     Ryan<ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/

require_once MODELS . DS . 'Abstract.php';

class Sysgroup extends Fisma_Model
{
    protected $_name = 'system_groups';
    protected $_primary = 'id';
}

