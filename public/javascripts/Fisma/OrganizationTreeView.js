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
    };

    OTV.prototype = {
        _contentDiv: null,
        
        _treeView: null,

        _disposalCheckboxContainer: null,
        _loadingContainer: null,        
        _treeViewContainer: null,
        
        _showDisposedSystems: false,

        // These constants are used to track whether a node is being dragged above, onto, or below another node
        _currentDragDestination: null,

        render: function () {

            this._disposalCheckboxContainer = document.createElement("div");
            this._renderDisposalCheckbox(this._disposalCheckboxContainer);
            this._contentDiv.appendChild(this._disposalCheckboxContainer);
            
            this._loadingContainer = document.createElement("div");
            this._renderLoading(this._loadingContainer);
            this._contentDiv.appendChild(this._loadingContainer);

            this._treeViewContainer = document.createElement("div");
            this._renderTreeView(this._treeViewContainer);
            this._contentDiv.appendChild(this._treeViewContainer);

//            this.addContextMenu();
        },

        _renderDisposalCheckbox: function (container) {
            var checkbox = document.createElement("input");
            checkbox.type = "checkbox";
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
            this._showDisposedSystems = event.toElement.checked;
            this._renderTreeView();
        },
        
        _renderTreeView: function (container) {
            this._showLoadingImage();

            var url = '/organization/tree-data/format/json';

            if (this._showDisposedSystems) {
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
                        Fisma.TreeNodeDragBehavior.makeTreeViewDraggable(this._treeView);

                        // Expand the first two levels of the tree by default
                        var defaultExpandNodes = this._treeView.getNodesBy(function (node) {return node.depth < 2});
                        defaultExpandNodes.map(function (node) {node.expand()});

                        this._treeView.draw();
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

                // Set the background color of disposal systems as pink 
                var sdlcPhase = (node.System) ? node.System.sdlcPhase : false;
                if (sdlcPhase === 'disposal') {
                    node.labelStyle = node.orgType + ' disposal';
                } else {
                    node.labelStyle = node.orgType;
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
        }
    };

    Fisma.OrganizationTreeView = OTV;
})();
