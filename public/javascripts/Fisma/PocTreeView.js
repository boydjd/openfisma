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
     * A treeview widget that is specialized for displaying the POC hierarchy
     *
     * @namespace Fisma
     * @class PocTreeView
     * @extends n/a
     * @constructor
     * @param contentDivId {String} The name of the div which will hold this widget
     */
    var PTV = function(contentDivId) {
        this._contentDiv = document.getElementById(contentDivId);

        if (YAHOO.lang.isNull(this._contentDiv)) {
            throw "Invalid contentDivId";
        }

        this._storage = new Fisma.PersistentStorage("Poc.Tree");
    };

    PTV.prototype = {
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

            that._loadingContainer = document.createElement("div");
            that._renderLoading(that._loadingContainer);
            that._contentDiv.appendChild(that._loadingContainer);

            that._treeViewContainer = document.createElement("div");
            that._renderTreeView(that._treeViewContainer);
            that._contentDiv.appendChild(that._treeViewContainer);
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
         * Render the treeview itself
         *
         * @method OrganizationTreeView._renderTreeView
         * @param container {HTMLElement} The container that the checkbox is rendered into
         */
        _renderTreeView: function (container) {
            this._showLoadingImage();

            var url = '/poc/tree-data/format/json';

            YAHOO.util.Connect.asyncRequest(
                'GET',
                url,
                {
                    success: function (response) {
                        var json = YAHOO.lang.JSON.parse(response.responseText);
                        if (json.treeData.length > 0) {
                            // Set up callbacks for the tree drag-n-drop behavior
                            var callbacks = {
                                dragFinished: {
                                    fn: this.handleDragDrop,
                                    context: this
                                },
                                testDragTargetDelegate: {
                                    fn: this.testDragTarget,
                                    context: this
                                }
                            };

                            // Load the tree data into a tree view
                            this._treeView = new YAHOO.widget.TreeView(this._treeViewContainer);
                            this._buildTreeNodes(json.treeData, this._treeView.getRoot());
                            Fisma.TreeNodeDragBehavior.makeTreeViewDraggable(
                                this._treeView,
                                callbacks,
                                this
                            );

                            // Expand the first two levels of the tree by default
                            var defaultExpandNodes = this._treeView.getNodesBy(function (node) {return node.depth < 2;});
                            $.each(defaultExpandNodes, function (key, node) {node.expand();});

                            this._treeView.draw();
                        }

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
         * @param parent {YAHOO.widget.Node} The tree node that is the parent to the nodes you want to create
         */
        _buildTreeNodes: function (nodeList, parent) {

            for (var i in nodeList) {
                var node = nodeList[i];
                var yuiNode;

                if (node.hasOwnProperty('id')) {
                    yuiNode = this._buildOrgNode(node, parent);
                } else {
                    yuiNode = this._buildPocNode(node, parent);
                }

                // Recurse
                if (YAHOO.lang.isArray(node.children) && node.children.length > 0) {
                    this._buildTreeNodes(node.children, yuiNode);
                }
            }
        },

        /**
         * Create a node that represents an organization.
         *
         * @param node {Object} Dictionary of node data
         * @param parent {YAHOO.widget.Node} The tree node that is the parent to the node you want to create
         * @return YAHOO.widget.Node
         */
        _buildOrgNode: function (node, parent) {
            var imageUrl = "/icon/get/id/" + node.iconId + "/size/small";
            var nodeText = "<img src='"
                         + imageUrl
                         + "'>&nbsp;<b>"
                         + PHP_JS().htmlspecialchars(node.label)
                         + "</b> - <i>"
                         + PHP_JS().htmlspecialchars(node.orgTypeLabel)
                          + "</i>";

            var yuiNode = new YAHOO.widget.HTMLNode(
                {
                    html: nodeText,
                    organizationId: node.id,
                    type: node.orgType,
                    systemId: node.systemId
                },
                parent,
                false
            );

            return yuiNode;
        },

        /**
         * Create a node that represents a POC.
         *
         * @param node {Object} Dictionary of node data
         * @param parent {YAHOO.widget.Node} The tree node that is the parent to the node you want to create
         * @return YAHOO.widget.Node
         */
        _buildPocNode: function (node, parent) {
            var imageUrl = "/images/poc-small.png";
            var nodeText = "<img src='"
                         + imageUrl
                         + "'>&nbsp;<b>"
                         + node.p_nameFirst
                         + " "
                         + node.p_nameLast
                         + " ("
                         + node.p_username
                         + ")</b> - <i>Point of Contact</i>";

            var yuiNode = new YAHOO.widget.HTMLNode(
                {
                    html: nodeText,
                    pocId: node.p_id
                },
                parent,
                false
            );

            return yuiNode;
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
            // Show a modal panel while waiting for the operation to complete. This is a bit ugly for usability,
            // but it prevents the user from modifying the tree while an update is already pending.
            if (YAHOO.lang.isNull(this._savePanel)) {
                this._savePanel = new YAHOO.widget.Panel(
                    YAHOO.util.Dom.generateId(),
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

            // Set up the POST data string for this operation
            var destination = (YAHOO.lang.isValue(destNode.data.pocId))
                            ? ('&destPoc=' + destNode.data.pocId)
                            : ('&destOrg=' + destNode.data.organizationId);

            var query = '/poc/move-node/';
            var postData = 'src=' + srcNode.data.pocId + destination + '&dragLocation=' + dragLocation
                           + '&csrf=' + $('[name="csrf"]').val();

            YAHOO.util.Connect.asyncRequest(
                'POST',
                query,
                {
                    success: function (event) {
                        var result = YAHOO.lang.JSON.parse(event.responseText);

                        if (result.success) {
                            treeNodeDragBehavior.completeDragDrop(srcNode, destNode, dragLocation);

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
                postData
            );
        },

        /**
         * Determines whether the source node is eligible to be drag-and-dropped onto the destination node.
         *
         * @param srcNode {YAHOO.widget.Node} The tree node that is being dragged
         * @param destNode {YAHOO.widget.Node} The tree node that the source is being dropped onto
         * @param dragLocation {Fisma.TreeNodeDragBehavior.DRAG_LOCATION}
         * @return bool
         */
        testDragTarget: function (srcNode, destNode, dragLocation) {

            // Reject a drag/drop if the source is not a POC
            if (!YAHOO.lang.isValue(srcNode.data.pocId)) {
                return false;
            }

            // Reject a drag/drop onto a POC node (but accept above and below a POC node)
            if (YAHOO.lang.isValue(destNode.data.pocId)
                && dragLocation === Fisma.TreeNodeDragBehavior.DRAG_LOCATION.ONTO) {

                return false;
            }

            return true;
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
        }
    };

    Fisma.PocTreeView = PTV;
})();
