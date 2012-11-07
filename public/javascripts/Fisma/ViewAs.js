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
 *
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.ViewAs = {
    selectUserForm : function() {
        Fisma.UrlPanel.showPanel(
            "View As...",
            "/view-as/select-user-form/format/html",
            function (panel) {
                var scriptNodes = panel.body.getElementsByTagName('script'),
                    i;
                for (i = 0; i < scriptNodes.length; i++) {
                    if (scriptNodes[i].getAttribute('executeFlag') != 'true') {
                        try {
                            eval(scriptNodes[i].text);
                        } catch (e) {
                            Fisma.Util.showAlertDialog(
                                'Not able to execute one of the scripts embedded in this page: ' + e.message
                            );
                        }
                        // Set a flag that prevents this script from being executed more than once
                        scriptNodes[i].setAttribute('executeFlag', 'true');
                    }
                }

                // stick the current URL into the hidden field
                $(panel.body).find("input[name=url]").val(document.location.pathname + document.location.search);
            }
        );
    },

    setupAutocomplete: function() {
    },

    provideVisualCues: function() {
        if ($('#view-as').length > 0) {
            $('div.sectionHeader, ul.yui-nav, div.searchBox, div#mainmenu').addClass('viewAsMode');
        } else {
            $('.viewAsMode').removeClass('viewAsMode');
        }
    }
};

