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
            popupCalendarDiv.style.zIndex = 1000;
            textEl.parentNode.appendChild(popupCalendarDiv);

            var textFieldPosition = YAHOO.util.Dom.getRegion(textEl);
            var calendarPosition = [
                textFieldPosition.left,
                textFieldPosition.bottom + 5
            ];

            YAHOO.util.Dom.setXY(popupCalendarDiv, calendarPosition);

            var calendar = new YAHOO.widget.Calendar(popupCalendarDiv, {close : true, title : 'Pick A Date'});
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
        }
    };

    Fisma.Calendar = Calendar;
}());
