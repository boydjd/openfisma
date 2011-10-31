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
 * @copyright (c) Endeavor Systems, Inc. 2011 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * Provides various parsers for use with YUI table
     * 
     * @namespace Fisma
     * @class TableParse
     */
    var TableParse = {
        /**
         * Convert the file size with unit to total number
         *
         * @param oData {String} Data to convert.
         * @return {Number} A number, or null.
         */
        parseFileSize : function(oData) {
            var string = YAHOO.lang.trim(oData);

            if (string.indexOf(' ') !== -1) {
                var sizeParts = string.split(' ');
                var size = sizeParts[0];
                var unit = sizeParts[1];

                switch (unit) {
                    case "bytes":
                        return size;
                    case "KB":
                        return size * 1024;
                    case "MB":
                        return size * 1024 * 1024;
                    case "GB":
                        return size * 1024 * 1024 * 1024;
                    default:
                        throw "Invalid file unit";
                }
            }

            return null;
        }
    };

    Fisma.TableParse = TableParse;
})();
