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
 * @author    Christian Smith <christian.smith@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

Fisma.SecurityAuthorization = {
    /**
     * Popup a panel for upload evidence
     *
     * @return {Boolean} False to interrupt consequent operations
     */
    showPanel : function (event) {
        // Create a new panel
        var newPanel = new YAHOO.widget.Panel('panel', {modal : true, close : true});
        newPanel.setHeader('Select System');
        newPanel.setBody("Loading...");
        newPanel.render(document.body);
        newPanel.center();
        newPanel.show();

        // Register listener for the panel close event
        newPanel.hideEvent.subscribe(function () {
            Fisma.SecurityAuthorization.cancelPanel.call(Fisma.SecurityAuthorization);
        });

        Fisma.SecurityAuthorization.yuiPanel = newPanel;

        // Construct form action URL
        var createSystemForm = '/system/create/format/html';

        // Get panel content from artifact controller
        YAHOO.util.Connect.asyncRequest(
            'GET',
            createSystemForm,
            {
                success: function(o) {
                    o.argument.setBody(o.responseText);
                    o.argument.center();
                },

                failure: function(o) {
                    o.argument.setBody('The content for this panel could not be loaded.');
                    o.argument.center();
                },

                argument: newPanel
            },
            null);
    },

    completeForm : function(event) {
        document.getElementById('completeForm').submit();
    },
}
