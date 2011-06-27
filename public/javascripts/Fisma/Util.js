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
 */
 
Fisma.Util = {
    
    /**
     * Escapes the specified string so that it can be included in a regex without special characters affecting
     * the regex's meaning
     * 
     * Special characters are: .*+?|()[]{}\
     * 
     * @param rawValue Unescaped input
     */
    escapeRegexValue : function (rawValue) {
        var specials = new RegExp("[.*+?|()\\[\\]{}\\\\]", "g");
        
        return rawValue.replace(specials, "\\$&");
    },

    /**
     * Convert a string name of an object into a reference to that object.
     * 
     * For example, "Fisma.Foo.myFoo" returns a reference to the actual Fisma.Foo.myFoo object, if it exists, 
     * or throws an error if does not exist.
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
    },
    
    /**
     * Positions the specified panel relative to the specified element.
     * 
     * The goal is to put the panel near the specified element (typically, just underneath and left-aligned) but with
     * the added constraint of not positioning the panel anywhere that will be clipped by the current viewport.
     * 
     * @param panel
     * @param referenceElement
     */
    positionPanelRelativeToElement : function (panel, referenceElement) {

        // This is a constant which indicates how far down the panel should be below the reference element
        var VERTICAL_OFFSET = 5;

        panel.cfg.setProperty(
            "context", 
            [
                referenceElement, 
                YAHOO.widget.Overlay.TOP_LEFT, 
                YAHOO.widget.Overlay.BOTTOM_LEFT,
                null,
                [0, VERTICAL_OFFSET]
            ]);        
    },
    
    /**
     * Generate a timestamp in the format HH:MM:SS, using 24 hour time
     */
    getTimestamp : function () {
        var date = new Date();

        var hours = date.getHours() + "";

        if (hours.length == 1) {
         hours = "0" + hours;
        }

        var minutes = date.getMinutes() + "";

        if (minutes.length == 1) {
         minutes = "0" + minutes;
        }

        var seconds = date.getSeconds() + "";

        if (seconds.length == 1) {
         seconds = "0" + seconds;
        }

        return hours + ":" + minutes + ":" + seconds;
    },

    /**
     * Show a warning message before a record is deleted.
     */
    showDeleteWarning : function (event, config) {
        var  warningDialog =  
            new YAHOO.widget.SimpleDialog("warningDialog",  
                { width: "300px", 
                  fixedcenter: true, 
                  visible: false, 
                  draggable: false, 
                  close: true,
                  modal: true,
                  text: "WARNING: You are about to delete the record. This action cannot be undone. "
                         + "Do you want to continue?", 
                  icon: YAHOO.widget.SimpleDialog.ICON_WARN, 
                  constraintoviewport: true, 
                  buttons: [ { text:"Yes", handler : function () {
                                     document.location = config.url
                                     this.hide();
                                 }
                             }, 
                             { text:"No",  handler : function () {
                                     this.hide(); 
                                 }
                             } 
                           ] 
                } ); 
 
        warningDialog.setHeader("Are you sure?");
        warningDialog.render(document.body);
        warningDialog.show();
        YAHOO.util.Event.preventDefault(event);
    }
};
