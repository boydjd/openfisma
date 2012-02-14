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
 * @fileoverview This file contains related javascript code for the finding workflow feature
 *
 * @author    Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

Fisma.FindingWorkflow = {
    /**
     * Prepend the handling of InteractiveOrderedList drag-drop event
     *
     * @param (HTMLElement) source      The <li> element being dragged
     * @param (HTMLElement) destination The <li> element being dragged over
     * @param (boolean)     goingUp     Whether the source comes from below or above the destination
     */
    dragOverHandler : function(source, destination, goingUp) {
        if (goingUp) {
            if (jQuery(destination).prevAll('li').length < 1) { // first of the list
                precedence = parseFloat(jQuery(destination).find('input[name$="precedence"]').val()) - 1;
            } else {
                previousPrecedence = parseFloat(jQuery(destination).prev('li').find('input[name$="precedence"]').val());
                nextPrecedence = parseFloat(jQuery(destination).find('input[name$="precedence"]').val());
                precedence = (previousPrecedence + nextPrecedence) / 2;
            }
            jQuery(source).find('input[name$="precedence"]').val(precedence);
        } else {
            if (jQuery(destination).nextAll('li').length < 1) { // last of the list
                precedence = parseFloat(jQuery(destination).find('input[name$="precedence"]').val()) + 1;
            } else {
                previousPrecedence = parseFloat(jQuery(destination).find('input[name$="precedence"]').val());
                nextPrecedence = parseFloat(jQuery(destination).next('li').find('input[name$="precedence"]').val());
                precedence = (previousPrecedence + nextPrecedence) / 2;
            }
            jQuery(source).find('input[name$="precedence"]').val(precedence);
        }
    },

    /**
     * Handle the toggling of the detailPanel
     *
     * @param (HTMLElement) element The element that triggers the event
     */
    toggleDetailPanel : function(element) {
        jQuery(element).parents("span").next(".stepDetail").toggle("fast");
        return false;
    },

    /**
     * Append the handling of InteractiveOrderedList endDrag event
     *
     * @param (HTMLElement) source  The <li> element being dragged
     * @param (boolean)     moved   Whether the source was actually moved to another position on the list
     */
    endDragHandler : function(source, moved) {
        if (moved) {
            var source = jQuery(source).clone();
            source.children('span, div').remove();
            var message = source.text() + ' moved';
            source.remove();
            Fisma.FindingWorkflow.addChangeLogEntry(message);
        }
    },

    /**
     * Insert an entry into the Change Log Window
     *
     * @param (string) message  The message to insert
     */
    addChangeLogEntry : function(message) {
        var entry = document.createElement("div");
        entry.appendChild(document.createTextNode(message));
        entry.className = 'approvalStep';
        jQuery('#changeQueue div.approval').append(entry);
    }
};
