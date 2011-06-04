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
     * Adds drag-and-drop functionality to a yui treeview widget
     * 
     * @namespace Fisma
     * @class TreeNodeDragBehavior
     * @extends n/a
     * @constructor
     * @param treeView {YAHOO.widget.TreeView} A tree view widget containing the "element"
     * @param callback {function} This is called when a drag and drop is attempted by the user
     * @param callbackContext {object} The scope that the callback is called from
     * @param element {YAHOO.widget.TreeView} A reference to a tree node that is made draggable 
     */
    var TNDB = function(treeView, callback, callbackContext, element) {
        if (typeof(callback) != "function") {
            throw "The callback parameter must be a function";
        }

        this._dragDropGroup = YAHOO.util.Dom.generateId;
        this._treeView = treeView;
        this._dragFinishedCallback = callback;
        this._dragFinishedCallbackContext = callbackContext;

        TNDB.superclass.constructor.call(this, element, this._dragDropGroup, null);

        // Style the proxy element
        YAHOO.util.Dom.addClass(this.getDragEl(), "treeNodeDragProxy");
    };
    
    YAHOO.lang.extend(TNDB, YAHOO.util.DDProxy, {

        /**
         * The tree view whose behavior is being modified
         * 
         * @property _treeview
         * @type YAHOO.widget.TreeView
         * @protected
         */        
        _treeView: null,

        /**
         * The element which is the target of any current drag/drop operation
         * 
         * @property _currentDragTarget
         * @type HTMLElement
         * @protected
         */                
        _currentDragTarget: null,

        /**
         * Tracks if the current drag was successful across several event handlers
         * 
         * @property _currentDragSuccessful
         * @type boolean
         * @protected
         */                
        _currentDragSuccessful: false,

        /**
         * Unique ID for this drag and drop group
         * 
         * @property _dragDropGroup
         * @type string
         * @protected
         */                
        _dragDropGroup: null,

        /**
         * A callback when the user attempts to move a tree node
         * 
         * @property _dragFinishedCallback
         * @type string
         * @protected
         */                        
        _dragFinishedCallback: null,

        /**
         * The scope for the callback function
         * 
         * @property _dragFinishedCallbackContext
         * @type string
         * @protected
         */                
        _dragFinishedCallbackContext: null,
        
        /**
         * Override DDProxy to handle the start of a drag/drop event
         *
         * @method TreeNodeDragBehavior.startDrag
         * @param event {YAHOO.util.Event} The mousemove event
         * @param id {String} The element id this is hovering over
         */
        startDrag: function (event, id) {
            // Make the dragged proxy look like the source elemnt
            var dragEl = this.getDragEl();
            var clickEl = this.getEl();
    
            dragEl.innerHTML = clickEl.innerHTML;
            YAHOO.util.Dom.setStyle(dragEl, "background", "white");
            YAHOO.util.Dom.setStyle(dragEl, "border", "none");
        },

        /**
         * Override DDProxy to handle the end of a drag/drop event
         *
         * @method TreeNodeDragBehavior.endDrag
         * @param event {YAHOO.util.Event} The mousemove event
         * @param id {String} The element id this is hovering over
         */        
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

        /**
         * Override DDProxy to handle when a drag/drop proxy hovers over a potential target
         *
         * @method TreeNodeDragBehavior.onDragOver
         * @param event {YAHOO.util.Event} The mousemove event
         * @param id {String} The element id this is hovering over
         */
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

            if (dragLocation == TNDB.DRAG_LOCATION.ABOVE) {
                YAHOO.util.Dom.addClass(this._currentDragTarget, 'treeNodeDragAbove');
            } else if (dragLocation == TNDB.DRAG_LOCATION.ONTO) {
                YAHOO.util.Dom.addClass(this._currentDragTarget, 'treeNodeDragOnto');
            } else {
                YAHOO.util.Dom.addClass(this._currentDragTarget, 'treeNodeDragBelow');
            }
        },

        /**
         * Override DDProxy to handle when a drag/drop proxy stops hovering over a potential target
         *
         * @method TreeNodeDragBehavior.onDragOut
         * @param event {YAHOO.util.Event} The mousemove event
         * @param id {String} The element id this is hovering over
         */
        onDragOut: function (event, id) {
            // The drag out event removes provides visual feedback.
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragAbove');
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragOnto');
            YAHOO.util.Dom.removeClass(this._currentDragTarget, 'treeNodeDragBelow');
        },

        /**
         * Override DDProxy to handle when a drag/drop proxy is moused up over a potential target
         *
         * @method TreeNodeDragBehavior.onDragDrop
         * @param event {YAHOO.util.Event} The mousemove event
         * @param id {String} The element id this is hovering over
         */
        onDragDrop: function(event, id) {
            var srcNode = this._treeView.getNodeByElement(this.getEl());
            var destNode = this._treeView.getNodeByElement(document.getElementById(id));
            var dragLocation = this._getDragLocation(id, event);
    
            var success = this._dragFinishedCallback.call(
                this._dragFinishedCallbackContext, 
                this, 
                srcNode, 
                destNode, 
                dragLocation
            );

            this._currentDragSuccessful = success;
        },

        /**
         * Implementers should call this function when they have successfully run their callback
         * 
         * This method will reorder the nodes in the tree view to match the requested drag/drop event
         *
         * @method TreeNodeDragBehavior.onDragOut
         * @param srcNode {YAHOO.util.Event} The tree node being dragged
         * @param destNode {String} The tree node that is being hovered over
         * @param dragLocation {TreeNodeDragBehavior.DRAG_LOCATION} A target location relative to the destination node
         */
        completeDragDrop: function(srcNode, destNode, dragLocation) {
            this._treeView.popNode(srcNode);

            switch (dragLocation) {
                case Fisma.TreeNodeDragBehavior.DRAG_LOCATION.ABOVE:
                    srcNode.insertBefore(destNode);
                    break;
                case Fisma.TreeNodeDragBehavior.DRAG_LOCATION.ONTO:
                    srcNode.appendTo(destNode);
                    break;
                case Fisma.TreeNodeDragBehavior.DRAG_LOCATION.BELOW:
                    srcNode.insertAfter(destNode);
                    break;
            }

            this._treeView.getRoot().refresh();
            
            // YUI discards all the event handlers after refreshing a treeview, so we need to make it
            // draggable all over again.
            Fisma.TreeNodeDragBehavior.makeTreeViewDraggable(
                this._treeView,
                this._dragFinishedCallback,
                this._dragFinishedCallbackContext
            );            
        },

        /**
         * Determines where the user intends to drop the current node, based on where the mouse is relative to the
         * target node.
         *
         * @method TreeNodeDragBehavior.onDragOut
         * @param targetElement {HTMLElement} The DOM element that the mouse is over
         * @param event {YAHOO.util.Event} The mouse event
         * @returns {TreeNodeDragBehavior.DRAG_LOCATION}
         */
        _getDragLocation: function (targetElement, event) {
            var targetRegion = YAHOO.util.Dom.getRegion(targetElement);
            var height = targetRegion.bottom - targetRegion.top;
            var dragVerticalOffset = YAHOO.util.Event.getPageY(event) - targetRegion.top;

            // This ratio indicates how far down the drag was inside the element. This is used for deciding 
            // whether the mouse is near the top, near the bottom, or somewhere in the middle.
            var verticalRatio = dragVerticalOffset / height;

            if (verticalRatio < 0.25) {
                return TNDB.DRAG_LOCATION.ABOVE;
            } else if (verticalRatio < 0.75) {
                return TNDB.DRAG_LOCATION.ONTO;
            } else {
                return TNDB.DRAG_LOCATION.BELOW;
            }
        }
    });

    /**
     * Adds the draggable behavior to an existing tree view
     *
     * @method TreeNodeDragBehavior.onReady
     * @param treeView {YAHOO.widget.TreeView} A tree view widget containing the "element"
     * @param callback {function} This is called when a drag and drop is attempted by the user
     * @param callbackContext {object} The scope that the callback is called from
     * @static
     */
    TNDB.makeTreeViewDraggable = function (treeView, callback, callbackContext) {

        // Get a list of all nodes in the tree
        var nodes = treeView.getNodesBy(function (node) {return true;});

        for (var nodeIndex in nodes) {
            var node = nodes[nodeIndex];

            var yuiNodeDrag = new TNDB(treeView, callback, callbackContext, node.contentElId, this._dragDropGroup);
        }
    };
    
    /**
     * Constants that represent drag targets relative to a destination node
     *
     * @property DRAG_LOCATION
     * @static
     */
    TNDB.DRAG_LOCATION = {
        ABOVE: 0,
        ONTO: 1,
        BELOW: 2
    };

    Fisma.TreeNodeDragBehavior = TNDB;
})();
