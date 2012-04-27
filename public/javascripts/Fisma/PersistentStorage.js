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
     * Class to store local data and persist to the server.
     *
     * @namespace Fisma
     * @class PeristentStorage
     * @extends Fisma.Storage
     * @constructor
     * @param namespace {String} Namespace of stored data.
     */
    Fisma.PersistentStorage = function(namespace) {
        Fisma.PersistentStorage.superclass.constructor.call(this, namespace);
    };
    YAHOO.extend(Fisma.PersistentStorage, Fisma.Storage, {
        /**
         * @property _modified
         * @type Array
         * @protected
         */
        _modified: null,

        /**
         * Set value for key
         *
         * @method PersistentStorage.set
         * @param key {String}
         * @param value {String|Array|Object}
         */
        set: function(key, value) {
            if (this._modified === null) {
                this._modified = {};
            }
            this._modified[key] = value;
            return this._set(key, value);
        },

        /**
         * Initialize the local storage with default values.
         *
         * @method Storage.init
         * @param values {Object} Object literal of key-value pairs to set
         */
        init: function(values) {
            var key;
            for (key in values) {
                this._set(key, values[key]);
            }
        },

        /**
         * Synchronize the server with the local state.
         *
         * @method PersistentStorage.sync
         * @param reply {Array} Array of keys to reply with, null implies all keys.
         * @param callback {Function|Object} Callback function/object.
         */
        sync: function(reply, callback) {
            var successFn = null,
                failureFn = null,
                scope = null;
            if (callback) {
                if (typeof(callback) === "function") {
                    successFn = callback;
                } else if (callback.success && typeof(callback.success) === "function") {
                    successFn = callback.success;
                }
                if (callback.failure && typeof(callback.failure) === "function") {
                    failureFn = callback.failure;
                }
                if (callback.scope) {
                    scope = callback.scope;
                }
            }
            Fisma.Storage.onReady(function() {
                var uri = '/storage/sync/format/json',
                    csrfInputs = $('[name="csrf"]').val(),
                    callback = {
                        scope: this,
                        success: function(response) {
                            var object = YAHOO.lang.JSON.parse(response.responseText);
                            if (object.status === "ok") {
                                this.init(object.data);
                                this._modified = null;
                            }
                            if (successFn) {
                                successFn.call(scope || this, response, object);
                            }
                        },
                        failure: function() {
                            if (failureFn) {
                                failureFn.call(scope || this);
                            }
                        }
                    },
                    postData = $.param({
                        csrf: csrfInputs,
                        namespace: this.namespace,
                        updates: YAHOO.lang.JSON.stringify(this._modified),
                        reply: reply ? YAHOO.lang.JSON.stringify(reply) : null
                    });
                YAHOO.util.Connect.asyncRequest ( 'POST', uri , callback , postData );
            }, this, true);
        }
    });
}());
