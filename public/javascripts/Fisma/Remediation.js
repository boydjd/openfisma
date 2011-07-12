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
 * @fileoverview This file contains related javascript code about the feature finding remediation
 *
 * @author    Jackson Yang <yangjianshan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Remediation = {
    /**
     * Popup a panel for upload evidence
     * 
     * @return {Boolean} False to interrupt consequent operations
     */
    upload_evidence : function() {

        Fisma.UrlPanel.showPanel(
            'Upload Evidence', 
            '/finding/remediation/upload-form', 
            Fisma.Remediation.upload_evidence_form_init);

        return false;
    },

    /**
     * Initialize another form finding_detail_upload_evidence after panel loaded
     */
    upload_evidence_form_init : function() {
        // Initialize form action from finding_detail.action since they are separated forms and the form from
        // from the panel belongs to document body rather than the form document.finding_detail.But they should
        // have same target action. So set the latter`s action with the former`s.
        document.finding_detail_upload_evidence.action = document.finding_detail.action;
    },

   /**
     * To approve or deny mitigation strategy or evidence with comment
     * 
     * @param {String} action The action name: APPROVED or DENIED
     * @param {String} formId 
     * @param {String} panelTitle the text shows on the panel.
     */
    remediationAction : function(action, formId, panelTitle) {

        var content = document.createElement('div');
        var p = document.createElement('p');
        var c_title;
        if ('APPROVED' === action) {
            c_title = document.createTextNode('Comments (OPTIONAL):');
        } else {
            c_title = document.createTextNode('Comments:');
        }
        p.appendChild(c_title);
        content.appendChild(p);
        var textarea = document.createElement('textarea');
        textarea.id = 'dialog_comment';
        textarea.name = 'comment';
        textarea.rows = 5;
        textarea.cols = 60;
        content.appendChild(textarea);
        var div = document.createElement('div');
        div.style.height = '20px';
        content.appendChild(div);
        var button = document.createElement('input');
        button.type = 'button';
        button.id = 'dialog_continue';
        button.value = 'Continue';
        content.appendChild(button);
       
        Fisma.HtmlPanel.showPanel(panelTitle, content.innerHTML);

        document.getElementById('dialog_continue').onclick = function (){
            var form2 = document.getElementById(formId);
            var comment = document.getElementById('dialog_comment').value;

            if ('DENIED' === action) { 
                if (comment.match(/^\s*$/)) {
                    var alertMessage = 'Comments are required in order to submit.';
                    var config = {zIndex : 10000};
                    Fisma.Util.showAlertDialog(alertMessage, config);
                    return;
                }
            }

            form2.elements['comment'].value = comment;
            form2.elements['decision'].value = action;

            var sub = document.createElement('input');
            sub.type = 'hidden';
            sub.name = 'submit_msa';
            sub.value = action;
            form2.appendChild(sub);
            form2.submit();
            return;
        };
        
        return true;
    }

};
