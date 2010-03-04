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
 * ZFDebug Doctrine ORM plugin
 *
 * @category   Danceric
 * @package    Danceric_Controller
 * @subpackage Plugins
 * @uses ZFDebug
 * @uses _Controller_Plugin_Debug_Plugin
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 *
 * Ganked from {@link http://github.com/danceric/zfdebugdoctrine}
 */
class Danceric_Controller_Plugin_Debug_Plugin_Doctrine extends ZFDebug_Controller_Plugin_Debug_Plugin 
implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{
    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'doctrine';

    /**
     * @var array Doctrine connection profiler that will listen to events
     */
    protected $_profilers = array();

    /**
     * Create ZFDebug_Controller_Plugin_Debug_Plugin_Variables
     *
     * @param Doctrine_Manager|array $options
     * @return void
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['manager']) || !count($options['manager'])) {
            if (Doctrine_Manager::getInstance()) {
                $options['manager'] = Doctrine_Manager::getInstance();
            }
        }

        foreach ($options['manager']->getIterator() as $connection) {
            $this->_profilers[$connection->getName()] = new Doctrine_Connection_Profiler();
            $connection->setListener($this->_profilers[$connection->getName()]);
        }
    }

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab()
    {
        if (!$this->_profilers)
            return 'No Profiler';

        foreach ($this->_profilers as $profiler) {
            $time = 0;
            foreach ($profiler as $event) {
                $time += $event->getElapsedSecs();
            }
            $profilerInfo[] = $profiler->count() . ' in ' . round($time*1000, 2)  . ' ms';
        }
        $html = implode(' / ', $profilerInfo);

        return $html;
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel()
    {
        if (!$this->_profilers)
            return '';

        $html = '<h4>Database queries</h4>';
        
        foreach ($this->_profilers as $name => $profiler) {
                $html .= '<h4>Connection '.$name.'</h4><ol>';
                foreach ($profiler as $event) {
                    if (in_array($event->getName(), array('query', 'execute', 'exec'))) {
                        $info = htmlspecialchars($event->getQuery());
                    } else {
                        $info = '<em>' . htmlspecialchars($event->getName()) . '</em>';
                    }

                    $html .= '<li><strong>[' . round($event->getElapsedSecs()*1000, 2) . ' ms]</strong> ';
                    $html .= $info;
                
                    $params = $event->getParams();
                    if (!empty($params)) {
                        $html .= '<ul><em>bindings:</em> <li>'. implode('</li><li>', $params) . '</li></ul>';
                    }
                    $html .= '</li>';
                }
                $html .= '</ol>';
        }

        return $html;
    }

}
