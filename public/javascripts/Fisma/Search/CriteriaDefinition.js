/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * @fileoverview Defines what search criteria are available in Fisma.Search.Criteria
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Search.CriteriaDefinition = function () {
    return {
        date : {
            dateAfter : {label : "After", renderer : 'singleDate'},
            dateBefore : {label : "Before", renderer : 'singleDate'},
            dateBetween : {label : "Between", renderer : 'betweenDate'},
            singleDate : {label : "Is", renderer : 'singleDate', isDefault : true},
            dateToday : {label : "Today", renderer : 'none'},
            dateThisWeek : {label : "This Week", renderer : 'none'},
            dateThisMonth : {label : "This Month", renderer : 'none'},
            dateThisYear : {label : "This Year", renderer : 'none'}
        },

        integer : {
            integerBetween : {label : "Between", renderer : 'betweenInteger'},
            integerDoesNotEqual : {label : "Does Not Equal", renderer : 'singleInteger'},
            integerEquals : {label : "Equals", renderer : 'singleInteger', isDefault : true},
            integerGreaterThan : {label : "Greater Than", renderer : 'singleInteger'},
            integerLessThan : {label : "Less Than", renderer : 'singleInteger'}
        },

        text : {
            textContains : {label : "Contains", renderer : 'text', isDefault : true},
            textDoesNotContain : {label : "Does Not Contain", renderer : 'text'}
        }
    };
}();