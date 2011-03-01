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
    Fisma.Storage.Persistent = function(namespace) {
        Fisma.Storage.Persistent.superclass.constructor.call(this, namespace);
    };
    YAHOO.extend(Fisma.Storage.Persistent, Fisma.Storage, {
        _modified: {},

        get: function(key) {
            /*
             * @todo: sanity check for key existence.
             *        if key doesn't exist, perform sync() and then forcefully set the key to null if it still doesn't
             *        exist.
             */
            return this._get(key);
        },
        set: function(key, value) {
            this._modified[key] = true;
            return this._set(key, value);
        },

        init: function(values) {
            foreach (var key in values) {
                this._set(key, values[key]);
            }
        },
        sync: function() {
            /*
             * Not yet implemented.
             * Step 1: Send modified keys to server.
             * Step 2: Call init() with response object.
             * Step 3: Clear modified object
             */
        }
    };
})();
