YAHOO.namespace ("fisma.TreeTable"); 

// Holds a reference to the tree which is being displayed.
// This only supports one tree table per instance.
YAHOO.fisma.TreeTable.treeRoot;

YAHOO.fisma.TreeTable.render = function (tableId, tree, depth) {
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
        if (depth > 1) {
            newRow.style.display = "none";
        } else if (depth < 1) {
            node.expanded = true;
        }
       
        // Populate the new row with table cells
        var cell = newRow.insertCell(0);

        // @doctrine convert to YUI and remove innerHTML if possible
        // general cleanup is needed too
        needsLink = !YAHOO.lang.isUndefined(node.children);
        linkOpen = (needsLink ? "<a href='#' onclick='YAHOO.fisma.TreeTable.toggleNode(\"" + node.nickname + "\")'>" : "");
        linkClose = needsLink ? "</a>" : "";
        controlImage = node.expanded ? "minus.png" : "plus.png";
        control = needsLink ? "<img class=\"control\" id=\"" + node.nickname + "Img\" src=\"/images/" + controlImage + "\">" : "<img class=\"control\" id=\"" + node.nickname + "Img\" src=\"/images/leaf_node.png\">";

        cell.innerHTML = "<div class=\"treeTable" + depth + "\">" + linkOpen + control + "<img class=\"icon\" src=\"/images/" + node.type + ".png\">" + node.label + '<br><i>' + node.type + '</i>' + linkClose;

        // Insert table cells to hold all of the counts for this row
        var i = 1;
        for (var c in node.counts) {
            cell = newRow.insertCell(i++);
            cell.className = 'onTime';
            cell.appendChild(document.createTextNode("" + node.counts[c]));
        } 

        // If this node has children, then recursively render the children
        if (!YAHOO.lang.isUndefined(node.children)) {
            if (depth > 2) return;
            YAHOO.fisma.TreeTable.render(tableId, node.children, depth+1);
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
        if (!YAHOO.lang.isUndefined(node.children)) {
            node.expanded = false;
            document.getElementById(node.nickname + "Img").src = '/images/plus.png';
            YAHOO.fisma.TreeTable.hideSubtree(node.children);
        }
    }
}

YAHOO.fisma.TreeTable.showSubtree = function (treeNode) {
    for (nodeId in treeNode) {
        node = treeNode[nodeId];
        document.getElementById(node.nickname).style.display = 'table-row';
    }   
}

YAHOO.fisma.TreeTable.findNode = function (nodeName, tree) {
    for (var nodeId in tree) {
        node = tree[nodeId];
        if (node.nickname == nodeName) {
            return node;
        } else if (!YAHOO.lang.isUndefined(node.children)) {
            var foundNode = YAHOO.fisma.TreeTable.findNode(nodeName, node.children);
            if (foundNode != false) {
                return foundNode;
            }
        }
    }
    return false;
}
