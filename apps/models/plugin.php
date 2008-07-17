<?php
/**
 * @file plugin.php
 *
 * plugin model
 *
 * @author     Ryan<ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/
require_once 'Abstract.php';
class Plugin extends Fisma_Model
{
    protected $_name = 'plugins';
    protected $_primary = 'id';
}
