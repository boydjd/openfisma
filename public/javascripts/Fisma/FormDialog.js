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
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * A form dialog is a modal dialog which displays an HTML form, where the values of each element are bound to
     * the values of an underlying object.
     * 
     * @constructor
     * @param formUrl {String} The URL to load the blank form from
     * @param element {String|HTMLElement} The element or element ID representing the Dialog
     * @param config {Object} YUI Dialog Configuration
     */
    Fisma.FormDialog = function(formUrl, element, config) {
        // Sanitize inputs
        var safeConfig = YAHOO.lang.isValue(config) ? config : {};
        var safeElement = YAHOO.lang.isValue(element) ? element : YAHOO.util.Dom.generateId();

        // Configuration Defaults
        safeConfig.modal = true;

        Fisma.FormDialog.superclass.constructor.call(this, safeElement, safeConfig);

        this._showLoadingMessage();
        this._requestForm(formUrl);
    };

    YAHOO.extend(Fisma.FormDialog, YAHOO.widget.Panel, {
        /**
         * Store bindings for the form
         */
        _values: {},
        
        /**
         * Indicates if the form is ready to accept bindings
         */
        _formIsReady: false,

        /**
         * Show a loading message (while loading the form)
         */
        _showLoadingMessage: function() {
            this.setBody('Loading…');
            this.render(document.body);
            this.center();
            this.show();
        },

        /**
         * Request the blank form from a specified URL
         * 
         * @param url {String}
         */
        _requestForm: function(url) {
            YAHOO.util.Connect.asyncRequest(
                'GET',
                url,
                {
                    success: this._loadForm,

                    failure: function(connectionData) {
                        Fisma.Util.showAlertDialog('An unexpected error occurred.');
                        this.hide();
                    },
                    
                    scope: this
                },
                null
            );

        },
        
        /**
         * Load the returned form into the dialog
         * 
         * @param connectionData {Object} Returned by YUI connection class
         */
        _loadForm: function(connectionData) {
            try {
                var response = connectionData.responseText;

                this.setBody(response);
                this.center();
                this._formIsReady = true;
                this._applyValues();
            } catch (error) {
                Fisma.Util.showAlertDialog('An unexpected error occurred: ' + error);
                this.hide();
            }
        },

        /**
         * Apply binding variables to the form's DOM representation
         */
        _applyValues: function() {
            var value;

            for (var key in this._values) {
                value = this._values[key];
                
                console.log(key);console.log(value);
            }
        },

        /**
         * Internal convenience method for encoding values.
         *
         * @param values {Object}
         */
        setValues: function(values) {
            this._values = values;
            
            if (this._formIsReady) {
                this._applyValues();
            }
        }
    });
})();
