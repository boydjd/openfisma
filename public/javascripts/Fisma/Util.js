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
     * Show confirm window with warning message. config object can have width, text, isLink, url and func
     * 
     * @param event
     * @param config 
     */
    showConfirmDialog : function (event, config) {
            var  warningDialog =  
            new YAHOO.widget.SimpleDialog("warningDialog",  
                { width: config.width ? config.width : "300px", 
                  fixedcenter: true, 
                  visible: false, 
                  draggable: false, 
                  close: true,
                  modal: true,
                  text: config.text, 
                  icon: YAHOO.widget.SimpleDialog.ICON_WARN, 
                  constraintoviewport: true, 
                  buttons: [ { text:"Yes", handler : function () {
                                     if (config.url) {
                                         document.location = config.url;
                                     }else if(config.func) {
                                         var funcObj = Fisma.Util.getObjectFromName(config.func);
                                         if ('function' ===  typeof funcObj) {
                                             funcObj.apply(this, config.args);
                                         }
                                     }
                                     
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
        if (config.isLink) {
            YAHOO.util.Event.preventDefault(event);
        }
    },
 
    /**
     * Show alert warning message. The config object can have width, text and zIndex property
     * 
     * @param config object
     */
    showAlertDialog : function (config) {
        var  warningDialog =  
            new YAHOO.widget.SimpleDialog("warningDialog",  
                { width: config.width ? config.width : "400px", 
                  fixedcenter: true, 
                  visible: true, 
                  close: true,
                  modal: false,
                  text: config.text, 
                  icon: YAHOO.widget.SimpleDialog.ICON_WARN, 
                  constraintoviewport: true, 
                  effect:{
                         effect: YAHOO.widget.ContainerEffect.FADE,
                         duration: 0.25
                  }, 
                  zIndex: config.zIndex ? config.zIndex : null ,
                  draggable: true,
                  buttons: [ { text:"Ok", handler : function () {
                                     this.hide();
                                 }
                             } 
                        ] 
                } ); 
 
        warningDialog.setHeader("WARNING");
        warningDialog.render(document.body);
        warningDialog.show();
    }
};
