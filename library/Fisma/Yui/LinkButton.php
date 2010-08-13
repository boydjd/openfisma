<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * A PHP wrapper for the YUI Button class used to decorate links
 *
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 */
class Fisma_Yui_LinkButton
{
    /**
     * Link tag id
     *
     * @var string
     */
    protected $_linkId;

    /**
     * Link/button label
     *
     * @var string
     */
    protected $_linkLabel;

    /**
     * Link URL of this button
     *
     * @var string
     */
    protected $_linkUrl;

    /**
     * Create an new LinkButton
     *
     * @param string $id  Link tag id
     * @param string $label Link/button label
     * @param string $url Link to which the buttons points.
     */
    public function __construct($id, $label, $url)
    {
        $this->_linkId = $id;
        $this->_linkLabel = $label;
        $this->_linkUrl = $url;
    }

    /**
     * Render button to markup string
     *
     * @return string Button markup
     */
    public function __toString()
    {
        return '<a id="' . $this->_linkId . '"'
             . '   href="' . $this->_linkUrl . '">'
             . $this->_linkLabel
             . '</a>'
             . '<script type="text/javascript">'
             . 'new YAHOO.widget.Button("' . $this->_linkId . '");'
             . '</script>';
    }
}
