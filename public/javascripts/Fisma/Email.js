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
 * @version   $Id$
 */

Fisma.Email = function() {
    return {
        /**
         * Initializes the ShowRecipientDialog widget
         */
        showRecipientDialog : function() {
            // If someone opens the dialog, and then just closes the dialog, but leave pannel_c div there, 
            // it can be display two pannel_c div when open again, so we need remove it.
            var tabContainer = document.getElementById('tabContainer');
            if (document.getElementById("panel_c")) {
                tabContainer.removeChild(document.getElementById("panel_c"));
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
            panel('Test E-mail Configuration', tabContainer, '', content.innerHTML);

            // Set onclick handler to handle dialog_recipient 
            document.getElementById('dialogRecipientSendBtn').onclick = Fisma.Email.sendTestEmail;
        },

        /**
         * Send test email to specified recipient
         */
        sendTestEmail : function() {
            if (document.getElementById('testEmailRecipient').value == '') {
                /** @todo english */
                alert("Recipient is required.");
                document.getElementById('testEmailRecipient').focus();
                return false;
            }

            // Get dialog_recipient value to recipient
            var recipient = document.getElementById('testEmailRecipient').value;
            var form  = document.getElementById('email_config');
            form.elements['recipient'].value = recipient;

            // Post data through YUI
            YAHOO.util.Connect.setForm(form);
            YAHOO.util.Connect.asyncRequest('POST', '/config/test-email-config/format/json',
                                            {
                                                success: function(o) {
                                                    var data = YAHOO.lang.JSON.parse(o.responseText);
                                                    message(data.msg, data.type);
                                                },
                                                /** @todo english */
                                                failure: function(o) {alert('Failed to send mail: ' + o.statusText);}
                                            },
                                            null);

            // Remove panel
            var panelMask = document.getElementById("panel_mask");
            panelMask.style.visibility = "hidden";
            document.getElementById('tabContainer').removeChild(document.getElementById("panel_c"));
        }
    };
}();
