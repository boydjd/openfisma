/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    Fisma.Menu = {
        resolveOnClickObjects: function(obj) {
            if (obj.onclick && obj.onclick.fn) {
                obj.onclick.fn = Fisma.Util.getObjectFromName(obj.onclick.fn);
            }

            if (obj.submenu) {
                var groups = obj.submenu.itemdata;
                var i;
                for (i in groups) {
                    var group = groups[i];
                    var j;
                    for (j in group) {
                        var item = group[j];
                        Fisma.Menu.resolveOnClickObjects(item);
                    }
                }
            }
        },

        goTo: function(eType, eObject, param) {
            Fisma.Util.showInputDialog(
                "Go To " + param.model + "...",
                "ID",
                {
                    'continue': function(ev, obj) {
                        YAHOO.util.Event.stopEvent(ev);
                        var input = Number(obj.textField.value.trim());
                        if (isFinite(input)) {
                            obj.errorDiv.innerHTML = "Navigating to ID " + input + "...";
                            window.location = param.controller + "/view/id/" + input;
                        } else { // input NaN
                            obj.errorDiv.innerHTML = "Please enter a single ID number.";
                        }
                    },
                    'cancel': function(ev, obj) {
                    }
                }
            );
        }
    };
}());
