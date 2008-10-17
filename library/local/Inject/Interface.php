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
 *
 */


/**
 * The interface of plugins for various scan reports
 */
interface Inject_Interface
{
    /** 
     * To decide if the file is valid according to this type.
     *
     * @param string $file filename 
     * @return boolean
     */
    public function isValid($file);

    /** 
     * Convert the file to an intermediate format, which is iteratable 
     * and can be can be read or injected into database
     *
     * @param string $data the content of the injecting content
     * @return mixed
     */
    public function parse($data);
}
