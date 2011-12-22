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
 * @fileoverview Show recipient dialog and validate inputed email address
 *
 * @author    Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Email = function() {
    return {
        /**
         * Hold the opened YUI panel object.
         * 
         * @type YAHOO.widget.Panel
         */
        panelElement : null,
         
        /**
         * Initializes the ShowRecipientDialog widget
         */
        showRecipientDialog : function() {

            // Remove used old panel if necessary
            if (Fisma.Email.panelElement !== null && Fisma.Email.panelElement instanceof YAHOO.widget.Panel) {
                Fisma.Email.panelElement.removeMask();
                Fisma.Email.panelElement.destroy();
                Fisma.Email.panelElement = null;
            }
    
            // Create a dialog
            var content = document.createElement('div');
            var p = document.createElement('p');
            var contentTitle = document.createTextNode('* Target E-mail Address:');
            p.appendChild(contentTitle);
            content.appendChild(p);
    
            // Add email address input to dialog
            var emailAddress = document.createElement('input');
            emailAddress.id = 'testEmailRecipient';
            emailAddress.name = 'recipient';
            content.appendChild(emailAddress);
    
            // Add line spacing to dialog
            var lineSpacingDiv = document.createElement('div');
            lineSpacingDiv.style.height = '10px';
            content.appendChild(lineSpacingDiv);
    
            // Add submmit button to dialog
            var sendBtn = document.createElement('input');
            sendBtn.type = 'button';
            sendBtn.id = 'dialogRecipientSendBtn';
            sendBtn.style.marginLeft = '10px';
            sendBtn.value = 'Send';
            content.appendChild(sendBtn);
    
            // Load panel
            var panelConfig = {
                    width : "260px",
                    modal : false
                };
            Fisma.Email.panelElement = Fisma.HtmlPanel.showPanel(
                'Test E-mail Configuration',
                content.innerHTML,
                null,
                panelConfig);

            // Set onclick handler to handle dialog_recipient
            document.getElementById('dialogRecipientSendBtn').onclick = Fisma.Email.sendTestEmail;
        },

        /**
         * Send test email to specified recipient
         */
        sendTestEmail : function() {
            
            if (document.getElementById('testEmailRecipient').value === '') {
                /** @todo english */
                var alertMessage = "Recipient is required.";
                var config = {zIndex : 10000};
                Fisma.Util.showAlertDialog(alertMessage, config);
                document.getElementById('testEmailRecipient').focus();
                return false;
            }
    
            // Get dialog_recipient value to recipient
            var recipient = document.getElementById('testEmailRecipient').value;
            var form = document.getElementById('email_config');
            form.elements['recipient'].value = recipient;
    
            var element = document.getElementById('sendTestEmail');

            spinner = new Fisma.Spinner(element.parentNode);
            spinner.show();
            
            // Post data through YUI
            YAHOO.util.Connect.setForm(form);
            YAHOO.util.Connect.asyncRequest('POST', '/config/test-email-config/format/json', {
                success : function(o) {
                    var data = YAHOO.lang.JSON.parse(o.responseText);
                    message(data.msg, data.type, true);
                    spinner.hide();
                },
                failure : function(o) {
                    var alertMessage = 'Failed to send test mail: ' + o.statusText;
                    Fisma.Util.showAlertDialog(alertMessage);
                    spinner.hide();
                }
            }, null);
    
            // Remove used panel
            if (Fisma.Email.panelElement !== null && Fisma.Email.panelElement instanceof YAHOO.widget.Panel) {
                Fisma.Email.panelElement.hide();
                Fisma.Email.panelElement.destroy();
                Fisma.Email.panelElement = null;
            }
            
            return true;
        }
    };
}();
