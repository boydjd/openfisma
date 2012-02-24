/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * @fileoverview This file contains the model for EACH ITEM in an InteractiveOrderedListItem
 *
 * @author    Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */
/**
 * Constructor for an item of the InterativeOrderedList
 *
 * @param (string) id               The id of the <li> element
 * @param (string) listName         The name of the list this item belongs to
 * @param (array)  extraHandlers    An array of functions to be appended to event handler
 * @param (object) config           The default config object for YAHOO.util.DDProxy
 */
Fisma.InteractiveOrderedListItem = function(id, listName, extraHandlers, config) {
    // Call default constructors
    Fisma.InteractiveOrderedListItem.superclass.constructor.call(this, id, listName, config);

    // Beautify the proxy element
    YAHOO.util.Dom.setStyle(this.getDragEl(), "opacity", 0.67);

    // Assign extra config data
    this.extraHandlers = extraHandlers;
    this.goingUp = false;
    this.lastY = 0;
};

YAHOO.extend(Fisma.InteractiveOrderedListItem, YAHOO.util.DDProxy, {
    startDrag: function(x, y) {
        // make the proxy look like the source element
        var dragEl = this.getDragEl();
        var clickEl = this.getEl();
        YAHOO.util.Dom.setStyle(clickEl, "visibility", "hidden");

        dragEl.innerHTML = clickEl.innerHTML;

        this.nextId = jQuery(clickEl).next('li').attr('id');
    },

    endDrag: function(e) {
        var srcEl = this.getEl();
        var proxy = this.getDragEl();

        // Show the proxy element and animate it to the src element's location
        YAHOO.util.Dom.setStyle(proxy, "visibility", "");
        var animator = new YAHOO.util.Motion(
            proxy, {
                points: {
                    to: YAHOO.util.Dom.getXY(srcEl)
                }
            },
            0.2,
            YAHOO.util.Easing.easeOut
        );
        var proxyid = proxy.id;
        var thisid = this.id;
        var nextId = this.nextId;
        var endDrag = this.extraHandlers.endDrag;

        // Hide the proxy and show the source element when finished with the animation
        animator.onComplete.subscribe(function() {
                YAHOO.util.Dom.setStyle(proxyid, "visibility", "hidden");
                YAHOO.util.Dom.setStyle(thisid, "visibility", "");
                endDrag(srcEl, (nextId != jQuery(srcEl).next('li').attr('id')));
            });
        animator.animate();
    },

    onDrag: function(e) {
        // Keep track of the direction of the drag for use during onDragOver
        var y = YAHOO.util.Event.getPageY(e);

        if (y < this.lastY) {
            this.goingUp = true;
        } else if (y > this.lastY) {
            this.goingUp = false;
        }

        this.lastY = y;
    },

    onDragOver: function(e, id) {
        var srcEl = this.getEl();
        var destEl = YAHOO.util.Dom.get(id);

        // We are only concerned with list items, we ignore the dragover
        // notifications for the list.
        if (destEl.nodeName.toLowerCase() == "li") {
            var orig_p = srcEl.parentNode;
            var p = destEl.parentNode;

            if (this.goingUp) {
                p.insertBefore(srcEl, destEl); // insert above
            } else {
                p.insertBefore(srcEl, destEl.nextSibling); // insert below
            }

            YAHOO.util.DragDropMgr.refreshCache();
        }
    }
});

Fisma.InteractiveOrderedListItem.appendNewTo = function(listId, jsHandlers) {
    var list = jQuery('ul#' + listId + 'Container');
    var defaultItem = list.children('#' + listId + 'Skeleton');

    var newItem = defaultItem.clone();
    var newId = listId + '_' + list.children('li').length;
    newItem.attr('id', newId);
    newItem.appendTo(list);

    new Fisma.InteractiveOrderedListItem(
        newId,
        listId,
        jsHandlers,
        {
            dragElId : 'dragListProxy'
        }
    );

    // This unfortunate hack is to get around YAHOO DragDrop onMouseDown handler shadowing over onClick
    var selector = 'li#' + newId + ' input[type=text], li#' + newId + ' textarea, li#' + newId + ' select';
    var inputs = YAHOO.util.Selector.query(selector);
    for (var i in inputs) {
        YAHOO.util.Event.on(inputs[i], 'click', function(clickEvent) {
            if (clickEvent.target) {
                clickEvent.target.focus();
            } else {
                clickEvent.srcElement.focus();
            }
        });
    }

    return jsHandlers.onAppend(listId, newId);
}
