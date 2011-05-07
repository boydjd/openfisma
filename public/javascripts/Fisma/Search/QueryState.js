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
    var Lang = YAHOO.lang;
    /**
     * Enable getting and setting of query state information
     *
     * @namespace Fisma.Search
     * @class QueryState
     * @constructor
     * @param model {String} Model for which this state information applies.
     * @param init {Object} Object literal of default state.
     */
    var QueryState = function(model, init) {
            this._model = model;
            this._storage = new Fisma.Storage('Fisma.Search.QueryState');
        };
    QueryState.TYPE_SIMPLE = "simple";
    QueryState.TYPE_ADVANCED = "advanced";
    QueryState.prototype = {
        /**
         * Basic getter for all state information.
         *
         * @method getState
         * @return {Object}
         */
        getState: function () {
            return this._storage.get(this._model);
        },

        /**
         * Basic setter for state information
         *
         * @method setState
         * @param value {Object} State information.
         */
        setState: function (value) {
            this._storage.set(this._model, value);
        },

        /**
         * Get search type
         *
         * @method getSearchType
         * @return {String} TYPE_SIMPLE or TYPE_ADVANCED, default TYPE_SIMPLE
         */
        getSearchType: function() {
            var state = this.getState();
            if (!Lang.isObject(state) || !Lang.isValue(state.searchType)) {
                return QueryState.TYPE_SIMPLE;
            } 
            switch (state.searchType) {
                case QueryState.TYPE_SIMPLE:
                case QueryState.TYPE_ADVANCED:
                    return state.searchType;
                default:
                    throw "Invalid search type encountered.";
            }
        },

        /**
         * Basic setter for search type
         *
         * @method setSearchType
         * @param type {String} Search type, "simple" or "advanced"
         */
        setSearchType: function(type) {
            var oldData = this.getState() || {},
                newData = {};
            newData.searchType = type;
            if (type === "simple") {
                newData.keywords = oldData.keywords || "";
            } else if (type === "advanced") {
                newData.advancedQuery = oldData.advancedQuery || [];
            } else {
                throw "Invalid search type specified.";
            }
            this.setState(newData);
        },

        /**
         * Get search keywords
         *
         * @method getKeywords
         * @return {String} Keywords
         */
        getKeywords: function() {
            var state = this.getState();
            if (!Lang.isObject(state) || !Lang.isValue(state.keywords)) {
                return "";
            } 
            return state.keywords;
        },

        /**
         * Basic setter for search keywords
         *
         * @method setKeywords
         * @param type {String} Search keywords
         */
        setKeywords: function(keywords) {
            if (!Lang.isString(keywords)) {
                throw "Can not set non-string as keywords.";
            }
            if (this.getSearchType() !== QueryState.TYPE_SIMPLE) {
                throw "Attempting to save keywords for non-simple search.";
            }
            var data = this.getState() || {};
            data.keywords = keywords;
            this.setState(data);
        },

        /**
         * Get advanced search query
         *
         * @method getAdvancedQuery
         * @return {Object} Query
         */
        getAdvancedQuery: function() {
            var state = this.getState();
            if (!Lang.isObject(state) || !Lang.isObject(state.advancedQuery)) {
                return {};
            } 
            return state.advancedQuery;
        },

        /**
         * Basic setter for advanced search query.
         *
         * @method setAdvancedQuery
         * @param query {Object} Advanced search query
         */
        setAdvancedQuery: function(query) {
            if (!Lang.isObject(query)) {
                throw "Can not set non-object as advanced search query.";
            }
            if (this.getSearchType() !== QueryState.TYPE_ADVANCED) {
                throw "Attempting to save advanced search query for non-advanced search.";
            }
            var data = this.getState() || {};
            data.advancedQuery = query;
            this.setState(data);
        }
    };
    Fisma.Search.QueryState = QueryState;
})();
