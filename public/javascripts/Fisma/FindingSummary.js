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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * A widget that displays finding summary data in a tree table widget.
     * 
     * @namespace Fisma
     * @class TreeTable
     * @constructor
     * @param dataUrl {String}
     * @param numberColumns {Integer} The number of columns to display in the table.
     * @param msApprovals {Object} An array of the mitigation strategy approval levels.
     * @param evApprovals {Object} An array of the evidence approval levels.
     */
    var FS = function(dataUrl, numberColumns, msApprovals, evApprovals) {
        FS.superclass.constructor.call(this, dataUrl, numberColumns);
        
        this._msApprovals = msApprovals;
        this._evApprovals = evApprovals;

        this._columnLabels = Array();
        this._columnLabels = this._columnLabels.concat(
            FS.MITIGATION_COLUMNS, 
            msApprovals, 
            FS.EVIDENCE_COLUMNS, 
            evApprovals, 
            FS.AGGREGATE_COLUMNS
        );
    };
    
    /**
     * The labels for mitigation columns (not including approvals)
     * 
     * @static
     */
    FS.MITIGATION_COLUMNS = ["NEW", "DRAFT"];

    /**
     * The labels for evidence columns (not including approvals)
     * 
     * @static
     */
    FS.EVIDENCE_COLUMNS = ["EN"];
    
    /**
     * The labels for aggregate columns
     * 
     * @static
     */
    FS.AGGREGATE_COLUMNS = ["OPEN", "CLOSED", "TOTAL"];
    
    /**
     * The options for the summary type menu.
     * 
     * The key is the string that gets passed to the server. The value is the string that gets displayed to the user.
     * 
     * @static
     */
    FS.SUMMARY_TYPES = {
        organizationHierarchy: "Organization Hierarchy",
        pointOfContact: "Point Of Contact",
        systemAggregation: "System Aggregation"
    };
    
    /**
     * The default view to display.
     * 
     * @see Fisma.FindingSummary.SUMMARY_TYPES
     * @static
     */
    FS.DEFAULT_VIEW = "organizationHierarchy";
    
    YAHOO.lang.extend(FS, Fisma.TreeTable, {
        /**
         * An array of the mitigation strategy approval levels.
         */
        _msApprovals: null,

        /**
         * An array of the mitigation strategy approval levels.
         */
        _evApprovals: null,

        /**
         * The columns labels (not including the first column)
         */
        _columnLabels: null,
        
        /**
         * The view type currently selected.
         * 
         * @see Fisma.FindingSummary.SUMMARY_TYPES
         * @param {String}
         */
        _currentViewType: null,
        
        /**
         * An event that fires when the view type changes.
         * 
         * @param {YAHOO.util.CustomEvent}
         */
        onViewTypeChange: new YAHOO.util.CustomEvent("onViewTypeChange"),

        /**
         * Define the content of the tooltips used on this page.
         */
        _tooltips: {
            viewBy: null,
            mitigationStrategy: null,
            remediation: null
        },

        /**
         * Override to insert custom parameters into the query string.
         * 
         * @return {String}
         */
        _getDataUrl: function () {
            var url = FS.superclass._getDataUrl.call(this)
                    + '/summaryType/' 
                    + (this._currentViewType || FS.DEFAULT_VIEW);
            
            return url;
        },

        /**
         * Render a customized header for this tree table.
         * 
         * @param rows {Array} An array of TR elements to render the header inside of.
         */
        _renderHeader: function (rows) {
            this._renderHeaderRow1(rows[0]);
            this._renderHeaderRow2(rows[1]);
            this._renderHeaderRow3(rows[2]);
        },

        /**
         * Render the first row of the header
         * 
         * @param row {HTMLElement} A TR element to render into.
         */
        _renderHeaderRow1: function(row) {
            var that = this; // For closure

            // Create top left cell
            var firstCell = document.createElement('th');
            row.appendChild(firstCell);
            firstCell.style.borderBottom = "none";
            firstCell.rowSpan = 3;

            var firstCellSpan = document.createElement('span');
            firstCell.appendChild(firstCellSpan);
            firstCellSpan.appendChild(document.createTextNode("View By: "))
            
            if (YAHOO.lang.isValue(this._tooltips.viewBy)) {
                firstCellSpan.className = "tooltip";

                var viewByTooltip = new YAHOO.widget.Tooltip(
                    "viewByTooltip",
                    {
                        context: firstCellSpan,
                        showdelay: 150,
                        hidedelay: 150,
                        autodismissdelay: 25000,
                        text: this._tooltips.viewBy,
                        effect: {effect:YAHOO.widget.ContainerEffect.FADE, duration: 0.25},
                        width: "50%"
                    }
                );   
            }

            var select = document.createElement("select");
            select.onchange = function (event) {that.changeViewType.call(that, event, select);};

            for (var optionKey in FS.SUMMARY_TYPES) {
                var option = new Option(FS.SUMMARY_TYPES[optionKey], optionKey)
                
                if (option.value == this._currentViewType) {
                    option.selected = true;
                }
                
                // Workaround for IE7:
                if (YAHOO.env.ua.ie == 7) {
                    select.add(option, select.options[null]);
                } else {
                    select.add(option, null);
                }
            }
            firstCell.appendChild(select);

            // Create the cell that spans the mitigation strategy columns
            var mitigationCell = document.createElement('th');
            mitigationCell.colSpan = FS.MITIGATION_COLUMNS.length + this._msApprovals.length;
            mitigationCell.style.borderBottom = "none";
            row.appendChild(mitigationCell);
            
            var mitigationCellSpan = document.createElement('span');
            mitigationCell.appendChild(mitigationCellSpan);
            mitigationCellSpan.appendChild(document.createTextNode("Mitigation Strategy"));

            if (YAHOO.lang.isValue(this._tooltips.mitigationStrategy)) {
                mitigationCellSpan.className = "tooltip";

                var msTooltip = new YAHOO.widget.Tooltip(
                    "msTooltip",
                    {
                        context: mitigationCellSpan,
                        showdelay: 150,
                        hidedelay: 150,
                        autodismissdelay: 25000,
                        text: this._tooltips.mitigationStrategy,
                        effect: {effect:YAHOO.widget.ContainerEffect.FADE, duration: 0.25},
                        width: "50%"
                    }
                );
            }

            // Create the cell that spans the evidence columns
            var remediationCell = document.createElement('th');
            remediationCell.colSpan = FS.EVIDENCE_COLUMNS.length + this._evApprovals.length;
            remediationCell.style.borderBottom = "none";
            row.appendChild(remediationCell);

            var remediationCellSpan = document.createElement('span');
            remediationCell.appendChild(remediationCellSpan);
            remediationCellSpan.appendChild(document.createTextNode("Remediation"));

            if (YAHOO.lang.isValue(this._tooltips.remediation)) {
                remediationCellSpan.className = "tooltip";

                var remediationTooltip = new YAHOO.widget.Tooltip(
                    "remediationTooltip",
                    {
                        context: remediationCellSpan,
                        showdelay: 150,
                        hidedelay: 150,
                        autodismissdelay: 25000,
                        text: this._tooltips.remediation,
                        effect: {effect:YAHOO.widget.ContainerEffect.FADE, duration: 0.25},
                        width: "50%"
                    }
                );
            }

            // Create the cell that spans the aggregate columns
            var blankCell = document.createElement('th');
            blankCell.colSpan = FS.AGGREGATE_COLUMNS.length;
            blankCell.rowSpan = 2;
            row.appendChild(blankCell);
        },
        
        /**
         * Render the second row of the header
         * 
         * @param row {HTMLElement} A TR element to render into.
         */
        _renderHeaderRow2: function(row) {
            var blankCell1 = document.createElement('th');
            blankCell1.colSpan = FS.MITIGATION_COLUMNS.length;
            blankCell1.style.borderTop = "none";
            row.appendChild(blankCell1);

            var msApprovalCell = document.createElement('th');
            msApprovalCell.appendChild(document.createTextNode("Approval"));
            msApprovalCell.colSpan = this._msApprovals.length;
            row.appendChild(msApprovalCell);

            var blankCell2 = document.createElement('th');
            blankCell2.style.borderTop = "none";
            blankCell1.style.colSpan = FS.EVIDENCE_COLUMNS.length;
            row.appendChild(blankCell2);

            var evApprovalCell = document.createElement('th');
            evApprovalCell.appendChild(document.createTextNode("Approval"));
            evApprovalCell.colSpan = this._evApprovals.length;
            row.appendChild(evApprovalCell);
        },

        /**
         * Render the second row of the header
         * 
         * @param row {HTMLElement} A TR element to render into.
         */
        _renderHeaderRow3: function(row) {
            var label;
            var cell;
            var link;

            for (var index in this._columnLabels) {
                cell = document.createElement('th');
                cell.style.borderBottom = "none";
                
                label = this._columnLabels[index];

                link = document.createElement('a');
                cell.appendChild(link);
                if ("OPEN" == label) {
                    link.href = "/finding/remediation/list?q=/denormalizedStatus/textNotExactMatch/CLOSED";
                } else if ("TOTAL" == label) {
                    // Pass a blank query, otherwise the saved settings of previous search will be used
                    link.href = "/finding/remediation/list?q=/denormalizedStatus/textContains/";
                } else {
                    link.href = "/finding/remediation/list?q=/denormalizedStatus/textExactMatch/" 
                              + encodeURIComponent(label);
                }
                link.appendChild(document.createTextNode(label));

                row.appendChild(cell);
            }
        },

        /**
         * Aggregate data from leaf nodes up to root nodes.
         * 
         * @param node {Array}
         */
        _preprocessTreeData: function(node) {
            var nodeData = node.nodeData;

            // Start aggregrating at the current node
            nodeData.aggregate = {
                total: nodeData.total || 0,
                closed: nodeData.closed || 0
            };

            for (var i in this._columnLabels) {
                var column = $P.urlencode(this._columnLabels[i]);
                
                if (column === 'CLOSED' || column === 'TOTAL') {
                    // These columns don't distinguish ontime from overdue (they're handled above)
                    continue;
                }
                
                nodeData.aggregate["ontime_" + column] = nodeData["ontime_" + column] || 0;
                nodeData.aggregate["overdue_" + column] = nodeData["overdue_" + column] || 0;
            }

            // Include children's aggregate values in this node's aggregate value
            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                for (var i in node.children) {
                    var child = node.children[i];
                    var childData = child.nodeData;
                    
                    this._preprocessTreeData(child);
                    
                    nodeData.aggregate.total += childData.aggregate.total;
                    nodeData.aggregate.closed += childData.aggregate.closed;
                    
                    for (var i in this._columnLabels) {
                        var column = $P.urlencode(this._columnLabels[i]);

                        if (column === 'CLOSED' || column === 'TOTAL') {
                            // These columns don't distinguish ontime from overdue (they're handled above)
                            continue;
                        }

                        nodeData.aggregate["ontime_" + column] += childData.aggregate["ontime_" + column];
                        nodeData.aggregate["overdue_" + column] += childData.aggregate["overdue_" + column];
                    }
                }
            }
        },

        /**
         * Render cells in this finding summary table.
         * 
         * @param container {HTMLElement} The parent container to render cell content inside of.
         * @param nodeData {Object} Data related to this node.
         * @param columnNumber {Integer} The [zero-indexed] column which needs to be rendered.
         * @param nodeState {TreeTable.NodeState}
         */
        _renderCell: function (container, nodeData, columnNumber, nodeState) {
            if (columnNumber == 0) {
                container.style.minWidth = "15em";
                container.style.height = "2.5em";
                container.style.overflow = "hidden";

                // The node icon is a graphical representation of what type of node this is: agency, bureau, etc.          
                var nodeIcon = document.createElement('img');
                nodeIcon.className = "icon";
                nodeIcon.src = "/images/" + nodeData.icon + ".png";
                container.appendChild(nodeIcon);

                // Add text to the cell
                container.appendChild(document.createTextNode(nodeData.label));
                container.appendChild(document.createElement('br'));
                container.appendChild(document.createTextNode(nodeData.typeLabel));                    
            } else {
                var completedCount;

                container.style.textAlign = "center";
                container.style.padding = "0px";

                // Use php.js urlencode() to mimic the server-side urlencode()
                var status = $P.urlencode(this._columnLabels[columnNumber - 1]);

                var link = document.createElement("a");
                var ontimeUrl = this._makeUrl(true, status, nodeState, nodeData.rowLabel, nodeData.searchKey);
                var overdueUrl = this._makeUrl(false, status, nodeState, nodeData.rowLabel, nodeData.searchKey);

                // If we are in a collapsed tree node, then switch single-record node data to aggregate node data
                if (nodeState == Fisma.TreeTable.NodeState.COLLAPSED) {
                    nodeData = nodeData.aggregate;
                }

                if (status == "CLOSED") {
                    link.href = ontimeUrl;
                    container.appendChild(link);
                    link.appendChild(document.createTextNode(nodeData.closed || 0));
                } else if (status == "TOTAL") {
                    link.href = ontimeUrl;
                    container.appendChild(link);
                    link.appendChild(document.createTextNode(nodeData.total || 0));
                } else {
                    // Set the rendering style based on the existence or absence of ontime and overdue findings
                    var ontime = nodeData["ontime_" + status] || 0;
                    var overdue = nodeData["overdue_" + status] || 0;

                    if (ontime == 0 && overdue == 0) {
                        container.className = "ontime";
                        container.appendChild(document.createTextNode('-'));                    
                    } else if (ontime > 0 && overdue == 0) {
                        container.className = "ontime";
                        container.appendChild(link);

                        link.href = ontimeUrl;
                        link.appendChild(document.createTextNode(ontime || 0));
                    } else if (ontime == 0 && overdue > 0) {
                        container.className = "overdue";
                        container.appendChild(link);

                        link.href = overdueUrl;
                        link.appendChild(document.createTextNode(overdue || 0));
                    } else {
                        // This is executed when ontime > 0 && overdue > 0
                        this._renderSplitCell(container, ontime, ontimeUrl, overdue, overdueUrl);
                    }
                }
            }
        },
        
        /**
         * Render a cell that shows both ontime and overdue numbers.
         * 
         * @param container {HTMLElement}
         * @param ontime {Integer} The number to display in the ontime part of the split
         * @param ontimeUrl {String}
         * @param overdue {Integer} The number to display in the overdue part of the split
         * @param overdueUrl {String}
         */
        _renderSplitCell: function (container, ontime, ontimeUrl, overdue, overdueUrl) {
            // No good CSS way to do this...
            var innerTable = document.createElement("table");
            innerTable.style.width = "100%";
            innerTable.style.height = "100%";
            innerTable.style.marginBottom = "0px";
            container.appendChild(innerTable);

            var ontimeRow = innerTable.insertRow(innerTable.rows.length);
            
            var ontimeCell = document.createElement('td');
            ontimeRow.appendChild(ontimeCell);
            ontimeCell.style.borderWidth = "0px";
            ontimeCell.style.borderBottomWidth = "1px";
            ontimeCell.className = "ontime";
            
            var ontimeLink = document.createElement("a");
            ontimeCell.appendChild(ontimeLink);
            ontimeLink.appendChild(document.createTextNode(ontime));
            ontimeLink.href = ontimeUrl;

            var overdueRow = innerTable.insertRow(innerTable.rows.length);

            var overdueCell = document.createElement('td');
            overdueRow.appendChild(overdueCell);
            overdueCell.style.border = "none";
            overdueCell.className = "overdue";

            var overdueLink = document.createElement("a");
            overdueCell.appendChild(overdueLink);
            overdueLink.appendChild(document.createTextNode(overdue));
            overdueLink.href = overdueUrl;
        },
        
        /**
         * The event handler for the view type menu.
         * 
         * @param event {Event}
         * @param select {HTMLElement} The select element that changed
         */
        changeViewType: function (event, select) {
            this.setViewType(select.options[select.selectedIndex].value);
            this.onViewTypeChange.fire(this._currentViewType);
            this.reloadData();
        },
        
        /**
         * Set the view type for this object. 
         * 
         * (Notice that this doesn't take effect until the table is rendered).
         * 
         * @param viewType {FS.SUMMARY_TYPES}
         */
        setViewType: function (viewType) {
            if (FS.SUMMARY_TYPES.hasOwnProperty(viewType)) {
                this._currentViewType = viewType;
            } else {
                throw "Unexpected view type: (" + viewType + ")";
            }
        },
        
        /**
         * This summary has a 3-level header.
         * 
         * @return {Integer}
         */
        _getNumberHeaderRows: function () {
            return 3;
        },
        
        /**
         * Make a URL for a cell based on ontime/overdue, finding status, node state, and organization.
         * 
         * This function also incorporates filter state to build URLs.
         * 
         * @param ontime {Boolean} True if ontime, false if overdue.
         * @param status {String}
         * @param nodeState {Fisma.TreeTable.NodeState} 
         * @param rowLabel {String}
         * @param searchKey {String} The search parameter to search for the rowLabel in.
         * @return {String}
         */
        _makeUrl: function (ontime, status, nodeState, rowLabel, searchKey) {
            var url = "/finding/remediation/list?q=";

            // The status contains plus symbols ('+') which is encoded before, so it should be decoded here.
            status = $P.urldecode(status);

            // Add status criterion
            if (status != "TOTAL" && status != "OPEN") {
                url += "/denormalizedStatus/textExactMatch/" + encodeURIComponent(status);
            } else if (status == "OPEN"){
                url += "/denormalizedStatus/textNotExactMatch/CLOSED";
            }
            
            // Add organization/POC criterion
            if (nodeState == Fisma.TreeTable.NodeState.COLLAPSED) {
                if (this._currentViewType == "systemAggregation") {
                    url += '/' + searchKey + '/systemAggregationSubtree/' + encodeURIComponent(rowLabel);
                } else {
                    url += '/' + searchKey + '/organizationSubtree/' + encodeURIComponent(rowLabel);
                }
            } else {
                url += '/' + searchKey + '/textExactMatch/' + encodeURIComponent(rowLabel);
            }

            // Add ontime criteria (if applicable)
            if (status != "TOTAL" && status != "CLOSED") {
                var today = new Date();
                var todayString = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();

                if (ontime) {
                    url += "/nextDueDate/dateAfter/" + todayString;
                } else {
                    url += "/nextDueDate/dateBefore/" + todayString;                    
                }                
            }
            
            // Add filter criteria
            msSelect = this._filters.mitigationType.select;
            msValue = msSelect.options[msSelect.selectedIndex].value;
            if (msValue != "none") {
                url += "/type/enumIs/" + encodeURIComponent(msValue);
            }

            sourceSelect = this._filters.findingSource.select;
            sourceValue = sourceSelect.options[sourceSelect.selectedIndex].value;
            sourceLabel = sourceSelect.options[sourceSelect.selectedIndex].text;
            if (sourceValue != "none") {
                url += "/source/textExactMatch/" + encodeURIComponent(sourceLabel);
            }

            return url;
        },
        
        /**
         * Set the value of a tooltip. Tooltips must be set rendering the table.
         */
        setTooltip: function (name, html) {
            this._tooltips[name] = html;
        }
    });

    Fisma.FindingSummary = FS;
})();
