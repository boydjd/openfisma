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
     * The ID of the container that displays the "POC not found" message
     */
    POC_MESSAGE_CONTAINER_ID : "findingPocNotMatched",

    /**
     * A static reference to the POC create form panel
     */
    createPocPanel : null,
    
    /**
     * A static reference to the username that should prepopulate the POC create panel
     */
    createPocDefaultUsername : null,
    
    /**
     * A static reference to the autocomplete which is used for matching a POC
     */
    pocAutocomplete : null,
    
    /**
     * A static reference to the hidden input element that stores the POC id
     */
    pocHiddenEl : null,

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
        
        var securityControlElement = document.getElementById('securityControlId');
        
        var securityControlId = escape(securityControlElement.value);
        
        YAHOO.util.Connect.asyncRequest(
            'GET', 
            '/security-control/single-control/id/' + securityControlId, 
            {
                success: function (connection) {
                    controlContainer.innerHTML = connection.responseText;
                },
                
                failure : function (connection) {
                    Fisma.Util.showAlertDialog('Unable to load security control definition.');
                }
            }
        );
    },

    /**
     * Configure the autocomplete that is used for selecting a POC
     * 
     * @param autocomplete {YAHOO.widget.AutoComplete}
     * @param params {Array} The arguments passed to the autocomplete constructor
     */
    setupPocAutocomplete : function (autocomplete, params) {
        Fisma.Finding.pocAutocomplete = autocomplete;
        Fisma.Finding.pocHiddenEl = document.getElementById(params.hiddenFieldId);

        // Set up the events to display the POC not found message
        autocomplete.dataReturnEvent.subscribe(Fisma.Finding.displayPocNotFoundMessage);
        autocomplete.containerCollapseEvent.subscribe(Fisma.Finding.displayPocNotFoundMessage);

        // Set up the events to hide the POC not found message
        autocomplete.itemSelectEvent.subscribe(Fisma.Finding.hidePocNotFoundMessage);
        autocomplete.containerExpandEvent.subscribe(Fisma.Finding.hidePocNotFoundMessage);
    },

    /**
     * Display a message to let the user know that the POC they were looking for could not be found
     * 
     * This is registered as the event handler for both the data return event and the container collapse event, so it
     * has some conditional logic based on what "type" and what arguments it receives.
     * 
     * @param type {String} Name of the event.
     * @param args {Array} Event arguments.
     */   
    displayPocNotFoundMessage : function (type, args) {
        var autocomplete = Fisma.Finding.pocAutocomplete;

        // This event handler handles 2 events, only 1 of which has a results array, so this setter is conditional.
        var results = args.length >= 2 ? args[2] : null;

        // Don't show the POC message if there are autocomplete results available
        if (YAHOO.lang.isValue(results) && results.length !== 0) {
            Fisma.Finding.hidePocNotFoundMessage();
            return;
        }

        /* Don't show the POC message if the user selected an item.
         * 
         * There's no way to do this without using autocomplete's private member _bItemSelected.
         */
        if (type == "containerCollapse" && autocomplete._bItemSelected) {
            return;
        }

        // Don't display the POC not found message if the autocomplete list is visible
        if (autocomplete.isContainerOpen()) {
            return;
        }

        var unmatchedQuery = autocomplete.getInputEl().value;

        // Don't show the POC not found message if the 
        if (unmatchedQuery.match(/^\s*$/)) {
            return;
        }

        // Otherwise, display the POC not found message
        var container = document.getElementById(Fisma.Finding.POC_MESSAGE_CONTAINER_ID);

        if (YAHOO.lang.isNull(container)) {
            container = Fisma.Finding._createPocNotFoundContainer(
                Fisma.Finding.POC_MESSAGE_CONTAINER_ID, 
                autocomplete.getInputEl().parentNode
            );
        }

        container.firstChild.nodeValue = "No point of contact named \"" 
                                       + unmatchedQuery
                                       + "\" was found. Click here to create one.";
        container.style.display = 'block';

        Fisma.Finding.createPocDefaultUsername = unmatchedQuery;
    },

    /**
     * Create the container for the POC not found message
     * 
     * @param id {String} The ID to set on the container
     * @param parent {HTMLElement} The autocomplete that this container belongs to
     */
    _createPocNotFoundContainer : function (id, parent) {
        var container = document.createElement('div');

        YAHOO.util.Event.addListener(container, "click", Fisma.Finding.displayCreatePocForm, this, true);
        container.className = 'pocNotMatched';
        container.id = id;
        container.appendChild(document.createTextNode(""));

        parent.appendChild(container);
        
        return container;
    },

    /**
     * Hide the POC not found message
     */
    hidePocNotFoundMessage : function () {
        var container = document.getElementById(Fisma.Finding.POC_MESSAGE_CONTAINER_ID);

        if (YAHOO.lang.isValue(container)) {
            container.style.display = 'none';
        }
    },

    /**
     * Display a POC creation form inside a modal dialog.
     * 
     * When setting a finding POC, if the user doesn't select from the autocomplete list, then prompt them to see if
     * they want to create a new POC instead.
     */
    displayCreatePocForm : function () {
        if (YAHOO.lang.isNull(Fisma.Finding.createPocPanel)) {
            var panelConfig = {width : "50em", modal : true};

            Fisma.Finding.createPocPanel = Fisma.UrlPanel.showPanel(
                'Create New Point Of Contact',
                '/poc/form',
                Fisma.Finding.populatePocForm,
                'createPocPanel',
                panelConfig
            );

            Fisma.Finding.createPocPanel.subscribe("hide", this.removePocMessageBox, this, true);            
        } else {
            // Handle OFJ-1579 IE7 bug.
            if (YAHOO.env.ua.ie === 7) {
                Fisma.Finding.createPocPanel.center();
            }

            Fisma.Finding.createPocPanel.show();
            document.getElementById("username").value = Fisma.Finding.createPocDefaultUsername;
            Fisma.Finding.createPocMessageBox();
        }
    },

    /**
     * Create POC modal dialog's custom message box
     */
    createPocMessageBox: function () {
        var messageBarContainer = document.getElementById("pocMessageBar");

        if (YAHOO.lang.isNull(messageBarContainer)) {
            throw "No message bar container found.";
        }

        var pocMessageBox = new Fisma.MessageBox(messageBarContainer);
        Fisma.Registry.get("messageBoxStack").push(pocMessageBox);
    },

    /**
     * Remove the POC modal dialog's custom message box
     * 
     * @param event {YAHOO.util.Event} The YUI event subscriber signature.
     */
    removePocMessageBox: function (event) {
        Fisma.Registry.get("messageBoxStack").pop();

        // Handle OFJ-1579 IE7 bug.
        if (YAHOO.env.ua.ie === 7) {
            this.createPocPanel.moveTo(5000,0);
        }
    },

    /**
     * Populate the POC create form with some default values
     */
    populatePocForm : function () {
        // this method is called in the wrong scope :(
        Fisma.Finding.createPocMessageBox();

        // Fill in the username
        var usernameEl = document.getElementById('username');
        usernameEl.value = Fisma.Finding.createPocDefaultUsername;

        // The form contains some scripts that need to be executed
        var scriptNodes = Fisma.Finding.createPocPanel.body.getElementsByTagName('script');

        for (var i=0; i < scriptNodes.length; i++) {
            try {
                eval(scriptNodes[i].text);
            } catch (e) {
                var message = 'Not able to execute one of the scripts embedded in this page: ' + e.message;
                Fisma.Util.showAlertDialog(message);
            } 
        }
        
        // The tool tips will display underneath the modal dialog mask, so we'll move them up to a higher layer.
        var tooltips = YAHOO.util.Dom.getElementsByClassName('yui-tt', 'div');
        
        for (var index in tooltips) {
            var tooltip = tooltips[index];

            // The yui panel is usually 4, so anything higher is good.
            tooltip.style.zIndex = 5;
        }
    },
    
    /**
     * Submit an XHR to create a POC object
     */
    createPoc : function () {
        // The scope is the button that was clicked, so save it for closures
        var button = this;
        var form = Fisma.Finding.createPocPanel.body.getElementsByTagName('form')[0];

        // Disable the submit button
        button.set("disabled", true);

        // Save the username so we can populate it back on the create finding form
        var username = document.getElementById("username").value;

        YAHOO.util.Connect.setForm(form);
        YAHOO.util.Connect.asyncRequest('POST', '/poc/create/format/json', {
            success : function(o) {
                var result;

                try {
                    result = YAHOO.lang.JSON.parse(o.responseText).result;
                } catch (e) {
                    result = {success : false, message : e};
                }

                if (result.success) {
                    Fisma.Finding.createPocPanel.hide();
                    Fisma.Finding.hidePocNotFoundMessage();

                    /* Trick the autocomplete into think it has selected an item. This violates it's abstraction (by
                     * accessing a private member) but there is no public api to do this. Otherwise, if the user clicks
                     * on the field, YUI will clear it out due to the "enforce selection" feature. 
                     */
                    Fisma.Finding.pocAutocomplete._bItemSelected = true;

                    // Populate the autocomplete with the values corresponding to this new POC
                    var pocId = parseInt(result.message, 10);
                    Fisma.Finding.pocHiddenEl.value = pocId;
                    Fisma.Finding.pocAutocomplete.getInputEl().value = username;

                    message('A point of contact has been created.', 'info', true);
                } else {
                    message(result.message, 'warning', true);
                    button.set("disabled", false);
                }
            },
            failure : function(o) {
                var alertMessage = 'Failed to create new point of contact: ' + o.statusText;
                Fisma.Finding.createPocPanel.setBody(alertMessage);
            }
        }, null);
    },

    /**
     * Configure the autocomplete that is used for selecting a security control
     * 
     * @param autocomplete {YAHOO.widget.AutoComplete}
     * @param params {Array} The arguments passed to the autocomplete constructor
     */
    setupSecurityControlAutocomplete : function (autocomplete, params) {
        autocomplete.itemSelectEvent.subscribe(Fisma.Finding.handleSecurityControlSelection);
    }
};
