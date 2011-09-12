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
     * Provides basic session-level storage of data.
     * @namespace Fisma
     * @class Storage
     * @constructor
     * @param namespace {String} The data namespace.
     */
    var FS = function(namespace) {
        this.namespace = namespace;
        FS._initStorageEngine();
    };

    /**
     * Helper to ensure the storage engine is initialized
     *
     * @method _initStorageEngine
     * @protected
     * @static
     */
    FS._initStorageEngine = function() {
        if (YAHOO.lang.isNull(FS._storageEngine)) {
            var engineConf = {swfURL: "/swfstore.swf", containerID: "swfstoreContainer"};
            FS._storageEngine = YAHOO.util.StorageManager.get(
                YAHOO.util.StorageEngineGears.ENGINE_NAME,
                YAHOO.util.StorageManager.LOCATION_SESSION,
                {
                    engine: engineConf,
                    force: false,
                    order: [
                        //YAHOO.util.StorageEngineGears,
                        //YAHOO.util.StorageEngineHTML5,
                        YAHOO.util.StorageEngineSWF
                    ]
                }
            );
        }
    };

    /**
     * Underlying storage engine.
     *
     * @property Storage._storageEngine
     * @type Object
     * @private
     * @static
     */
    FS._storageEngine = null;

    /**
     * Clear all storage space.
     *
     * @method clear
     * @static
     */
    FS.clear = function() {
        FS._initStorageEngine();
        FS._storageEngine.clear();
    };

    /**
     * Register a callback for when the storage engine is ready.
     *
     * @method Storage.onReady
     * @param fn {Function} Callback function.
     * @param obj {Object} Object passed to callback.
     * @param scope {Object|Boolean} Object to use for callback scope, true to use obj as scope.
     * @static
     */
    FS.onReady = function(fn, obj, scope) {
        YAHOO.util.Event.onContentReady('swfstoreContainer', function() {
            FS._initStorageEngine();
            var engine = FS._storageEngine;
            var locationSession = YAHOO.util.StorageManager.LOCATION_SESSION === engine._location;
            // check readiness (this is how the YAHOO examples do it)
            if (!(engine.isReady || (engine._swf && locationSession))) {
                engine.subscribe(engine.CE_READY, fn, obj, scope);
            } else {
                var s = new YAHOO.util.Subscriber(fn, obj, scope);
                s.fn.call(s.getScope(window), s.obj);
            }
        });
    };
    FS.prototype = {
        /**
         * Get value for key
         *
         * @method Storage.get
         * @param key {String}
         * @return {String|Array|Object}
         */
        get: function(key) {
            return this._get(key);
        },
        /**
         * Set value for key
         *
         * @method Storage.set
         * @param key {String}
         * @param value {String|Array|Object}
         */
        set: function(key, value) {
            this._set(key, value);
        },

        /**
         * Internal convenience method for decoding values.
         *
         * @method Storage._get
         * @param key {String}
         * @return {String|Array|Object}
         * @protected
         */
        _get: function(key) {
            YAHOO.lang.JSON.useNativeParse = true;
            YAHOO.lang.JSON.useNativeStringify = true;
            var value = FS._storageEngine.getItem(this.namespace + ":" + key);
            return YAHOO.lang.isNull(value) ? null : YAHOO.lang.JSON.parse(value);
        },
        /**
         * Internal convenience method for encoding values.
         *
         * @method Storage._set
         * @param key {String}
         * @param value {String|Array|Object}
         * @protected
         */
        _set: function(key, value) {
            YAHOO.lang.JSON.useNativeParse = true;
            YAHOO.lang.JSON.useNativeStringify = true;
            FS._storageEngine.setItem(this.namespace + ":" + key, YAHOO.lang.JSON.stringify(value));
        }
    };
    Fisma.Storage = FS;
})();
