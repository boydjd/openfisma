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
     * @param steps {Object} An array of workflow steps.
     */
    var FS = function(dataUrl, numberColumns, steps) {
        FS.superclass.constructor.call(this, dataUrl, numberColumns);

        this._steps = steps;

        this._columnLabels = [];
        this._columnLabels = this._columnLabels.concat(
            steps,
            FS.AGGREGATE_COLUMNS
        );
    };

    /**
     * The labels for mitigation columns (not including approvals)
     *
     * @static
     */
    FS.MITIGATION_COLUMNS = [];

    /**
     * The labels for evidence columns (not including approvals)
     *
     * @static
     */
    FS.EVIDENCE_COLUMNS = [];

    /**
     * The labels for aggregate columns
     *
     * @static
     */
    FS.AGGREGATE_COLUMNS = ["ALL OPEN", "ALL CLOSED", "TOTAL"];

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
        systemAggregation: "System Hierarchy"
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
         * An array of workflow steps
         */
        _steps: null,

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
            var row = rows[0], label, cell, link, index, that = this;

            Fisma.SummaryTable = this;

            // Create top left cell
            var firstCell = document.createElement('th');
            row.appendChild(firstCell);
            firstCell.style.borderBottom = "none";

            var firstCellSpan = document.createElement('span');
            firstCell.appendChild(firstCellSpan);
            firstCellSpan.appendChild(document.createTextNode("View By: "));

            if (YAHOO.lang.isValue(this._tooltips.viewBy)) {
                firstCellSpan.className = "tooltip";
                firstCellSpan.title = this._tooltips.viewBy;
            }

            var select = document.createElement("select");
            select.onchange = function (event) {that.changeViewType.call(that, event, select);};

            $(select).button();

            var optionKey;
            for (optionKey in FS.SUMMARY_TYPES) {
                var option = new Option(FS.SUMMARY_TYPES[optionKey], optionKey);

                if (option.value === this._currentViewType) {
                    option.selected = true;
                }

                // Workaround for IE7:
                if (YAHOO.env.ua.ie === 7) {
                    select.add(option, select.options[null]);
                } else {
                    select.add(option, null);
                }
            }
            firstCell.appendChild(select);

            for (index in this._columnLabels) {
                cell = document.createElement('th');
                cell.style.borderBottom = "none";

                label = this._columnLabels[index];
                $(cell).attr('header', label);

                link = document.createElement('a');
                cell.appendChild(link);
                if ("ALL OPEN" === label) {
                    link.href = "/finding/remediation/list?q=/isResolved/booleanNo";
                } else if ("ALL CLOSED" === label) {
                    link.href = "/finding/remediation/list?q=/isResolved/booleanYes";
                } else if ("TOTAL" === label) {
                    // Pass a blank query, otherwise the saved settings of previous search will be used
                    link.href = "/finding/remediation/list?q=/id/integerEquals/";
                } else {
                    link.href = "/finding/remediation/list?q=/workflowStep/textExactMatch/"
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

            var index,
                column;
            for (index in this._columnLabels) {
                column = $P.urlencode(this._columnLabels[index]);

                if (column === 'ALL+CLOSED' || column === 'TOTAL') {
                    // These columns don't distinguish ontime from overdue (they're handled above)
                    continue;
                }

                nodeData.aggregate["ontime_" + column] = nodeData["ontime_" + column] || 0;
                nodeData.aggregate["overdue_" + column] = nodeData["overdue_" + column] || 0;
            }

            // Include children's aggregate values in this node's aggregate value
            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                var i;
                for (i in node.children) {
                    var child = node.children[i];
                    var childData = child.nodeData;

                    this._preprocessTreeData(child);

                    nodeData.aggregate.total += childData.aggregate.total;
                    nodeData.aggregate.closed += childData.aggregate.closed;

                    var j;
                    for (j in this._columnLabels) {
                        column = $P.urlencode(this._columnLabels[j]);

                        if (column === 'ALL+CLOSED' || column === 'TOTAL') {
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
            if (columnNumber === 0) {
                container.style.minWidth = "15em";
                container.style.height = "2.5em";
                container.style.overflow = "hidden";

                // The node icon is a graphical representation of what type of node this is: agency, bureau, etc.
                var nodeIcon = document.createElement('img');
                nodeIcon.className = "icon";
                if (nodeData.iconId) {
                    nodeIcon.src = "/icon/get/id/" + nodeData.iconId;
                } else {
                    nodeIcon.src = "/images/" + nodeData.icon + ".png";
                }
                nodeIcon.alt = "";
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
                $(container).attr('header', this._columnLabels[columnNumber - 1]);

                var link = document.createElement("a");
                var ontimeUrl = this._makeUrl(true, status, nodeState, nodeData.rowLabel, nodeData.searchKey);
                var overdueUrl = this._makeUrl(false, status, nodeState, nodeData.rowLabel, nodeData.searchKey);

                // If we are in a collapsed tree node, then switch single-record node data to aggregate node data
                if (nodeState === Fisma.TreeTable.NodeState.COLLAPSED) {
                    nodeData = nodeData.aggregate;
                }

                if (status === "ALL+CLOSED") {
                    link.href = ontimeUrl;
                    link.title = "Resolved findings";
                    container.appendChild(link);
                    link.appendChild(document.createTextNode(nodeData.closed || 0));
                } else if (status === "TOTAL") {
                    link.href = ontimeUrl;
                    link.title = "Total findings";
                    container.appendChild(link);
                    link.appendChild(document.createTextNode(nodeData.total || 0));
                } else {
                    // Set the rendering style based on the existence or absence of ontime and overdue findings
                    var ontime = nodeData["ontime_" + status] || 0;
                    var overdue = nodeData["overdue_" + status] || 0;

                    if (ontime === 0 && overdue === 0) {
                        container.className = "ontime";
                        container.appendChild(document.createTextNode('-'));
                    } else if (ontime > 0 && overdue === 0) {
                        container.className = "ontime";
                        container.appendChild(link);

                        link.href = ontimeUrl;
                        link.title = "On-time findings";
                        link.appendChild(document.createTextNode(ontime || 0));
                    } else if (ontime === 0 && overdue > 0) {
                        container.className = "overdue";
                        container.appendChild(link);

                        link.href = overdueUrl;
                        link.title = "Overdue findings";
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
            ontimeLink.title = "On-time findings";
            ontimeLink.appendChild(document.createTextNode(ontime));
            ontimeLink.href = ontimeUrl;

            var overdueRow = innerTable.insertRow(innerTable.rows.length);

            var overdueCell = document.createElement('td');
            overdueRow.appendChild(overdueCell);
            overdueCell.style.border = "none";
            overdueCell.className = "overdue";

            var overdueLink = document.createElement("a");
            overdueCell.appendChild(overdueLink);
            overdueLink.title = "Overdue findings";
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
            if (status !== "TOTAL" && status !== "ALL OPEN" && status !== "ALL CLOSED") {
                url += "/workflowStep/textExactMatch/" + encodeURIComponent(status);
            } else if (status === "ALL OPEN"){
                url += "/isResolved/booleanNo";
            } else if (status === "ALL CLOSED"){
                url += "/isResolved/booleanYes";
            }

            // Add organization/POC criterion
            if (nodeState === Fisma.TreeTable.NodeState.COLLAPSED) {
                if (this._currentViewType === "systemAggregation") {
                    url += '/' + searchKey + '/systemAggregationSubtree/' + encodeURIComponent(rowLabel);
                } else {
                    url += '/' + searchKey + '/organizationSubtree/' + encodeURIComponent(rowLabel);
                }
            } else {
                url += '/' + searchKey + '/textExactMatch/' + encodeURIComponent(rowLabel);
            }

            // Add ontime criteria (if applicable)
            if (status !== "TOTAL" && status !== "ALL CLOSED") {
                var today = new Date(),
                    yesterday, todayString, yesterdayString;
                yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                todayString = today.getFullYear() + '-' + (today.getMonth() + 1) + '-' + today.getDate();
                yesterdayString = yesterday.getFullYear() + '-' + (yesterday.getMonth() + 1) + '-' + yesterday.getDate();

                if (ontime) {
                    url += "/nextDueDate/dateAfter/" + yesterdayString;
                } else {
                    url += "/nextDueDate/dateBefore/" + todayString;
                }
            }

            // Add filter criteria
            var msSelect = this._filters.mitigationType.select;
            var msValue = msSelect.options[msSelect.selectedIndex].value;
            var msLabel = msSelect.options[msSelect.selectedIndex].text;
            if (msValue !== "none") {
                url += "/workflow/textExactMatch/" + encodeURIComponent(msLabel);
            }

            var sourceSelect = this._filters.findingSource.select;
            var sourceValue = sourceSelect.options[sourceSelect.selectedIndex].value;
            var sourceLabel = sourceSelect.options[sourceSelect.selectedIndex].text;
            if (sourceValue !== "none") {
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
}());
