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

        this._closeContainer = document.createElement('div');
        this._closeContainer.className = "closeControl";
        this._closeContainer.appendChild(document.createTextNode(closeCharacter));
        this._container.appendChild(this._closeContainer);

        YAHOO.util.Event.addListener(this._closeContainer, "click", function () {this.hide();}, this, true);

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
         * A container of close element
         */
        _closeContainer: null,

        /**
         * Append new message text to the existing message box
         *
         * @param message {String}
         * @return {Fisma.MessageBox} Fluent Interface
         */
        addMessage: function (message) {
            this._subcontainer.innerHTML += message;

            return this;
        },

        /**
         * Set the message text (overwriting what was previously there)
         *
         * @param message {String}
         * @return {Fisma.MessageBox} Fluent Interface
         */
        setMessage: function (message) {
            this._subcontainer.innerHTML = message;

            return this;
        },

        /**
         * Set the error level for this box.
         *
         * This affects appearance but in the future may also affect behavior.
         *
         * @param level {MessageBar.ERROR_LEVEL}
         * @return {Fisma.MessageBox} Fluent Interface
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

            return this;
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
         *
         * @return {Fisma.MessageBox} Fluent Interface
         */
        show: function () {
            YAHOO.util.Dom.removeClass(this._container, "hide");

            this._setClosedIconToVerticalCenter();
            return this;
        },

        /**
         * Hide the message box
         *
         * @return {Fisma.MessageBox} Fluent Interface
         */
        hide: function () {
            YAHOO.util.Dom.addClass(this._container, "hide");

            return this;
        },

        /**
         * Set the close icon to vertical center of message box
         */
        _setClosedIconToVerticalCenter: function () {
            var msgbarRegion = YAHOO.util.Dom.getRegion(this._container);
            var closeRegion = YAHOO.util.Dom.getRegion(this._closeContainer);
            var closeTop = msgbarRegion.top + Math.round(msgbarRegion.height - closeRegion.height)/2;
            YAHOO.util.Dom.setY(this._closeContainer, closeTop);
        }
    };

    Fisma.MessageBox = MB;
}());
