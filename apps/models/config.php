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
    protected $_name = 'configurations';
    protected $_primary = 'id';
}

