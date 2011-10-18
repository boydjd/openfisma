/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * @fileoverview The Finding Summary displays a tree of information systems and summary counts with expand/collapse
 * controls to navigate the tree structure of the information systems. Summary information is automatically rolled up
 * or drilled down as the user navigates the tree.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * A widget that combines a table layout with the nesting and expand/collapse behavior of a tree widget.
     * 
     * Data loaded into this widget should be a nested array. Each level of nesting represents a level of nesting
     * in the tree. Each element in the array must have at least the following two elements:
     * 
     *     1. nodeData - a hash of key/values that will be passed to your custom rendering functions.
     *     2. children - an array of nodes that are nested underneath the current node.
     * 
     * @namespace Fisma
     * @class TreeTable
     * @constructor
     * @param dataUrl {String} The URL to load table data from.
     * @param numberColumns {Integer} The number of columns to display in the table.
     */
    var TT = function(dataUrl, numberColumns) {
        this._dataUrl = dataUrl;
        this._numberColumns = numberColumns;

        this._buttons = [
            {label: "Expand All", fn: this.expandAllNodes, image: "/images/expand.png"},
            {label: "Collapse All", fn: this.collapseAllNodes, image: "/images/collapse.png"},
            {label: "Export PDF", fn: function () {alert("NOT IMPLEMENTED");}, image: "/images/pdf.gif"}
        ];

        TT.superclass.constructor.call(this);
    };

    /**
     * Node state indicates if a node is collapsed or expanded.
     * 
     * Used an enumeration.
     * 
     * @static
     */
    TT.NodeState = {
        EXPANDED: "EXPANDED",
        COLLAPSED: "COLLAPSED",
        LEAF_NODE: "LEAF_NODE"
    };

    YAHOO.lang.extend(TT, Object, {
        /**
         * A list of buttons displayed in the toolbar above the table.
         */
        _buttons: null,

        /**
         * The URL to load table data from.
         */
        _dataUrl: null,
        
        /**
         * The number of tree levels to display during the initial render
         * 
         * E.g. 1 means display only the root, 2 means display the root and root's immediate children, etc.
         */
        _defaultDisplayLevel: 2,
        
        /**
         * A TR element that contains the "loading" message that is displayed while requesting data.
         */
        _loadingBar: null,
        
        /**
         * The number of columns displayed on this table.
         */
        _numberColumns: null,
        
        /**
         * A reference to the table element.
         */
        _table: null,

        /**
         * Object representing the hierarchical data for this table. See class doc for description.
         */
        _treeData: null,

        /**
         * Renders the widget.
         * 
         * This doesn't really do much because the widget relies on an XHR to get data. This method just creates the 
         * static parts of the table and then requests data.
         * 
         * @param container {HTMLElement} The parent container to render this widget into.
         */
        render: function (container) {
            // Render the buttons
            var buttonContainer = document.createElement('div');
            buttonContainer.className = 'searchBox';
            container.appendChild(buttonContainer);
            this._renderButtons(buttonContainer);

            // Create the table
            var table = document.createElement('table');
            table.className = "treeTable";
            container.appendChild(table);
            this._table = table;
            
            // Render the header
            var headerRow = document.createElement('tr');
            this._renderHeader(headerRow);
            table.appendChild(headerRow);

            // Render the loading bar
            var loadingBar = document.createElement('tr');
            this._renderLoadingBar(loadingBar);
            table.appendChild(loadingBar);
            this._loadingBar = loadingBar;
            
            // Request data
            this._requestData();
        },

        /**
         * Expands a collapsed node or collapses an expanded node.
         * 
         * @param event {YAHOO.util.Event}
         * @param node {Object}
         */        
        toggleNode: function (event, node) {
            switch (node.state) {
                case TT.NodeState.EXPANDED:
                    this._setNodeState(node, TT.NodeState.COLLAPSED);
                    break;
                case TT.NodeState.COLLAPSED:
                    this._setNodeState(node, TT.NodeState.EXPANDED);
                    break;
                case TT.NodeState.LEAF_NODE:
                    throw "Cannot toggle a leaf node's state";
                    break;
                default:
                    throw "Unexpected node state (" + node.state + ")";
            }
        },
        
        /**
         * Collapse a node an all nodes underneath it.
         * 
         * @param node {Array}
         */
        collapseSubtree: function(node) {
            if (node.state == TT.NodeState.EXPANDED) {
                this._setNodeState(node, TT.NodeState.COLLAPSED);                
            }
            
            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                for (var i in node.children) {
                    this.collapseSubtree(node.children[i]);
                }
            }
        }, 
        
        /**
         * Collapse all nodes.
         */
        collapseAllNodes: function() {
            for (var i in this._treeData) {
                var rootNode = this._treeData[i];
                
                this.collapseSubtree(rootNode);
                this._hideChildren(rootNode);
            }
        },
        
        /**
         * Expand a node and all nodes underneath it.
         * 
         * @param node {Array}
         */
        expandSubtree: function(node) {
            if (node.state == TT.NodeState.COLLAPSED) {
                this._setNodeState(node, TT.NodeState.EXPANDED);                
            }
            
            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                for (var i in node.children) {
                    this.expandSubtree(node.children[i]);
                }
            }
        },
        
        /**
         * Expand all nodes.
         */
        expandAllNodes: function() {
            for (var i in this._treeData) {
                var rootNode = this._treeData[i];
                
                this.expandSubtree(rootNode);
            }
        },

        /**
         * Reload the data in the table.
         */
        reloadTable: function () {
            alert("THIS IS OLD CODE AND DOESNT WORK");
            return;
            var loadingRow = document.getElementById('loadingRow');
            loadingRow.style.display = ''; // reset to table-row, work around an IE6 bug
            var orgTable = document.getElementById('summaryTable');

            // Remove existing summary data
            while (orgTable.rows.length > 4) {
                orgTable.deleteRow(orgTable.rows.length - 1);
            }

            YAHOO.util.Connect.asyncRequest('GET', 
                                            url, 
                                            {
                                                success: function(o) {
                                                    var summaryData = YAHOO.lang.JSON.parse(o.responseText);
                                                    this._hideLoadingBar();
                                                    summary.render('summaryTable', summaryData, true);
                                                },
                                                failure: function(o) {
                                                    var alertMessage = 'Unable to load finding summary: ' 
                                                    + o.statusText;
                                                    Fisma.Util.showAlertDialog(alertMessage);
                                                    this._hideLoadingBar();
                                                }
                                            }, 
                                            null);
        },

        /**
         * Render the buttons associated with this tree table
         * 
         * @param container {HTMLElement}
         */
        _renderButtons: function(container) {
            var button, buttonDefinition;

            for (var i in this._buttons) {
                buttonDefinition = this._buttons[i];
                
                button = new YAHOO.widget.Button({
                    type: "button",
                    container: container,
                    label: buttonDefinition.label,
                    onclick: {
                        fn: buttonDefinition.fn,
                        scope: this
                    }
                });

                button._button.style.background = 'url(' + buttonDefinition.image + ') 10% 50% no-repeat';
                button._button.style.paddingLeft = '3em';
            }
        },

        /**
         * Render the table header.
         * 
         * This method is intended to be overridden by subclasses to customize the appearance of the table.
         * 
         * @param containerRow {HTMLElement} The parent container (a TR element) to render the header inside of.
         */
        _renderHeader: function (containerRow) {
            var cell = document.createElement('th');
            cell.appendChild(document.createTextNode('Override the _renderHeader method!'));
            containerRow.appendChild(cell);
        },

        /**
         * Render cells in this tree table.
         * 
         * This method is intended to be overridden by subclasses to customize the appearance of the table.
         * 
         * @param container {HTMLElement} The parent container to render cell content inside of.
         * @param nodeData {Object} Data related to this node.
         * @param columnNumber {Integer} The [zero-indexed] column which needs to be rendered.
         * @param nodeState {TreeTable.NodeState}
         */
        _renderCell: function (container, nodeData, columnNumber, nodeState) {
            container.appendChild(document.createTextNode('Override the _renderCell method!'));
        },

        /**
         * Render the loading bar.
         * 
         * @param containerRow {HTMLElement} The parent container (a TR element) to render the loading bar inside of.
         */
        _renderLoadingBar: function (containerRow) {
            var loadingCell = document.createElement('th');
            loadingCell.colSpan = 10;
            containerRow.appendChild(loadingCell);
            
            var message = document.createElement('p');
            message.appendChild(document.createTextNode('Loading…'));
            loadingCell.appendChild(message);
            
            var spinnerImage = document.createElement('img');
            spinnerImage.src = '/images/loading_bar.gif';
            loadingCell.appendChild(spinnerImage);
        },

        /**
         * Request data for the table.
         */
        _requestData: function () {
            YAHOO.util.Connect.asyncRequest(
                'GET', 
                this._dataUrl, 
                {
                    success: this._handleDataRefresh,
                    failure: this._handleDataRefreshFailed,
                    scope: this
                }, 
                null
            );            
        },
        
        /**
         * Handle a data refresh event.
         * 
         * @param response {Object} YUI Response object
         */
        _handleDataRefresh: function (response) {
            this._treeData = YAHOO.lang.JSON.parse(response.responseText);

            for (var nodeIndex = 0; nodeIndex < this._treeData.length; nodeIndex++) {
                var rootNode = this._treeData[nodeIndex];

                this._preprocessTreeData(rootNode);
                this._renderNode(rootNode, 0);
                this._setInitialTreeState(rootNode, 0);
            }

            this._hideLoadingBar();
        },
        
        /**
         * Handle a failed data refresh event.
         * 
         * @param response {Object} YUI Response object
         */
        _handleDataRefreshFailed: function (response) {
            this._hideLoadingBar();
            Fisma.Util.showAlertDialog('Unable to load finding summary: ' + response.statusText);
        },

        /**
         * A stub function. Subclasses can implement this in order to do any pre-procesing necessary (such as 
         * aggregation).
         * 
         * Notice the rendering abstractions (particularly _renderCell) only provide node data, not the full 
         * node object. Therefore, any processing which requires knowing the tree structure cannot be done
         * at render time and must be done in this step.
         * 
         * @param node {Array}
         */
        _preprocessTreeData: function(node) {
            // Customize by overriding in a subclass
        },

        /**
         * Recursively renders the data rows starting from a root node.
         * 
         * Rows with children are actually rendered TWICE, once in their expanded state and once in their collapsed 
         * state. This makes it easy to render changes to the tree by simply hiding and showing certain rows -- nothing
         * actually needs to be rendered again.
         * 
         * @param node {Object} The node object to render.
         * @param level {Integer} The level of nesting, starting with 0.
         */         
        _renderNode: function (node, level) {
            node.row = document.createElement('tr');
            this._table.appendChild(node.row);

            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                // The collapsed and expanded views are only rendered if the node has children                
                node.expandedRow = document.createElement('tr');
                this._renderNodeState(node.expandedRow, node, level, TT.NodeState.EXPANDED);
                this._table.appendChild(node.expandedRow);

                node.collapsedRow = document.createElement('tr');
                this._renderNodeState(node.collapsedRow, node, level, TT.NodeState.COLLAPSED);
                this._table.appendChild(node.collapsedRow);
            } else {
                // If a node doesn't have children, it's rendered in "leaf node" state
                node.leafNodeRow = document.createElement('tr');
                this._renderNodeState(node.leafNodeRow, node, level, TT.NodeState.LEAF_NODE);
                this._table.appendChild(node.leafNodeRow);
            }

            // If this node has children, then recursively render the children
            for (var childIndex = 0; childIndex < node.children.length; childIndex++) {
                var childNode = node.children[childIndex];
                this._renderNode(childNode, level + 1);
            }
        },

        /**
         * Render a node with the specified NodeState.
         * 
         * @param container {HTMLElement} The tr that contains this node state.
         * @param node {Object} The node object to render.
         * @param level {Integer} The level of nesting, starting with 0.
         * @param nodeState {TreeTable.NodeState} The object state to render.
         */
        _renderNodeState: function(container, node, level, nodeState) {
            /*
             *  Render the first cell on this row
             */
            var cell = document.createElement('td');
            container.appendChild(cell);

            var firstCellDiv = document.createElement("div");
            firstCellDiv.className = "treeTable" + level;
            cell.appendChild(firstCellDiv);

            // Add a hover effect for clickable nodes
            if (nodeState != TT.NodeState.LEAF_NODE) {
                firstCellDiv.className += " link";
            }

            var toggleControlImage = document.createElement('img');
            toggleControlImage.className = 'control';

            var toggleControl = document.createElement('span');
            toggleControl.appendChild(toggleControlImage);

            switch (nodeState) {
                case TT.NodeState.EXPANDED:
                    YAHOO.util.Event.addListener(firstCellDiv, "click", this.toggleNode, node, this);
                    toggleControlImage.src = "/images/minus.png";
                    break;
                case TT.NodeState.COLLAPSED:
                    YAHOO.util.Event.addListener(firstCellDiv, "click", this.toggleNode, node, this);
                    toggleControlImage.src = "/images/plus.png";
                    break;
                case TT.NodeState.LEAF_NODE:
                    // This state uses an invisible PNG and has no click event.
                    toggleControlImage.src = "/images/leaf_node.png";
                    break;
                default:
                    throw "Unexpected nodeState (" + nodeState + ")";
            }

            firstCellDiv.appendChild(toggleControl);                
            this._renderCell(firstCellDiv, node.nodeData, 0, nodeState);

            /*
             *  Render the remaining cells on the this row
             */
            for (var i = 1; i < this._numberColumns; i++) {
                cell = document.createElement('td');
                this._renderCell(cell, node.nodeData, i, nodeState);
                container.appendChild(cell);
            }
        },

        /**
         * Initially the table is rendered with all nodes (and node states) displayed. This sets the tree to a sensible
         * default.
         * 
         * @param node {Object} The node object to render.
         * @param level {Integer} The level of nesting, starting with 0.
         */
        _setInitialTreeState: function(node, level) {        
            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                // Set the initial states on children first (so we work from the bottom of the tree upwards)
                for (var i in node.children) {
                    var child = node.children[i];

                    this._setInitialTreeState(child, level + 1);
                }

                // Set the node itself to a default expanded/collapsed state based on its depth
                if (level < this._defaultDisplayLevel - 1) {
                    this._setNodeState(node, TT.NodeState.EXPANDED);                
                } else {
                    this._setNodeState(node, TT.NodeState.COLLAPSED);
                }
            } else {
                // If the node does not have children, then set it's initial state to "leaf node"
                this._setNodeState(node, TT.NodeState.LEAF_NODE);
            }
        },

        /**
         * Set a node to the specified state
         * 
         * @param node {Array}
         * @param newState {TreeTable.NodeState}
         */
        _setNodeState: function(node, newState) {
            if (node.state != TT.NodeState.LEAF_NODE) {
                switch (newState) {
                    case TT.NodeState.EXPANDED:
                        node.collapsedRow.style.display = 'none';
                        node.expandedRow.style.display = ''; // Set to '' instead of 'table-row' due to a bug in IE
                        this._showChildren(node);
                        break;
                    case TT.NodeState.COLLAPSED:
                        node.collapsedRow.style.display = '';
                        node.expandedRow.style.display = 'none';
                        this._hideChildren(node);
                        break;
                    case TT.NodeState.LEAF_NODE:
                        // Leaf node state can be set once but can't be changed after setting.
                        break;
                    default:
                        throw "Unexpected node state (" + newState + ")";
                }

                node.state = newState;
            } else {
                throw "Cannot change state on a leaf node";
            }
        },

        /**
         * Hide a row for a particular node
         * 
         * This is a simple operation, just look for any rows that have been rendered for this node 
         * and hide everything you find.
         * 
         * @param node {Array}
         */
        _hideRow: function(node) {
            if (YAHOO.lang.isValue(node.collapsedRow)) {
                node.collapsedRow.style.display = 'none';
            }

            if (YAHOO.lang.isValue(node.expandedRow)) {
                node.expandedRow.style.display = 'none';
            }

            if (YAHOO.lang.isValue(node.leafNodeRow)) {
                node.leafNodeRow.style.display = 'none';
            }
        },

        /**
         * Show (i.e. unhide) a row for a particular node.
         * 
         * Display the rendering for this row that matches it's state.
         * 
         * @param node {Array}
         */
        _showRow: function(node) {
            switch (node.state) {
                case TT.NodeState.EXPANDED:
                    node.expandedRow.style.display = ''; // Set to '' instead of 'table-row' due to a bug in IE
                    break;
                case TT.NodeState.COLLAPSED:
                    node.collapsedRow.style.display = ''; // Set to '' instead of 'table-row' due to a bug in IE
                    break;
                case TT.NodeState.LEAF_NODE:
                    node.leafNodeRow.style.display = '';
                    break;
                default:
                    throw "Unexpected node state (" + node.state + ")";
            }
        },

        /**
         * Recursively hide all children of a given node.
         * 
         * @param node {Array}
         */
        _hideChildren: function(node) {
            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                for (var i in node.children) {
                    var child = node.children[i];
                    
                    this._hideRow(child);
                    this._hideChildren(child);
                }
            }
        },
        
        /**
         * Show children of a given node.
         * 
         * This shows all immediate children, plus recursively shows the children of any subtrees that are in the
         * expanded state.
         * 
         * @param node {Array}
         */
        _showChildren: function(node) {
            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                for (var i in node.children) {
                    var child = node.children[i];
                    
                    this._showRow(child);
                    
                    if (child.state === TT.NodeState.EXPANDED) {
                        this._showChildren(child);                        
                    }
                }
            }
        },

        /**
         * Hide the "loading" message that is displayed while loading the table data.
         */
        _hideLoadingBar: function () {
            this._loadingBar.style.display = 'none';
        }
    });

    Fisma.TreeTable = TT;
})();
