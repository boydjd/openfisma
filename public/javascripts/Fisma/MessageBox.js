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
     * A box that is used to display error or success messages to the user.
     * 
     * @namespace Fisma
     * @class MessageBox
     * @extends n/a
     * @constructor
     * @param container {HTMLElement} The container to render messages inside of.
     */
    var MB = function(container) {
        var that = this;

        if (!YAHOO.lang.isValue(container)) {
            throw "Container must be an HTML element object.";
        }

        while (container.childNodes.length > 0) {
            container.removeChild(container.firstChild);
        }

        this._container = container;
        
        // Default error level is "warn" for legacy compatibility
        this.setErrorLevel(MB.ERROR_LEVEL.WARN);
        this.hide();

        // Add a control to allow a user to dismiss the message
        var closeCharacter = "✗";
        if (YAHOO.env.ua.ie === 7) {
            // IE7 has bad font rendering. Use a simpler character.
            closeCharacter = "x";
        }

        var closeControl = document.createElement('div');
        closeControl.className = "closeControl";
        closeControl.appendChild(document.createTextNode(closeCharacter));
        this._container.appendChild(closeControl);

        YAHOO.util.Event.addListener(closeControl, "click", function () {this.hide();}, this, true);
        
        // Add the subcontainer
        this._subcontainer = document.createElement('div');
        this._container.appendChild(this._subcontainer);
    };

    /**
     * An enumeration of error levels
     * 
     * @static
     */
    MB.ERROR_LEVEL = {
        WARN: 0,
        INFO: 1
    };

    MB.prototype = {
        /**
         * A reference to the HTML container that this is rendered inside of.
         * 
         * @param HTMLElement
         */
        _container: null,

        /**
         * A subcontainer that is used to hold the message (since the main container also has a "close" control)
         * 
         * @param HTMLElement
         */
        _subcontainer: null,
        
        /**
         * The criticality or error level for this message
         */
        _errorLevel: null,
        
        /**
         * Append new message text to the existing message box
         * 
         * @param message {String}
         */
        addMessage: function (message) {
            this._subcontainer.innerHTML += message;
        },
        
        /**
         * Set the message text (overwriting what was previously there)
         * 
         * @param message {String}
         */        
        setMessage: function (message) {
            this._subcontainer.innerHTML = message;
        },
        
        /**
         * Set the error level for this box.
         * 
         * This affects appearance but in the future may also affect behavior.
         * 
         * @param level {MessageBar.ERROR_LEVEL}
         */
        setErrorLevel: function (level) {
            switch (level) {
                case MB.ERROR_LEVEL.WARN:
                    this._container.className = "messageBox warn";
                    break;
                case MB.ERROR_LEVEL.INFO:
                    this._container.className = "messageBox info";
                    break;
                default:
                    throw "Invalid error level specified (" + level + ").";
            }

            this._errorLevel = level;
        },

        /**
         * Return the current error level.
         * 
         * @return {MessageBar.ERROR_LEVEL}
         */
        getErrorLevel: function () {
            return this._errorLevel;
        },

        /**
         * Show the message box
         */
        show: function () {
            YAHOO.util.Dom.removeClass(this._container, "hide");
        },
        
        /**
         * Hide the message box
         */
        hide: function () {
            YAHOO.util.Dom.addClass(this._container, "hide");
        }
    };

    Fisma.MessageBox = MB;
})();
