/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * A utility class to provide some basic functionality TinyMCE doesn't provide out of the box.
     *
     * @namespace Fisma
     * @class TinyMCE
     */
    var FT = {
        _isInitialized: false,
        _initCallbacks: [],
        registerInitCallback : function(callback) {
            FT._initCallbacks.push(callback);
            if (FT._isInitialized) {
                FT.onInit();
            }
        },
        onInit: function() {
            var c;
            while (FT._initCallbacks.length > 0) {
                c = FT._initCallbacks.pop();
                c.call(c);
            }
            FT._isInitialized = true;
        }
    };

    Fisma.TinyMCE = FT;
}());
