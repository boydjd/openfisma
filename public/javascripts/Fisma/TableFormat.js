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
 * @fileoverview Provides various formatters for use with YUI table
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 * @version   $Id: Incident.js 3288 2010-04-29 23:36:21Z mhaase $
 */

Fisma.TableFormat = {
    /**
     * CSS green color
     */
    greenColor : 'lightgreen',

    /**
     * CSS yellow color
     */
    yellowColor : 'yellow',

    /**
     * CSS red color
     */
    redColor : 'pink',

    /**
     * Color an element green
     */
    green : function (element) {
        element.style.backgroundColor = Fisma.TableFormat.greenColor;
    },

    /**
     * Color an element yellow
     */
    yellow : function (element) {
        element.style.backgroundColor = Fisma.TableFormat.yellowColor;
    },

    /**
     * Color an element red
     */
    red : function (element) {
        element.style.backgroundColor = Fisma.TableFormat.redColor;
    },

    /**
     * A formatter which colors the security authorization date in red, yellow, or green (or not at all)
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    securityAuthorization : function (elCell, oRecord, oColumn, oData) {
        elCell.innerHTML = oData;

        // Date format is YYYY-MM-DD. Convert into javascript date object.
        dateParts = oData.split('-');

        if (3 == dateParts.length) {

            authorizedDate = new Date(dateParts[0], dateParts[1], dateParts[2]);

            greenDate = new Date();
            greenDate.setMonth(greenDate.getMonth() - 30);

            yellowDate = new Date();
            yellowDate.setMonth(yellowDate.getMonth() - 36);

            if (authorizedDate >= greenDate) {
                Fisma.TableFormat.green(elCell.parentNode);
            } else if (authorizedDate >= yellowDate) {
                Fisma.TableFormat.yellow(elCell.parentNode);
            } else {
                Fisma.TableFormat.red(elCell.parentNode);
            }
        }
    },

    /**
     * A formatter which colors the self-assessment date in red, yellow, or green (or not at all)
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    selfAssessment : function (elCell, oRecord, oColumn, oData) {
        elCell.innerHTML = oData;

        // Date format is YYYY-MM-DD. Convert into javascript date object.
        dateParts = oData.split('-');

        if (3 == dateParts.length) {

            assessmentDate = new Date(dateParts[0], dateParts[1], dateParts[2]);

            greenDate = new Date();
            greenDate.setMonth(greenDate.getMonth() - 8);

            yellowDate = new Date();
            yellowDate.setMonth(yellowDate.getMonth() - 12);

            if (assessmentDate >= greenDate) {
                Fisma.TableFormat.green(elCell.parentNode);
            } else if (assessmentDate >= yellowDate) {
                Fisma.TableFormat.yellow(elCell.parentNode);
            } else {
                Fisma.TableFormat.red(elCell.parentNode);
            }
        }
    },

    /**
     * A proxy for selfAssessment() above -- they have identical formatting logic
     */
    contingencyPlanTest : function (elCell, oRecord, oColumn, oData) {
        Fisma.TableFormat.selfAssessment(elCell, oRecord, oColumn, oData);
    },

    /**
     * A formatter which colors cells green if the value is YES, and red if the value is NO
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    yesNo : function (elCell, oRecord, oColumn, oData) {
        elCell.innerHTML = oData;

        if ('YES' == oData) {
            Fisma.TableFormat.green(elCell.parentNode);
        } else if ('NO' == oData) {
            Fisma.TableFormat.red(elCell.parentNode);
        }
    },

    /**
     * A formatter which displays an edit icon that is linked to an edit page
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
     editControl : function (elCell, oRecord, oColumn, oData) {

        var icon = document.createElement('img');
        icon.src = '/images/edit.png';

        var link = document.createElement('a');
        link.href = oData;
        link.appendChild(icon);

        elCell.appendChild(link);
    },

    /**
     * A formatter which displays a delete icon that is linked to an edit page
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    deleteControl : function (elCell, oRecord, oColumn, oData) {

        var icon = document.createElement('img');
        icon.src = '/images/del.png';

        var link = document.createElement('a');
        link.href = oData;
        link.appendChild(icon);

        elCell.appendChild(link);
    },

    /**
     * A formatter which converts escaped HTML into unescaped HTML
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    formatHtml : function(el, oRecord, oColumn, oData) {
        el.innerHTML = oData.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">");
    },

    /**
     * A formatter which displays the total of overdue findings that is linked to a finding search page
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    overdueFinding : function (elCell, oRecord, oColumn, oData) {

        // Construct overdue finding search url
        overdueFindingSearchUrl = '/finding/remediation/list/queryType/advanced';

        // Handle organization field
        var organization = oRecord.getData('System');

        if (organization) {
        
            // Since organization may be html-encoded, decode the html before (url)-escaping it
            organization = $P.html_entity_decode(organization);
            
            overdueFindingSearchUrl += "/organization/textExactMatch/" + escape(organization);
        }

        // Handle status field
        var status = oRecord.getData('Status');

        if (status) {
            status = PHP_JS().html_entity_decode(status);
            overdueFindingSearchUrl += "/denormalizedStatus/textExactMatch/" + escape(status);
        }

        // Handle source field
        var parameters = oColumn.formatterParameters;

        if (parameters.source) {
            overdueFindingSearchUrl += "/source/textExactMatch/" + escape(parameters.source);
        }

        // Handle date fields
        var from = null;

        if (parameters.from) {
            fromDate = new Date();
            fromDate.setDate(fromDate.getDate() - parseInt(parameters.from, 10));
            
            from = fromDate.getFullYear() + '-' + (fromDate.getMonth() + 1) + '-' + fromDate.getDate();
        }

        var to = null;

        if (parameters.to) {
            toDate = new Date();
            toDate.setDate(toDate.getDate() - parseInt(parameters.to, 10));
            
            to = toDate.getFullYear() + '-' + (toDate.getMonth() + 1) + '-' + toDate.getDate();
        }

        if (from && to) {
            overdueFindingSearchUrl += "/nextDueDate/dateBetween/" + to + "/" + from;
        } else if (from) {
            overdueFindingSearchUrl += "/nextDueDate/dateBefore/" + from;
        } else {
            // This is the TOTAL column
            var yesterday = new Date();
            yesterday.setDate(yesterday.getDate() - 1);
            var yesterdayString = yesterday.getFullYear() 
            yesterdayString += '-' 
            yesterdayString += (yesterday.getMonth() + 1) 
            yesterdayString += '-' 
            yesterdayString += yesterday.getDate();

            overdueFindingSearchUrl += "/nextDueDate/dateBefore/" + yesterdayString;
        }

        elCell.innerHTML = "<a href="
                         + overdueFindingSearchUrl
                         + ">"
                         + oData
                         + "</a>";
    },

    /**
     * A formatter which colors the the percentage of the required documents 
     * which system has completed in red, yellow, or green (or not at all)
     * 
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    completeDocTypePercentage : function (elCell, oRecord, oColumn, oData) {
        percentage = parseInt(oData, 10);

        if (oData !== null) {
            elCell.innerHTML = oData + "%";

            if (percentage >= 95 && percentage <= 100) {
                Fisma.TableFormat.green(elCell.parentNode);
            } else if (percentage >= 80 && percentage < 95) {
                Fisma.TableFormat.yellow(elCell.parentNode);
            } else if (percentage >= 0 && percentage < 80) {
                Fisma.TableFormat.red(elCell.parentNode);
            }
        }
    },

    /**
     * A formatter which displays the missing document type name
     * 
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    incompleteDocumentType : function (elCell, oRecord, oColumn, oData) {
        var docTypeNames = '';
        if (oData.length > 0) {
            docTypeNames += '<ul><li>'
            docTypeNames += oData.replace(/,/g, '</li><li>')
            docTypeNames += '</li></ul>';
        }

        elCell.innerHTML = docTypeNames;
    },
    
    /**
     * Creates a checkbox element that can be used to select the record. If the model has soft delete and 
     * any of the records are deleted, then the checkbox is replaced by an icon so that user's don't try to 
     * "re-delete" any already-deleted items.
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    formatCheckbox : function (elCell, oRecord, oColumn, oData) {
        
        if (oRecord.getData('deleted_at')) {

            elCell.parentNode.style.backgroundColor = "pink";
            
        } else {
            var checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.className = YAHOO.widget.DataTable.CLASS_CHECKBOX;
            checkbox.checked = oData;

            if (elCell.firstChild) {
                elCell.removeChild(el.firstChild);            
            }

            elCell.appendChild(checkbox);
        }        
    }
};
