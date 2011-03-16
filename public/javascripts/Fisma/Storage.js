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
    var FS = function(namespace) {
        this.namespace = namespace;
    };

    FS._storageEngine = YAHOO.util.StorageManager.get(
        null, // no preferred engine
        YAHOO.util.StorageManager.LOCATION_SESSION
    );
    FS.onReady = function(fn, obj, scope) {
            if (!FS._storageEngine.isReady) {
                FS._storageEngine.subscribe(FS._storageEngine.CE_READY, fn, obj, scope);
            } else {
                var s = scope === true ? obj : scope;
                if (typeof(s) !== "object") {
                    s = fn;
                }
                fn.call(s, obj);
            }
        },
    FS.prototype = {
        get: function(key) {
            return this._get(key);
        },
        set: function(key, value) {
            this._set(key, value);
        },

        _get: function(key) {
            return YAHOO.lang.JSON.parse(FS._storageEngine.getItem(this.namespace + ":" + key));
        },
        _set: function(key, value) {
            FS._storageEngine.setItem(this.namespace + ":" + key, YAHOO.lang.JSON.stringify(value));
        }
    };
    Fisma.Storage = FS;
})();
