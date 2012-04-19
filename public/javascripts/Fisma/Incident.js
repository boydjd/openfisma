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
        window.location = window.location.href;
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

        this.commentTable = Fisma.Registry.get('comments');

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
        if (!(elementNode === parent.nodeType && "TR" === parent.tagName)) {
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
        if (!(elementNode === tdEl.nodeType && "TD" === tdEl.tagName)) {
            throw "Cannot locate the table data (td) element for this incident step.";
        }

        // Use regex to pull out the step number from the label
        var label = tdEl.firstChild.nodeValue;
        var numberMatches = label.match(/\d+/);

        // Sanity check: should match exactly 1 string of digits
        if (numberMatches.length !== 1) {
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

        var i;
        for (i in trEls) {
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
        var nameElClone = nameField.cloneNode(true);

        //The next sibling should be role field
        var roleField = YAHOO.util.Dom.getNextSibling(nameField);
        var roleElClone = roleField.cloneNode(true);

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
        var newTextareaEl;
        if (YAHOO.env.ua.ie) {
            newTextareaEl = document.createElement("<textarea name='" + textareaName + "'></textarea>");
        } else {
            newTextareaEl = document.createElement('textarea');
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
    },

    /**
     * Confirm before an incident is rejected
     *
     * @param {YAHOO.util.Event} e
     */
    confirmReject: function (e) {
        var confirmation = {
            text: "Are you sure you want to reject this incident? This action can NOT be undone.",
            func: function () {
                var incidentForm = document.getElementById('incident_detail');

                var hiddenEl = document.createElement('input');
                hiddenEl.type = "hidden";
                hiddenEl.name = "reject";
                hiddenEl.value = "reject";

                incidentForm.appendChild(hiddenEl);
                incidentForm.submit();
            }
        };

        Fisma.Util.showConfirmDialog(e, confirmation);
    },

    /**
     * Add an actor or observer to a specified incident.
     *
     * Because this is coming from PHP, we can only pass 1 argument, so the argument is a dictionary containing:
     *
     * type: either 'actor' or 'observer'
     * incidentId: the ID of the incident to add the person to
     *
     * @param {YAHOO.util.Event} event
     * @param {Object} args
     */
    addUser: function(event, args) {
        var type = args.type;
        var incidentId = args.incidentId;
        var userId = document.getElementById(type + "Id").value;
        var username = document.getElementById(type + "Autocomplete").value;

        var postData = Fisma.Util.convertObjectToPostData({
            csrf: document.getElementById('incident_detail').elements.csrf.value,
            userId: userId,
            username: username,
            incidentId: incidentId,
            type: type
        });

        // Put button in the closure scope
        var button = this;
        button.set('disabled', true);

        YAHOO.util.Connect.asyncRequest(
            'POST',
            '/incident/add-user/format/json',
            {
                success: function(o) {
                    button.set('disabled', false);

                    var parsed, response, user;

                    try {
                        parsed = YAHOO.lang.JSON.parse(o.responseText);
                        response = parsed.response;
                        user = parsed.user;
                    } catch (e) {
                        response = {success: false, message: "invalid response from server"};
                    }

                    if (response.success) {
                        var dataTable;

                        if (type === 'actor') {
                            dataTable = Fisma.Registry.get('actorTable');
                        } else {
                            dataTable = Fisma.Registry.get('observerTable');
                        }

                        Fisma.Incident.addUserToTable(user, dataTable);
                        Fisma.Registry.get('messageBoxStack').peek().hide();
                    } else {
                        var message = "Cannot add actor or observer: " + response.message;
                        Fisma.Registry.get('messageBoxStack').peek().setMessage(message).show();
                    }
                },

                failure: function(o) {
                    button.set('disabled', false);

                    var message = 'Cannot add actor or observer: ' + o.statusText;
                    Fisma.Registry.get('messageBoxStack').peek().setMessage(message).show();
                }
            },
            postData

        );
    },

    /**
     * Add a user (actor or observer) to a data table.
     *
     * @param {Object} user
     * @param {YAHOO.widget.DataTable} table
     */
    addUserToTable: function (user, table) {
        table.addRow(user, 0);
        table.set("sortedBy", null);

        // Highlight the added row so the user can see that it worked
        var blinker = new Fisma.Blinker(
            100,
            6,
            function () {
                table.highlightRow(0);
            },
            function () {
                table.unhighlightRow(0);
            }
        );

        blinker.start();
    },

    /**
     * Remove a user (actor or observer) from an incident.
     *
     * @param {Integer} incidentId
     * @param {Integer} userId
     * @param {YAHOO.widget.DataTable} table
     */
    removeUser: function (incidentId, userId, table) {
        var postData = Fisma.Util.convertObjectToPostData({
            incidentId: incidentId,
            userId: userId,
            csrf: document.getElementById('incident_detail').elements.csrf.value
        });

        YAHOO.util.Connect.asyncRequest(
            'POST',
            '/incident/remove-user/format/json',
            {
                success: function(o) {
                    var response;

                    try {
                        response = YAHOO.lang.JSON.parse(o.responseText).response;
                    } catch (e) {
                        response = {success: false, message: "invalid response from server"};
                    }

                    if (response.success) {
                        Fisma.Incident.removeUserFromTable(userId, table);
                        Fisma.Registry.get('messageBoxStack').peek().hide();
                    } else {
                        var message = "Cannot remove actor or observer: " + response.message;
                        Fisma.Registry.get('messageBoxStack').peek().setMessage(message).show();
                    }
                },

                failure: function(o) {
                    var message = 'Cannot remove actor or observer: ' + o.statusText;
                    Fisma.Registry.get('messageBoxStack').peek().setMessage(message).show();
                }
            },
            postData
        );
    },

    /**
     * Remove a user row from a data table
     *
     * @param {Integer} userId
     * @param {YAHOO.widget.DataTable} table
     */
    removeUserFromTable: function (userId, table) {
        var recordSet = table.getRecordSet();

        // There doesn't seem to be an easy way to get a particular record from a click event, so loop over the table
        // to find the matching record.
        var i;
        for (i = 0; i < recordSet.getLength(); i++) {
            var record = recordSet.getRecord(i);

            if (record.getData('userId') === userId) {
                recordSet.deleteRecord(recordSet.getRecordIndex(record));
                table.render();
                return;
            }
        }
    },

    /**
     * This is called when a user presses enter on an incident actor or observer autocomplete field.
     *
     * It responds by triggering the "add actor" or "add observer" button click event.
     *
     * @param {Fisma.AutoComplete} ac
     * @param {String} type Either "actor" or "observer"
     */
    handleAutocompleteEnterKey: function (ac, type) {
        if (!ac.isContainerOpen()) {
            var button = document.getElementById('add' + $P.ucfirst(type) + "-button");

            if (button) {
                button.click();
            }
        }
    }
};
