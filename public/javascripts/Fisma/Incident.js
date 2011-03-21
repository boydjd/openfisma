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
 * @fileoverview Provides client-side behavior for the Incident Reporting module
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */
 
Fisma.Incident = {
    /**
     * A reference to a YUI table which contains comments for the current page
     * 
     * This reference will be set when the page loads by the script which initializes the table
     */
    commentTable : null,

    /**
     * This is called after an artifact has been uploaded successfully. We could do something nifty here like
     * dynamically update the page, but at the moment I'm going to be lazy and just refresh the entire page.
     * 
     * @param yuiPanel This is required for a callback but not used here
     */
    attachArtifactCallback : function (yuiPanel) {
        window.location.href = window.location.href;
    },
    
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
        var commentCountEl = document.getElementById('incidentCommentsCount').firstChild;
        commentCountEl.nodeValue++;
        
        // Hide YUI dialog
        yuiPanel.hide();
        yuiPanel.destroy();
    },
    
    /**
     * Given the button element inside an incident workflow step, return the <tr> parent element which contains
     * the entire step.
     * 
     * @param element The button element which was clicked
     */
    getIncidentStepParentElement : function (element) {
        var parent = element.parentNode.parentNode.parentNode;
        
        // Sanity check: this must be a <tr> element node
        var elementNode = 1;
        if (!(elementNode == parent.nodeType && "TR" == parent.tagName)) {
            throw "Cannot locate the parent element for this incident step.";
        }
        
        return parent;
    },
    
    /**
     * Given a reference to a <tr> containing an incidnet workflow step, return the step number of that step.
     * 
     * @param trElement The table row element which contains the incident step
     */
    getIncidentStepNumber : function(trElement) {
        var tdEl = trElement.firstChild;
        
        // Sanity check: this must be a <tr> element node
        var elementNode = 1;
        if (!(elementNode == tdEl.nodeType && "TD" == tdEl.tagName)) {
            throw "Cannot locate the table data (td) element for this incident step.";
        }
        
        // Use regex to pull out the step number from the label
        var label = tdEl.firstChild.nodeValue;        
        var numberMatches = label.match(/\d+/);
        
        // Sanity check: should match exactly 1 string of digits
        if (numberMatches.length != 1) {
            throw "Not able to locate the step number in the incident step label.";
        }
        
        return numberMatches[0];
    },
    
    /**
     * Renumber all of the incident steps
     * 
     * This takes the table element as a parameter, and it rewrites the label for each table row that has the class
     * "incidentStep".
     *
     * @param tableEl
     */
    renumberAllIncidentSteps : function(tableEl) {
        var trEls = YAHOO.util.Dom.getElementsByClassName('incidentStep', 'tr', tableEl);
        var stepNumber = 1;
        
        for (var i in trEls) {
            var trEl = trEls[i];
            
            trEl.firstChild.firstChild.nodeValue = "Step " + stepNumber + ":";
            stepNumber++;
        }
    },
    
    /**
     * Add an incident step above the current incident step (current refers to the one containing the "Add" button
     * that was clicked.)
     * 
     * @param element The button element that was clicked
     */
    addIncidentStepAbove : function (element) {
        var rowEl = this.getIncidentStepParentElement(element);
        var textareaId = this.generateTextareaId(rowEl.parentNode);
        var rowElClone = this.generateIncidentStep(rowEl, textareaId);
        
        rowEl.parentNode.insertBefore(rowElClone, rowEl);

        tinyMCE.execCommand ('mceAddControl', false, textareaId);
        
        this.renumberAllIncidentSteps(rowEl.parentNode);
        
        return false;
    },
    
    addIncidentStepBelow : function (element) {
        var rowEl = this.getIncidentStepParentElement(element);
        var textareaId = this.generateTextareaId(rowEl.parentNode);
        var rowElClone = this.generateIncidentStep(rowEl, textareaId);
        
        // There is no "insertAfter" method for DOM elements, so this is a little tricky
        if (rowEl.nextSibling) {
            rowEl.parentNode.insertBefore(rowElClone, rowEl.nextSibling);
        } else {
            rowEl.parentNode.appendChild(rowElClone);
        }
        
        tinyMCE.execCommand ('mceAddControl', false, textareaId);

        this.renumberAllIncidentSteps(rowEl.parentNode);

        return false;
    },
    
    removeIncidentStep : function (element) {
        var rowEl = this.getIncidentStepParentElement(element);
        
        rowEl.parentNode.removeChild(rowEl);
        
        this.renumberAllIncidentSteps(rowEl.parentNode);
        
        return false; 
    },

    /** 
    * This takes a tr element and an unique id as  parameters, and it generates a new incident step form fields 
    * including name, description and buttons. And compose newly generated elements to a tr element node.
    *    
    * @param tableEl
    * @param textareaId an unique id for textarea so that tinyMCE can be added.
    * @return a <tr> element node containing sub form fields.
    */
    generateIncidentStep: function (rowEl, textareaId) {
        //To check the tr node structure, see the render() at class Fisma_Zend_Form_Element_IncidentWorkflowStep
        //clone tr node without its children
        var rowElClone = rowEl.cloneNode(false);
        var tableEl = rowEl.parentNode;

        //create first td node containing "Step " text
        var newTdElStep = document.createElement('td');
        newTdElStep.innerHTML = 'Step : ';
        rowElClone.appendChild(newTdElStep);

        //create second td node containing all the form fields
        var newTdElForm = document.createElement('td');
        rowElClone.appendChild(newTdElForm);

        //the last child of rowEl is <td> block containing the subform fields
        var tdForm = YAHOO.util.Dom.getLastChild(rowEl);

        //The first child of <td> block should be Name field.
        var nameField = YAHOO.util.Dom.getFirstChild(tdForm);
        var nameElClone = nameField.cloneNode(true) 

        //The next sibling should be role field  
        var roleField = YAHOO.util.Dom.getNextSibling(nameField);
        var roleElClone = roleField.cloneNode(true) 

        newTdElForm.appendChild(nameElClone); 
        newTdElForm.appendChild(roleElClone); 

        //create p node for Desription and textarea
        var elP = document.createElement('p');
        elP.innerHTML = 'Description: ';

        var descField = YAHOO.util.Dom.getNextSibling(roleField);
        var textareaField = YAHOO.util.Dom.getFirstChild(descField);

        //get original textarea attribute value
        var textareaRows = YAHOO.util.Dom.getAttribute(textareaField, 'rows');
        var textareaCols = YAHOO.util.Dom.getAttribute(textareaField, 'cols');
        var textareaName = YAHOO.util.Dom.getAttribute(textareaField, 'name');

        // To create an element with a NAME attribute and its value for IE.
        if (YAHOO.env.ua.ie) {
            var newTextareaEl = document.createElement("<textarea name='" + textareaName + "'></textarea>");
        } else {
            var newTextareaEl = document.createElement('textarea');
        }

        newTextareaEl.setAttribute('id',textareaId);
        newTextareaEl.setAttribute('rows',textareaRows);
        newTextareaEl.setAttribute('cols',textareaCols);
        newTextareaEl.setAttribute('name',textareaName);

        elP.appendChild(newTextareaEl);
        newTdElForm.appendChild(elP); 

        //get button field of form 
        var buttonField = YAHOO.util.Dom.getNextSibling(descField);
        var buttonElClone = buttonField.cloneNode(true);
        newTdElForm.appendChild(buttonElClone); 

        return rowElClone;
    },

    /** 
    * This takes a table element  as  parameters, and it generates an unique Id for textarea 
    *    
    * @param tableEl
    * @return ID.
    */
    generateTextareaId: function (element) {
        var trEls = YAHOO.util.Dom.getElementsByClassName('incidentStep', 'tr', element);
        var stepNumber = 1 + trEls.length;
        var textareaId = 'textareaid' + stepNumber;
        return textareaId;
    }
};
