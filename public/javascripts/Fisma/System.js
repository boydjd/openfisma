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
 * @version   $Id: AttachArtifacts.js 3188 2010-04-08 19:35:38Z mhaase $
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
     * Triggers a pop-up confirmation asking if the user truly wants to convert the current
     * system to an organization, and if yes, redirects to the proper action.
     * 
     * @param event $event 
     * @param config $config 
     * @access public
     * @return void
     */
    convertToOrganization : function (event, config) {
    
        var dialogText = "WARNING: You are about to convert this system to an organization. " +
                        "After this conversion all system information (FIPS-199 and FISMA Data) will be " +
                        "permanently lost.\n\n" +
                        "Do you want to continue?";
        var yesButtonEvent = function () {
                Fisma.System.showWaitPanelWhileConverting();
                this.hide(); 
                document.location = "/system/convert-to-org/id/" + config.id;
            };
        var noButtonEvent = function () {
                this.destroy(); 
            };
        var dialogButtons = 
            [
                {
                    text: "Yes",
                    handler: yesButtonEvent
                }, 
                {
                    text:"No",
                    handler: noButtonEvent
                } 
            ];
        var dialogConfig = {
            width: "300px", 
            fixedcenter: true, 
            visible: false, 
            draggable: false, 
            close: true,
            modal: true,
            text: dialogText, 
            icon: YAHOO.widget.SimpleDialog.ICON_WARN, 
            constraintoviewport: true, 
            buttons: dialogButtons
        };
        
        var warningDialog = new YAHOO.widget.SimpleDialog("warningDialog",  dialogConfig);
        warningDialog.setHeader("Are you sure?");
        warningDialog.render(document.body);
        warningDialog.show();
    },
    
    /**
     * Triggers a pop-up confirmation asking if the user truly wants to convert the current
     * organization to a system, and if yes, calls Fisma.System.AskForOrgToSysInput()
     * 
     * @param event $event 
     * @param config $config 
     * @access public
     * @return void
     */
    convertToSystem : function (event, config) {
    
        Fisma.Util.showConfirmDialog(
            event, 
            {
                text: "Are you sure you want to convert this organization to a system?",
                func: 'Fisma.System.askForOrgToSysInput',
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
        
        var inputForm = Fisma.System.getSystemConversionForm(sysId);
        inputForm.name = 'sysConversionForm';
        inputForm.id = 'sysConversionForm';
        inputForm.className = 'yui-pe-content';
        document.body.appendChild(inputForm);
        
        var submitButtonEvent = function () {
                Fisma.System.showWaitPanelWhileConverting();
                YAHOO.util.Dom.get('sysConversionForm').submit();
                this.hide(); 
            };
        var cancleButtonEvent = function () {
                this.destroy(); 
            };
        var dialogButtons = [
                { text:"Submit", handler:submitButtonEvent, isDefault:true }, 
                { text:"Cancel", handler:cancleButtonEvent }
            ];
        var inputSysInfoDialog = new YAHOO.widget.Dialog(
            "sysConversionForm",  
            {
                width : "300px", 
                fixedcenter : true, 
                visible : false,  
                constraintoviewport : true, 
                buttons : dialogButtons
            }
        ); 

        YAHOO.util.Dom.removeClass("sysConversionForm", "yui-pe-content");
        
        inputSysInfoDialog.render();
        inputSysInfoDialog.show();
    },
    
    /**
     * Creates and HTML input form which asks for the requiered information needed to 
     * convert an organization to a system
     * 
     * @access public
     * @return void
     */
    getSystemConversionForm : function (sysId) {
    
        var addInputRowOnTable = function (addToTable, descriptionText, opts) {

            var myRow = document.createElement('tr');

            var cellDescr = document.createElement('td');
            var descTextNode = document.createTextNode(descriptionText);
            cellDescr.appendChild(descTextNode);

            var cellInput = document.createElement('td');
            cellInput.align = 'right';
            cellInput.appendChild(rtnHighModLowSelectObj(descriptionText, opts));

            myRow.appendChild(cellDescr);
            myRow.appendChild(cellInput);
            addToTable.appendChild(myRow);
        };

        var rtnHighModLowSelectObj = function (selectObjName, states) {

            var selectObj = document.createElement('select');
            selectObj.name = selectObjName.replace(' ', '');

            for (var x = 0; x < states.length; x++) {
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

        var myDialogForm = document.createElement('form');
        myDialogForm.id = 'sysConversionForm';
        myDialogForm.method = 'post';
        myDialogForm.action = '/organization/convert-to-system/id/' + sysId;
        
        var dialogHead = document.createElement('div');
        dialogHead.className = 'hd';
        dialogHead.appendChild(document.createTextNode('Please enter system information'));
        var dialogBody = document.createElement('div');
        dialogBody.className = 'bd';
        
        var msg = document.createTextNode("Please input the needed system information in order to complete conversion");
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
            "savePanel",
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
        waitPanel.render(document.body);
        waitPanel.setBody('<img src="/images/loading_bar.gif">');
        waitPanel.show();
    }
};
