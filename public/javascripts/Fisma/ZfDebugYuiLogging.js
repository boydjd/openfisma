/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * along with OpenFISMA.  If not, see {@link http://www.gnu.org/licenses/}.
 *
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * Instantiate a global ZfDebugYuiLogging and if the layout has a container for it.
     */
    YAHOO.util.Event.onDOMReady(function () {
        YAHOO.util.Event.throwErrors = true;
        var zfDebugYuiLoggingTab = document.getElementById('zfdebug_yui_logging_tab');

        if (zfDebugYuiLoggingTab) {
            var zfDebugYuiLogging = new Fisma.ZfDebugYuiLogging(zfDebugYuiLoggingTab);
        }
    });

    /**
     * Render YUI Logger on container
     * 
     * @namespace Fisma
     * @class ZfDebugYuiLogging
     * @extends n/a
     * @constructor
     */
    var ZFDYL = function(container) {
        var logReader = new YAHOO.widget.LogReader(
                container, 
                {
                    draggable : false,
                    verboseOutput : false,
                    width : '95%'
                }
            );

        logReader.hideCategory("info");
        logReader.hideCategory("time");
        logReader.hideCategory("window");
        logReader.hideCategory("iframe");
    };

    Fisma.ZfDebugYuiLogging = ZFDYL;
})();
