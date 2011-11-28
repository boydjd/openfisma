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
 * A plugin for ZFDebug toolbar which displays the YUI logging widget.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    ZfDebug
 */
class Fisma_ZfDebug_Plugin_YuiLogging implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{
    //constants
    const TAB_NAME = 'YUI Logging';
    const ICON_MIME = 'data:image/png';
    const ICON_DATA = <<<STR
base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAHhSURBVDjLpZI9SJVxFMZ/r2YFflw/kcQs
iJt5b1ije0tDtbQ3GtFQYwVNFbQ1ujRFa1MUJKQ4VhYqd7K4gopK3UIly+57nnMaXjHjqotnOfDnnOd/nt85SURwkDi02+ODqbsldxUlD0mvHw09ubSXQF1t8512nGJ/Uz/5lnxi0tB+E9QI3D//+EfVqhtppGx
UNzCzmf0Ekojg4fS9cBeSoyzHQNuZxNyYXp5ZM5Mk1ZkZT688b6thIBenG/N4OB5B4InciYBCVyGnEBHO+/LH3SFKQuF4OEs/51ndXMXC8Ajqknrcg1O5PGa2h4CJUqVES0OO7sYevv2qoFBmJ/4gF4boaOrg6r
PLYWaYiVfDo0my8w5uj12PQleB0vcp5I6HsHAUoqUhR29zH+5B4IxNTvDmxljy3x2YCYUwZVlbzXJh9UKeQY6t2m0Lt94Oh5loPdqK3EkjzZi4MM/Y9Db3MTv/mYWVxaqkw9IOATNR7B5ABHPrZQrtg9sb8XDKa
1+QOwsri4zeHD9SAzE1wxBTXz9xtvMc5ZU5lirLSKIz18nJnhOZjb22YKkhd4odg5icpcoyL669TAAujlyIvmPHSWXY1ti1AmZ8mJ3ElP1ips1/YM3H300g+W+51nc95YPEX8fEbdA2ReVYAAAAAElFTkSuQmCC
STR;
    const LAYOUT_NOT_INSTANTIATED_ERROR = 1;
    /**
     * Has to return html code for the menu tab
     *
     * @return string
     */
    public function getTab()
    {
        return self::TAB_NAME;
    }

    /**
     * Has to return html code for the content panel
     *
     * @param Zend_Layout $layout Optional, defaults to null. 
     * @return string
     * @throws Fisma_Zend_Exception
     */
    public function getPanel($layout = null)
    {
        //if $layout is not provided, use currentLayout
        if($layout==null) {
            $layout = Zend_Layout::getMvcInstance();
        }

        //if currentLayout is not instantiated, return with an error code
        if($layout==null) {
            throw new Fisma_Zend_Exception(self::LAYOUT_NOT_INSTANTIATED_ERROR);
        } else {
            $view = $layout->getView();
        }

        return $view->partial('debug/zfdebug-yui-logging-tab.phtml');
    }

    /**
     * Has to return a unique identifier for the specific plugin
     *
     * @return string
     */
    public function getIdentifier()
    {
        return get_class($this);
    }
    
    /**
     * Return the path to an icon
     *
     * @return string
     */
    public function getIconData()
    {
        return self::ICON_MIME.';'.self::ICON_DATA;
    }
}
