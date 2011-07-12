<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * Fisma_Zend_Log_Writer_Stream 
 * 
 * @uses Zend_Log_Writer_Stream
 * @package Fisma_Zend_Log_Writer 
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Zend_Log_Writer_Stream extends Zend_Log_Writer_Stream
{
    /**
     * __construct 
     * 
     * @param mixed $streamOrUrl 
     * @param mixed $mode 
     * @access public
     * @return void
     */
    public function __construct($streamOrUrl, $mode = null)
    {
        parent::__construct($streamOrUrl, $mode = null);

        $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '(none)';
            
        // Log the current username if we are in an authenticated session
        if (CurrentUser::getInstance()) {
            $username = CurrentUser::getInstance()->username;
        } else {
            $username = '(none)';
        }
            
        $format = "%timestamp% level=%priorityName% user=$username ip=$ip\n%message%\n\n";
        $formatter = new Zend_Log_Formatter_Simple($format);

        $this->_formatter = $formatter;
    }

    /**
     * Create a new instance of Fisma_Zend_Log_Writer_Stream
     *
     * This is a direct copy of the factory method from Zend_Log_Writer_Stream. The reason this is here is due to the
     * usage of self() in the parent class. Because PHP 5.2 does not have late static binding, self() ends up calling
     * the constructor of the parent class, rather than the overridden constructor that we've defined and implemented
     * here in this class. This could easily be resolved by changing Zend_Log to use the static() keyword instead of
     * the self() keyword, which was added in PHP 5.3 along with late static binding. Alas, this is not going to
     * happen as ZF still supports PHP 5.2, so this ugliness will just have to be here.
     *
     * @param  array|Zend_Config $config
     * @return Zend_Log_Writer_Stream
     */
    static public function factory($config)
    {
        $config = self::_parseConfig($config);
        $config = array_merge(array(
            'stream' => null,
            'mode'   => null,
        ), $config);

        $streamOrUrl = isset($config['url']) ? $config['url'] : $config['stream'];

        return new self(
            $streamOrUrl,
            $config['mode']
        );
    }
}
