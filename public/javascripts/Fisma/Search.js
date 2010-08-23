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
 * @fileoverview Functions related to the search engine and search UI
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @package   Fisma
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
         * Executes a simple search. This works in tandem with the search-simple.form and 
         * Fisma_Zend_Controller_Action_Object.
         * 
         * @param form Reference to the search form
         * @param baseUrl The base URL for searching. Query arguments will be appended to this.
         */
        handleSimpleSearchClickEvent : function (form, baseUrl) {
            try {                
                var dataTable = Fisma.Search.yuiDataTables['searchResultsTable'];

                var onDataTableRefresh = {
                    success : dataTable.onDataReturnReplaceRows,
                    failure : dataTable.onDataReturnReplaceRows,
                    scope : dataTable,
                    argument : dataTable.getState()
                }

                searchUrl = baseUrl + '/keywords/' + form.keywords.value;
                
                dataTable.showTableMessage(YAHOO.widget.DataTable.MSG_LOADING);
                dataTable.getDataSource().sendRequest(searchUrl, onDataTableRefresh);
            } catch (error) {
                ; // Nothing we can really do here, but catching the error prevents a page refresh b/c we return false
            }

            // Return false to prevent the form from actually being submitted
            return false;
        },
    
        /**
         * Highlight marked words in the search results table
         * 
         * Due to a quirk in Solr, highlights are indicated by three asterisks ***. This method just has to go 
         * through and find the asterisks, strip them out, and replace the content between them with highlighted text.
         * 
         * @param The YUI data table to perform highlighting on
         */
        highlightSearchResultsTable :  function (dataTable) {
            var dataTable = Fisma.Search.yuiDataTables['searchResultsTable'];
            
            var tbody = dataTable.getTbodyEl();
            
            var cells = tbody.getElementsByTagName('td');

            var regex = /^(.*?)(\*\*\*)(.*?)(\*\*\*)(.*)$/;

            for (var i in cells) {
                var cell = cells[i];

                var parentNode = cell.firstChild;
                var textNode = parentNode.firstChild;
                var cellText = textNode.nodeValue;

                var highlightMatches;

                // Stores all of the matched text snippets
                var matches = [];
                
                do {

                    highlightMatches = cellText.match(regex);

                    // Match 5 subexpressions plus the overall match -> 6 total matches
                    if (highlightMatches && highlightMatches.length == 6) {

                        var preMatch = highlightMatches[1];
                        var highlightMatch = highlightMatches[3];
                        var postMatch = highlightMatches[5];
                        
                        matches.push(preMatch);
                        matches.push(highlightMatch);
                        
                        cellText = postMatch;
                    } else {
                        
                        // Any remaining text gets pushed onto the matches list
                        matches.push(cellText);
                        
                        // If the user text happens to contain *** that will throw the highlighter off, potentially
                        // creating a denial of service. To prevent that, we short circuit the loop right here.
                        highlightMatches = null;
                    }
                    
                } while (highlightMatches);
                
                // If matches contains more than 1 item, then it must contain an odd number of items. Even numbered
                // indices are plain text and odd numbered indices are highlighted text. Manipulate the DOM to reflect
                // this.
                if ((matches.length > 1) && (matches.length % 2 == 1)) {
                    
                    // Remove current text
                    parentNode.removeChild(textNode);
                    
                    // Iterate over matches and create new text nodes (for plain text) and new spans (for highlighted
                    // text)
                    for (var j in matches) {
                        var match = matches[j];

                        var newTextNode = document.createTextNode(match);

                        if (j % 2 == 0) {
                            // This is a plaintext node
                            parentNode.appendChild(newTextNode);
                        } else {
                            // This is a highlighted node
                            var newSpan = document.createElement('span');
                            newSpan.className = 'highlight';
                            newSpan.appendChild(newTextNode);
                            
                            parentNode.appendChild(newSpan);
                        }
                    }
                }
            }
        }
    }
}();
