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
 * @author    Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * Generic storage class helps to manage global data.
     * 
     * @namespace Fisma
     * @class Registry
     */
    var Registry = {
        /**
         * Registry object provides storage for shared objects.
         */
        _registry: {},

        /**
         * Get value for name
         *
         * @method Registry.get
         * @param name {String}
         * @return {String|Array|Object}
         */
        get: function(name) {
            if (!YAHOO.lang.isString(name) || name === ""){
                throw new TypeError("Registry.get(): Registry name must be a non-empty string.");
            }

            return this._registry[name];
        },

        /**
         * Sets a registry with a given name and value.
         *
         * @method Registry.set
         * @param name {String}
         * @param value {String|Array|Object}
         */
        set: function(name, value) {
            if (!YAHOO.lang.isString(name)){
                throw new TypeError("Registry.set(): Registry name must be a string.");
            }

            if (YAHOO.lang.isUndefined(value)){
                throw new TypeError("Registry.set(): Registry Value cannot be undefined.");
            }

            this._registry[name] = value;
        },

        /**
         * Returns TRUE if the name is a named value in the registry,
         * or FALSE if name was not found in the registry.
         * 
         * @param name {String}
         */
        isRegistered: function(name) {
            if (!YAHOO.lang.isString(name) || name === ""){
                throw new TypeError("Registry.isRegistered(): Registry name must be a non-empty string.");
            }

            return !YAHOO.lang.isUndefined(this._registry[name]) ? true : false;
        }
    };

    Fisma.Registry = Registry;
}());
