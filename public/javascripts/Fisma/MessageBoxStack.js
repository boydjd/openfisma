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
     * Instantiate a global message box stack, and install a default message box if the layout has a container for it.
     */
    YAHOO.util.Event.onDOMReady(function () {
        var messageBoxStack = new Fisma.MessageBoxStack();
        Fisma.Registry.set("messageBoxStack", messageBoxStack);

        var messageBoxContainer = document.getElementById('msgbar');

        if (messageBoxContainer) {            
            var mainMessageBox = new Fisma.MessageBox(messageBoxContainer);
            
            messageBoxStack.push(mainMessageBox);
        }
    });

    /**
     * A stack structure for message boxes.
     * 
     * Messages can be routed to a different message box by pushing that message box onto a stack.
     * 
     * @namespace Fisma
     * @class MessageBoxManager
     * @extends n/a
     * @constructor
     */
    var MBS = function(container) {
        this._messageBoxes = [];
    };

    MBS.prototype = {
        _messageBoxes: null,

        peek: function () {
            return this._messageBoxes[this._messageBoxes.length - 1];
        },

        push: function (messageBox) {
            this._messageBoxes.push(messageBox);
        },

        pop: function () {
            return this._messageBoxes.pop();
        }
    };

    Fisma.MessageBoxStack = MBS;
}());
