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
 * @fileoverview Offer a utility javascript function which renders a YUI dialog from specified HTML text.
 *
 * @author    Jackson Yang <yangjianshan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */
Fisma.HtmlPanel = function() {
    return {
        /**
         * Popup a YUI panel dialog which renders user specified HTML text.
         * 
         * @param {String} title The YUI panel dialog title
         * @param {String} html The content source of the panel
         * @param {String} element The optional element or its id representing the Panel
         * @param {YAHOO.util.Config} userConfig The optional user specified config object
         * @returns {YAHOO.widget.Panel} The opened YUI panel object
         */
        showPanel : function(title, html, element, userConfig) {
            // Initialize element or its id representing the panel with default value conditionally
            if (typeof(element) === 'undefined' || element === null)
            {
                element = "panel";
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
            /** @todo english */
            panel.setBody("Loading...");
            panel.render(document.body);
            panel.center();
            panel.show();
            
            // Fill the panel with HTML text
            if (html !== '') {
                panel.setBody(html);
                panel.center();
            }
            
            return panel;
        }
    };
}();
