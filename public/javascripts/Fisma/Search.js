/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * @fileoverview Various client side behaviors related to search functionality
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Search = (function() {
    return {

        /**
         * A reference to the YUI data table which is used for displaying search results
         */
        yuiDataTable : null,

        /**
         * A callback function which is called when the YUI data table reference is set
         */
        onSetTableCallback : null,

        /**
         * True if the test configuration process is currently running
         */
        testConfigurationActive : false,

        /**
         * Advanced search panel
         */
        advancedSearchPanel : null,

        /**
         * A spinner that is display while persising the user's column preferences cookie
         */
        columnPreferencesSpinner : null,

        /**
         * A boolean which determines whether soft-deleted items are displayed in search results
         */
        showDeletedRecords : false,

        /**
         * User search preferences for when a search hasn't been executed on this model this session.
         */
        searchPreferences: null,

        /**
         * Boolean flag as to whether the search preferences have been updated.
         */
        updateSearchPreferences: false,

        /**
         * Test the current system configuration
         */
        testConfiguration : function () {

            if (Fisma.Search.testConfigurationActive) {
                return;
            }

            Fisma.Search.testConfigurationActive = true;

            var testConfigurationButton = document.getElementById('testConfiguration');
            YAHOO.util.Dom.addClass(testConfigurationButton, "yui-button-disabled");

            var spinner = new Fisma.Spinner(testConfigurationButton.parentNode);
            spinner.show();

            var postData = "csrf=" + document.getElementById('csrfToken').value;

            YAHOO.util.Connect.asyncRequest(
                'POST',
                '/config/test-search/format/json',
                {
                    success : function (o) {
                        var response = YAHOO.lang.JSON.parse(o.responseText).response;

                        if (response.success) {
                            Fisma.Util.message("Search configuration is valid", "notice", true);
                        } else {
                            Fisma.Util.message(response.message, "warning", true);
                        }

                        YAHOO.util.Dom.removeClass(testConfigurationButton, "yui-button-disabled");
                        Fisma.Search.testConfigurationActive = false;
                        spinner.hide();
                    },

                    failure : function (o) {
                        Fisma.Util.message('Error: ' + o.statusText, 'warning');

                        spinner.hide();
                    }
                },
                postData );
        },

        /**
         * Executes a search
         *
         * Two types of query are possible: simple and advanced. A hidden field is used to determine which of the
         * two to use while handling this event.
         *
         * @param form Reference to the search form
         * @param fromSearchForm {Boolean} indicate whether a search action comes from search form submission
         */
        executeSearch: function (form, fromSearchForm) {
            var dataTable = Fisma.Search.yuiDataTable;

            var onDataTableRefresh = {
                success : function (request, response, payload) {

                    // It sets start to 0 when fromSearchForm is true, so does payload.pagination.recordOffset
                    if (fromSearchForm) {
                        payload.pagination.recordOffset = 0;
                    }
                    dataTable.onDataReturnReplaceRows(request, response, payload);

                    // Update YUI's visual state to show sort on first data column
                    var sortColumnIndex = 0;
                    var sortColumn;

                    do {
                        sortColumn = dataTable.getColumn(sortColumnIndex);

                        sortColumnIndex++;
                    } while (sortColumn.formatter === Fisma.TableFormat.formatCheckbox);

                    // Reset the page to 1 if search form is submitted
                    if (!YAHOO.lang.isUndefined(form.search)  && 'Search' === form.search.value) {
                        dataTable.get('paginator').setPage(1);
                    }
                },
                failure : dataTable.onDataReturnReplaceRows,
                scope : dataTable,
                argument : dataTable.getState()
            };

            // Construct a query URL based on whether this is a simple or advanced search
            try {
                var postData = this.buildPostRequest(dataTable.getState(), fromSearchForm);

                dataTable.showTableMessage("Loading...");

                var dataSource = dataTable.getDataSource();
                dataSource.connMethodPost = true;
                dataSource.sendRequest(postData, onDataTableRefresh);
            } catch (error) {
                // If a string is thrown, then display that string to the user
                if ('string' === typeof error) {
                    Fisma.Util.showAlertDialog(error);
                }
            }
        },

        /**
         * Handles a search event. This works in tandem with the search.form and Fisma_Zend_Controller_Action_Object.
         *
         * @param form Reference to the search form
         */
        handleSearchEvent: function(form) {
            try {
                var queryState = new Fisma.Search.QueryState(form.modelName.value);
                var searchPrefs = {type: form.searchType.value};
                if (searchPrefs.type === 'advanced') {
                    var panelState = Fisma.Search.advancedSearchPanel.getPanelState();
                    var fields = {};
                    var i;
                    for (i in panelState) {
                        fields[panelState[i].field] = panelState[i].operator;
                    }
                    searchPrefs.fields = fields;
                }
                Fisma.Search.updateSearchPreferences = true;
                Fisma.Search.searchPreferences = searchPrefs;
                Fisma.Search.updateQueryState(queryState, form);
            } catch (e) {
                Fisma.Util.message(e);
            } finally {

                // Set the fromSearchForm to true when a search comes from search form submission
                Fisma.Search.executeSearch(form, true);
            }
        },

        /**
         * Update Query State
         *
         * @param queryState {Fisma.Search.QueryState}
         * @param form Reference to the search form
         */
        updateQueryState: function(queryState, form) {
            var Dom = YAHOO.util.Dom;
            var searchType = form.searchType.value;
            queryState.setSearchType(searchType);
            if (searchType === "simple") {
                queryState.setKeywords(form.keywords.value);
            } else if (searchType === "advanced") {
                queryState.setAdvancedQuery(Fisma.Search.advancedSearchPanel.getPanelState());
            }
        },

        /**
         * Returns a POST request suitable for submitting a search query
         *
         * @var form A reference to the form
         * @return Key value pairs (object) of query data
         */
        getQuery : function (form) {
            var searchType = document.getElementById('searchType').value;
            var query = {queryType : searchType};

            if ('simple' === searchType) {
                query.keywords = form.keywords.value;
            } else if ('advanced' === searchType) {
                var queryData = this.advancedSearchPanel.getQuery();
                query.query = YAHOO.lang.JSON.stringify(queryData);
            } else if ('faceted' === searchType) {
                query.keywords = form.keywords.value;
                var queryData = this.advancedSearchPanel.getQuery();
                query.query = YAHOO.lang.JSON.stringify(queryData);
            } else {
                throw "Invalid value for search type: " + searchType;
            }

            query.showDeleted = this.showDeletedRecords;

            query.csrf = document.getElementById('searchForm').csrf.value;

            return query;
        },

        /**
         * Convert an array of key value pairs into URL encoded post data
         *
         * @var object
         * @return string
         */
        convertQueryToPostData : function (object) {

            var uriComponents = [];
            var key;

            for (key in object) {
                var value = object[key];
                uriComponents.push(key + "=" + encodeURIComponent(value));
            }

            var postData = uriComponents.join('&');

            return postData;
        },

        /**
         * Download current search results into a file attachment (such as PDF or Excel)
         *
         * This function operates by creating a hidden form on the page and then calling submit() on that form.
         *
         * @var event Provided by YUI
         * @var format Either "pdf" or "xls"
         */
        exportToFile : function (event, format) {
            var searchForm = document.getElementById('searchForm');

            // The form's action is based on the data table's data source
            var table = Fisma.Search.yuiDataTable;
            var dataSource = table.getDataSource();
            var baseUrl = dataSource.liveData;

            // Create a hidden form for submitting the request
            var tempForm = document.createElement('form');

            tempForm.method = 'post';
            tempForm.action = baseUrl + '/format/' + format;
            tempForm.style.display = 'none';

            var query = Fisma.Search.getQuery(searchForm);

            // Create a hidden form element for each piece of post data
            var key;
            for (key in query) {
                var value = query[key];

                var hiddenField = document.createElement('input');

                hiddenField.type = 'hidden';
                hiddenField.name = key;
                hiddenField.value = value;

                tempForm.appendChild(hiddenField);
            }

            document.body.appendChild(tempForm);
            tempForm.submit();
        },

        /**
         * Handle YUI data table events (such as sort)
         *
         * @param tableState From YUI
         * @param table From YUI
         * @return string URL encoded post data
         */
        generateRequest: function (tableState, table) {
            var postData = "";

            try {
                postData = Fisma.Search.buildPostRequest(tableState);
            } catch (error) {
                if ('string' === typeof error) {
                    Fisma.Util.message(error, 'warning', true);
                }
            }

            table.getDataSource().connMethodPost = true;

            return postData;
        },

        /**
         * Method to generate the post data for the current query and table state
         *
         * @param tableState From YUI
         * @param fromSearchForm {Boolean} set start to 0 if it is true
         * @return {String} Post data representation of the current query
         */
        buildPostRequest: function (tableState, fromSearchForm) {
            var searchType = document.getElementById('searchType').value;
            var postData = {
                sort: tableState.sortedBy.key,
                dir: (tableState.sortedBy.dir === 'yui-dt-asc' ? 'asc' : 'desc'),
                start: (fromSearchForm ? 0 : tableState.pagination.recordOffset),
                count: tableState.pagination.rowsPerPage,
                csrf: document.getElementById('searchForm').csrf.value,
                showDeleted: Fisma.Search.showDeletedRecords,
                queryType: searchType
            };
            if ('simple' === searchType) {
                postData.keywords = document.getElementById('keywords').value;
            } else if ('advanced' === searchType) {
                postData.query = YAHOO.lang.JSON.stringify(Fisma.Search.advancedSearchPanel.getQuery());
            } else if ('faceted' === searchType) {
                postData.keywords = document.getElementById('keywords').value;
                postData.query = YAHOO.lang.JSON.stringify(Fisma.Search.advancedSearchPanel.getQuery());
            } else {
                throw "Invalid value for search type: " + searchType;
            }

            if (Fisma.Search.updateSearchPreferences) {
                postData.queryOptions = YAHOO.lang.JSON.stringify(Fisma.Search.searchPreferences);
            }

            var postDataArray = [];
            var key;
            for (key in postData) {
                postDataArray.push(key + "=" + encodeURIComponent(postData[key]));
            }
            return postDataArray.join("&");
        },

        /**
         * Highlight marked words in the search results table
         *
         * Due to a quirk in Solr, highlights are delimited by three asterisks ***. This method just has to go
         * through and find the asterisks, strip them out, and replace the content between them with highlighted text.
         *
         * @param dataTable The YUI data table to perform highlighting on
         */
        highlightSearchResultsTable :  function (dataTable) {

            var tbody = dataTable.getTbodyEl();

            var cells = tbody.getElementsByTagName('td');

            var delimiter = '***';

            Fisma.Highlighter.highlightDelimitedText(cells, delimiter);
        },

        /**
         * Show or hide the advanced search options UI
         */
        toggleAdvancedSearchPanel : function () {
            var Dom = YAHOO.util.Dom;
            var yuiButton = YAHOO.widget.Button.getButton("advanced");
            var advancedSearch = Dom.get("advancedSearch");
            if (advancedSearch.style.display === 'none') {
                advancedSearch.style.display = 'block';
                Dom.get('keywords').style.visibility = 'hidden';
                Dom.get('searchType').value = 'advanced';
                if (yuiButton) { yuiButton.set("checked", true); }
            } else {
                advancedSearch.style.display = 'none';
                Dom.get('keywords').style.visibility = 'visible';
                Dom.get('searchType').value = 'simple';
                if (yuiButton) { yuiButton.set("checked", false); }

                // The error message of advance search should not be displayed
                // after the advanced search options is hidden
                Dom.get('msgbar').style.display = 'none';
            }
        },

        /**
         * Toggle internal logic of advanced search without manipulating the layout
         *
         * @param boolean state The state to set to
         */
        setFacetSearch : function (state) {
            var Dom = YAHOO.util.Dom;
            if (state) {
                //Dom.get('keywords').style.visibility = 'hidden';
                Dom.get('searchType').value = 'faceted';
            } else {
                //Dom.get('keywords').style.visibility = 'visible';
                Dom.get('searchType').value = 'simple';
            }
        },

        /**
         * Handle facet links
         *
         * @param linkElement The HTML anchor element, whose id contains the filter
         * @return false
         */
        facetSearch : function (linkElement) {
            var args = linkElement.id.split('_');
            if (args.shift() != 'filter') {
                return false;
            }
            var field = args.shift();
            Fisma.Search.setFacetSearch(true);

            jQuery('a[id^=filter_' + field + '].selected').removeClass('selected');
            linkElement.className = 'selected';

            var panel = Fisma.Search.advancedSearchPanel;
            panel.criteria = [];
            jQuery('a.selected').each(function(index, element){
                var args = element.id.split('_');
                if (args.shift() != 'filter') {
                    return false;
                }
                var field = args.shift();
                var type = args.shift();
                if (type != 'all') {
                    var criterion1 = new Fisma.Search.Criteria(panel, panel.searchableFields);
                    criterion1.currentField = criterion1.getField(field);
                    criterion1.currentQueryType = type;
                    criterion1.forcedOperands = args;
                    panel.criteria.push(criterion1);
                }
            });

            Fisma.Search.executeSearch(YAHOO.util.Dom.get('searchForm'), true);
            /*if (type == 'all') {
                Fisma.Search.setFacetSearch(false); // re-activate simple search functionality
            }*/

            return false;
        },

        /**
         * Show or hide the search columns UI
         */
        toggleSearchColumnsPanel : function () {
            if (document.getElementById('searchColumns').style.display === 'none') {
                document.getElementById('searchColumns').style.display = 'block';
            } else {
                document.getElementById('searchColumns').style.display = 'none';
            }
        },

        /**
         * Initialize the search columns UI
         *
         * @param container The HTML element to render into
         * @param searchOptions The options defined in Fisma_Search_Searchable interface
         * @param columnVisibility Initial visibility of table columns
         */
        initializeSearchColumnsPanel : function (container) {

            // Set up the cookie used for tracking which columns are visible
            var modelName = document.getElementById('modelName').value,
                prefs = new Fisma.Search.TablePreferences(modelName),
                columns = Fisma.Search.yuiDataTable.getColumnSet().keys,
                // Title elements used for accessibility
                checkedTitle = "Column is visible. Click to hide column.",
                uncheckedTitle = "Column is hidden. Click to unhide column.";

            var index;
            var columnToggleButtonClickEvent = function (event, obj) {
                var table = Fisma.Search.yuiDataTable,
                column = table.getColumn(obj.name),
                checked = this.get("checked");

                this.set("title", checked ? checkedTitle : uncheckedTitle);

                if (checked) {
                    table.showColumn(column);
                } else {
                    table.hideColumn(column);
                }

                obj.prefs.setColumnVisibility(obj.name, checked);
            };

            for (index in columns) {
                var column = columns[index],
                    columnName = column.key;

                if (columnName === "deleteCheckbox") {
                    continue;
                }

                var checked = !column.hidden;

                var columnToggleButton = new YAHOO.widget.Button({
                    type : "checkbox",
                    label : column.label,
                    container : container,
                    checked : checked,
                    onclick : {
                        fn  : columnToggleButtonClickEvent,
                        obj : {name: columnName, prefs: prefs}
                    }
                });

                columnToggleButton.set("title", checked ? checkedTitle : uncheckedTitle);
            }

            var saveDiv = document.createElement('div');

            // Create the Save button
            var saveButton = new YAHOO.widget.Button({
                type : "button",
                label : "Save Column Preferences",
                container : saveDiv,
                onclick : {
                    fn : Fisma.Search.persistColumnPreferences
                }
            });

            if (!Fisma.Search.columnPreferencesSpinner) {
                Fisma.Search.columnPreferencesSpinner = new Fisma.Spinner(saveDiv);
            }

            container.appendChild(saveDiv);
        },

        /**
         * Toggles the display of the "more" options for search
         *
         * This includes things like help, column toggles, and advanced search
         */
        toggleMoreButton : function () {
            if (document.getElementById('moreSearchOptions').style.display === 'none') {
                document.getElementById('moreSearchOptions').style.display = 'block';
            } else {
                document.getElementById('moreSearchOptions').style.display = 'none';
            }
        },

        /**
         * Persist the column cookie into the user's profile
         */
        persistColumnPreferences : function () {

            var modelName = document.getElementById('modelName').value,
                prefs = new Fisma.Search.TablePreferences(modelName);
            Fisma.Search.columnPreferencesSpinner.show();

            prefs.persist({
                success : function (response, object) {
                    Fisma.Search.columnPreferencesSpinner.hide();

                    if (object.status === "ok") {
                        Fisma.Util.message("Your column preferences have been saved", "notice", true);
                    } else {
                        Fisma.Util.message(object.status, "warning", true);
                    }
                },

                failure : function (response) {
                    Fisma.Search.columnPreferencesSpinner.hide();

                    Fisma.Util.message('Error: ' + response.statusText, 'warning', true);
                }
            });
        },

        /**
         * Toggle the boolean value which controls whether deleted records are shown
         */
        toggleShowDeletedRecords : function () {
            Fisma.Search.showDeletedRecords = !Fisma.Search.showDeletedRecords;

            var searchForm = document.getElementById('searchForm');

            Fisma.Search.handleSearchEvent(searchForm);
        },

        /**
         * Delete the records selected in the YUI data table
         */
        deleteSelectedRecords : function () {
            var checkedRecords = [];
            var dataTable = Fisma.Search.yuiDataTable;
            var selectedRows = dataTable.getSelectedRows();

            // Create an array containing the PKs of records to delete
            var i;
            for (i = 0; i < selectedRows.length; i++) {
                var record = dataTable.getRecord(selectedRows[i]);

                if (record) {
                    checkedRecords.push(record.getData('id'));
                }
            }

            // Do some sanity checking
            if (0 === checkedRecords.length) {
                Fisma.Util.message("No records selected for deletion.", "warning", true);

                return;
            }
            var deleteRecords = [];
            deleteRecords.push(YAHOO.lang.JSON.stringify(checkedRecords));

            var warningMessage = '';
            if (1 === checkedRecords.length) {
                warningMessage = 'Delete 1 record?';
            } else {
                warningMessage = "Delete " + checkedRecords.length + " records?";
            }
            var config = {text : warningMessage,
                          func : 'Fisma.Search.doDelete',
                          args : deleteRecords  };
            var e = null;
            Fisma.Util.showConfirmDialog(e, config);
          },

         doDelete : function (checkedRecords) {
            // Derive the URL for the multi-delete action
            var dataTable = Fisma.Search.yuiDataTable;

            // Flushes cache so that datatable will reload data instead of use cache
            dataTable.getDataSource().flushCache();

            var searchUrl = Fisma.Search.yuiDataTable.getDataSource().liveData;
            var urlPieces = searchUrl.split('/');

            urlPieces[urlPieces.length-1] = 'multi-delete';

            var multiDeleteUrl = urlPieces.join('/');

            var onDataTableRefresh = {
                success : function (request, response, payload) {
                    dataTable.onDataReturnReplaceRows(request, response, payload);

                    // Update YUI's visual state to show sort on first data column
                    var sortColumnIndex = 0;
                    var sortColumn;

                    do {
                        sortColumn = dataTable.getColumn(sortColumnIndex);

                        sortColumnIndex++;
                    } while (sortColumn.formatter === Fisma.TableFormat.formatCheckbox);

                    dataTable.set("sortedBy", {key : sortColumn.key, dir : YAHOO.widget.DataTable.CLASS_ASC});
                    dataTable.get('paginator').setPage(1);
                },
                failure : dataTable.onDataReturnReplaceRows,
                scope : dataTable,
                argument : dataTable.getState()
            };

            // Create a post string containing the IDs of the records to delete and the CSRF token
            var postString = "csrf=";
            postString += document.getElementById('searchForm').csrf.value;
            postString += "&records=";
            postString += checkedRecords;

            // Submit request to delete records
            YAHOO.util.Connect.asyncRequest(
                'POST',
                multiDeleteUrl,
                {
                    success : function(o) {
                        var messages = [];

                        if (o.responseText !== undefined) {
                            var response = YAHOO.lang.JSON.parse(o.responseText);

                            Fisma.Util.message(response.msg, response.status, true);
                        }

                        // Refresh search results
                        var query = Fisma.Search.getQuery(document.getElementById('searchForm'));
                        var postData = Fisma.Search.convertQueryToPostData(query);

                        dataTable.showTableMessage("Loading...");

                        var dataSource = dataTable.getDataSource();
                        dataSource.connMethodPost = true;
                        dataSource.sendRequest(postData, onDataTableRefresh);
                    },
                    failure : function(o) {
                        var text = 'An error occurred while trying to delete the records.';
                        text += ' The error has been logged for administrator review.';
                        Fisma.Util.message(text, "warning", true);
                    }
                },
                postString);
        },

        /**
         * A method to add a YUI table to the "registry" that this object keeps track of
         *
         * @var table A YUI table
         */
        setTable : function (table) {
            this.yuiDataTable = table;

            if (this.onSetTableCallback) {
                this.onSetTableCallback();
            }
        },

        /**
         * Set a callback function to call when the YUI table gets set (see setTable)
         */
        onSetTable : function(callback) {
            this.onSetTableCallback = callback;
            if (YAHOO.lang.isObject(this.yuiDataTable)) {
                // if already set, go ahead and run the callback
                this.onSetTableCallback();
            }
        },

        /**
         * Key press listener
         *
         * @param element The element to which the key event sould be attached
         */
        onKeyPress : function (element) {
            var searchForm = YAHOO.util.Dom.get('searchForm');
            var keyHandle = new YAHOO.util.KeyListener(
                                    element,
                                    // Just listen to 'Return' and 'Enter' key
                                    {keys : YAHOO.util.KeyListener.KEY.ENTER},
                                    function () {
                                        Fisma.Search.handleSearchEvent(searchForm);
                                    });
            keyHandle.enable();
         }
    };
}());
