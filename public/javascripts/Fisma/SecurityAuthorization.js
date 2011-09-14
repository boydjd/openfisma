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
     * Store a data table used for selecting controls
     */
    selectControlsTable: null,
    
    /**
     * Store a panel which displays the add control form
     */
    addControlPanel: null,
 
    /**
     * A dialog used for viewing a control during step 2 (select) and editing control enhancements
     * 
     * @var Fisma.FormDialog
     */
    selectControlsDialog: null,
    
    /**
     * A reference to the tab view on the SA view page
     */
    tabView: null,

    /**
     * A reference to the overview widget
     */
    overview: null,

    /**
     * Run the import baseline controls action and update the table with the results
     * 
     * @param int authorizationId The primary key of the authorization object
     */
    confirmImportBaselineControls: function (event, authorizationId) {
        confirmation = {
            func: Fisma.SecurityAuthorization.importBaselineControls,
            args: [authorizationId],
            text: "Are you sure you want to import the baseline controls for this information system?"
        };
        
        Fisma.Util.showConfirmDialog(null, confirmation);
    },

    /**
     * Run the import baseline controls action and update the table with the results
     * 
     * @param int authorizationId The primary key of the authorization object
     */
    importBaselineControls: function (authorizationId) {
        var image = "<img src='/images/loading_bar.gif'>";
        var dialogConfig = {width: '20em', modal: true, close: false};
        var modalDialog = Fisma.HtmlPanel.showPanel("Importing baseline controls…", image, null, dialogConfig);
        
        // Import controls via XHR
        YAHOO.util.Connect.asyncRequest(
            'GET',
            '/sa/security-authorization/import-baseline-security-controls/id/' + authorizationId + '/format/json',
            {
                success: function(o) {
                    try {
                        var response = YAHOO.lang.JSON.parse(o.responseText).response;
                        
                        if (response.success) {
                            // Hide warning message
                            var noControlsWarning = document.getElementById("no-security-controls-warning");
                            if (noControlsWarning) {
                                noControlsWarning.style.display = 'none';
                            }

                            // Refresh controls table
                            var dt = Fisma.SecurityAuthorization.selectControlsTable;
                            dt.showTableMessage("Loading baseline controls...");
                            dt.getDataSource().sendRequest('', {success: dt.onDataReturnInitializeTable, scope: dt});
                            dt.on("dataReturnEvent", function () {
                                modalDialog.destroy();
                            });
                            
                            // Update overview
                            var overview = Fisma.SecurityAuthorization.overview;
                            if (YAHOO.lang.isValue(overview)) {
                                overview.updateStepProgress(3, null, response.payload.controlCount);
                                overview.updateStepProgress(4, null, response.payload.controlCount);
                            }
                        } else {
                            Fisma.Util.showAlertDialog('An error occurred: ' + response.message);
                            modalDialog.destroy();
                        }
                    } catch (error) {
                        Fisma.Util.showAlertDialog('An unexpected error occurred: ' + error);
                        modalDialog.destroy();
                    }
                },

                failure: function(o) {
                    Fisma.Util.showAlertDialog('An unexpected error occurred.');
                    modalDialog.destroy();
                }
            },
            null
        );
    },

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
    
    /**
     * Run an XHR request to add a single security control to an SA
     * 
     * @param HTMLElement addControlForm
     */
    addControl: function (addControlForm) {
        var modalDialog = Fisma.SecurityAuthorization.addControlPanel;

        YAHOO.util.Connect.setForm(addControlForm);
        YAHOO.util.Connect.asyncRequest(
            'POST',
            '/sa/security-authorization/add-control/format/json',
            {
                success: function(o) {
                    try {
                        var response = YAHOO.lang.JSON.parse(o.responseText).response;
                        
                        if (response.success) {
                            // Hide warning message
                            var noControlsWarning = document.getElementById("no-security-controls-warning");
                            if (noControlsWarning) {
                                noControlsWarning.style.display = 'none';
                            }

                            // Refresh controls table
                            var dt = Fisma.SecurityAuthorization.selectControlsTable;
                            dt.showTableMessage("Updating list of controls…");
                            dt.getDataSource().sendRequest('', {success: dt.onDataReturnInitializeTable, scope: dt});
                            dt.on("dataReturnEvent", function () {
                                modalDialog.destroy();
                            });

                            // Update the overview tab
                            if (YAHOO.lang.isValue(Fisma.SecurityAuthorization.overview)) {
                                Fisma.SecurityAuthorization.overview.incrementStepDenominator(3);
                                Fisma.SecurityAuthorization.overview.incrementStepDenominator(4);
                            }
                        } else {
                            Fisma.Util.showAlertDialog('An error occurred: ' + response.message);
                            modalDialog.destroy();
                        }
                    } catch (error) {
                        Fisma.Util.showAlertDialog('An unexpected error occurred: ' + error);
                        modalDialog.destroy();
                    }
                },

                failure: function(o) {
                    Fisma.Util.showAlertDialog('An unexpected error occurred.');
                    modalDialog.destroy();
                }
            },
            null
        );
    },

    /**
     * Show the form for adding a single security control
     * 
     * @param YAHOO.util.Event ev
     * @param object obj
     */
    showAddControlForm: function (ev, obj) {
        var id = obj,
            panel = Fisma.HtmlPanel.showPanel("Add Security Control", null, null, { modal : true }),
            url = "/sa/security-authorization/show-add-control-form/format/html/id/" + id;

        var callbacks = {
            success: function(o) {
                var panel = o.argument;
                panel.setBody(o.responseText);
                panel.center();
            },
            failure: function(o) {
                alert('Error getting "add control" form: ' + o.statusText);
                var panel = o.argument;
                panel.destroy();
            },
            argument: panel
        };

        Fisma.SecurityAuthorization.addControlPanel = panel;
        YAHOO.util.Connect.asyncRequest('GET', url, callbacks, null);
    }
}
