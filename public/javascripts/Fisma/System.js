/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * @fileoverview Provides client-side behavior for the AttachArtifacts behavior
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */
 
Fisma.System = {
    /**
     * Called when a system document finishes uploading
     * 
     * Eventually it would be nifty to refresh the YUI table but for now we will just refresh the entire page
     */
    uploadDocumentCallback : function (yuiPanel) {
        window.location.href = window.location.href;
    },


    /**
     * removeSelectedUsers 
     * 
     * @param event $event 
     * @param config $config 
     * @access public
     * @return void
     */
    removeSelectedUsers : function (event, config) {
        var userRoles = [];
        var data = new Object();

        $('input:checkbox[name="rolesAndUsers[][]"]:checked').each(
            function() {
                if ($(this).val() !== "") {
                    userRoles.push($(this).val());
                }
            }
        );

        data.organizationId = config.organizationId;
        data.userRoles = userRoles;
        data.csrf = $('[name="csrf"]').val();

        $.ajax({
            type: "POST",
            url: '/user/remove-user-roles/',
            data: data,
            dataType: "json",
            success: function() {
                $("#rolesAndUsers").load('/system/get-user-access-tree/id/' + data.organizationId + '/name/rolesAndUsers');
        }});
    },

    /**
     * addUser 
     * 
     * @param event $event 
     * @param config $config 
     * @access public
     * @return void
     */
    addUser : function (event, config) {
        var data = new Object();

        data.userId = $('#addUserId').val();
        data.roleId = $('#roles').val();
        data.organizationId = config.organizationId;
        data.csrf = $('[name="csrf"]').val();

        $.ajax({
            type: "POST",
            url: '/system/add-user/',
            data: data,
            dataType: "json",
            success: function() {
                $("#rolesAndUsers").load('/system/get-user-access-tree/id/' + data.organizationId + '/name/rolesAndUsers');
            }
        });
    },

    /**
     * addSelectedUsers 
     * 
     * @param event $event 
     * @param config $config 
     * @access public
     * @return void
     */
    addSelectedUsers : function (event, config) {
        var userRoles = [];
        var data = new Object();

        $('input:checkbox[name="copyUserAccessTree[][]"]:checked').each(
            function() {
                if ($(this).val() !== "") {
                    userRoles.push($(this).val());
                }
            }
        );

        data.userRoles = userRoles;
        data.organizationId = config.organizationId;
        data.csrf = $('[name="csrf"]').val();

        $.ajax({
            type: "POST",
            url: '/user/add-user-roles-to-organization/',
            data: data,
            dataType: "json",
            success: function() {
                $("#rolesAndUsers").load('/system/get-user-access-tree/id/' + data.organizationId + '/name/rolesAndUsers');
            }
        });
    },

    /**
     * Displays the hidden block on the FIPS-199 page to add information types to a system
     */

    showInformationTypes : function () {
        document.getElementById('addInformationTypes').style.display = 'block';
    },


    /**
     * Build URL for adding information type to the system
     */
    addInformationType : function (elCell, oRecord, oColumn, oData) {
        elCell.innerHTML = "<a href='/system/add-information-type/id/"
            + oRecord.getData('system')
            + "/sitId/"
            + oData
            + "'>Add</a>";
    },

    /**
     * Build URL for removing information types from a system
     */
    removeInformationType : function (elCell, oRecord, oColumn, oData) {
        elCell.innerHTML = "<a href='/system/remove-information-type/id/"
            + oRecord.getData('system')
            + "/sitId/"
            + oData
            + "'>Remove</a>";
    },

    /**
     * Run an XHR request to add an available information type to the system information types
     */
    handleAvailableInformationTypesTableClick: function (event, id) {
        var targetEl = event.target;
        var selectedId = Fisma.System.availableInformationTypesTable.getRecord(targetEl);
        var recordId = selectedId.getData('id');

        var postData = "id=" + id + "&sitId=" + recordId;

        YAHOO.util.Connect.asyncRequest(
            'POST',
            '/system/add-information-type/format/json',
            {
                success: function(o) {
                    try {
                        var response = YAHOO.lang.JSON.parse(o.responseText).response;

                        if (response.success) {
                            var addInformationTypes = document.getElementById("addInformationTypes");
                            if (addInformationTypes) {
                                addInformationTypes.style.display = 'none';
                            }

                            var dt = Fisma.System.assignedInformationTypesTable;
                            document.getElementById('addInformationTypes').style.display = 'block';
                            dt.showTableMessage("Updating list of information types…");
                            dt.getDataSource().sendRequest('', {success: dt.onDataReturnInitializeTable, scope: dt});
                            dt.on("dataReturnEvent", function () {
                            });
                        } else {
                            Fisma.Util.showAlertDialog('An error occurred: ' + response.message);
                        }
                    } catch (error) {
                        Fisma.Util.showAlertDialog('An unexpected error occurred: ' + error);
                    }
                },

                failure: function(o) {
                    Fisma.Util.showAlertDialog('An unexpected error occurred.');
                }
            },
            postData
        );
    }
};
