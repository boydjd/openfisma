<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Model
 */

/**
 * A business object which represents assets belonging to a particular
 * information system.
 *
 * @package   Model
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 */
class Asset extends Zend_Db_Table
{
    protected $_name = 'assets';
    protected $_primary = 'id';
    
    /**
     * getAssetId() - Retrieve an asset ID by using the unique key (network id, ip address, port)
     *
     * @param int $networkId
     * @param int $ipAddress
     * @param int $port
     * @return int Return can be null if no asset is found
     *
     * @todo I like this as a static function, but it doesnt fit well with ZF's table class. Ideal solution is
     * to create new subclass of Zend_Db_Table which has better support for static operations.
     */
    static function getAssetId($networkId, $ipAddress, $port) {
        $asset = new Asset();
        $sql = 'SELECT id
                  FROM assets
                 WHERE network_id = ?
                   AND address_ip = ?
                   AND address_port = ?';
        $assetData = $asset->getAdapter()->fetchRow($sql, array($networkId, $ipAddress, $port));
        $assetId = isset($assetData) ? $assetData['id'] : null;
        return $assetId;
    }
}
