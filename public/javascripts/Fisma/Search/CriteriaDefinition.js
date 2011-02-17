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
            dateAfter : {label : "After", renderer : 'singleDate', query : 'oneInput'},
            dateBefore : {label : "Before", renderer : 'singleDate', query : 'oneInput'},
            dateBetween : {label : "Between", renderer : 'betweenDate', query : 'twoInputs'},
            dateDay : {label : "Is", renderer : 'singleDate', query : 'oneInput', isDefault : true},
            dateThisMonth : {label : "This Month", renderer : 'none', query : 'noInputs'},
            dateThisYear : {label : "This Year", renderer : 'none', query : 'noInputs'},
            dateToday : {label : "Today", renderer : 'none', query : 'noInputs'}
        },

        "float" : {
            floatBetween : {label : "Between", renderer : 'betweenFloat', query : 'twoInputs'},
            floatGreaterThan : {label : "Greater Than", renderer : 'singleFloat', query : 'oneInput', isDefault : true},
            floatLessThan : {label : "Less Than", renderer : 'singleFloat', query : 'oneInput'}            
        },

        integer : {
            integerBetween : {label : "Between", renderer : 'betweenInteger', query : 'twoInputs'},
            integerDoesNotEqual : {label : "Does Not Equal", renderer : 'singleInteger', query : 'oneInput'},
            integerEquals : {label : "Equals", renderer : 'singleInteger', query : 'oneInput', isDefault : true},
            integerGreaterThan : {label : "Greater Than", renderer : 'singleInteger', query : 'oneInput'},
            integerLessThan : {label : "Less Than", renderer : 'singleInteger', query : 'oneInput'}
        },

        nonSortableText : {
            textContains : {label : "Contains", renderer : 'text', query : 'oneInput', isDefault : true},
            textDoesNotContain : {label : "Does Not Contain", renderer : 'text', query : 'oneInput'}
        },

        sortableText : {
            textContains : {label : "Contains", renderer : 'text', query : 'oneInput', isDefault : true},
            textDoesNotContain : {label : "Does Not Contain", renderer : 'text', query : 'oneInput'},
            textExactMatch : {label : "Exact Match", renderer : 'text', query : 'oneInput'},
            textNotExactMatch : {label : "Not Exact Match", renderer : 'text', query : 'oneInput'}
        },
        
        "enum" : {
            enumIs : {label : "Is", renderer : "enumSelect", query : "enumSelect", isDefault : true},
            enumIsNot : {label : "Is Not", renderer : "enumSelect", query : "enumSelect"}
        }
    };
}();