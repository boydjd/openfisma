/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
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
            'timestamp' : comment.createdTs,
            'username' : comment.username,
            'comment' : comment.comment,
            'delete' :
                '/comment/remove/format/json/type/Finding/' +
                'commentId/' + comment.id +
                '/id/' + Fisma.Commentable.config.id
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

        var securityControlId = window.escape(securityControlElement.value);

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
        if (type === "containerCollapse" && autocomplete._bItemSelected) {
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

        container.firstChild.nodeValue = "No user named \""
                                       + unmatchedQuery
                                       + "\" was found.";
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
     * Configure the autocomplete that is used for selecting a security control
     *
     * @param autocomplete {YAHOO.widget.AutoComplete}
     * @param params {Array} The arguments passed to the autocomplete constructor
     */
    setupSecurityControlAutocomplete : function (autocomplete, params) {
        autocomplete.itemSelectEvent.subscribe(Fisma.Finding.handleSecurityControlSelection);
    },

    /**
    * This takes a YUI datatable as parameters, delete a row, then refresh the table
    *
    * @param YUI datatable
    */
    deleteEvidence: function (oArgs) {
        var oRecord = this.getRecord(oArgs.target);
        var data = oRecord.getData();
        var postData = {};

        var that = this;
        postData.id = data.id;
        postData.attachmentId = data.attachmentId;
        postData.csrf = $('[name="csrf"]').val();

        $.ajax({
            type: "POST",
            url: '/finding/remediation/delete-evidence/',
            data: postData,
            dataType: "json",
            success: function() {
                that.deleteRow(oArgs.target);
            }
        });
    },

    /**
     * Set the default Poc of an organization to Poc autocomplete field.
     *
     * @param id {String} The ID of organization.
     */
    setDefaultPoc : function (id) {

        YAHOO.util.Connect.asyncRequest(
            'GET',
            '/organization/get-poc/format/json/id/' + id,
            {
                success: function (connection) {
                    var result = YAHOO.lang.JSON.parse(connection.responseText);

                    Fisma.Finding.pocAutocomplete._bItemSelected = true;
                    Fisma.Finding.pocHiddenEl.value = result.pocId;
                    Fisma.Finding.pocAutocomplete.getInputEl().value = result.value;

                },

                failure : function (connection) {
                }
            }
        );
    },

    /**
     * Initialize the dashboard
     */
    initDashboard : function () {
        //sortable
        var storage = new Fisma.Storage('finding.dashboard');
        var leftColumn = storage.get('findingAnalystLeft');
        var rightColumn = storage.get('findingAnalystRight');

        if (leftColumn) {
            $.each(leftColumn.split(','), function(index, id){
                $('#' + id).find('script').remove();
                $('#' + id).appendTo('#findingAnalystLeft');
            });
        }
        if (rightColumn) {
            $.each(rightColumn.split(','), function(index, id){
                $('#' + id).find('script').remove();
                $('#' + id).appendTo('#findingAnalystRight');
            });
        }
        $(".column33, .column66")
            .sortable({
                placeholder : 'ui-sortable-proxy',
                update: function(event, ui) {
                    storage.set(event.target.id, $(event.target).sortable("toArray").join());
                },
                cancel: 'div.section'
            })
            .find('.sectionHeader')
                .css('cursor', 'move')
                .disableSelection()
        ;

        //collapsible
        $(".sectionHeader").filter(function(index){
            return ($('span.ui-icon', this).length < 1);
        })
            .prepend("<span class='ui-icon ui-icon-minusthick'></span>")
            .dblclick(function() {
                $(this).find('.ui-icon').click();
            })
            .find(".ui-icon")
                .css('cursor', 'pointer')
                .click(function() {
                    $(this).toggleClass("ui-icon-minusthick").toggleClass("ui-icon-plusthick");
                    var container = $(this).parents(".sectionContainer:first");
                    container.find(".section").toggle();
                    storage.set(container.attr('id'), $(this).hasClass("ui-icon-plusthick"));
                })
                .each(function() {
                    var container = $(this).parents(".sectionContainer:first");
                    var collapsed = storage.get(container.attr('id'));
                    if (collapsed) {
                        $(this).click();
                    }
                })
        ;

        //layout switch

        if ($('#toolbarRight #changeLayout').length > 0) {
            $('#TabView_FindingManager_TabViewContainer #changeLayout').remove();
        } else {
            $('#toolbarRight').prepend($('#changeLayout'));
            var layoutButton = new YAHOO.widget.Button("menuLayout", {type: "menu", menu: "menuLayoutSelect"});
            $("#layoutLeft").click(function() {
                $(".column33").removeClass('right').addClass('left');
                $(".column66").removeClass('left').addClass('right');
                layoutButton.getMenu().hide();
                storage.set('analystLayout', 'layoutLeft');
            });
            $("#layoutRight").click(function() {
                $(".column33").removeClass('left').addClass('right');
                $(".column66").removeClass('right').addClass('left');
                layoutButton.getMenu().hide();
                storage.set('analystLayout', 'layoutRight');
            });
        }
        var layout = storage.get('analystLayout');
        if (layout) {
            $('#' + layout).click();
        }

        //hide layout if not in Analyst view
        Fisma.tabView.subscribe('activeIndexChange', function(args) {
            if (args.newValue === 0) {
                $('#changeLayout').show();
            } else {
                $('#changeLayout').hide();
            }
        });
    },

    /**
     * Handle the onclick event of the "Show all" / "Show only" links
     */
    restrictTableLengthClickHandler: function (linkElement, num, registryName) {
        $(linkElement).parents('.section').find('tr.yui-dt-rec').show();
        $(linkElement).parents('.section').find('tr.yui-dt-rec span.bar').show(); //IE7 quirk mode
        if (num > 0) {
            $(linkElement).parents('.section').find('tr.yui-dt-rec:gt(' + --num + ')').hide();
            $(linkElement).parents('.section').find('tr.yui-dt-rec:gt(' + num + ') span.bar').hide(); //IE7 quirk mode
        }

        $(linkElement).siblings('a').show();
        $(linkElement).hide();

        new Fisma.Storage('finding.dashboard')
            .set(registryName, (linkElement.innerHTML.indexOf('only') > 0) ? 'only' : 'all');
        return false;
    },

    /**
     * Handle the renderEvent of the datatable
     */
    restrictTableLength: function () {
        var section = $(this.getContainerEl()).parents('.section');
        var registryName = $(this.getContainerEl()).attr('registryName');
        var defaultView = 'only';
        if (new Fisma.Storage('finding.dashboard').get(registryName)) {
            defaultView = new Fisma.Storage('finding.dashboard').get(registryName);
        }
        section.find('a:contains(' + defaultView + ')').click();
    },

    renameTag: function(tag) {
        var jcell = $('td').filter(function(index){
            return ($(this).text() === tag);
        });
        var row = jcell.parent().get(0);
        var datatable = Fisma.Registry.get('findingLinkTypeTable');
        datatable.selectRow(row);

        Fisma.Util.showInputDialog(
            "Rename '" + tag + "' ...",
            "New name",
            {
                'continue': function(ev, obj) {
                    YAHOO.util.Event.stopEvent(ev);
                    var input = obj.textField.value;
                    if (input !== "") {
                        obj.errorDiv.innerHTML = "Renaming '" + tag + "'...";

                        $.post(
                            '/finding/relationship/rename',
                            {
                                format: 'json',
                                oldTag: tag,
                                newTag: input,
                                csrf: $('[name=csrf]').val()
                            },
                            function(data) {
                                $('[name=csrf]').val(data.csrfToken);

                                if (data.result.success) {
                                    if (data.result.message) {
                                        Fisma.Util.showAlertDialog(data.result.message);
                                    } else {
                                        datatable.updateRow(row, {
                                            'Type': input,
                                            'Used': jcell.siblings().eq(0).find('div').text(),
                                            'Edit': {func: Fisma.Finding.renameTag, param: input},
                                            'Delete': '/finding/relationship/delete/tag/' + encodeURIComponent(input)
                                        });
                                    }
                                } else {
                                    Fisma.Util.showAlertDialog(data.result.message);
                                }
                                obj.panel.hide();
                                obj.panel.destroy();
                            }
                        );

                    } else {
                        obj.errorDiv.innerHTML = "Tag name cannot be blank.";
                    }
                },
                'cancel': function(ev, obj) {
                    datatable.unselectRow(row);
                }
            },
            tag
        );
    },

    addTag: function() {
        var datatable = Fisma.Registry.get('findingLinkTypeTable');

        Fisma.Util.showInputDialog(
            "Add a tag ...",
            "Tag name",
            {
                'continue': function(ev, obj) {
                    YAHOO.util.Event.stopEvent(ev);
                    var input = obj.textField.value;
                    if (input !== "") {
                        obj.errorDiv.innerHTML = "Adding tag '" + input + "'...";

                        $.post(
                            '/finding/relationship/new',
                            {
                                format: 'json',
                                tag: input,
                                csrf: $('[name=csrf]').val()
                            },
                            function(data) {
                                $('[name=csrf]').val(data.csrfToken);

                                if (data.result.success) {
                                    if (data.result.message) {
                                        Fisma.Util.showAlertDialog(data.result.message);
                                    } else {
                                        datatable.addRow({
                                            'Type': input,
                                            'Used': '0',
                                            'Edit': {func: Fisma.Finding.renameTag, param: input},
                                            'Delete': '/finding/relationship/delete/tag/' + encodeURIComponent(input)
                                        });
                                    }
                                } else {
                                    Fisma.Util.showAlertDialog(data.result.message);
                                }
                                obj.panel.hide();
                                obj.panel.destroy();
                            }
                        );
                    } else {
                        obj.errorDiv.innerHTML = "Tag name cannot be blank.";
                    }
                },
                'cancel': function(ev, obj) {
                }
            }
        );
    }
};
