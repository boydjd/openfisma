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
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

/**
 * Constructor
 * 
 */
Fisma.ControlTree = function (treeElem, dataUrl) {
    var ctObj = this;
    YAHOO.util.Connect.asyncRequest(
        'GET', 
        dataUrl, 
        {
            success: function(o) {
                var json = YAHOO.lang.JSON.parse(o.responseText);
                ctObj.showTree(json.treeData);
            },
            failure: function(o) {
                alert('Unable to load the organization tree: ' + o.statusText);
            }
        }, 
        null);

    this.treeElem = treeElem;
}

Fisma.ControlTree.prototype = {
    showTree: function(treeNodes) {
        this.tree = new YAHOO.widget.TreeView(this.treeElem);
        this.renderTreeNodes(treeNodes, this.tree.getRoot());
        this.tree.draw();
    },

    renderTreeNodes: function(treeNodes, parentNode) {
        for (var i in treeNodes) {
            this.renderFamily(i, treeNodes[i], parentNode);
        }
    },

    renderFamily: function(family, controls, parent) {
        var familyNode = new YAHOO.widget.TextNode(family, parent, false);
        for (var i in controls) {
            var control = controls[i];
            this.renderControl(control, familyNode);
        }
    },

    renderControl: function(control, parent) {
        var nodeText = "<b>" + PHP_JS().htmlspecialchars(control.code) + "</b> - <i>"
                             + PHP_JS().htmlspecialchars(control.name) + "</i>";
        var controlNode = new YAHOO.widget.TextNode(nodeText, parent, false);
        for (var i in control.enhancements) {
            var enhancement = control.enhancements[i];
            this.renderEnhancement(enhancement, controlNode);
        }

    },

    renderEnhancement: function(enhancement, parent) {
        var enhancementNode = new YAHOO.widget.TextNode(enhancement, parent, false);
    }
};
