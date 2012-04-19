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
        window.location = window.location.href;
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
        var data = {};

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
        var data = {};

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
        var data = {};

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
     * Triggers a pop-up confirmation asking if the user truly wants to convert the current
     * system/organization to an organization/system, and if yes, redirects to the proper action.
     *
     * @param event $event
     * @param config $config
     * @access public
     * @return void
     */
    convertToOrgOrSystem : function (event, config) {

        Fisma.Util.showConfirmDialog(
            event,
            {
                text: config.text,
                func: config.func,
                args: [config.id]
            }
        );
    },

    /**
     * Shows an input dialog for the user to input the needed information for
     * a Oranization-t-o-System conversion (the system FIPS-199 and FISMA Data).
     * After input, will redirect the user to /organization/convert-to-system/~
     *
     * @access public
     * @return void
     */
    askForOrgToSysInput : function (sysId) {
        var panel;

        var populateForm = function () {

            // The form contains some scripts that need to be executed
            var scriptNodes = panel.body.getElementsByTagName('script');
            var i;

            for (i = 0; i < scriptNodes.length; i++) {
                try {
                    eval(scriptNodes[i].text);
                } catch (e) {
                    var message = 'Not able to execute one of the scripts embedded in this page: ' + e.message;
                    Fisma.Util.showAlertDialog(message);
                }
            }

            // The tool tips will display underneath the modal dialog mask, so we'll move them up to a higher layer.
            var tooltips = YAHOO.util.Dom.getElementsByClassName('yui-tt', 'div');
            var index;

            for (index in tooltips) {
                var tooltip = tooltips[index];

                // The yui panel is usually 4, so anything higher is good.
                tooltip.style.zIndex = 5;
            }
        };
        var panelConfig = {width : "40em", modal : true};

        panel = Fisma.UrlPanel.showPanel(
            'Convert Organization To System',
            '/organization/convert-to-system-form/format/html/id/' + sysId,
            populateForm,
            'convertToSystemPanel',
            panelConfig
        );

        panel.hideEvent.subscribe(function (e) {
            setTimeout(function () {panel.destroy();}, 0);
        });
    },

    /**
     * Shows an input dialog for the user to input the organization type
     * After input, will redirect the user to /system/convert-to-org/~
     *
     * @access public
     * @return void
     */
    askForSysToOrgInput : function (orgId) {
        var panel;

        var populateForm = function () {

            // The form contains some scripts that need to be executed
            var scriptNodes = panel.body.getElementsByTagName('script');
            var i;

            for (i = 0; i < scriptNodes.length; i++) {
                try {
                    eval(scriptNodes[i].text);
                } catch (e) {
                    var message = 'Not able to execute one of the scripts embedded in this page: ' + e.message;
                    Fisma.Util.showAlertDialog(message);
                }
            }
        };
        var panelConfig = {width : "40em", modal : true};

        panel = Fisma.UrlPanel.showPanel(
            'Convert System To Organization',
            '/system/convert-to-organization-form/format/html/id/' + orgId,
            populateForm,
            'convertToOrganizationPanel',
            panelConfig
        );

        panel.hideEvent.subscribe(function (e) {
            setTimeout(function () {panel.destroy();}, 0);
        });
    },

    /**
     * Creates and HTML input form which asks for the requiered information needed to
     * convert an organization to a system
     *
     * @access public
     * @return void
     */
    getSystemConversionForm : function (sysId) {

        var rtnHighModLowSelectObj = function (selectObjName, states) {

            var selectObj = document.createElement('select');
            selectObj.name = selectObjName.replace(' ', '');
            var x;

            for (x = 0; x < states.length; x++) {
                var myOption = document.createElement('option');
                myOption.value = states[x];
                var optText = document.createTextNode(states[x]);
                var boldOptText = document.createElement('b');
                boldOptText.appendChild(optText);
                myOption.appendChild(boldOptText);
                selectObj.appendChild(myOption);
            }

            return selectObj;
        };

        var addInputRowOnTable = function (addToTable, descriptionText, opts) {

            var myRow = document.createElement('tr');

            var cellDescr = document.createElement('td');
            var descTextNode = document.createTextNode(descriptionText + ':');
            cellDescr.appendChild(descTextNode);

            var cellInput = document.createElement('td');
            cellInput.align = 'right';
            cellInput.appendChild(rtnHighModLowSelectObj(descriptionText, opts));

            myRow.appendChild(cellDescr);
            myRow.appendChild(cellInput);
            addToTable.appendChild(myRow);
        };

        var myDialogForm = document.createElement('form');
        myDialogForm.id = 'sysConversionForm';
        myDialogForm.method = 'post';
        myDialogForm.action = '/organization/convert-to-system/id/' + sysId;

        var dialogHead = document.createElement('div');
        dialogHead.className = 'hd';
        dialogHead.appendChild(document.createTextNode('System information'));
        var dialogBody = document.createElement('div');
        dialogBody.className = 'bd';

        var msg = document.createTextNode("Please input the needed system information in order to complete conversion.");
        dialogBody.appendChild(msg);
        dialogBody.appendChild(document.createElement('br'));
        dialogBody.appendChild(document.createElement('br'));

        var tbl = document.createElement('table');
        tbl.width = '100%';
        addInputRowOnTable(tbl, 'Type', ['gss', 'major', 'minor']);
        addInputRowOnTable(tbl, 'SDLC Phase', ['initiation','development','implementation','operations','disposal']);
        addInputRowOnTable(tbl, 'Confidentiality', ['NA', 'HIGH', 'MODERATE', 'LOW']);
        addInputRowOnTable(tbl, 'Integrity', ['HIGH', 'MODERATE', 'LOW']);
        addInputRowOnTable(tbl, 'Availability', ['HIGH', 'MODERATE', 'LOW']);
        dialogBody.appendChild(tbl);

        myDialogForm.appendChild(dialogHead);
        myDialogForm.appendChild(dialogBody);
        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.value = $('[name="csrf"]').val();
        csrf.name = 'csrf';
        myDialogForm.appendChild(csrf);
        return myDialogForm;
    },

    /**
     * Shows YUI wait panel on the DOM. Use when navigating away from this page.
     *
     * @access public
     * @return void
     */
    showWaitPanelWhileConverting : function () {
        var waitPanel = new YAHOO.widget.Panel(
            YAHOO.util.Dom.generateId(),
            {
                width: "250px",
                fixedcenter: true,
                close: false,
                draggable: false,
                modal: true,
                visible: true
            }
        );
        waitPanel.setHeader('Converting...');
        waitPanel.setBody('<img src="/images/loading_bar.gif">');
        waitPanel.render(document.body);
        waitPanel.show();
    },
    
    /**
     * Checked all checkbox by name 
     * 
     * @access public
     * @return void
     */
    selectAllByName : function (event, config) {
        $('input:checkbox[name="' + config.name + '"]').attr("checked", "checked");
    }
};
