<?php
/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * A view helper which translates, escapes, and echoes the logicalName of a column for the current $view->table
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2013 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    View_Helper
 */
class View_Helper_Column extends Zend_View_Helper_Abstract
{
    public function column($columnName, $table = null, $echo = true)
    {
        $view = $this->view;
        $table = ($table) ? $table : (isset($view->table) ? $view->table : null);

        $column = $columnName;
        if ($table) {
            $column = $view->translate($table->getLogicalName($columnName));
        }

        if ($echo) {
            echo $column;
        }

        return $view->escape($column);
    }
}
