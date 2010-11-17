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
Fisma.ControlTree = function (treeElem, actionUrls) {
    this.treeElem = treeElem;
    this.actionUrls = actionUrls;

    var ctObj = this;
    YAHOO.util.Connect.asyncRequest(
        'GET', 
        this.actionUrls.controlTreeData, 
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
    actionUrls: null,
    
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
        if (control.common) {
            label += " (Common)";
        } else if (control.inherits != null) {
            label += " (Inherits From " + control.inherits + ")";
        }
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
        var props = {
            label: enhancement.description,
            renderHidden: true,
            securityControlEnhancementId: enhancement.id
        };
        var enhancementNode = new YAHOO.widget.TextNode( props, parent, false);
        if (this.enhancementContextMenuTriggers == null) {
            this.enhancementContextMenuTriggers = [];
        }
        this.enhancementContextMenuTriggers.push(enhancementNode.labelElId);
    },

    updateContextMenus: function() {
        if (this.controlContextMenu == null) {
            var controlContextMenuItems = [
                { text: "Remove Control", value: "removeControl" },
                { text: "Add Enhancements", value: "addEnhancements" },
                { text: "Edit Common Control", value: "editCommonControl" }
            ];
            this.controlContextMenu = new YAHOO.widget.ContextMenu( 
                "controlContextMenu", 
                { 
                    trigger:  this.controlContextMenuTriggers, 
                    lazyload: true,
                    itemData: controlContextMenuItems
                }
            );
            this.controlContextMenu.subscribe("click", this.onContextMenuClick, this); 
        } else {
            this.controlContextMenu.cfg.setProperty("trigger", this.controlContextMenuTriggers);
        }

        if (this.enhancementContextMenu == null) {
            var enhancementContextMenuItems = [
                { text: "Remove Enhancement", value: "removeEnhancement" }
            ];
            this.enhancementContextMenu = new YAHOO.widget.ContextMenu( 
                "enhancementContextMenu", 
                { 
                    trigger:  this.enhancementContextMenuTriggers, 
                    lazyload: true,
                    itemData: enhancementContextMenuItems
                }
            );
            this.enhancementContextMenu.subscribe("click", this.onContextMenuClick, this); 
        } else {
            this.enhancementContextMenu.cfg.setProperty("trigger", this.enhancementContextMenuTriggers);
        }
    },

    onContextMenuClick: function (p_sType, p_aArgs, p_oValue) {
        var controlTree = p_oValue,
            targetNode = controlTree.treeView.getNodeByElement(this.contextEventTarget),
            menuItem = p_aArgs[1];

        switch (menuItem.value) {
            case "removeControl":
                controlTree.removeControl(targetNode);
                break;
            case "addEnhancements":
                controlTree.addEnhancements(targetNode);
                break;
            case "removeEnhancement":
                controlTree.removeEnhancement(targetNode);
                break;
            case "editCommonControl":
                controlTree.editCommonControl(targetNode);
                break;
            default:
                alert("Action not yet implemented.");
        }
    },

    removeControl: function (node) {
        var securityControlId = node.data.securityControlId,
            actionUrl = this.actionUrls.removeControl,
            post = "securityControlId=" + securityControlId,
            parentNode = node.parent,
            ctObj = this;
        var callbacks = {
            success: function(o) {
                var json = YAHOO.lang.JSON.parse(o.responseText);
                if (json.result == 'ok') {
                    ctObj.treeView.removeNode(node, true);
                    ctObj.updateContextMenus();
                } else {
                    alert(json.result);
                }
            },
            failure: function(o) {
                alert('Unable to remove the control tree: ' + o.statusText);
            }
        };
        YAHOO.util.Connect.asyncRequest( 'POST', actionUrl, callbacks, post);
    },

    removeEnhancement: function (node) {
        var securityControlEnhancementId = node.data.securityControlEnhancementId,
            actionUrl = this.actionUrls.removeEnhancement,
            post = "securityControlEnhancementId=" + securityControlEnhancementId,
            ctObj = this;
        var callbacks = {
            success: function(o) {
                var json = YAHOO.lang.JSON.parse(o.responseText);
                if (json.result == 'ok') {
                    ctObj.treeView.removeNode(node, true);
                    ctObj.updateContextMenus();
                } else {
                    alert(json.result);
                }
            },
            failure: function(o) {
                alert('Unable to remove the control tree: ' + o.statusText);
            }
        };
        YAHOO.util.Connect.asyncRequest( 'POST', actionUrl, callbacks, post);
    },

    addEnhancements: function(controlNode) {
        var securityControlId = controlNode.data.securityControlId,
            panel = Fisma.HtmlPanel.showPanel("Add Security Control", null, null, { modal : true }),
            actionUrl = this.actionUrls.addEnhancements,
            getUrl = actionUrl + '/securityControlId/' + securityControlId,
            ctObj = this;
        var callbacks = {
            success: function(o) {
                var panel = o.argument;
                panel.setBody(o.responseText);
                panel.center();
            },
            failure: function(o) {
                var panel = o.argument;
                panel.destroy();
                alert('Error getting "add control" form: ' + o.statusText);
            },
            argument: panel
        };
        YAHOO.util.Connect.asyncRequest( 'GET', getUrl, callbacks);
    },


    editCommonControl: function(controlNode) {
        var securityControlId = controlNode.data.securityControlId,
            panel = Fisma.HtmlPanel.showPanel("Edit Common Security Control", null, null, { modal : true }),
            actionUrl = this.actionUrls.editCommonControl,
            getUrl = actionUrl + '/securityControlId/' + securityControlId,
            ctObj = this;
        var callbacks = {
            success: function(o) {
                var panel = o.argument;
                panel.setBody(o.responseText);
                panel.center();
            },
            failure: function(o) {
                var panel = o.argument;
                panel.destroy();
                alert('Error getting "edit common control" form: ' + o.statusText);
            },
            argument: panel
        };
        YAHOO.util.Connect.asyncRequest( 'GET', getUrl, callbacks);
    }

};
