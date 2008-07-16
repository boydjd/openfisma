<?php
/**
 * @file network.php
 *
 * @description network model
 *
 * @author     Ryan<ryan.yang@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
*/
require_once 'Abstract.php';
class Network extends Fisma_Model
{
    protected $_name = 'networks';
    protected $_primary = 'id';
}
