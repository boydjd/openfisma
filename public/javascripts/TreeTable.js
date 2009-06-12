YAHOO.namespace ("fisma.TreeTable"); 

// Holds a reference to the tree which is being displayed.
// This only supports one tree table per instance.
YAHOO.fisma.TreeTable.treeRoot;

// How many tree levels to display, by default
YAHOO.fisma.TreeTable.defaultDisplayLevel = 3;

YAHOO.fisma.TreeTable.render = function (tableId, tree) {
    // Set the global tree root first, if necessary
    if (YAHOO.lang.isUndefined(YAHOO.fisma.TreeTable.treeRoot)) {
        YAHOO.fisma.TreeTable.treeRoot = tree;
    }
    var table = document.getElementById(tableId);

    // Render each node at this level
    for (var nodeId in tree) {
        var node = tree[nodeId];

        // Add two rows to the table for this node
        var firstRow = table.insertRow(table.rows.length);
        firstRow.id = node.nickname;
        var secondRow = table.insertRow(table.rows.length);
        secondRow.id = node.nickname + "2";
       
        // The first cell of the first row is the system label
        var firstCell = firstRow.insertCell(0);

        // Determine which set of counts to show initially (single or all)
        node.expanded = (node.level < YAHOO.fisma.TreeTable.defaultDisplayLevel - 1);
        var ontime = node.expanded ? node.single_ontime : node.all_ontime;
        var overdue = node.expanded ? node.single_overdue : node.all_overdue;
        node.hasOverdue = YAHOO.fisma.TreeTable.arraySum(overdue) > 0;

        // @doctrine convert to YUI and remove innerHTML if possible
        // general cleanup is needed too
        needsLink = node.children.length > 0;
        linkOpen = (needsLink ? "<a href='#' onclick='YAHOO.fisma.TreeTable.toggleNode(\"" + node.nickname + "\")'>" : "");
        linkClose = needsLink ? "</a>" : "";
        linkDivClass = needsLink ? " link" : "";
        controlImage = node.expanded ? "minus.png" : "plus.png";
        control = needsLink ? "<img class=\"control\" id=\"" + node.nickname + "Img\" src=\"/images/" + controlImage + "\">" : "<img class=\"control\" id=\"" + node.nickname + "Img\" src=\"/images/leaf_node.png\">";

        firstCell.innerHTML = "<div class=\"treeTable" + node.level + linkDivClass + "\">" + linkOpen + control + "<img class=\"icon\" src=\"/images/" + node.orgType + ".png\">" + node.label + '<br><i>' + node.orgTypeLabel + '</i>' + linkClose + '</div>';

        // The remaining cells on the first row are summary counts
        var i = 1; // b/c the system label is in the first cell
        for (var c in ontime) {
            count = ontime[c];
            cell = firstRow.insertCell(i++);
            if (c == 'CLOSED' || c == 'TOTAL') {
                // The last two colums don't have the ontime/overdue distinction
                cell.className = "noDueDate";
            } else {
                // The in between columns should have the ontime class
                cell.className = 'onTime';                
            }
            cell.appendChild(document.createTextNode("" + count));
        }

        // Now add cells to the second row
        for (var c in overdue) {
            count = overdue[c];
            cell = secondRow.insertCell(secondRow.childNodes.length);
            cell.className = 'overdue';
            cell.appendChild(document.createTextNode("" + count));
        }

        // Hide both rows by default
        firstRow.style.display = "none";
        secondRow.style.display = "none";

        // Selectively display one or both rows based on current level and whether it has overdues
        if (node.level < YAHOO.fisma.TreeTable.defaultDisplayLevel) {
            firstRow.style.display = 'table-row';
            if (node.hasOverdue) {
                firstRow.childNodes[0].rowSpan = "2";
                firstRow.childNodes[firstRow.childNodes.length - 2].rowSpan = "2";
                firstRow.childNodes[firstRow.childNodes.length - 1].rowSpan = "2";
                secondRow.style.display = 'table-row';
            }
        }
        
        // If this node has children, then recursively render the children
        if (node.children.length > 0) {
            YAHOO.fisma.TreeTable.render(tableId, node.children);
        }
    }
}

YAHOO.fisma.TreeTable.toggleNode = function (treeNode) {
    node = YAHOO.fisma.TreeTable.findNode(treeNode, YAHOO.fisma.TreeTable.treeRoot);
    if (node.expanded) {
        YAHOO.fisma.TreeTable.collapseNode(node, true);
        YAHOO.fisma.TreeTable.hideSubtree(node.children);
    } else {
        YAHOO.fisma.TreeTable.expandNode(node);
        YAHOO.fisma.TreeTable.showSubtree(node.children, false);
    }
}

YAHOO.fisma.TreeTable.expandNode = function (treeNode, recursive) {
    // When expanding a node, switch the counts displayed from the "all" counts to the "single"
    treeNode.ontime = treeNode.single_ontime;
    treeNode.overdue = treeNode.single_overdue;
    treeNode.hasOverdue = YAHOO.fisma.TreeTable.arraySum(treeNode.overdue) > 0;

    // Update the ontime row first
    var ontimeRow = document.getElementById(treeNode.nickname);    
    var i = 1; // start at 1 b/c the first column is the system name
    for (c in treeNode.ontime) {
        count = treeNode.ontime[c];
        ontimeRow.childNodes[i++].firstChild.nodeValue = count;
    }
    
    // Then update the overdue row, or hide it if there are no overdues
    var overdueRow = document.getElementById(treeNode.nickname + "2");
    if (treeNode.hasOverdue) {
        // Do not hide the overdue row. Instead, update the counts
        var i = 0;
        for (c in treeNode.overdue) {
            count = treeNode.overdue[c];
            overdueRow.childNodes[i++].firstChild.nodeValue = count;
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
        YAHOO.fisma.TreeTable.showSubtree(treeNode.children, false);
        for (var child in treeNode.children) {
            YAHOO.fisma.TreeTable.expandNode(treeNode.children[child], true);
        }
    }
}

YAHOO.fisma.TreeTable.collapseNode = function (treeNode, displayOverdue) {
    // When collapsing a node, switch the counts displayed from the "single" counts to the "all"
    treeNode.ontime = treeNode.all_ontime;
    treeNode.overdue = treeNode.all_overdue;
    treeNode.hasOverdue = YAHOO.fisma.TreeTable.arraySum(treeNode.overdue) > 0;

    // Update the ontime row first
    var ontimeRow = document.getElementById(treeNode.nickname);
    var i = 1; // start at 1 b/c the first column is the system name
    for (c in treeNode.ontime) {
        count = treeNode.ontime[c];
        ontimeRow.childNodes[i++].firstChild.nodeValue = count;
    }
    
    // Update the overdue row. Display the row first if necessary.
    var overdueRow = document.getElementById(treeNode.nickname + "2");
    if (displayOverdue && treeNode.hasOverdue) {
        // Show the overdue row and adjust the rowspans on the ontime row to compensate
        ontimeRow.childNodes[0].rowSpan = "2";
        ontimeRow.childNodes[ontimeRow.childNodes.length - 2].rowSpan = "2";
        ontimeRow.childNodes[ontimeRow.childNodes.length - 1].rowSpan = "2";
        overdueRow.style.display = 'table-row';

        var i = 0;
        for (c in treeNode.all_overdue) {
            count = treeNode.all_overdue[c];
            overdueRow.childNodes[i++].firstChild.nodeValue = count;
        }
    }

    // If the node has children, the hide those children
    if (treeNode.children.length > 0) {
        YAHOO.fisma.TreeTable.hideSubtree(treeNode.children);
    }
        
    document.getElementById(treeNode.nickname + "Img").src = "/images/plus.png";
    treeNode.expanded = false;
}

YAHOO.fisma.TreeTable.hideSubtree = function (nodeArray) {
    for (nodeId in nodeArray) {
        node = nodeArray[nodeId];

        // Now update this node
        ontimeRow = document.getElementById(node.nickname);
        ontimeRow.style.display = 'none';
        overdueRow = document.getElementById(node.nickname + "2");
        overdueRow.style.display = 'none';

        // Recurse through children
        if (node.children.length > 0) {
            YAHOO.fisma.TreeTable.collapseNode(node, false);
            YAHOO.fisma.TreeTable.hideSubtree(node.children);
        }
    }
}

YAHOO.fisma.TreeTable.showSubtree = function (nodeArray, recursive) {
    for (nodeId in nodeArray) {
        node = nodeArray[nodeId];

        // Recurse through the child nodes (if necessary)
        if (recursive && node.children.length > 0) {
            YAHOO.fisma.TreeTable.expandNode(node);
            YAHOO.fisma.TreeTable.showSubtree(node.children, true);            
        }

        // Now update this node
        ontimeRow = document.getElementById(node.nickname);
        ontimeRow.style.display = 'table-row';
        overdueRow = document.getElementById(node.nickname + "2");
        if (node.hasOverdue) {
            ontimeRow.childNodes[0].rowSpan = "2";
            ontimeRow.childNodes[ontimeRow.childNodes.length - 2].rowSpan = "2";
            ontimeRow.childNodes[ontimeRow.childNodes.length - 1].rowSpan = "2";
            overdueRow.style.display = 'table-row';
        }
    }   
}

YAHOO.fisma.TreeTable.collapseAll = function () {
    for (nodeId in YAHOO.fisma.TreeTable.treeRoot) {
        node = YAHOO.fisma.TreeTable.treeRoot[nodeId];
        YAHOO.fisma.TreeTable.collapseNode(node, true);
        YAHOO.fisma.TreeTable.hideSubtree(node.children);
    }
}

YAHOO.fisma.TreeTable.expandAll = function () {
    for (nodeId in YAHOO.fisma.TreeTable.treeRoot) {
        node = YAHOO.fisma.TreeTable.treeRoot[nodeId];
        YAHOO.fisma.TreeTable.expandNode(node, true);
    }
}

YAHOO.fisma.TreeTable.findNode = function (nodeName, tree) {
    for (var nodeId in tree) {
        node = tree[nodeId];
        if (node.nickname == nodeName) {
            return node;
        } else if (node.children.length > 0) {
            var foundNode = YAHOO.fisma.TreeTable.findNode(nodeName, node.children);
            if (foundNode != false) {
                return foundNode;
            }
        }
    }
    return false;
}

YAHOO.fisma.TreeTable.arraySum = function (a) {
    var sum = 0;
    for (var i in a) {
        sum += a[i];
    }
    return sum;
}
