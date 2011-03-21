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
 *
 * @fileoverview Handle adding and removing tabs for role/organization assignments to the tabview
 *
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license http://www.openfisma.org/content/license
 * @version $Id$
 */

Fisma.TabView.Roles = function() {
    return {
        init : function(roles, userid, readOnly) {
            YAHOO.util.Event.addListener('role', 'change', function(e) {
                YAHOO.util.Dom.batch(YAHOO.util.Dom.getChildren('role'), function(el) {
                    var tabView = Fisma.tabView;
                    var tabs = tabView.get('tabs');

                    if (el.selected) {
                        var found = 0;
                        
                        for (var i in tabs) {
                            if (tabs[i].get('id') == el.value) {
                                found = 1;
                                break;
                            }
                        }

                        if (!found) {
                            for (var i in roles) {
                                if (roles[i]['id'] == el.value) {
                                    var label = $P.htmlspecialchars(roles[i]['nickname']);
                                    break;
                                }
                            }

                            var newTab = new YAHOO.widget.Tab({
                                id: el.value,
                                label: label,
                                dataSrc: '/user/get-organization-subform/user/' + userid + '/role/' 
                                    + el.value + '/readOnly/' + readOnly,
                                cacheData: true,
                                active: true
                            });
                            newTab.subscribe("dataLoadedChange", Fisma.prepareTab);
                            tabView.addTab(newTab);
                        }
                    } else {
                        for (var i in tabs) {
                            if (tabs[i].get('id') == el.value) {
                                tabView.removeTab(tabs[i]);
                            }
                        }
                    }
                });
            });
        }
    }
}();
