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
     * Provides basic session-level storage of data.
     * @namespace Fisma
     * @class OrganizationTreeView
     * @todo TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO 
     * @constructor
     */
    var OTV = function(contentDivId) {
        this._contentDiv = document.getElementById(contentDivId);
        
        if (YAHOO.lang.isNull(this._contentDiv)) {
            throw "Invalid contentDivId";
        }
        
        this._storage = new Fisma.PersistentStorage("Organization.Tree");
    };

    OTV.prototype = {
        _contentDiv: null,
        
        _treeView: null,

        _disposalCheckboxContainer: null,
        _loadingContainer: null,        
        _treeViewContainer: null,
        
        _dragDropGroupName: "organizationTreeDragDropGroup",
        
        _savePanel: null,
        
        _storage: null,

        // These constants are used to track whether a node is being dragged above, onto, or below another node
        _currentDragDestination: null,

        render: function () {
            var that = this;

            // We need storage before we can render anything
            Fisma.Storage.onReady(function () {
                that._disposalCheckboxContainer = document.createElement("div");
                that._renderDisposalCheckbox(that._disposalCheckboxContainer);
                that._contentDiv.appendChild(that._disposalCheckboxContainer);

                that._loadingContainer = document.createElement("div");
                that._renderLoading(that._loadingContainer);
                that._contentDiv.appendChild(that._loadingContainer);

                that._treeViewContainer = document.createElement("div");
                that._renderTreeView(that._treeViewContainer);
                that._contentDiv.appendChild(that._treeViewContainer);                
            });
        },

        _renderDisposalCheckbox: function (container) {
            var checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.checked = this._storage.get("includeDisposalSystem");
            YAHOO.util.Dom.generateId(checkbox);
            YAHOO.util.Event.addListener(checkbox, "click", this._handleDisposalCheckboxAction, this, true);
            container.appendChild(checkbox);
            
            var label = document.createElement("label");
            label.setAttribute("for", checkbox.id);
            label.appendChild(document.createTextNode("Display Disposed Systems"));
            container.appendChild(label);
            
            container.setAttribute("class", "showDisposalSystem");
        },
       
        _renderLoading: function (container) {
            var loadingImage = document.createElement("img");
            loadingImage.src = "/images/spinners/small.gif";

            container.style.display = "none";
            container.appendChild(loadingImage);
        },

        _showLoadingImage: function () {
            this._loadingContainer.style.display = "block";
        },
        
        _hideLoadingImage: function () {
            this._loadingContainer.style.display = "none";    
        },
        
        _handleDisposalCheckboxAction: function (event) {
            this._storage.set("includeDisposalSystem", event.toElement.checked);
            this._renderTreeView();
        },
        
        _renderTreeView: function (container) {
            this._showLoadingImage();

            var url = '/organization/tree-data/format/json';

            if (this._storage.get("includeDisposalSystem") === true) {
                url += '/displayDisposalSystem/true';
            }

            YAHOO.util.Connect.asyncRequest(
                'GET', 
                url, 
                {
                    success: function (response) {
                        var json = YAHOO.lang.JSON.parse(response.responseText);

                        // Load the tree data into a tree view
                        this._treeView = new YAHOO.widget.TreeView(this._treeViewContainer);
                        this._buildTreeNodes(json.treeData, this._treeView.getRoot());
                        Fisma.TreeNodeDragBehavior.makeTreeViewDraggable(
                            this._treeView,
                            this.handleDragDrop,
                            this,
                            this._dragDropGroupName
                        );

                        // Expand the first two levels of the tree by default
                        var defaultExpandNodes = this._treeView.getNodesBy(function (node) {return node.depth < 2});
                        defaultExpandNodes.map(function (node) {node.expand()});

                        this._treeView.draw();
                        this._buildContextMenu();
                        this._hideLoadingImage();
                    },
                    failure: function (response) {
                        alert('Unable to load the organization tree: ' + response.statusText);
                    },
                    scope: this
                }, 
                null
            );
        },
        
        _buildTreeNodes: function (nodeList, parent) {

            for (var i in nodeList) {
                var node = nodeList[i];
                var nodeText = "<b>" + PHP_JS().htmlspecialchars(node.label) + "</b> - <i>"
                                 + PHP_JS().htmlspecialchars(node.orgTypeLabel) + "</i>";

                var yuiNode = new YAHOO.widget.TextNode(
                    {
                        label: nodeText,
                        organizationId: node.id,
                        type: node.orgType,
                        systemId: node.systemId
                    }, 
                    parent,
                    false
                );

                // Set the label style
                yuiNode.labelStyle = node.orgType;

                var sdlcPhase = YAHOO.lang.isUndefined(node.System) ? false : node.System.sdlcPhase;
                if (sdlcPhase === 'disposal') {
                    yuiNode.labelStyle += " disposal";
                }

                // Recurse
                if (node.children.length > 0) {
                    this._buildTreeNodes(node.children, yuiNode);
                }
            }
        },

        expandAll: function () {
            this._treeView.getRoot().expandAll();
        },
        
        collapseAll: function () {
            this._treeView.getRoot().collapseAll();
        },
        
        handleDragDrop: function (treeNodeDragBehavior, srcNode, destNode, dragLocation) {
            // Set up the GET query string for this operation
            var query = '/organization/move-node/src/' 
                      + srcNode.data.organizationId 
                      + '/dest/' 
                      + destNode.data.organizationId 
                      + '/dragLocation/' 
                      + dragLocation;
    
            // Show a modal panel while waiting for the operation to complete. This is a bit ugly for usability,
            // but it prevents the user from modifying the tree while an update is already pending.
            if (YAHOO.lang.isNull(this._savePanel)) {
                this._savePanel = new YAHOO.widget.Panel(
                    "savePanel",
                    {
                        width: "250px",
                        fixedcenter: true,
                        close: false,
                        draggable: false,
                        modal: true,
                        visible: true
                    }
                );                

                this._savePanel.setHeader('Saving...');
                this._savePanel.render(document.body);
            }

            this._savePanel.setBody('<img src="/images/loading_bar.gif">')
            this._savePanel.show();
    
            YAHOO.util.Connect.asyncRequest(
                'GET', 
                query, 
                {
                    success: function (event) {
                        var result = YAHOO.lang.JSON.parse(event.responseText);

                        if (result.success) {
                            treeNodeDragBehavior.completeDragDrop(srcNode, destNode, dragLocation);
                            
                            // Moving elements in a YUI tree destroys their event listeners, so we have to re-add
                            // the context menu listener
                            this._buildContextMenu();
                            
                            this._savePanel.hide();
                        } else {
                            this._displayDragDropError("Error: " + result.message);
                        }
                    },
                    failure: function (event) {
                        this._displayDragDropError(
                            'Unable to reach the server to save your changes: ' + event.statusText
                        );
                        this._savePanel.hide();
                    },
                    scope: this
                }, 
                null
            );
            
            return true;
        },
        
        _displayDragDropError: function (message) {
            var alertDiv = document.createElement("div");

            var p1 = document.createElement("p");
            p1.appendChild(document.createTextNode(message));

            var p2 = document.createElement("p");
            var button = new YAHOO.widget.Button({
                label: "OK",
                container: p2,
                onclick: {
                    fn: function () {this._savePanel.hide();}
                }
            });
            
            alertDiv.appendChild(p1);
            alertDiv.appendChild(p2);

            this._savePanel.setBody(alertDiv);
        },
        
        _buildContextMenu: function () {
            var contextMenuItems = ["View"];

            var treeNodeContextMenu = new YAHOO.widget.ContextMenu(
                YAHOO.util.Dom.generateId(),
                { 
                    trigger: this._treeView.getEl(),
                    itemdata: contextMenuItems,
                    lazyload: true
                }
            );

            treeNodeContextMenu.subscribe("click", this._contextMenuHandler, this, true);
        },

        _contextMenuHandler: function (event, eventArgs) {
            var targetElement = eventArgs[1].parent.contextEventTarget;
            var targetNode = this._treeView.getNodeByElement(targetElement);
        
            // Create a request URL to view this object
            var url;
            var type = targetNode.data.type;

            if (type == 'agency' || type == 'bureau' || type == 'organization') {
                var url = '/organization/view/id/' + targetNode.data.organizationId;
            } else {
                var url = '/system/view/id/' + targetNode.data.systemId;                
            }

            window.location = url;
        }
    };

    Fisma.OrganizationTreeView = OTV;
})();
