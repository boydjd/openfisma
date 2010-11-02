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
    this.treeElem = treeElem;

    var ctObj = this;
    YAHOO.util.Connect.asyncRequest(
        'GET', 
        dataUrl, 
        {
            success: function(o) {
                var json = YAHOO.lang.JSON.parse(o.responseText);
                ctObj.showTree(json.treeData);
                ctObj.updateContextMenus();
            },
            failure: function(o) {
                alert('Unable to load the organization tree: ' + o.statusText);
            }
        }, 
        null);
}

Fisma.ControlTree.prototype = {
    treeView: null,
    treeElem: null,
    controlContextMenu: null,
    controlContextMenuTriggers: null,
    enhancementContextMenu: null,
    enhancementContextMenuTriggers: null,
    
    showTree: function(treeNodes) {
        this.treeView = new YAHOO.widget.TreeView(this.treeElem);
        this.renderTreeNodes(treeNodes, this.treeView.getRoot());
        this.treeView.draw();
    },

    renderTreeNodes: function(treeNodes, parentNode) {
        for (var i in treeNodes) {
            this.renderFamily(i, treeNodes[i], parentNode);
        }
    },

    renderFamily: function(family, controls, parent) {
        var familyNode = new YAHOO.widget.TextNode(
            { label: family, renderHidden: true },
            parent,
            false
        );
        for (var i in controls) {
            this.renderControl(controls[i], familyNode);
        }
    },

    renderControl: function(control, parent) {
        var label = "<b>" + PHP_JS().htmlspecialchars(control.code) + "</b> - <i>"
                          + PHP_JS().htmlspecialchars(control.name) + "</i>";
        var props = {
            label: label,
            renderHidden: true,
            securityControlId: control.id
        };

        var controlNode = new YAHOO.widget.TextNode(props, parent, false);
        if (this.controlContextMenuTriggers == null) {
            this.controlContextMenuTriggers = [];
        }
        this.controlContextMenuTriggers.push(controlNode.labelElId);

        for (var i in control.enhancements) {
            this.renderEnhancement(control.enhancements[i], controlNode);
        }

    },

    renderEnhancement: function(enhancement, parent) {
        var enhancementNode = new YAHOO.widget.TextNode(
            { label: enhancement, renderHidden: true },
            parent,
            false
        );
        if (this.enhancementContextMenuTriggers == null) {
            this.enhancementContextMenuTriggers = [];
        }
        this.enhancementContextMenuTriggers.push(enhancementNode.labelElId);
    },

    updateContextMenus: function() {
        if (this.controlContextMenu == null) {
            var controlContextMenuItems = [
                "Remove Control",
                "Add Enhancements",
                "Edit Common Control"
            ];
            this.controlContextMenu = new YAHOO.widget.ContextMenu( 
                "controlContextMenu", 
                { 
                    trigger:  this.controlContextMenuTriggers, 
                    lazyload: true,
                    itemData: controlContextMenuItems
                }
            );
        } else {
            this.controlContextMenu.cfg.setProperty("trigger", this.controlContextMenuTriggers);
        }

        if (this.enhancementContextMenu == null) {
            var enhancementContextMenuItems = [
                "Remove Enhancement"
            ];
            this.enhancementContextMenu = new YAHOO.widget.ContextMenu( 
                "enhancementContextMenu", 
                { 
                    trigger:  this.enhancementContextMenuTriggers, 
                    lazyload: true,
                    itemData: enhancementContextMenuItems
                }
            );
        } else {
            this.enhancementContextMenu.cfg.setProperty("trigger", this.enhancementContextMenuTriggers);
        }
    }
};
