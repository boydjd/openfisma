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
 * @fileoverview The Finding Summary displays a tree of information systems and summary counts with expand/collapse
 * controls to navigate the tree structure of the information systems. Summary information is automatically rolled up
 * or drilled down as the user navigates the tree.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */
 
Fisma.FindingSummary = function() {
    return {
        /**
         * A pointer to the root node of the tree which is being displayed
         */
        treeRoot : null,
        
        /**
         * Holds the value of the type filter on the current page
         */
        filterType : null,
        
        /**
         * Holds the value of the status filter on the current page
         */
        filterSource : null, 
        
        /**
         * The number of tree levels to display during the initial render
         */
        defaultDisplayLevel : 2,
        
        /**
         * Renders the finding summary view
         * 
         * @todo this is still a monster function which is really unreadable
         * 
         * @param tableId DOM ID of the table which this gets rendered into
         * @param tree A tree structure which contains the counts which are rendered in this table
         * @param newTree If true, then this is the root node which is being rendered
         */         
        render : function(tableId, tree, newTree) {
            /**
             * Set the tree root first.
             */
            if (newTree) {
                this.treeRoot = tree;
            }
            
            // Get reference to the HTML table element which this is rendering into
            var table = document.getElementById(tableId);

            // Render each node at this level
            for (var nodeId in tree) {
                var c;
                var node = tree[nodeId];

                // Append two rows ('ontime' and 'overdue') to the table for this node
                var firstRow = table.insertRow(table.rows.length);
                firstRow.id = node.nickname + "_ontime";
                
                var secondRow = table.insertRow(table.rows.length);
                secondRow.id = node.nickname + "_overdue";

                // The first cell of the first row is the system label
                var firstCell = firstRow.insertCell(0);

                // Determine which set of counts to show initially (single or all)
                node.expanded = (node.level < this.defaultDisplayLevel - 1);
                var ontime = node.expanded ? node.single_ontime : node.all_ontime;
                var overdue = node.expanded ? node.single_overdue : node.all_overdue;
                node.hasOverdue = this.hasOverdue(overdue);

                var expandControlImage = document.createElement('img');
                expandControlImage.className = 'control';
                expandControlImage.id = node.nickname + "Img";

                var expandControl = document.createElement('a');
                expandControl.appendChild(expandControlImage);

                // Does this node need an collapse/expand control?
                var needsExpandControl = node.children.length > 0;                
                if (needsExpandControl) {
                    expandControl.nickname = node.nickname;
                    expandControl.findingSummary = this;
                    expandControl.onclick = function () {
                        this.findingSummary.toggleNode(this.nickname); 
                        return false;
                    };
                    expandControlImage.src = "/images/" + (node.expanded ? "minus.png" : "plus.png");
                } else {
                    expandControlImage.src = "/images/leaf_node.png";
                }

                // Render the first cell on this row
                var firstCellDiv = document.createElement("div");
                firstCellDiv.className = "treeTable" + node.level + (needsExpandControl ? " link" : "");
                firstCellDiv.appendChild(expandControl);
                
                // The node icon is a graphical representation of what type of node this is: agency, bureau, etc.          
                var nodeIcon = document.createElement('img');
                nodeIcon.className = "icon";
                nodeIcon.src = "/images/" + node.orgType + ".png";
                expandControl.appendChild(nodeIcon);
                
                // Add text to the cell
                expandControl.appendChild(document.createTextNode(node.label));
                expandControl.appendChild(document.createElement('br'));
                expandControl.appendChild(document.createTextNode(node.orgTypeLabel));
                
                firstCell.appendChild(firstCellDiv);
                
                // Render the remaining cells on the this row (which are all summary counts)
                var i = 1; // start at 1 because the system label is in the first cell
                for (c in ontime) {
                    count = ontime[c];
                    i++;
                    cell = firstRow.insertCell(i);
                    if (c == 'CLOSED' || c == 'TOTAL') {
                        // The last two colums don't have the ontime/overdue distinction
                        cell.className = "noDueDate";
                    } else {
                        // The in between columns should have the ontime class
                        cell.className = 'onTime';                
                    }
                    this.updateCellCount(cell, count, node.nickname, c, 'ontime', node.expanded);
                }

                // Now add cells to the second row
                for (c in overdue) {
                    count = overdue[c];
                    cell = secondRow.insertCell(secondRow.childNodes.length);
                    cell.className = 'overdue';
                    this.updateCellCount(cell, count, node.nickname, c, 'overdue', node.expanded);
                }

                // Hide both rows by default
                firstRow.style.display = "none";
                secondRow.style.display = "none";

                // Selectively display one or both rows based on current level and whether it has overdues
                if (node.level < this.defaultDisplayLevel) {
                    // set to default instead of 'table-row' to work around an IE6 bug
                    firstRow.style.display = '';  
                    if (node.hasOverdue) {
                        firstRow.childNodes[0].rowSpan = "2";
                        firstRow.childNodes[firstRow.childNodes.length - 2].rowSpan = "2";
                        firstRow.childNodes[firstRow.childNodes.length - 1].rowSpan = "2";
                        // set to default instead of 'table-row' to work around an IE6 bug
                        secondRow.style.display = '';  
                    }
                }

                // If this node has children, then recursively render the children
                if (node.children.length > 0) {
                    this.render(tableId, node.children);
                }
            }            
        },

        /**
         * A function to handle a user click to either expand or collapse a particular tree node
         * 
         * @param treeNode
         */        
        toggleNode : function (treeNode) {
            node = this.findNode(treeNode, this.treeRoot);
            if (node.expanded) {
                this.collapseNode(node, true);
                this.hideSubtree(node.children);
            } else {
                this.expandNode(node);
                this.showSubtree(node.children, false);
            }            
        },
        
        /**
         * Expand a tree node in the finding summary table
         * 
         * @param treeNode
         * @param recursive Indicates whether children should be recursively expanded
         */
        expandNode : function (treeNode, recursive) {
            // When expanding a node, switch the counts displayed from the "all" counts to the "single"
            treeNode.ontime = treeNode.single_ontime;
            treeNode.overdue = treeNode.single_overdue;
            treeNode.hasOverdue = this.hasOverdue(treeNode.overdue);

            // Update the ontime row first
            var ontimeRow = document.getElementById(treeNode.nickname + "_ontime");    
            var i = 1; // start at 1 b/c the first column is the system name
            for (c in treeNode.ontime) {
                count = treeNode.ontime[c];
                this.updateCellCount(ontimeRow.childNodes[i], count, treeNode.nickname, c, 'ontime', true);
                i++;
            }

            // Then update the overdue row, or hide it if there are no overdues
            var overdueRow = document.getElementById(treeNode.nickname + "_overdue");
            if (treeNode.hasOverdue) {
                // Do not hide the overdue row. Instead, update the counts
                i = 0;
                for (c in treeNode.overdue) {
                    count = treeNode.overdue[c];
                    this.updateCellCount(overdueRow.childNodes[i], count, treeNode.nickname, c, 'overdue', true);
                    i++;
                }
            } else {
                // Hide the overdue row and adjust the rowspans on the ontime row to compensate
                ontimeRow.childNodes[0].rowSpan = "1";
                ontimeRow.childNodes[ontimeRow.childNodes.length - 2].rowSpan = "1";
                ontimeRow.childNodes[ontimeRow.childNodes.length - 1].rowSpan = "1";
                overdueRow.style.display = 'none';
            }

            // Update the control image and internal status field
            if (treeNode.children.length > 0) {
                document.getElementById(treeNode.nickname + "Img").src = "/images/minus.png";
            }
            treeNode.expanded = true;

            // If the function is called recursively and this node has children, then
            // expand the children.
            if (recursive && treeNode.children.length > 0) {
                this.showSubtree(treeNode.children, false);
                for (var child in treeNode.children) {
                    this.expandNode(treeNode.children[child], true);
                }
            }
        }, 
        
        /**
         * Collapse a tree node and all of its children
         * 
         * @param treeNode
         * @param displayOverdue ???
         */
        collapseNode : function (treeNode, displayOverdue) {
            // When collapsing a node, switch the counts displayed from the "single" counts to the "all"
            treeNode.ontime = treeNode.all_ontime;
            treeNode.overdue = treeNode.all_overdue;
            treeNode.hasOverdue = this.hasOverdue(treeNode.overdue);

            // Update the ontime row first
            var ontimeRow = document.getElementById(treeNode.nickname + "_ontime");
            var i = 1; // start at 1 b/c the first column is the system name
            for (c in treeNode.ontime) {
                count = treeNode.ontime[c];
                this.updateCellCount(ontimeRow.childNodes[i], count, treeNode.nickname, c, 'ontime', false);
                i++;
            }

            // Update the overdue row. Display the row first if necessary.
            var overdueRow = document.getElementById(treeNode.nickname + "_overdue");
            if (displayOverdue && treeNode.hasOverdue) {
                // Show the overdue row and adjust the rowspans on the ontime row to compensate
                ontimeRow.childNodes[0].rowSpan = "2";
                ontimeRow.childNodes[ontimeRow.childNodes.length - 2].rowSpan = "2";
                ontimeRow.childNodes[ontimeRow.childNodes.length - 1].rowSpan = "2";
                overdueRow.style.display = '';  // set to default instead of 'table-row' to work around an IE6 bug

                i = 0;
                for (c in treeNode.all_overdue) {
                    count = treeNode.all_overdue[c];
                    this.updateCellCount(overdueRow.childNodes[i], count, treeNode.nickname, c, 'overdue', false);
                    i++;
                }
            }

            // If the node has children, then hide those children
            if (treeNode.children.length > 0) {
                this.hideSubtree(treeNode.children);
            }

            document.getElementById(treeNode.nickname + "Img").src = "/images/plus.png";
            treeNode.expanded = false;
        }, 
        
        /**
         * Hide an entire subtree
         * 
         * This differs from 'collapsing' a node because a collapsed node is still displayed, whereas a hidden subtree
         * isn't even displayed.
         * 
         * @param nodeArray This will generally be all of the children of a parent which is being collapsed.
         */
        hideSubtree : function (nodeArray) {
            for (nodeId in nodeArray) {
                node = nodeArray[nodeId];

                // Now update this node
                ontimeRow = document.getElementById(node.nickname + "_ontime");
                ontimeRow.style.display = 'none';
                overdueRow = document.getElementById(node.nickname + "_overdue");
                overdueRow.style.display = 'none';

                // Recurse through children
                if (node.children.length > 0) {
                    this.collapseNode(node, false);
                    this.hideSubtree(node.children);
                }
            }
        }, 
        
        /**
         * Make children of a node visible
         * 
         * @param nodeArray This will generally be all of the children of a parent node which is being expanded
         * @param recursive If true, then this makes the entire subtree visible. If false, then just the nodeArray is 
         * visible.
         */
        showSubtree : function (nodeArray, recursive) {
            for (nodeId in nodeArray) {
                node = nodeArray[nodeId];

                // Recurse through the child nodes (if necessary)
                if (recursive && node.children.length > 0) {
                    this.expandNode(node);
                    this.showSubtree(node.children, true);            
                }

                // Now update this node
                ontimeRow = document.getElementById(node.nickname + "_ontime");
                ontimeRow.style.display = '';  // set to default instead of 'table-row' to work around an IE6 bug
                overdueRow = document.getElementById(node.nickname + "_overdue");
                if (node.hasOverdue) {
                    ontimeRow.childNodes[0].rowSpan = "2";
                    ontimeRow.childNodes[ontimeRow.childNodes.length - 2].rowSpan = "2";
                    ontimeRow.childNodes[ontimeRow.childNodes.length - 1].rowSpan = "2";
                    overdueRow.style.display = '';  // set to default instead of 'table-row' to work around an IE6 bug
                }
            }               
        }, 
        
        /**
         * Collapse all nodes in the tree. This results in just the root node(s) being displayed, all others hidden.
         */
        collapseAll : function () {
            for (nodeId in this.treeRoot) {
                node = this.treeRoot[nodeId];
                this.collapseNode(node, true);
                this.hideSubtree(node.children);
            }            
        }, 
        
        /**
         * Expand all nodes in the tree. This results in all nodes being displayed.
         */
        expandAll : function () {
            for (nodeId in this.treeRoot) {
                node = this.treeRoot[nodeId];
                this.expandNode(node, true);
            } 
        }, 
        
        /**
         * Find a node by name in a given subtree
         * 
         * @param nodeName
         * @param tree
         * @return Either a node or boolean false
         */
        findNode : function (nodeName, tree) {
            for (var nodeId in tree) {
                node = tree[nodeId];
                if (node.nickname === nodeName) {
                    return node;
                } else if (node.children.length > 0) {
                    var foundNode = this.findNode(nodeName, node.children);
                    if (foundNode != false) {
                        return foundNode;
                    }
                }
            }
            
            return false;            
        }, 
        
        /**
         * Returns true if the specified node has any overdue items, false otherwise
         * 
         * A node has overdue items if any of the counts in its overdue array is greater than 0
         * 
         * @param An array of overdue counts for a particular node
         * @return boolean
         */
        hasOverdue : function (overdueCountArray) {
            for (var i in overdueCountArray) {
                if (overdueCountArray[i] > 0) {
                    return true;
                }
            }
            
            return false;
        },
        
        /**
         * Update the count that is displayed inside a particular cell
         * 
         * @param cell An HTML table cell
         * @param count The count to display
         * @param orgName Used to generate link
         * @param ontime Used to generate link
         * @param expanded Used to generate link
         */
        updateCellCount : function (cell, count, orgName, status, ontime, expanded) {
            var link;
            if (!cell.hasChildNodes()) {
                // Initialize this cell
                if (count > 0) {
                    link = document.createElement('a');
                    link.href = this.makeLink(orgName, status, ontime, expanded);
                    link.appendChild(document.createTextNode(count));
                    cell.appendChild(link);
                } else {
                    cell.appendChild(document.createTextNode('-'));
                }
            } else {
                // The cell is already initialized, so we may need to add or remove child elements
                if (cell.firstChild.hasChildNodes()) {
                    // The cell contains an anchor
                    if (count > 0) {
                        // Update the anchor text
                        cell.firstChild.firstChild.nodeValue = count;
                        cell.firstChild.href = this.makeLink(orgName, status, ontime, expanded);
                    } else {
                        // Remove the anchor
                        cell.removeChild(cell.firstChild);
                        cell.appendChild(document.createTextNode('-'));
                    }
                } else {
                    // The cell contains just a text node
                    if (count > 0) {
                        // Need to add a new anchor
                        cell.removeChild(cell.firstChild);
                        link = document.createElement('a');
                        link.href = this.makeLink(orgName, status, ontime, expanded);
                        link.appendChild(document.createTextNode(count));
                        cell.appendChild(link);
                    } else {
                        // Update the text node value
                        cell.firstChild.nodeValue = '-';
                    }
                }
            }
        }, 
        
        /**
         * Generate the URI that a cell will link to
         * 
         * These search engine uses these parameters to filter the search based on the cell that was clicked
         * 
         * @param orgName
         * @param status
         * @param ontime
         * @param expanded
         * @return String URI
         */
        makeLink : function (orgName, status, ontime, expanded) {
            // CLOSED and TOTAL columns should not have an 'ontime' criteria in the link
            var onTimeString = '';
            if (!(status == 'CLOSED' || status == 'TOTAL')) {
                var now = new Date();
                
                var nowStr = now.getFullYear() + "-" + (now.getMonth() + 1) + "-" + now.getDate();

                if ('ontime' == ontime) {
                    onTimeString = '/nextDueDate/dateAfter/' + nowStr;
                } else {
                    onTimeString = '/nextDueDate/dateBefore/' + nowStr;
                }
            }

            // Include any status
            var statusString = '';
            if (status !== '' && status !=='TOTAL') {
                statusString = '/denormalizedStatus/textExactMatch/' + escape(status);
            }

            // Include any filters
            var filterType = '';
            if (!YAHOO.lang.isNull(this.filterType) && this.filterType !== '') {
                filterType = '/type/enumIs/' + this.filterType;
            }

            var filterSource = '';
            if (!YAHOO.lang.isNull(this.filterSource) && this.filterSource !== '') {
                filterSource = '/source/textExactMatch/' + this.filterSource;
            }

            // Render the link
            var uri = '/finding/remediation/list/queryType/advanced' + onTimeString + statusString + filterType + filterSource;

            if (expanded) {
                uri += '/organization/textExactMatch/' + orgName;
            } else {
                uri += '/organization/organizationSubtree/' + orgName;
            }

            return uri;            
        }, 
        
        /**
         * Redirect to a URI which exports the summary table
         * 
         * @param format Only 'pdf' is valid at the moment.
         */
        exportTable : function (format) {
            var uri = '/finding/remediation/summary-data/format/' + format + this.listExpandedNodes(this.treeRoot, '');

            document.location = uri;            
        }, 
        
        /**
         * Returns a URI paramter string that represents which nodes are expanded and which nodes are collapsed
         * 
         * This is used during export to make the exported tree mirror what the user sees in the browser
         * 
         * @param nodes A subtree to render into the return string
         * @param visibleNodes Pass a blank string. This is an accumulator which is used for recursive calls.
         * @return String URI
         */
        listExpandedNodes : function (nodes, visibleNodes) {
            for (var n in nodes) {
                var node = nodes[n];
                if (node.expanded) {
                    visibleNodes += '/e/' + node.id;
                    visibleNodes = this.listExpandedNodes(node.children, visibleNodes);
                } else {
                    visibleNodes += '/c/' + node.id;
                }
            }

            return visibleNodes;
        }
    };
};
