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
     * A treeview widget that is specialized for displaying the organization/system hierarchy
     * 
     * @namespace Fisma
     * @class OrganizationTreeView
     * @extends n/a
     * @constructor
     * @param contentDivId {String} The name of the div which will hold this widget
     */
    var OTV = function(contentDivId) {
        this._contentDiv = document.getElementById(contentDivId);
        
        if (YAHOO.lang.isNull(this._contentDiv)) {
            throw "Invalid contentDivId";
        }
        
        this._storage = new Fisma.PersistentStorage("Organization.Tree");
    };

    OTV.prototype = {
        /**
         * The outermost div for this widget (expected to exist on the page already and to be empty)
         * 
         * @type HTMLElement
         * @protected
         */                
        _contentDiv: null,

        /**
         * A YUI tree view widget
         * 
         * @type YAHOO.widget.TreeView
         * @protected
         */                
        _treeView: null,

        /**
         * The div containing the "include disposal systems" checkbox
         * 
         * @type HTMLElement
         * @protected
         */                
        _disposalCheckboxContainer: null,

        /**
         * The checkbox for "include disposal systems"
         * 
         * @type HTMLElement
         * @protected
         */                
        _disposalCheckbox: null,
        
        /**
         * The div containing the loading spinner
         * 
         * @type HTMLElement
         * @protected
         */                
        _loadingContainer: null,        

        /**
         * The container div that YUI renders the tree view into
         * 
         * @type HTMLElement
         * @protected
         */                
        _treeViewContainer: null,

        /**
         * A modal dialog used to keep the user from modifying the tree while it's changes are being sychronized to 
         * the server.
         * 
         * Also used to display errors if a save operation fails.
         * 
         * @type YAHOO.widget.Panel
         * @protected
         */                        
        _savePanel: null,

        /**
         * Persistent storage for some of the features in this widget
         * 
         * @type Fisma.PersistentStorage
         * @protected
         */                
        _storage: null,

        /**
         * Render the entire widget
         *
         * @method OrganizationTreeView.render
         */
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

        /**
         * Render the "include disposal systems" interface
         *
         * @method OrganizationTreeView._renderDisposalCheckbox
         * @param container {HTMLElement} The container that the checkbox is rendered into
         */
        _renderDisposalCheckbox: function (container) {
            this._disposalCheckbox = document.createElement("input");
            this._disposalCheckbox.type = "checkbox";
            this._disposalCheckbox.checked = this._storage.get("includeDisposalSystem");
            YAHOO.util.Dom.generateId(this._disposalCheckbox);
            YAHOO.util.Event.addListener(
                this._disposalCheckbox, 
                "click", 
                this._handleDisposalCheckboxAction, 
                this, 
                true
            );
            container.appendChild(this._disposalCheckbox);
            
            var label = document.createElement("label");
            label.setAttribute("for", this._disposalCheckbox.id);
            label.appendChild(document.createTextNode("Display Disposed Systems"));
            container.appendChild(label);
            
            container.setAttribute("class", "showDisposalSystem");
        },
       
        /**
         * Render the loading spinner
         *
         * @method OrganizationTreeView._renderLoading
         * @param container {HTMLElement} The container that the checkbox is rendered into
         */
        _renderLoading: function (container) {
            var loadingImage = document.createElement("img");
            loadingImage.src = "/images/spinners/small.gif";

            container.style.display = "none";
            container.appendChild(loadingImage);
        },

        /**
         * Show the loading spinner
         *
         * @method OrganizationTreeView._showLoadingImage
         */
        _showLoadingImage: function () {
            this._loadingContainer.style.display = "block";
        },

        /**
         * Show the loading spinner
         *
         * @method OrganizationTreeView._hideLoadingImage
         */        
        _hideLoadingImage: function () {
            this._loadingContainer.style.display = "none";    
        },

        /**
         * Set the user preference for the include disposal system checkbox and re-render the tree view
         *
         * @method OrganizationTreeView._handleDisposalCheckboxAction
         * @param event {YAHOO.util.Event} The mouse event
         */
        _handleDisposalCheckboxAction: function (event) {
            this._storage.set("includeDisposalSystem", this._disposalCheckbox.checked);
            this._renderTreeView();
        },

        /**
         * Render the treeview itself
         *
         * @method OrganizationTreeView._renderTreeView
         * @param container {HTMLElement} The container that the checkbox is rendered into
         */
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
                            this
                        );

                        // Expand the first two levels of the tree by default
                        var defaultExpandNodes = this._treeView.getNodesBy(function (node) {return node.depth < 2;});
                        $.each(defaultExpandNodes, function (key, node) {node.expand();});

                        this._treeView.draw();
                        this._buildContextMenu();
                        this._hideLoadingImage();
                    },
                    failure: function (response) {
                        var alertMessage = 'Unable to load the organization tree: ' + response.statusText;
                        Fisma.Util.showAlertDialog(alertMessage);
                    },
                    scope: this
                }, 
                null
            );
        },

        /**
         * Load the given nodes into a treeView.
         * 
         * This function is recursive, so the first time it's called, you need to pass in the root node of the tree
         * view.
         *
         * @method OrganizationTreeView._buildTreeNodes
         * @param nodeList {Array} Nested array of organization/system data to load into the tree view
         * @param nodeList {YAHOO.widget.Node} The tree node that is the parent to the nodes you want to create
         */
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

        /**
         * Expand all nodes in the tree
         *
         * @method OrganizationTreeView.expandAll
         */        
        expandAll: function () {
            this._treeView.getRoot().expandAll();
        },

        /**
         * Collapse all nodes in the tree
         *
         * @method OrganizationTreeView.collapseAll
         */                
        collapseAll: function () {
            this._treeView.getRoot().collapseAll();
        },

        /**
         * A callback that handles the drag/drop operation by synchronized the user's action with the server.
         * 
         * A modal dialog is used to prevent the user from performing more drag/drops while the current one is still
         * being synchronized.
         *
         * @method OrganizationTreeView.handleDragDrop
         * @param treeNodeDragBehavior {TreeNodeDragBehavior} A reference to the caller
         * @param srcNode {YAHOO.widget.Node} The tree node that is being dragged
         * @param destNode {YAHOO.widget.Node} The tree node that the source is being dropped onto
         * @param dragLocation {TreeNodeDragBehavior.DRAG_LOCATION} The drag target relative to destNode
         */        
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

            this._savePanel.setBody('<img src="/images/loading_bar.gif">');
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
        },

        /**
         * Display an error message using the save panel.
         * 
         * Notice that this assumes the save panel is already displayed (because it's usually used to display
         * error messages related to saving).
         *
         * @method OrganizationTreeView._displayDragDropError
         * @param message {String} The error message to display
         */        
        _displayDragDropError: function (message) {
            var alertDiv = document.createElement("div");

            var p1 = document.createElement("p");
            p1.appendChild(document.createTextNode(message));

            var p2 = document.createElement("p");

            var that = this;
            var button = new YAHOO.widget.Button({
                label: "OK",
                container: p2,
                onclick: {
                    fn: function () {that._savePanel.hide();}
                }
            });
            
            alertDiv.appendChild(p1);
            alertDiv.appendChild(p2);

            this._savePanel.setBody(alertDiv);
        },

        /**
         * Add the context menu behavior to the tree view
         *
         * @method OrganizationTreeView._buildContextMenu
         */                
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

        /**
         * A callback for context menu events
         *
         * @method OrganizationTreeView._contextMenuHandler
         * @param event {String} The name of the event
         * @param eventArgs {Array} An array of YAHOO.util.Event
         */                
        _contextMenuHandler: function (event, eventArgs) {
            var targetElement = eventArgs[1].parent.contextEventTarget;
            var targetNode = this._treeView.getNodeByElement(targetElement);
        
            // Create a request URL to view this object
            var url;
            var type = targetNode.data.type;

            if (type == 'agency' || type == 'bureau' || type == 'organization') {
                url = '/organization/view/id/' + targetNode.data.organizationId;
            } else {
                url = '/system/view/id/' + targetNode.data.systemId;                
            }

            window.location = url;
        }
    };

    Fisma.OrganizationTreeView = OTV;
})();
