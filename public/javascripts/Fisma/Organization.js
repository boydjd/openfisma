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
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * Provides organization related functionality
     *
     * @namespace Fisma
     * @class Organization
     */
    var Organization = {
        /**
         * Organization type filter callback function
         * It set the default organization type, store the selected organization type and refresh window with url
         *
         * @method Organization.typeHandle
         * @param event {String} The name of the event
         * @param config {Array} An array of YAHOO.util.Event
         */
        typeHandle : function (event, config) {
            // Set the selected organization type
            var organizationTypeFilter = YAHOO.widget.Button.getButton('orgTypeFilter-button').getMenu().srcElement;
            var selectedType = organizationTypeFilter.options[organizationTypeFilter.selectedIndex];

            // Store the selected organizationTypeId to storage table
            var orgTypeStorage = new Fisma.PersistentStorage(config.namespace);
            orgTypeStorage.set('orgType', selectedType.value);
            orgTypeStorage.sync();

            Fisma.Storage.onReady(function() {
                // Construct the url and refresh the result after a user changes organization type
                if (!YAHOO.lang.isUndefined(config) && config.url) {
                    var url = config.url + '?orgTypeId=' + encodeURIComponent(selectedType.value);
                    window.location.href = url;
                }
            });
        },

        /**
         * A dictionary of info panels that have already been created.
         *
         * We use this to make sure that we don't create multiple panels for the same object.
         */
        infoPanelList : {},

        /**
         * Create the user info panel and position it near the referenceElement
         *
         * @param referenceElement
         * @param userId The ID number of the user to get info for
         * @return YAHOO.widget.Panel
         */
        createInfoPanel : function (referenceElement, userId) {

            var PANEL_WIDTH = 500; // in pixels
            var panel = new YAHOO.widget.Panel(
                YAHOO.util.Dom.generateId(),
                {
                    width: PANEL_WIDTH + 'px',
                    modal : false,
                    close : true,
                    constraintoviewport : true
                }
            );

            panel.setHeader('Organization/System Details');
            panel.setBody("Loading detailed information for <em>" + $(referenceElement).text().trim() + "</em>...");
            panel.render(document.body);

            Fisma.Util.positionPanelRelativeToElement(panel, referenceElement);

            // Load panel content using asynchronous request
            YAHOO.util.Connect.asyncRequest(
                'GET',
                '/organization/info/format/html/id/' + encodeURI(userId),
                {
                    success: function(o) {
                        panel.setBody(o.responseText);
                        Fisma.Util.positionPanelRelativeToElement(panel, referenceElement);
                    },

                    failure: function(o) {
                        panel.setBody('Information cannot be loaded.');
                        Fisma.Util.positionPanelRelativeToElement(panel, referenceElement);
                    }
                },
                null);

            return panel;
        },

        /**
         * Display a dialog which shows information for the specified organization/syste,.
         *
         * @param referenceElement The panel will be displayed near this element
         * @param orgId The ID number of the org/sys to get info for
         */
        displayInfo : function (referenceElement, orgId) {
            var panel;
            if (typeof Fisma.Organization.infoPanelList[orgId] === 'undefined') {
                // Create new panel
                panel = Fisma.Organization.createInfoPanel(referenceElement, orgId);
                Fisma.Organization.infoPanelList[orgId] = panel;
                panel.show();
            } else {
                // Panel already exists
                panel = Fisma.Organization.infoPanelList[orgId];

                // If panel is hidden then display it, or if its already visible, then hide it.
                if (panel.cfg.getProperty("visible")) {
                    panel.hide();
                } else {
                    panel.bringToTop();
                    panel.show();
                }
            }
        },

        /**
         * Capture parent select onChange and update copyUserAccess
         */
        parentChanged: function (selectElement) {
            var parentButton = YAHOO.widget.Button.getButton(selectElement.id + '-button');
            var copyUserAccessButton =
                YAHOO.widget.Button.getButton('copyOrganizationId-button') ||
                YAHOO.widget.Button.getButton('cloneOrganizationId-button')
            ;
            copyUserAccessButton.set('label', parentButton.get('label'));
            copyUserAccessButton.set('selectedMenuItem', parentButton.get('selectedMenuItem'));
        },

        renameTag: function(tag) {
            var jcell = $('td').filter(function(index){
                return ($(this).text() === tag);
            });
            var row = jcell.parent().get(0);
            var datatable = Fisma.Registry.get('assetPocListTable');
            datatable.selectRow(row);

            Fisma.Util.showInputDialog(
                "Rename '" + tag + "' ...",
                "New name",
                {
                    'continue': function(ev, obj) {
                        YAHOO.util.Event.stopEvent(ev);
                        var input = obj.textField.value;
                        if (input !== "") {
                            obj.errorDiv.innerHTML = "Renaming '" + tag + "'...";

                            $.post(
                                '/organization/rename-poc-list',
                                {
                                    format: 'json',
                                    oldTag: tag,
                                    newTag: input,
                                    csrf: $('[name=csrf]').val()
                                },
                                function(data) {
                                    $('[name=csrf]').val(data.csrfToken);

                                    if (data.result.success) {
                                        if (data.result.message) {
                                            Fisma.Util.showAlertDialog(data.result.message);
                                        } else {
                                            datatable.updateRow(row, {
                                                'Position': input,
                                                'Assignees': jcell.siblings().eq(0).text(),
                                                'Edit': {func:Fisma.Organization.renameTag, param:input},
                                                'Delete': '/organization/remove-poc-list/tag/' + encodeURIComponent(input)
                                            });
                                        }
                                    } else {
                                        Fisma.Util.showAlertDialog(data.result.message);
                                    }
                                    obj.panel.hide();
                                    obj.panel.destroy();
                                }
                            );

                        } else {
                            obj.errorDiv.innerHTML = "Tag name cannot be blank.";
                        }
                    },
                    'cancel': function(ev, obj) {
                        datatable.unselectRow(row);
                    }
                }
            );
        },

        addTag: function() {
            var datatable = Fisma.Registry.get('assetPocListTable');

            Fisma.Util.showInputDialog(
                "Add a tag ...",
                "Tag name",
                {
                    'continue': function(ev, obj) {
                        YAHOO.util.Event.stopEvent(ev);
                        var input = obj.textField.value;
                        if (input !== "") {
                            obj.errorDiv.innerHTML = "Adding position '" + input + "'...";

                            $.post(
                                '/organization/add-poc-list/',
                                {
                                    format: 'json',
                                    tag: input,
                                    csrf: $('[name=csrf]').val()
                                },
                                function(data) {
                                    $('[name=csrf]').val(data.csrfToken);

                                    if (data.result.success) {
                                        if (data.result.message) {
                                            Fisma.Util.showAlertDialog(data.result.message);
                                        } else {
                                            datatable.addRow({
                                                'Position': input,
                                                'Assignees': '0',
                                                'Edit': {func:Fisma.Organization.renameTag, param:input},
                                                'Delete': '/organization/remove-poc-list/tag/' + encodeURIComponent(input)
                                            });
                                        }
                                    } else {
                                        Fisma.Util.showAlertDialog(data.result.message);
                                    }
                                    obj.panel.hide();
                                    obj.panel.destroy();
                                }
                            );
                        } else {
                            obj.errorDiv.innerHTML = "Position name cannot be blank.";
                        }
                    },
                    'cancel': function(ev, obj) {
                    }
                }
            );
        }
    };

    Fisma.Organization = Organization;
}());
