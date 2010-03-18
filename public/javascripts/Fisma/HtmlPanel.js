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
 * @version   $Id$
 */

Fisma.HtmlPanel = function() {
    return {
        /**
         * Popup a YUI panel dialog which renders user specified HTML text.
         * 
         * @param {String} title The YUI panel dialog title
         * @param {String} html The content source of the panel
         * @returns {YAHOO.widget.Panel} The opened YUI panel object
         */
        showPanel : function(title, html) {
            var newPanel = new YAHOO.widget.Panel('panel', {
                width : "540px",
                modal : true
            });
            newPanel.setHeader(title);
            /** @todo english */
            newPanel.setBody("Loading...");
            newPanel.render(document.body);
            newPanel.center();
            newPanel.show();

            if (html != '') {
                newPanel.setBody(html);
                newPanel.center();
            } else {
                /** @todo english */
                alert('The parameter html can not be empty.');
            }

            return newPanel;
        }
    };
}();
