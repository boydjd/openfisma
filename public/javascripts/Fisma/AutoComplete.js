/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * @fileoverview AutoComplete namespace
 *
 * @author    Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @package   Fisma
 * @requires  YAHOO.widget.AutoComplete
 * @requires  YAHOO.widget.DS_XHR
 * @requires  Fisma
 */

Fisma.AutoComplete = (function() {
    return {
        /**
         * Used for tracking if there are any open requests.
         */
        requestCount : 0,

        /**
         * Used for tracking if any results have been populated
         */
        resultsPopulated : false,

        /**
         * Initializes the AutoComplete widget
         *
         * @param oEvent
         * @param aArgs
         * @param {Array} params
         */
        init : function(oEvent, aArgs, params) {
            var acRDS = new YAHOO.widget.DS_XHR(params.xhr, params.schema);

            acRDS.responseType = YAHOO.widget.DS_XHR.TYPE_JSON;
            acRDS.maxCacheEntries = 500;
            acRDS.queryMatchContains = true;

            var ac = new YAHOO.widget.AutoComplete(params.fieldId, params.containerId, acRDS);

            ac.maxResultsDisplayed = 20;
            ac.forceSelection = true;

            var spinnerImage = document.getElementById(params.containerId + "Spinner");

            /**
             * Enable the spinner
             */
            ac.dataRequestEvent.subscribe(function () {
                spinnerImage.style.visibility = "visible";
                Fisma.AutoComplete.requestCount++;
            });

            /**
             * Disable the spinner if there are no pending requests
             */
            ac.dataReturnEvent.subscribe(function () {
                Fisma.AutoComplete.requestCount--;

                if (0 === Fisma.AutoComplete.requestCount) {
                    spinnerImage.style.visibility = "hidden";
                }
            });

            /**
             * Re-display the autocomplete menu if the text field loses and then regains focus
             */
            ac.getInputEl().onclick = function () {
                if (Fisma.AutoComplete.resultsPopulated) {
                    ac.expandContainer();
                }
            };

            /**
             * Record the fact that the results have been retrieved
             */
            ac.containerPopulateEvent.subscribe(function () {
                Fisma.AutoComplete.resultsPopulated = true;
            });

            /**
             * Override generateRequest method of YAHOO.widget.AutoComplete
             *
             * @param {String} query Query terms
             * @returns {String}
             */
            ac.generateRequest = function(query) {
                return params.queryPrepend + query;
            };

            /**
             * Overridable method that returns HTML markup for one result to be populated
             * as innerHTML of an <li> element.
             *
             * @method formatResult
             * @param oResultData {Object} Result data object.
             * @param sQuery {String} The corresponding query string.
             * @param sResultMatch {HTMLElement} The current query string.
             * @return {String} HTML markup of formatted result data.
             */
            ac.formatResult = function(oResultData, sQuery, sResultMatch) {
                var sMarkup = (sResultMatch) ? PHP_JS().htmlspecialchars(sResultMatch) : "";

                // Create a regex to match the query case insensitively
                var regex = new RegExp('\\b(' + sQuery + ')', 'i');
                sResultMatch = sResultMatch.replace(regex, "<em>$1</em>");

                return sResultMatch;
            };

            ac.itemSelectEvent.subscribe(
                Fisma.AutoComplete.updateHiddenField,
                params.hiddenFieldId
            );

            ac.selectionEnforceEvent.subscribe(
                Fisma.AutoComplete.clearHiddenField,
                params.hiddenFieldId
            );

            /* If 'enterKeyEventHandler' is specified, then the input field will not submit a
             * form when the user presses return or enter. Instead, it will call the specified function.
             * If set to false, the form will not submit but not handler will be called. If unset or set
             * to true, then the default event handling will takel place.
             */
            if (params.hasOwnProperty('enterKeyEventHandler')) {
                YAHOO.util.Event.on(ac.getInputEl(), "keydown", function (e) {
                    if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
                        YAHOO.util.Event.preventDefault(e);

                        if (!YAHOO.lang.isNull(params.enterKeyEventHandler)) {
                            var enterKeyEventHandler = Fisma.Util.getObjectFromName(params.enterKeyEventHandler);

                            if (enterKeyEventHandler) {
                                enterKeyEventHandler(ac, params.enterKeyEventArgs);
                            }
                        }
                    }
                });
            }

            // Call the setup callback, if it is defined. This allows an implementer to tweak the autocomplete object.
            if (YAHOO.lang.isValue(params.setupCallback)) {
                var setupCallback = Fisma.Util.getObjectFromName(params.setupCallback);

                setupCallback(ac, params);
            }
        },

        /**
         * Sets value of hiddenField to item selected
         *
         * @param sType {String} The event name
         * @param aArgs {Array} YUI event arguments
         * @param hiddenFieldId {String} The ID of the hidden field
         */
        updateHiddenField : function(sType, aArgs, hiddenFieldId) {
            document.getElementById(hiddenFieldId).value = aArgs[2][1].id;
            $('#' + hiddenFieldId).trigger('change');
        },

        /**
         * Clears the value of the hidden field
         *
         * @param sType {String} The event name
         * @param aArgs {Array} YUI event arguments
         * @param hiddenFieldId {String} The ID of the hidden field
         */
        clearHiddenField : function (sType, aArgs, hiddenFieldId) {
            document.getElementById(hiddenFieldId).value = "";
            $('#' + hiddenFieldId).trigger('change');
        }
    };
}());
