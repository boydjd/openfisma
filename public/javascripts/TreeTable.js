YAHOO.namespace ("fisma.TreeTable"); 

// Holds a reference to the tree which is being displayed.
// This only supports one tree table per instance.
YAHOO.fisma.TreeTable.treeRoot;

// How many tree levels to display, by default
YAHOO.fisma.TreeTable.defaultDisplayLevel = 1;

YAHOO.fisma.TreeTable.render = function (tableId, tree) {
    if (YAHOO.lang.isUndefined(YAHOO.fisma.TreeTable.treeRoot)) {
        YAHOO.fisma.TreeTable.treeRoot = tree;
    }
    var table = document.getElementById(tableId);

    for (var nodeId in tree) {
        var node = tree[nodeId];

        // Add a row to the table for this node
        var newRow = table.insertRow(table.rows.length);
        newRow.id = node.nickname;

        // Based on the current depth, show/hide the new row and set the expanded attribute on the
        // corresponding node
        node.expanded = false;
        if (node.level > YAHOO.fisma.TreeTable.defaultDisplayLevel) {
            newRow.style.display = "none";
        } else if (node.level < YAHOO.fisma.TreeTable.defaultDisplayLevel) {
            node.expanded = true;
        }
       
        // Populate the new row with table cells
        var firstCell = newRow.insertCell(0);

        // @doctrine convert to YUI and remove innerHTML if possible
        // general cleanup is needed too
        needsLink = node.children.length > 0;
        linkOpen = (needsLink ? "<a href='#' onclick='YAHOO.fisma.TreeTable.toggleNode(\"" + node.nickname + "\")'>" : "");
        linkClose = needsLink ? "</a>" : "";
        controlImage = node.expanded ? "minus.png" : "plus.png";
        control = needsLink ? "<img class=\"control\" id=\"" + node.nickname + "Img\" src=\"/images/" + controlImage + "\">" : "<img class=\"control\" id=\"" + node.nickname + "Img\" src=\"/images/leaf_node.png\">";

        firstCell.innerHTML = "<div class=\"treeTable" + node.level + "\">" + linkOpen + control + "<img class=\"icon\" src=\"/images/" + node.orgType + ".png\">" + node.label + '<br><i>' + node.orgType + '</i>' + linkClose;

        // If there are overdue items, then the first cell needs to expand to span two rows
        var i = 1;
        if (!YAHOO.lang.isUndefined(node.overdue)) {
            firstCell.rowSpan = "2";
        }

        // Insert cells for ontime findings
        for (var c in node.ontime) {
            cell = newRow.insertCell(i++);
            if (c > node.ontime.length - 3) {
                // The last two colums don't have the ontime/overdue distinction
                cell.className = "noDueDate";
                
                // The last two columns have rowspan=2 if there are overdue items
                if (!YAHOO.lang.isUndefined(node.overdue)) {
                    cell.rowSpan = "2";
                }
            } else {
                // The in between columns should have the ontime class
                cell.className = 'onTime';                
            }
            cell.appendChild(document.createTextNode("" + node.ontime[c]));
        }
    
        // If there are overdue items, then add a new row to the table and insert cells for
        // overdue findings
        if (!YAHOO.lang.isUndefined(node.overdue)) {
            var secondRow = table.insertRow(table.rows.length);
            secondRow.id  = node.nickname + '2';
            if (node.level > YAHOO.fisma.TreeTable.defaultDisplayLevel) {
                secondRow.style.display = "none";
            }
            i = 0;
            for (var c in node.overdue) {
                cell = secondRow.insertCell(i++);
                cell.className = 'overdue';
                cell.appendChild(document.createTextNode("" + node.overdue[c]));
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
    if (node.expanded == true) {
        // hide child nodes
        document.getElementById(node.nickname + "Img").src = "/images/plus.png";
        node.expanded = false;
        YAHOO.fisma.TreeTable.hideSubtree(node.children);
    } else {
        // show child nodes
        document.getElementById(node.nickname + "Img").src = "/images/minus.png";
        node.expanded = true;
        YAHOO.fisma.TreeTable.showSubtree(node.children);
    }
}

YAHOO.fisma.TreeTable.hideSubtree = function (treeNode) {
    for (nodeId in treeNode) {
        node = treeNode[nodeId];
        document.getElementById(node.nickname).style.display = 'none';
        var secondRow = document.getElementById(node.nickname + '2');
        if (secondRow) {
            secondRow.style.display = 'none';
        }
        if (node.children.length > 0) {
            node.expanded = false;
            document.getElementById(node.nickname + "Img").src = '/images/plus.png';
            YAHOO.fisma.TreeTable.hideSubtree(node.children);
        }
    }
}

YAHOO.fisma.TreeTable.showSubtree = function (treeNode, recursive) {
    for (nodeId in treeNode) {
        node = treeNode[nodeId];
        document.getElementById(node.nickname).style.display = 'table-row';
        var secondRow = document.getElementById(node.nickname + '2');
        if (secondRow) {
            secondRow.style.display = 'table-row';
        }
        if (recursive && node.children.length > 0) {
            node.expanded = true;
            document.getElementById(node.nickname + "Img").src = '/images/minus.png';
            YAHOO.fisma.TreeTable.showSubtree(node.children, true);            
        }
    }   
}

YAHOO.fisma.TreeTable.collapseAll = function () {
    for (nodeId in YAHOO.fisma.TreeTable.treeRoot) {
        node = YAHOO.fisma.TreeTable.treeRoot[nodeId];
        document.getElementById(node.nickname + "Img").src = "/images/plus.png";
        node.expanded = false;
        YAHOO.fisma.TreeTable.hideSubtree(node.children);
    }
}

YAHOO.fisma.TreeTable.expandAll = function () {
    for (nodeId in YAHOO.fisma.TreeTable.treeRoot) {
        node = YAHOO.fisma.TreeTable.treeRoot[nodeId];
        document.getElementById(node.nickname + "Img").src = "/images/minus.png";
        node.expanded = true;
        YAHOO.fisma.TreeTable.showSubtree(node.children, true);
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
