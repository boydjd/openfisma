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
 * @version   $Id$
 */

Fisma.Remediation = {
    /**
     * Popup a panel for upload evidence
     * 
     * @return {Boolean} False to interrupt consequent operations
     */
    upload_evidence : function() {
        if (!form_confirm(document.finding_detail, 'Upload Evidence')) {
            return false;
        }

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
     * To approve evidence with optional comment
     * 
     * @param {String} formname The main form name from page
     * @return {Boolean} False if user gives up this operation
     */
    ev_approve : function(formname) {
        if (!form_confirm(document.finding_detail, 'approve the evidence package')) {
            return false;
        }

        var content = document.createElement('div');
        var p = document.createElement('p');
        p.appendChild(document.createTextNode('Comments (OPTIONAL):'));
        content.appendChild(p);
        var dt = document.createElement('textarea');
        dt.rows = 5;
        dt.cols = 60;
        dt.id = 'dialog_comment';
        dt.name = 'comment';
        content.appendChild(dt);
        var div = document.createElement('div');
        div.style.height = '20px';
        content.appendChild(div);
        var button = document.createElement('input');
        button.type = 'button';
        button.id = 'dialog_continue';
        button.value = 'Continue';
        content.appendChild(button);

        Fisma.HtmlPanel.showPanel('Evidence Approval', content.innerHTML);
        document.getElementById('dialog_continue').onclick = function (){
            var form2 = formname;
            var comment = document.getElementById('dialog_comment').value;
            form2.elements['comment'].value = comment;
            form2.elements['decision'].value = 'APPROVED';
            var submitMsa = document.createElement('input');
            submitMsa.type = 'hidden';
            submitMsa.name = 'submit_ea';
            submitMsa.value = 'APPROVED';
            form2.appendChild(submitMsa);
            form2.submit();
        };
        
        return true;
    },

    /**
     * To deny evidence with comment
     * 
     * @param {String} formname The main form name from page
     * @return {Boolean} False if user gives up this operation
     */
    ev_deny : function(formname) {
        if (!form_confirm(document.finding_detail, 'deny the evidence package')) {
            return false;
        }

        var content = document.createElement('div');
        var p = document.createElement('p');
        p.appendChild(document.createTextNode('Comments:'));
        content.appendChild(p);
        var dt = document.createElement('textarea');
        dt.rows = 5;
        dt.cols = 60;
        dt.id = 'dialog_comment';
        dt.name = 'comment';
        content.appendChild(dt);
        var div = document.createElement('div');
        div.style.height = '20px';
        content.appendChild(div);
        var button = document.createElement('input');
        button.type = 'button';
        button.id = 'dialog_continue';
        button.value = 'Continue';
        content.appendChild(button);

        Fisma.HtmlPanel.showPanel('Evidence Denial', content.innerHTML);
        document.getElementById('dialog_continue').onclick = function (){
            var form2 = formname;
            var comment = document.getElementById('dialog_comment').value;
            if (comment.match(/^\s*$/)) {
                alert('Comments are required in order to deny.');
                return;
            }
            form2.elements['comment'].value = comment;
            form2.elements['decision'].value = 'DENIED';
            var submitMsa = document.createElement('input');
            submitMsa.type = 'hidden';
            submitMsa.name = 'submit_ea';
            submitMsa.value = 'DENIED';
            form2.appendChild(submitMsa);
            form2.submit();
            return;
        };
        
        return true;
    },

    /**
     * To approve mitigation strategy with optional comment
     * 
     * @param {String} formname The main form name from page
     * @return {Boolean} False if user gives up this operation
     */
    ms_approve : function(formname) {
        if (!form_confirm(document.finding_detail, 'approve the mitigation strategy')) {
            return false;
        }

        var content = document.createElement('div');
        var p = document.createElement('p');
        var c_title = document.createTextNode('Comments (OPTIONAL):');
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
        
        Fisma.HtmlPanel.showPanel('Mitigation Strategy Approval', content.innerHTML);
        document.getElementById('dialog_continue').onclick = function (){
            var form2 = formname;
            var comment = document.getElementById('dialog_comment').value;
            form2.elements['comment'].value = comment;
            form2.elements['decision'].value = 'APPROVED';
            var submitMsa = document.createElement('input');
            submitMsa.type = 'hidden';
            submitMsa.name = 'submit_msa';
            submitMsa.value = 'APPROVED';
            form2.appendChild(submitMsa);
            form2.submit();
        };
        
        return true;
    },

    /**
     * To deny mitigation strategy with comment
     * 
     * @param {String} formname The main form name from page
     * @return {Boolean} False if user gives up this operation
     */
    ms_deny : function(formname) {
        if (!form_confirm(document.finding_detail, 'deny the mitigation strategy')) {
            return false;
        }

        var content = document.createElement('div');
        var p = document.createElement('p');
        var c_title = document.createTextNode('Comments:');
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
        
        Fisma.HtmlPanel.showPanel('Mitigation Strategy Denial', content.innerHTML);
        document.getElementById('dialog_continue').onclick = function (){
            var form2 = formname;
            var comment = document.getElementById('dialog_comment').value;
            if (comment.match(/^\s*$/)) {
                alert('Comments are required in order to submit.');
                return;
            }
            form2.elements['comment'].value = comment;
            form2.elements['decision'].value = 'DENIED';
            var submitMsa = document.createElement('input');
            submitMsa.type = 'hidden';
            submitMsa.name = 'submit_msa';
            submitMsa.value = 'DENIED';
            form2.appendChild(submitMsa);
            form2.submit();
            return;
        };
        
        return true;
    }
};
