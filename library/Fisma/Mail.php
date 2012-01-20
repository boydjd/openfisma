<?php
/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 *  This is generic encapsulation of mail in OpenFISMA.
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Mail
 */
class Fisma_Mail
{
    /**
     * Array for Fisma_Mail
     * 
     * @var array
     */
    private $_mail = array();

    /**
     * Constructor
     * 
     * @param array $configs
     * @param string $template
     * @param array $options 
     * @return void
     */
    public function __construct($configs = array(), $template = null, $options = array())
    {
        foreach ($configs as $key => $data) {
            $this->_mail[$key] = $data;
        }

        if (empty($this->_mail['sender'])) {
            $this->_mail['sender'] = Fisma::configuration()->getConfig('sender');
        }

        if (empty($this->_mail['senderName'])) {
            $this->_mail['senderName'] = Fisma::configuration()->getConfig('system_name');
        }

        if ($template) {
            $view = new Fisma_Zend_View();
            $view->setScriptPath(
                Fisma::getPath('application') . '/modules/default/views/scripts/mail/'
            );

            foreach ($options as $k => $v) {
                $view->$k = $v;
            }

            $this->_mail['body'] = $view->render("$template.phtml");
        }
    }

    /**
     * Set mail variable
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->_mail[$key] = $value;
    }

    /**
     * Get mail variable
     *
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->_mail[$key])) {
            return $this->_mail[$key];
        }

        return null;
    }
}

