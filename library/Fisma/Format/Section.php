<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * Present a HTML content section which contains specific content
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Format
 */
class Fisma_Format_Section
{
    /**
     * Renders start part of a HTMl panel section
     *
     * @param string $title The speicifed panel title
     * @param string|null $editableTarget The specified editable target element name
     * @param string $anchorName An anchor name attribute (optional)
     * @param string $id An HTML id for the sectionContainer
     * @return void
     * @yui document this class
     */
    static function startSection($title, $editableTarget = null, $anchorName = null, $id = null)
    {
        $anchorStartTag = isset($anchorName) ? "<a name='$anchorName'>" : '';
        $anchorEndTag = isset($anchorName) ? "</a>" : '';

        $editableText = isset($editableTarget) ? "class='editable' target='$editableTarget'" : '';

        $render = "<div class='sectionContainer'"
                . ((!empty($id)) ? " id='$id'" : "")
                . "><div class='sectionHeader'>"
                . "<span $editableText>{$anchorStartTag}{$title}{$anchorEndTag}</span></div>"
                . "<div class='section'>";

        print $render;
    }

    /**
     * Renders stop part of a HTMl panel section
     *
     * @return void
     */
    static function stopSection()
    {
        print "<div class='clear'></div></div></div>\n";
    }
}
