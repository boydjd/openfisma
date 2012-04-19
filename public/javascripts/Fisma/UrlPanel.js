/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * @fileoverview Offer a utility javascript function which renders a YUI dialog from user specified URL.
 *
 * @author    Jackson Yang <yangjianshan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

Fisma.UrlPanel = (function() {
    return {
        /**
         * Popup a YUI panel dialog which renders user URL specified page
         * asynchronously.
         *
         * @param {String} title The YUI panel dialog title
         * @param {String} url The source that YUI panel dialog loads content from
         * @param {Function} callback The callback handler function
         * @param {String} element The optional element or its id representing the Panel
         * @param {YAHOO.util.Config} userConfig The optional user specified config object
         * @returns {YAHOO.widget.Panel} The opened YUI panel object
         */
        showPanel : function(title, url, callback, element, userConfig) {
            // Initialize element or its id representing the panel with default value if necessary
            if (typeof(element) === 'undefined' || element === null)
            {
                element = YAHOO.util.Dom.generateId();
            }
            // Initialize user config with default config object if the user config is not specified or null
            if (typeof(userConfig) === 'undefined' || userConfig === null)
            {
                userConfig = {
                    width : "540px",
                    modal : true
                };
            }

            // Instantiate YUI panel for rendering
            var panel = new YAHOO.widget.Panel(element, userConfig);
            panel.setHeader(title);
            panel.setBody("Loading...");
            panel.render(document.body);
            panel.center();
            panel.show();

            // Load panel content from url
            if (url !== '') {
                YAHOO.util.Connect.asyncRequest('GET', url, {
                    success : function(o) {
                        o.argument.setBody(o.responseText);
                        o.argument.center();

                        if (typeof(callback) === "function") {
                            callback(o.argument);
                        }
                    },
                    failure : function(o) {
                        Fisma.Util.showAlertDialog('Failed to load the specific panel.');
                    },
                    argument : panel
                }, null);
            }

            return panel;
        }
    };
}());
