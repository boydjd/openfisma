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

Fisma.Search = function() {
    return {
        
        /**
         * A registry (..kind of..) for YUI data tables
         * 
         * Keys are names of YUI data tables and values are references to those actual tables
         */
        yuiDataTables : {},
        
        /**
         * True if the test configuration process is currently running
         */
        testConfigurationActive : false,
        
        /**
         * The base URL to the controller action for searching
         */
        baseUrl : '',
        
        /**
         * A URL which builds on the base URL to pass arguments to the search controller action
         * 
         * This is used by YUI data table to build its own queries with sorting added
         */
        searchActionUrl : '',
        
        /**
         * Field definitions for the advanced search interface
         */
        fields : null,
        
        /**
         * Advanced search panel
         */
        advancedSearchPanel : null,

        /**
         * Test the current system configuration
         */
        
        testConfiguration : function () {
            
            if (Fisma.Search.testConfigurationActive) {
                return;
            }

            Fisma.Search.testConfigurationActive = true;

            var testConfigurationButton = document.getElementById('testConfiguration');
            testConfigurationButton.className = "yui-button yui-push-button yui-button-disabled";

            var spinner = new Fisma.Spinner(testConfigurationButton.parentNode);
            spinner.show();

            var form = document.getElementById('search_config');
            YAHOO.util.Connect.setForm(form);

            YAHOO.util.Connect.asyncRequest(
                'POST', 
                '/config/test-search/format/json', 
                {
                    success : function (o) {
                        var response = YAHOO.lang.JSON.parse(o.responseText).response;
                        
                        if (response.success) {
                            message("Search configuration is valid", "notice");
                        } else {
                            message(response.message, "warning");
                        }

                        testConfigurationButton.className = "yui-button yui-push-button";
                        Fisma.Search.testConfigurationActive = false;
                        spinner.hide();
                    },

                    failure : function (o) {
                        message('Error: ' + o.statusText, 'warning');

                        spinner.hide();
                    }
                }
            );            
        },
        
        /**
         * Handles a search event. This works in tandem with the search.form and Fisma_Zend_Controller_Action_Object.
         * 
         * Two types of query are possible: simple and advanced. A hidden field is used to determine which of the 
         * two to use while handling this event.
         * 
         * @param form Reference to the search form
         */
        handleSearchEvent : function (form) {
            try {                
                var dataTable = Fisma.Search.yuiDataTables['searchResultsTable'];

                var onDataTableRefresh = {
                    success : dataTable.onDataReturnReplaceRows,
                    failure : dataTable.onDataReturnReplaceRows,
                    scope : dataTable,
                    argument : dataTable.getState()
                }
                
                // Construct a query URL based on whether this is a simple or advanced search
                var searchType = document.getElementById('searchType').value;
                var postData;

                if ('simple' == searchType) {
                    postData = "queryType=simple&keywords=" + form.keywords.value;
                } else if ('advanced' == searchType) {
                    var queryData = this.advancedSearchPanel.getQuery();

                    postData = "queryType=advanced&query=" + YAHOO.lang.JSON.stringify(queryData);
                } else {
                    throw "Invalid value for search type: " + searchType;
                }

                dataTable.showTableMessage(YAHOO.widget.DataTable.MSG_LOADING);
                dataTable.getDataSource().connMethodPost = true;
                dataTable.getDataSource().sendRequest(postData, onDataTableRefresh);
            } catch (error) {
                ; // Nothing we can really do here, but catching the error prevents a page refresh b/c we return false
            }

            // Return false to prevent the form from actually being submitted
            return false;
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
            var dataTable = Fisma.Search.yuiDataTables['searchResultsTable'];
            
            var tbody = dataTable.getTbodyEl();
            
            var cells = tbody.getElementsByTagName('td');

            var delimiter = '***';

            Fisma.Highlighter.highlightDelimitedText(cells, delimiter);
        },
        
        /**
         * Sets the base URL for search queries
         * 
         * @param url The base URL (parameters will be appended onto the end of this)
         */
        setBaseUrl : function (url) {
            this.baseUrl = url;
            
            // The search action should always be based on the base URL
            this.searchActionUrl = url;
        },
        
        /**
         * Get the search action URL for search queries
         * 
         * @return string
         */
        getSearchActionUrl : function () {
            return this.searchActionUrl;
        },
        
        /**
         * Show or hide the advanced search options UI
         */
        toggleAdvancedSearchPanel : function () {
            if (document.getElementById('advancedSearch').style.display == 'none') {

                document.getElementById('advancedSearch').style.display = 'block';
                document.getElementById('keywords').style.visibility = 'hidden';
                document.getElementById('searchType').value = 'advanced';

            } else {

                document.getElementById('advancedSearch').style.display = 'none';
                document.getElementById('keywords').style.visibility = 'visible';
                document.getElementById('searchType').value = 'simple';

            }
        },
        
        /**
         * Show or hide the search columns UI
         */
        toggleSearchColumnsPanel : function () {
            if (document.getElementById('searchColumns').style.display == 'none') {
                document.getElementById('searchColumns').style.display = 'block';
            } else {
                document.getElementById('searchColumns').style.display = 'none';
            }            
        }
    }
}();
