/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * @fileoverview Client-side behavior related to the Finding module
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */
 
Fisma.Finding = {
    /**
     * A reference to a YUI table which contains comments for the current page
     * 
     * This reference will be set when the page loads by the script which initializes the table
     */
    commentTable : null,
    
    /**
     * Handle successful comment events by inserting the latest comment into the top of the comment table
     * 
     * @param comment An object containing the comment record values
     * @param yuiPanel A reference to the modal YUI dialog
     */
    commentCallback : function (comment, yuiPanel) {
                
        var that = this;
        
        var commentRow = {
            timestamp : comment.createdTs,
            username : comment.username,
            comment : comment.comment
        };

        this.commentTable.addRow(commentRow);
        
        /*
         * Redo the sort. If the user had some other sort applied, then our element might be inserted in
         * the wrong place and the sort would be wrong.
         */
        this.commentTable.sortColumn(this.commentTable.getColumn(0), YAHOO.widget.DataTable.CLASS_DESC);
        
        // Highlight the added row so the user can see that it worked
        var rowBlinker = new Fisma.Blinker(
            100,
            6,
            function () {
                that.commentTable.highlightRow(0);
            },
            function () {
                that.commentTable.unhighlightRow(0);
            }            
        );
        
        rowBlinker.start();
        
        // Update the comment count in the tab UI
        var commentCountEl = document.getElementById('findingCommentsCount').firstChild;
        commentCountEl.nodeValue++;
                
        // Hide YUI dialog
        yuiPanel.hide();
        yuiPanel.destroy();
    },
    
    /**
     * A function which is called when the ECD needs to be changed and a justification needs to be provided.
     * 
     * This function will convert the ECD justification into an editable text field that can be submitted with the
     * form.
     */
    editEcdJustification : function () {
        
        // Hide the current text
        var currentEcdJustificationEl = document.getElementById('currentChangeDescription');
        currentEcdJustificationEl.style.display = 'none';
        
        // Copy current text into a new input element
        var currentEcdJustification;
        if (currentEcdJustificationEl.firstChild) {
            currentEcdJustification = currentEcdJustificationEl.firstChild.nodeValue;
        } else {
            currentEcdJustification = '';
        }
        
        var inputEl = document.createElement('input');
        inputEl.type = 'text';
        inputEl.value = currentEcdJustification;
        inputEl.name = 'finding[ecdChangeDescription]';
        
        currentEcdJustificationEl.parentNode.appendChild(inputEl);
    },
    
    /**
     * Show the search control on the finding view's Security Control tab
     */
    showSecurityControlSearch : function () {
        var button = document.getElementById('securityControlSearchButton');
        button.style.display = 'none';

        var searchForm = document.getElementById('findingSecurityControlSearch');
        searchForm.style.display = 'block';
    },
    
    /**
     * When the user selects a security control, refresh the screen with that control's data
     */
    handleSecurityControlSelection : function () {
        var controlContainer = document.getElementById('securityControlContainer');
        
        controlContainer.innerHTML = '<img src="/images/loading_bar.gif">';
        
        var securityControlElement = document.getElementById('finding[securityControlId]');
        
        var securityControlId = escape(securityControlElement.value);
        
        YAHOO.util.Connect.asyncRequest(
            'GET', 
            '/security-control/single-control/id/' + securityControlId, 
            {
                success: function (connection) {
                    controlContainer.innerHTML = connection.responseText;
                },
                
                failure : function (connection) {
                    alert('Unable to load security control definition.');
                }
            }
        );
    },

    /**
     * Show the warning message before a find is deleted.
     */
    deleteFinding : function (event, config) {
        var  warningDialog =  
            new YAHOO.widget.SimpleDialog("warningDialog",  
                { width: "300px", 
                  fixedcenter: true, 
                  visible: false, 
                  draggable: false, 
                  close: true,
                  modal: true,
                  text: "WARNING: You are about to delete the finding record. This action cannot be undone. "
                        + "Do you want to continue?", 
                  icon: YAHOO.widget.SimpleDialog.ICON_WARN, 
                  constraintoviewport: true, 
                  buttons: [ { text:"Yes", handler : function () {
                                   document.location = "/finding/remediation/delete/id/" + config.id;
                                   this.hide(); 
                               }
                             }, 
                             { text:"No",  handler : function () {
                                   this.hide(); 
                               }
                             } 
                           ] 
                } ); 
 
         warningDialog.setHeader("Are you sure?");
         warningDialog.render(document.body);
         warningDialog.show();
    }
};
