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
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */
(function() {
    /**
     * Instantiate a global Fisma.Calendar.callCalendar on the elements' date style classes.
     */
    YAHOO.util.Event.onDOMReady(function () {
        var calendars = YAHOO.util.Selector.query('.date');

        for(var i = 0; i < calendars.length; i ++) {
            YAHOO.util.Event.on(
                calendars[i].getAttribute('id')+'_show',
               'click',
               Fisma.Calendar.callCalendar,
               calendars[i].getAttribute('id')
            );
        }
    });

    /**
     * Provides calendar related functionality
     * 
     * @namespace Fisma
     * @class Calendar
     */
    var Calendar = {
        /**
         * Add pop up calendar to a text field
         *
         * @method Calendar.addCalendarPopupToTextField
         * @param textEl {String} The container that the calendar is rendered into
         */
        addCalendarPopupToTextField: function (textEl) {
            var popupCalendarDiv = document.createElement('div');
            popupCalendarDiv.style.position = 'absolute';
            popupCalendarDiv.style.zIndex = 99;
            textEl.parentNode.appendChild(popupCalendarDiv);

            var textFieldPosition = YAHOO.util.Dom.getRegion(textEl);
            var calendarPosition = [
                textFieldPosition.left,
                textFieldPosition.bottom + 5
            ];

            YAHOO.util.Dom.setXY(popupCalendarDiv, calendarPosition);

            var calendar = new YAHOO.widget.Calendar(popupCalendarDiv, {close : true});
            calendar.hide();
            
            // Fix bug: the calendar needs to be rendered AFTER the current event dispatch returns
            setTimeout(function () {calendar.render();}, 0);

            textEl.onfocus = function () { calendar.show(); };

            var handleSelect = function (type, args, obj) {
                var dateParts = args[0][0]; 
                var year = dateParts[0], month = dateParts[1].toString(), day = dateParts[2].toString();

                if (1 === month.length) {
                    month = "0" + month;
                }

                if (1 === day.length) {
                    day = "0" + day;
                }

                textEl.value = year + '-' + month + '-' + day;

                calendar.hide();
            };

            calendar.selectEvent.subscribe(handleSelect, calendar, true);
        },

        /**
         * Use to call showCalendar function
         *
         * @method Calendar.callCalendar
         * @param event {String} The name of the event
         * @param ele {String} The element id
         */
        callCalendar: function(evt, ele) {alert('testa');
            this.showCalendar(ele, ele+'_show');
        },

        /**
         * Display a calendar to the element id
         *
         * @method Calendar.typeHandle
         * @param block {String} The element id
         * @param trigger {Array} The element id
         */
        showCalendar: function (block, trigger) {
            var Event = YAHOO.util.Event, Dom = YAHOO.util.Dom, dialog, calendar;

            var showBtn = Dom.get(trigger);

            var dialog;
            var calendar;

            /*
             * Lazy Dialog Creation - Wait to create the Dialog, and setup document click listeners, 
             * until the first time the button is clicked.
             */ 
            if (!dialog) {
                function resetHandler() {
                    Dom.get(block).value = '';
                    closeHandler();
                }

                function closeHandler() {
                    dialog.hide();
                }

                dialog = new YAHOO.widget.Dialog("container", {
                    visible:false,
                    context:[block, "tl", "bl"],
                    draggable:true,
                    close:true
                });
                
                dialog.setHeader('Pick A Date');
                dialog.setBody('<div id="cal"></div><div class="clear"></div>');
                dialog.render(document.body);

                dialogEl = document.getElementById('container');
                dialogEl.style.padding = "0px"; // doesn't format itself correctly in safari, for some reason

                dialog.showEvent.subscribe(function() {
                    if (YAHOO.env.ua.ie) {
                        // Since we're hiding the table using yui-overlay-hidden, we 
                        // want to let the dialog know that the content size has changed, when
                        // shown
                        dialog.fireEvent("changeContent");
                    }
                });
            }

            // Lazy Calendar Creation - Wait to create the Calendar until the first time the button is clicked.
            if (!calendar) {

                calendar = new YAHOO.widget.Calendar("cal", {
                    iframe:false,          // Turn iframe off, since container has iframe support.
                    hide_blank_weeks:true  // Enable, to demonstrate how we handle changing height, using changeContent
                });
                calendar.render();

                calendar.selectEvent.subscribe(function() {
                    if (calendar.getSelectedDates().length > 0) {
                        var selDate = calendar.getSelectedDates()[0];
                        // Pretty Date Output, using Calendar's Locale values: Friday, 8 February 2008
                        //var wStr = calendar.cfg.getProperty("WEEKDAYS_LONG")[selDate.getDay()];
                        var dStr = (selDate.getDate() < 10) ? '0'+selDate.getDate() : selDate.getDate();
                        var mStr = (selDate.getMonth()+1 < 10) ? '0'+(selDate.getMonth()+1) : (selDate.getMonth()+1);
                        var yStr = selDate.getFullYear();

                        Dom.get(block).value = yStr + '-' + mStr + '-' + dStr;
                    } else {
                        Dom.get(block).value = "";
                    }
                    dialog.hide();
                    if ('finding[currentEcd]' == Dom.get(block).name) {
                        validateEcd();
                    }
                });

                calendar.renderEvent.subscribe(function() {
                    // Tell Dialog it's contents have changed, which allows 
                    // container to redraw the underlay (for IE6/Safari2)
                    dialog.fireEvent("changeContent");
                });
            }

            var seldate = calendar.getSelectedDates();

            if (seldate.length > 0) {
                // Set the pagedate to show the selected date if it exists
                calendar.cfg.setProperty("pagedate", seldate[0]);
                calendar.render();
            }
            dialog.show();
        }
    };

    Fisma.Calendar = Calendar;
})();
