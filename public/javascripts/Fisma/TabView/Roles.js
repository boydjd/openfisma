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
 */

Fisma.TabView.Roles = (function() {
    return {
        init : function(roles, userid, readOnly) {
            $("input[name^=role], input[name^=groups]").change(function(ev) {
                var tabs = Fisma.tabView.get("tabs"),
                    tabShown = false,
                    tabindex = null,
                    i, label, newTab;
                for (i in tabs) {
                    if (!tabs.hasOwnProperty(i)) {
                        continue;
                    }
                    if (tabs[i].get("id") === $(this).val()) {
                        tabindex = i;
                        tabShown = true;
                        break;
                    }
                }
                if ($(this).is(":checked") && !tabShown) {
                    for (i in roles) {
                        if (!roles.hasOwnProperty(i)) {
                            continue;
                        }
                        if (roles[i].id === $(this).val()) {
                            label = $("<span>").text(roles[i].name).html();
                            break;
                        }
                    }
                    newTab = new YAHOO.widget.Tab({
                        id: $(this).val(),
                        label: label,
                        dataSrc: '/user/get-organization-subform/user/' + userid
                            + '/role/' + $(this).val() + '/readOnly/' + readOnly,
                        cacheData: true,
                        active: true
                    });
                    newTab.subscribe("dataLoadedChange", Fisma.prepareTab);
                    Fisma.tabView.addTab(newTab);
                } else if(!$(this).is(":checked") && tabShown) {
                    Fisma.tabView.removeTab(tabs[tabindex]);
                }
            });
        }
    };
}());
