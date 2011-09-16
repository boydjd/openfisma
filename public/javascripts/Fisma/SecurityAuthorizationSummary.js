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
     * A widget that displays security authorization data in a tree table widget.
     * 
     * @namespace Fisma
     * @class TreeTable
     * @constructor
     * @param dataUrl {String}
     * @param numberColumns {Integer} The number of columns to display in the table.
     */
    var SAS = function(dataUrl, numberColumns) {
        SAS.superclass.constructor.call(this, dataUrl, numberColumns);
    };
    
    YAHOO.lang.extend(SAS, Fisma.TreeTable, {
        /**
         * Define the content of the organization tool tip text
         */
        _organizationTooltipText: "<p>Click on any item with a plus (+) to expand it and reveal the items contained in"
            + " it. Click any item with a minus (-) to collapse it and hide the items inside.</p><p>When an item is"
            + " collapsed, the summary numbers displayed will roll up data for all suborganizations and information"
            + " systems underneath it. When an item is expanded, the summary numbers displayed only include data"
            + " against that specific organization.</p>",

        /**
         * Render a customized header for this tree table.
         * 
         * @param containerRow {HTMLElement} The parent container (a TR element) to render the header inside of.
         */
        _renderHeader: function (containerRow) {
            // Render the first column
            var firstCell = document.createElement('th');

            var firstCellSpan = document.createElement('span');
            firstCellSpan.className = "tooltip";
            firstCellSpan.appendChild(document.createTextNode('Agency Organizations & Information Systems'));
            firstCell.appendChild(firstCellSpan);
            containerRow.appendChild(firstCell);

            organizationTooltip = new YAHOO.widget.Tooltip(
                "organizationTooltip",
                {
                    context: firstCellSpan,
                    showdelay: 150,
                    hidedelay: 150,
                    autodismissdelay: 25000,
                    text: this._organizationTooltipText,
                    effect: {effect:YAHOO.widget.ContainerEffect.FADE, duration: 0.25},
                    width: "50%"
                }
            )

            // Render the remaining columns
            var columnTitles = [
                'Step 1. Categorize',
                'Step 2. Select',
                'Step 3. Implement',
                'Step 4. Assess',
                'Step 5. Authorize'
            ];

            var cell;

            for (var index in columnTitles) {
                cell = document.createElement('th');
                cell.appendChild(document.createTextNode(columnTitles[index]));
                containerRow.appendChild(cell);
            }
        },

        /**
         * Aggregate data from leaf nodes up to root nodes.
         * 
         * @param node {Array}
         */
        _preprocessTreeData: function(node) {
            var nodeData = node.nodeData;

            nodeData.aggregate = {
                nodeCount: 0,
                step1: 0,
                step2: 0,
                step3: 0,
                step4: 0,
                step5: 0
            };

            if (YAHOO.lang.isValue(node.children) && node.children.length > 0) {
                for (var i in node.children) {
                    var child = node.children[i];
                    var childData = child.nodeData;
                    
                    this._preprocessTreeData(child);
                    
                    nodeData.aggregate.nodeCount += child.nodeData.aggregate.nodeCount;

                    nodeData.aggregate.step1 += parseInt(childData.aggregate.step1);
                    nodeData.aggregate.step2 += parseInt(childData.aggregate.step2);
                    nodeData.aggregate.step3 += parseInt(childData.aggregate.step3);
                    nodeData.aggregate.step4 += parseInt(childData.aggregate.step4);
                    nodeData.aggregate.step5 += parseInt(childData.aggregate.step5);
                }
            } else {
                // If a node has no children, then it's aggregate values are equal to its non-aggregate values
                nodeData.aggregate = Array();

                nodeData.aggregate.nodeCount = 1;

                nodeData.aggregate.step1 = parseInt(nodeData.step1);
                nodeData.aggregate.step2 = parseInt(nodeData.step2);
                nodeData.aggregate.step3 = parseInt(nodeData.step3);
                nodeData.aggregate.step4 = parseInt(nodeData.step4);
                nodeData.aggregate.step5 = parseInt(nodeData.step5);
            }
        },

        /**
         * Render cells in this SA summary table.
         * 
         * Aggregate values are calculated on the fly.
         * 
         * @param container {HTMLElement} The parent container to render cell content inside of.
         * @param nodeData {Object} Data related to this node.
         * @param columnNumber {Integer} The [zero-indexed] column which needs to be rendered.
         * @param nodeState {TreeTable.NodeState}
         */
        _renderCell: function (container, nodeData, columnNumber, nodeState) {
            if (columnNumber == 0) {
                // The node icon is a graphical representation of what type of node this is: agency, bureau, etc.          
                var nodeIcon = document.createElement('img');
                nodeIcon.className = "icon";
                nodeIcon.src = "/images/" + nodeData.orgType + ".png";
                container.appendChild(nodeIcon);

                // Add text to the cell
                container.appendChild(document.createTextNode(nodeData.nickname + ' - ' + nodeData.name));
                container.appendChild(document.createElement('br'));
                container.appendChild(document.createTextNode(nodeData.orgTypeLabel));
            } else {
                var completedCount;
                var totalCount = nodeData.aggregate.nodeCount;

                container.style.textAlign = 'center';

                if (nodeState == Fisma.TreeTable.NodeState.LEAF_NODE) {
                    completedCount = nodeData['step' + columnNumber];
                } else {
                    completedCount = nodeData.aggregate['step' + columnNumber];
                }

                container.appendChild(document.createTextNode(completedCount + '/' + totalCount));
                
                jQuery(container).peity("pie", {
                    colours: function () {
                        if (completedCount / totalCount > .9) {
                            return ['lightGreen', 'green'];
                        } else {
                            return ['pink', 'red'];
                        }
                    },
                    radius: function () {
                        return 24;
                    }
                });
            }
        }
    });

    Fisma.SecurityAuthorizationSummary = SAS;
})();
