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
 * @fileoverview Handle a click on the checkbox tree. Clicking a nested node will select all nodes inside of it,
 *               unless all of the subnodes are already selected, in which case it will deselect all subnodes.
 *               Holding down the option key while clicking disables this behavior.
 *               
 *               The checkbox tree DOM looks like this:
 *               <li><input type="checkbox" nestedLevel="0"><label></li>
 *                 <li><input type="checkbox" nestedLevel="1"><label></li>
 *                  <li><input type="checkbox" nestedLevel="2"><label></li>
 *               etc...
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.CheckboxTree = {
    /**
     * Handle a click on the checkbox tree
     *
     * @param clickedBox The HTML element which user clicked on
     * @param event Provided by YUI
     */
    handleClick : function(clickedBox, event) {
        // If the option key is held down, then skip all of this logic.
        if (event.altKey) {
            return;
        }

        var topListItem = clickedBox.parentNode;

        if (topListItem.nextSibling === null) {
            return;
        }

        // If there are no nested checkboxes, then there is nothing to do
        var nextCheckbox = topListItem.nextSibling.childNodes[0];
        if (nextCheckbox.getAttribute('nestedLevel') > clickedBox.getAttribute('nestedLevel')) {
            var minLevel = clickedBox.getAttribute('nestedlevel');
            var checkboxArray = new Array();
            var allChildNodesChecked = true;

            // Loop through all of the subnodes and see which ones are already checked
            var listItem = topListItem.nextSibling;
            var checkboxItem = listItem.childNodes[0];
            while (checkboxItem.getAttribute('nestedLevel') > minLevel) {
                if (!checkboxItem.checked) {
                    allChildNodesChecked = false;
                }
                
                checkboxArray.push(checkboxItem);
                
                if (listItem.nextSibling) {
                    listItem = listItem.nextSibling;
                    checkboxItem = listItem.childNodes[0];
                } else {
                    break;
                }
            }
            
            // Update the node which the user clicked on
            if (allChildNodesChecked) {
                clickedBox.checked = false;
            } else {
                clickedBox.checked = true;
            }
            
            // Now iterate through child nodes and update them
            for (var i in checkboxArray) {
                var checkbox = checkboxArray[i];
                
                if (allChildNodesChecked) {
                    checkbox.checked = false;
                } else {
                    checkbox.checked = true;
                }
            }
        }
    }
};
