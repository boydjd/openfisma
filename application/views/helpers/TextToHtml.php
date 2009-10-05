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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://openfisma.org/content/license
 * @version   $Id$
 * @package   View_Helper
 */

/**
 * Helper for rendering plain text as HTML.
 * 
 * For example, replacing line breaks with <p> tag pairs or <br>'s
 */
class View_Helper_TextToHtml extends Zend_View_Helper_Abstract
{
    /**
     * Render plain text to HTML
     *
     * @param string $text Plain text
     * @return string HTML
     */
    public function TextToHtml($text)
    {
        // Wrap in <p> tags
        $text = "<p>$text</p>";
        
        // Replace consecutive newlines with </p><p> and single newlines with <br>
        $text = str_replace("\n\n", '</p><p>', $text);
        $text = str_replace("\n", '<br>', $text);
        
        return $text;
    } 
}
