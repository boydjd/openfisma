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
     * @class TreeNodeDragBehavior
     * @todo TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO 
     * @constructor
     */
    var TNDB = function(treeView, element, dragDropGroup) {
        this._dragDropGroup = dragDropGroup;
        this._treeView = treeView;
        
        TNDB.superclass.constructor.call(this, element, this._dragDropGroup, null);

        // Style the proxy element
        YAHOO.util.Dom.addClass(this.getDragEl(), "treeNodeDragProxy");
    };
    
    YAHOO.lang.extend(TNDB, YAHOO.util.DDProxy, {
        
        _treeView: null,
        
        _currentDragTarget: null,

        _currentDragSuccessful: false,

        _dragDropGroup: null,

        DRAG_LOCATION: {
            ABOVE: 0,
            ONTO: 1,
            BELOW: 2
        },
        
        startDrag: function (event, id) {
            // Make the dragged proxy look like the source elemnt
            var dragEl = this.getDragEl();
            var clickEl = this.getEl();
    
            dragEl.innerHTML = clickEl.innerHTML;
            YAHOO.util.Dom.setStyle(dragEl, "background", "white");
            YAHOO.util.Dom.setStyle(dragEl, "border", "none");
        },
        
        endDrag: function (event, id) {
            var srcEl = this.getEl();
            var proxy = this.getDragEl();

            // Remove any visual highlighting
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragAbove');
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragOnto');
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragBelow');
    
            if (!this._currentDragSuccessful) {
                // Animate the proxy element returning to its origin
                YAHOO.util.Dom.setStyle(proxy, "visibility", "");
                var anim = new YAHOO.util.Motion(
                    proxy, 
                    { points: { to: YAHOO.util.Dom.getXY(srcEl) } },
                    0.2,
                    YAHOO.util.Easing.easeOut
                );
            
                // Hide the proxy element when the animation finishes
                anim.onComplete.subscribe(function () {
                    YAHOO.util.Dom.setStyle(proxy.id, "visibility", "hidden");
                });
                anim.animate();
                this._currentDragSuccessful = false;
            }
        },
        
        onDragOver: function (event, id) {
            var dragLocation = this._getDragLocation(id, event);
            
            /* If the drag is near the top of the element, then we set the top border. 
             * If its near the middle, we highlight the entire element. If its near the
             * bottom, we set the bottom border.
             */
            this._currentDragTarget = YAHOO.util.Dom.get(id);   

            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragAbove');
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragOnto');
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragBelow');

            if (dragLocation == this.DRAG_LOCATION.ABOVE) {
                YAHOO.util.Dom.addClass(this._currentDragTarget, 'treeNodeDragAbove');
            } else if (dragLocation == this.DRAG_LOCATION.ONTO) {
                YAHOO.util.Dom.addClass(this._currentDragTarget, 'treeNodeDragOnto');
            } else {
                YAHOO.util.Dom.addClass(this._currentDragTarget, 'treeNodeDragBelow');
            }
        },
        
        onDragOut: function (event, id) {
            // The drag out event removes provides visual feedback.
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragAbove');
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragOnto');
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragBelow');
        },
        
        // The drag drop event sends an asynchronous event to the server to update the persistent
        // storage of the tree. 
        onDragDrop: function(event, id) {
// TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO
            var srcNode = this._treeView.getNodeByElement(this.getEl());
            var destNode = this._treeView.getNodeByElement(document.getElementById(id));
            var dragLocation = this._getDragLocation(id, event);
    
            // Set up the query string for this operation
            var query = '/organization/move-node/src/' 
                      + srcNode.data.organizationId 
                      + '/dest/' 
                      + destNode.data.organizationId 
                      + '/dragLocation/' 
                      + dragLocation;
    
            // Show a modal panel while waiting for the operation to complete. This is a bit ugly for usability,
            // but it prevents the user from modifying the tree while an update is already pending.
            savePanel = new YAHOO.widget.Panel(
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
            savePanel.setHeader('Saving...');
            savePanel.setBody('<img src="/images/loading_bar.gif">')
            savePanel.render(document.body);
    
            YAHOO.util.Connect.asyncRequest(
                'GET', 
                query, 
                {
                    success: function (event) {
                        var result = YAHOO.lang.JSON.parse(event.responseText);
                        if (!result.success) {
                            var alertDiv = document.createElement("div");
    
                            var p1 = document.createElement("p");
                            p1.appendChild(document.createTextNode("Error: " + result.message));
    
                            var p2 = document.createElement("p");
                            var button = new YAHOO.widget.Button({
                                label: "OK",
                                container: p2,
                                onclick: {
                                    fn: function () {savePanel.hide();}
                                }
                            });
                            
                            alertDiv.appendChild(p1);
                            alertDiv.appendChild(p2);
    
                            savePanel.setBody(alertDiv);
                        } else {
                            this._treeView.popNode(srcNode);
    
                            switch (dragLocation) {
                                case this.DRAG_LOCATION.ABOVE:
                                    srcNode.insertBefore(destNode);
                                    break;
                                case this.DRAG_LOCATION.ONTO:
                                    srcNode.appendTo(destNode);
                                    break;
                                case this.DRAG_LOCATION.BELOW:
                                    srcNode.insertAfter(destNode);
                                    break;
                            }
    
                            this._treeView.getRoot().refresh();
                            
                            // YUI discards all the event handlers after refreshing a treeview, so we need to make it
                            // draggable all over again.
                            TNDB.makeTreeViewDraggable(this._treeView);
    
                            savePanel.hide();
                        }
                    },
                    failure: function (event) {
                        alert('Unable to reach the server to save your changes: ' 
                              + event.statusText);
                        savePanel.hide();
                    },
                    scope: this
                }, 
                null
            );
    
            this._currentDragSuccessful = true; 
        },

        _getDragLocation: function (targetElement, event) {
            var targetRegion = YAHOO.util.Dom.getRegion(targetElement);
            var height = targetRegion.bottom - targetRegion.top;
            var dragVerticalOffset = YAHOO.util.Event.getPageY(event) - targetRegion.top;

            // This ratio indicates how far down the drag was inside the element. This is used for deciding 
            // whether the mouse is near the top, near the bottom, or somewhere in the middle.
            var verticalRatio = dragVerticalOffset / height;

            if (verticalRatio < 0.25) {
                return this.DRAG_LOCATION.ABOVE;
            } else if (verticalRatio < 0.75) {
                return this.DRAG_LOCATION.ONTO;
            } else {
                return this.DRAG_LOCATION.BELOW;
            }
        }
    });

    // static
    TNDB.makeTreeViewDraggable = function (treeView, dragDropGroup) {

        // Get a list of all nodes in the tree
        var nodes = treeView.getNodesBy(function (node) {return true;});

        for (var nodeIndex in nodes) {
            var node = nodes[nodeIndex];

            var yuiNodeDrag = new TNDB(treeView, node.contentElId, dragDropGroup);
        }
    }

    Fisma.TreeNodeDragBehavior = TNDB;
})();
