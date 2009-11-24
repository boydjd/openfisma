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
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @fileoverview This is a description of the file's purpose and contents
 *
 * @author    Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

/**
 * Show recipient dialog and validate inputed email address
 */
function showRecipientDialog() {
    var tabContainer = document.getElementById('tabContainer');
    if (document.getElementById("panel_c")) {
        tabContainer.removeChild(document.getElementById("panel_c"));
    }

    // Create a dialog
    var content = document.createElement('div');
    var p = document.createElement('p');
    var c_title = document.createTextNode('* Target E-mail Address:');
    p.appendChild(c_title);
    content.appendChild(p);
    var emailAddress = document.createElement('input');
    emailAddress.id = 'dialogRecipientInput';
    emailAddress.className = "dialogRecipientInput";
    emailAddress.name = 'recipient';
    content.appendChild(emailAddress);
    var lineSpacingDiv = document.createElement('div');
    lineSpacingDiv.style.height = '10px';
    content.appendChild(lineSpacingDiv);
    var sendBtn = document.createElement('input');
    sendBtn.type = 'button';
    sendBtn.id = 'dialogRecipientSendBtn';
    sendBtn.style.marginLeft = '10px';
    sendBtn.value = 'Send';
    content.appendChild(sendBtn);

    // Load panel
    panel('Test E-mail Configuration', tabContainer, '', content.innerHTML);

    // Set onclick handler to handle dialog_recipient 
    document.getElementById('dialogRecipientSendBtn').onclick = function () {sendTestEmail(); }
}

/**
 * Send test email to specified recipient
 */
function sendTestEmail() {
    if (document.getElementById('dialogRecipientInput').value == '') {
        /** @todo english */
        alert("Recipient is required.");
        document.getElementById('dialogRecipientInput').focus();
        return false;
    }
    // Get dialog_recipient value to recipient
    var recipient = document.getElementById('dialogRecipientInput').value;
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
    var panel_mask = document.getElementById("panel_mask");
    panel_mask.style.visibility = "hidden";
    document.getElementById('tabContainer').removeChild(document.getElementById("panel_c"));
}
