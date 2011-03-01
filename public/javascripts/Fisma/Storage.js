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
    Fisma.Storage = function(namespace) {
        this.namespace = namespace;
        this.storageEngine = YAHOO.util.StorageManager.get(
            null, // no preferred engine
            YAHOO.util.StorageManager.LOCATION_SESSION,
        );

    };
    Fisma.Storage.prototype = {
        onReady: funtion(fn) {
            this.storageEngine.subscribe(this.storageEngine.CE_READY, fn);
        },

        get: function(key) {
            throw new Fisma.Storage.UnimplementedException("get");
        },
        set: function(key, value) {
            throw new Fisma.Storage.UnimplementedException("set");
        },

        _get: function(key) {
            this.storageEngine.getItem(namespace + ":" + key);
        },
        _set: function(key, value) {
            this.storageEngine.setItem(namespace + ":" + key, value);
        }
    };
})();
