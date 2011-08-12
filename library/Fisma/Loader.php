<?php
/**
 * Copyright (c) 2009 Endeavor Systems, Inc.
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
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @copyright (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license   http://openfisma.org/content/license 
 * @package   Fisma_Loader
 */

require_once (realpath(dirname(__FILE__) . '/../../public/phploader/loader.php'));

/**
 * Fisma_Loader - Loader class for loading JS and CSS via the YUI phploader library. phploader is a YUI PHP library that
 * allows us to load all of the necessary YUI components in a single HTTP request, rather than many seperate requests.
 *
 * @copyright (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license    http://openfisma.org/content/license
 * @package    Fisma_Loader
 */
class Fisma_Loader
{
    private $_appVersion;
    private $_yuiVersion;
    private $_debug;
    private $_loader;
    private $_config;

    /**
     * __construct - Set up and initialize the phploader object
     *
     * @param array $config Custom module metadata set for YUI phploader
     * @access public
     * @return void
     */
    public function __construct($config = NULL)
    {
        $versions = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getOption('versions');
        $this->_appVersion = $versions['application'];
        $this->_yuiVersion = $versions['yui'];
        $this->_debug = Fisma::debug();

        /**
         * Create the loader object. Pass custom configuration options if available.
         */
        if (is_null($config)) {
            $this->_loader = new YAHOO_util_Loader($this->_yuiVersion);
        } else {
            $this->_config = $config;
            $this->_loader = new YAHOO_util_Loader($this->_yuiVersion, 'custom_config_' 
                             . $this->_appVersion, $this->_config);
        }

        $this->_loader->base = "/lib/" . $this->_yuiVersion . "/build/";

        /**
         * If we're in debug mode, turn off rollups and combines, switch to DEBUG filter.
         */
        if ($this->_debug) {
            $this->_loader->allowRollups = FALSE;
            $this->_loader->filter = YUI_DEBUG;
            $this->_loader->combine = FALSE;
        } elseif ($this->_loader->apcAvail && $this->_loader->curlAvail) {
            /**
             * If embedding is available, we turn on rollups and local combo loader
             */
            $this->_loader->allowRollups = TRUE;
            $this->_loader->combine = TRUE;
            $this->_loader->comboBase = "/phploader/combo.php?";
        } else {
            /**
             * If embedding isn't available, rollups are turned on, but the combo loader is off
             * Embedding requires that both APC and cURL are available.
             */
            $this->_loader->allowRollups = TRUE;
            $this->_loader->combine = FALSE;
        }
    }

    /**
     * load - Load components into the YUI phploader
     *
     * @param array $components Array of components to load
     * @access public
     * @return void
     */
    public function load($components)
    {
        foreach ($components as $component) {
            $this->_loader->load($component);
        }
    }

    /**
     * script - Wrapper for phploader script tags
     *
     * @access public
     * @return string Script tags
     */
    public function script()
    {
        return $this->_loader->script();
    }

    /**
     * css - Wrapper for phploader CSS link tags
     *
     * @access public
     * @return string CSS link tags
     */
    public function css()
    {
        return $this->_loader->css();
    }

    /**
     * __toString - toString method Wrapper for script() and css()
     *
     * @access public
     * @return string Combined script and CSS tags
     */
    public function __toString()
    {
        $script = $this->script();
        $css = $this->css();
        return $script . $css;
    }
}
