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
    }
};
