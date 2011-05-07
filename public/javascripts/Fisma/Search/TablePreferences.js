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
    var YL = YAHOO.lang,
        FPS = Fisma.PersistentStorage,
    /**
     * Enable getting and setting of datatable-related preferences.
     *
     * @namespace Fisma.Search
     * @class TablePreferences
     * @constructor
     * @param model {String} Model for which this table is representing.
     * @param init {Object} Object literal of default state.
     */
        FSTP = function(model, init) {
            this._model = model;
            this._storage = new Fisma.PersistentStorage('Fisma.Search.TablePreferences');
            this._localStorage = new Fisma.Storage('Fisma.Search.TablePreferences.Local');
            this._state = null;
            Fisma.Storage.onReady(function() {
                var data = this._storage.get(this._model);
                this._state = YL.isObject(init) ? init : {};
                if (YL.isObject(data)) {
                    this._state = YL.merge(data, this._state);
                }
            }, this, true);
        };
    FSTP.prototype = {
        /**
         * Get specified columns visibility.
         *
         * @method TablePreferences.getColumnVisibility
         * @param column {String} Column key.
         * @param def {Boolean} Default state
         * @return {Boolean}
         */
        getColumnVisibility: function (column, def) {
            this._stateReady();
            if (YL.isValue(this._state.columnVisibility[column])) {
                return this._state.columnVisibility[column] ? true : false; // always return boolean
            }
            // if default not provided, assume false
            return YL.isValue(def) && def ? true : false;
        },

        /**
         * Set the specified columns visibility.
         *
         * @method TablePreferences.setColumnVisibility
         * @param column {String} Column key.
         * @param value {Boolean} Is visible?
         */
        setColumnVisibility: function (column, value) {
            this._stateReady();
            this._state.columnVisibility[column] = value;
            this._storage.set(this._model, this._state);
        },

        /**
         * Get sort column and direction
         *
         * @method TablePreferences.getSort
         * @return {Object}
         */
        getSort: function() {
            var data = this._localStorage.get(this._model);
            return YL.isObject(data) && YL.isObject(data.sort) ? data.sort : null;
        },
        /**
         * Set the sort column and direction
         *
         * @method TablePreferences.setSort
         * @param column {String} Column key.
         * @param dir {String} Sort direction
         */
        setSort: function(column, dir) {
            var data = this._localStorage.get(this._model);
            data = YL.isObject(data) ? data : {};
            data.sort = {column: column, dir: dir};
            this._localStorage.set(this._model, data);
        },

        /**
         * Get current page number
         *
         * @method TablePreferences.getpage
         * @return {Integer}
         */
        getPage: function() {
            var data = this._localStorage.get(this._model);
            return YL.isObject(data) && YL.isNumber(data.page) ? data.page: null;
        },
        /**
         * Set the current page number
         *
         * @method TablePreferences.setPage
         * @param page {Integer} Page number.
         */
        setPage: function(page) {
            var data = this._localStorage.get(this._model);
            data = YL.isObject(data) ? data : {};
            data.page = page;
            this._localStorage.set(this._model, data);
        },

        /**
         * Save table preferences
         *
         * @method TablePreferences.persist
         * @param callback {Function|Object} Callback on completion.
         */
        persist: function (callback) {
            var m = this._model,
                s = this._storage;
            // force a "set" to ensure sync will know it's been modified
            s.set(m, s.get(m));
            s.sync([m], callback);
        },

        /**
         * Internal method to assert the object is ready.
         *
         * @method TablePreferences._stateReady
         * @protected
         */
        _stateReady: function() {
            if (this._state === null) {
                throw "Attempting to use storage engine before it is ready.";
            }
            if (typeof(this._state.columnVisibility) === 'undefined') {
                this._state.columnVisibility = {};
            }
        }
    };
    Fisma.Search.TablePreferences = FSTP;
})();
