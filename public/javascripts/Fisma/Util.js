/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more 
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see 
 * {@link http://www.gnu.org/licenses/}.
 * 
 * @fileoverview Utility functions
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id: AttachArtifacts.js 3188 2010-04-08 19:35:38Z mhaase $
 */
 
Fisma.Util = {
    
    /**
     * Convert a string name of an object into a reference to that object.
     * 
     * For example, "Fisma.Foo.myFoo" returns a reference to the actual Fisma.Foo.myFoo object, if it exists, 
     * or null otherwise.
     * 
     * This is useful for sending references to objects in JSON syntax, since JSON cannot encode a reference directly.
     * 
     * @param objectName The string name of the object
     */
    getObjectFromName : function (objectName) {

        var pieces = objectName.split('.');
        var currentObj = window;

        for (piece in pieces) {
            currentObj = currentObj[pieces[piece]];

            if (currentObj == undefined) {
                throw "Specified object does not exist: " + objectName;
            }
        }

        // At this point, the current value of parentObj should be the object itself
        return currentObj;
    }
};
