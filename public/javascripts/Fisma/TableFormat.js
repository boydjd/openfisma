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

    month : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],

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
        var dateParts = oData.split('-');

        if (3 === dateParts.length) {

            var authorizedDate = new Date(dateParts[0], dateParts[1], dateParts[2]);

            var greenDate = new Date();
            greenDate.setMonth(greenDate.getMonth() - 30);

            var yellowDate = new Date();
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
        var dateParts = oData.split('-');

        if (3 === dateParts.length) {

            var assessmentDate = new Date(dateParts[0], dateParts[1], dateParts[2]);

            var greenDate = new Date();
            greenDate.setMonth(greenDate.getMonth() - 8);

            var yellowDate = new Date();
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
     * A formatter which wrap the img element around the source URI
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    imageControl : function (elCell, oRecord, oColumn, oData) {
        var img = document.createElement('img');
        img.src = oData;
        elCell.appendChild(img);
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
        elCell.parentNode.style.textAlign = 'center';

        if ('YES' === oData) {
            Fisma.TableFormat.green(elCell.parentNode);
        } else if ('NO' === oData) {
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

        var icon = document.createElement('img'),
            link;
        icon.src = '/images/edit.png';

        if (typeof oData === "object") {
            $(icon).click(function() {
                oData.func.call({}, oData.param);
            });
            link = icon;
        } else {
            link = document.createElement('a');
            link.href = oData;
            link.appendChild(icon);
        }

        while (elCell.hasChildNodes()) {
            elCell.removeChild(elCell.firstChild);
        }
        elCell.appendChild(link);
    },

    /**
     * A formatter which displays a delete icon that is linked to a delete action
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    deleteControl : function (elCell, oRecord, oColumn, oData) {
        if (oData) {
            var icon = document.createElement('img');
            icon.src = '/images/del.png';

            while (elCell.hasChildNodes()) {
                elCell.removeChild(elCell.firstChild);
            }
            elCell.appendChild(icon);
            elCell.parentNode.style.textAlign = 'center';

            YAHOO.util.Event.on(icon, "click", function() {
                Fisma.Util.formPostAction(null, oData, null);
            });
        }
    },

    /**
     * A formatter which used to convert escaped HTML into unescaped HTML ...
     * Now it uses the default formatter, for a few reasons:
     *    1. We don't store html unescaped anymore, unless it's unsafe html
     *    2. The javascript in data-table-local.phtml is wrong, and doesn't execute YAHOO formatters properly
     *    3. Using this as it was resulted in an XSS vulnerability, and I don't have time to rewrite the entire
     *       implementation so that it works properly.
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     * @TODO Fix data-table-local.phtml script so that it works with YAHOO formatters
     * @deprecated
     */
    formatHtml : function(el, oRecord, oColumn, oData) {
        YAHOO.widget.DataTable.formatDefault.apply(this, arguments);
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
        var overdueFindingSearchUrl = '/finding/remediation/list?q=';

        // Handle organization field
        var organization = oRecord.getData('System');

        if (organization) {

            // Since organization may be html-encoded, decode the html before (url)-escaping it
            organization = $P.html_entity_decode(organization);

            overdueFindingSearchUrl += "/organization/textExactMatch/" + encodeURIComponent(organization);
        }

        // Handle status field
        var status = oRecord.getData('Status');

        if (status) {
            status = PHP_JS().html_entity_decode(status);
            overdueFindingSearchUrl += "/denormalizedStatus/enumIs/" + encodeURIComponent(status);
        }

        // Handle source field
        var parameters = oColumn.formatterParameters;

        if (parameters.source) {
            overdueFindingSearchUrl += "/source/textExactMatch/" + encodeURIComponent(parameters.source);
        }

        // Handle date fields
        var from = null;

        if (parameters.from) {
            var fromDate = new Date();
            fromDate.setDate(fromDate.getDate() - parseInt(parameters.from, 10));

            from = fromDate.getFullYear() + '-' + (fromDate.getMonth() + 1) + '-' + fromDate.getDate();
        }

        var to = null;

        if (parameters.to) {
            var toDate = new Date();
            toDate.setDate(toDate.getDate() - parseInt(parameters.to, 10));

            to = toDate.getFullYear() + '-' + (toDate.getMonth() + 1) + '-' + toDate.getDate();
        }

        if (from && to) {
            overdueFindingSearchUrl += "/nextDueDate/dateBetween/" +
                                        encodeURIComponent(to) +
                                        "/" +
                                        encodeURIComponent(from);
        } else if (from) {
            overdueFindingSearchUrl += "/nextDueDate/dateBefore/" + encodeURIComponent(from);
        } else {

            // This is the TOTAL column
            var todayString = $P.date('Y-m-d');
            overdueFindingSearchUrl += "/nextDueDate/dateBefore/" + encodeURIComponent(todayString);
        }

        elCell.innerHTML = '<a href="' + overdueFindingSearchUrl + '">' + oData + "</a>";
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
        var percentage = parseInt(oData, 10);

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
            docTypeNames += '<ul><li>';
            docTypeNames += oData.replace(/,/g, '</li><li>');
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
                elCell.removeChild(elCell.firstChild);
            }

            elCell.appendChild(checkbox);
        }
    },

    /**
     * A formatter which displays the size of file with unit
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    formatFileSize : function (elCell, oRecord, oColumn, oData) {
        // Convert to number
        var size = parseInt(oData, 10);

        if(YAHOO.lang.isNumber(size)) {
            if (size < 1024) {
                size = size + ' bytes';
            } else if (size < (1024 * 1024)) {
                size = (size / 1024).toFixed(1) + ' KB';
            } else if (size < (1024 * 1024 * 1024)) {
                size = (size / (1024 * 1024)).toFixed(1) + ' MB';
            } else {
                size = (size / (1024 * 1024 * 1024)).toFixed(1) + ' GB';
            }

            elCell.innerHTML = size;
        }
    },

    /**
     * Show a control that can be used to remove the current record from the table.
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    remover: function (elCell, oRecord, oColumn, oData) {
        // Put table in closure scope
        var table = this;

        var img = document.createElement('img');
        img.src = '/images/delete_row.png';
        YAHOO.util.Event.on(
            img,
            "click",
            function () {
                YAHOO.util.Event.removeListener(img, "click");
                img.src = "/images/spinners/small.gif";

                Fisma.Incident.removeUser(
                    oRecord.getData('incidentId'),
                    oRecord.getData('userId'),
                    table
                );
            }
        );

        elCell.appendChild(img);
    },

    /**
     * A formatter which displays Yes or No for a boolean value
     *
     * The highlighting engine seems to do something very strange with boolean values:
     * true, no highlight: true (boolean value)
     * false, no highlight: false (boolean value)
     * true, highlighted: "******T" (string value)
     * false, highlighted: "******F" (string value)
     * The code below compensates for this.
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    formatBoolean : function (elCell, oRecord, oColumn, oData) {
        var cell = $(elCell);
        if (oData === true) {
            cell.text("Yes");
        } else if (oData === false) {
            cell.text("No");
        } else {
            cell.html($("<span/>").addClass("highlight").text(oData.substr(oData.length - 1) === "T" ? "Yes" : "No"));
        }
    },

    /**
     * A formatter which displays date as Jun 12, 2012
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    formatDate : function (elCell, oRecord, oColumn, oData) {
        if (oData) {
            var month = parseInt(oData.substr(5, 2), 10) - 1;

            var date = new Date();
            date.setFullYear(oData.substr(0,4));
            date.setMonth(month);
            date.setDate(oData.substr(8, 2));

            elCell.innerHTML = Fisma.TableFormat.month[date.getMonth()]
                              + ' '
                              + date.getDate()
                              + ', '
                              + date.getFullYear();
        }
    },

    /**
     * A formatter which displays date and time as Jun 12, 2012 at 2:24 AM
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The data stored in this cell
     */
    formatDateTime : function (elCell, oRecord, oColumn, oData) {
        if (oData) {
            var month = parseInt(oData.substr(5, 2), 10) - 1;

            var date = new Date();
            date.setFullYear(oData.substr(0,4));
            date.setMonth(month);
            date.setDate(oData.substr(8, 2));
            date.setHours(oData.substr(11, 2));
            date.setMinutes(oData.substr(14, 2));

            var hours = date.getHours();
            var am = true;
            if (hours > 12) {
                am = false;
                hours -= 12;
            } else if (hours === 12) {
                am = false;
            } else if (hours === 0) {
                hours = 12;
            }

            elCell.innerHTML = Fisma.TableFormat.month[date.getMonth()]
                              + ' '
                              + date.getDate()
                              + ', '
                              + date.getFullYear()
                              + ' at '
                              + hours
                              + ':'
                              + date.getMinutes()
                              + (am ? ' AM' : ' PM');
        }
    },

    /**
     * A formatter for organization / system with an icon
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The jsonified literal object
     *     {
     *         iconId,
     *         iconSize ('small' or 'large'),
     *         orgId (optional, will generate an <a> element if provided),
     *         displayName
     *     }
     */
    formatOrganization : function (elCell, oRecord, oColumn, oData) {
        oData = YAHOO.lang.JSON.parse(oData);
        if (oData) {
            if (oData.displayName) {
                elCell.innerHTML = oData.displayName;
            }
            if (oData.orgId) {
                elCell.innerHTML = "<span class='organizationInfo' "
                                 + "onclick='Fisma.Organization.displayInfo(this, " + oData.orgId + ");'"
                                 + "title='Click to show detailed information'>"
                                 + elCell.innerHTML
                                 + "</span>";
            }
            if (oData.iconId) {
                elCell.innerHTML = "<img "
                                 + "style='vertical-align:text-bottom;' "
                                 + "src='/icon/get/id/" + oData.iconId + "/size/small'/> " + elCell.innerHTML;
            }
        }
    },

    /**
     * A formatter for a link
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The jsonified literal object
     *     {
     *         url,
     *         displayText
     *     }
     */
    formatLink : function (elCell, oRecord, oColumn, oData) {
        oData = (YAHOO.lang.isObject(oData)) ? oData : YAHOO.lang.JSON.parse(oData);
        if (oData) {
            if (oData.displayText) {
                elCell.innerHTML = oData.displayText;
            }
            if (oData.url) {
                elCell.innerHTML = "<a href='" + oData.url + "'>" + elCell.innerHTML + "</a>";
            }
        }
    },

    /**
     * A formatter for a stacked bar by threat
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The jsonified literal object
     *     {
     *         LOW,
     *         MODERATE,
     *         HIGH,
     *         criteriaQuery (field/operator/),
     *         total (to calculate percentage)
     *     }
     */
    formatThreatBar : function (elCell, oRecord, oColumn, oData) {
        oData = YAHOO.lang.JSON.parse(oData);
        var linkData = YAHOO.lang.JSON.parse(oRecord.getData('displayTotal'));
        var html = "";
        if (oData.LOW) {
            html += "<a href='" + linkData.url + oData.criteriaQuery + "LOW' title='" + oData.LOW + "'>";
            html += "<span class='bar LOW' style='width:" + oData.LOW / oData.total * 80 + "%;'></span>";
            html += "</a>";
        }
        if (oData.MODERATE) {
            html += "<a href='" + linkData.url + oData.criteriaQuery + "MODERATE' title='" + oData.MODERATE + "'>";
            html += "<span class='bar MODERATE' style='width:" + oData.MODERATE / oData.total * 80 + "%;'></span>";
            html += "</a>";
        }
        if (oData.HIGH) {
            html += "<a href='" + linkData.url + oData.criteriaQuery + "HIGH' title='" + oData.HIGH + "'>";
            html += "<span class='bar HIGH' style='width:" + oData.HIGH / oData.total * 80 + "%;'></span>";
            html += "</a>";
        }
        var percentage = 100 * (
            parseInt(oData.LOW, 10) + parseInt(oData.MODERATE, 10) + parseInt(oData.HIGH, 10)
        ) / oData.total;
        if (percentage > 0 && percentage < 1) {
            html += '&nbsp;less than 1%';
        } else {
            html += '&nbsp;' + Math.round(percentage) + '%';
        }
        elCell.innerHTML = html;
        elCell.width = '200px';
    },

    /**
     * A formatter for comments
     *
     * @param elCell Reference to a container inside the <td> element
     * @param oRecord Reference to the YUI row object
     * @param oColumn Reference to the YUI column object
     * @param oData The jsonified literal array:
     *      [
     *          ['first comment username', 'first comment date', 'first comment text'],
     *          ['second comment username', 'second comment date', 'second comment text'],
     *          ...
     *      ]
     */
    formatComments : function (elCell, oRecord, oColumn, oData) {
        // null/undefined: just bail
        if (!oData) {
            return;
        }

        // helper function
        function highlightAndEscape(val) {
            // hold the result in an element
            var elem = $("<div />");
            elem.text(val); // escape
            // split on the highlight delimiter, ***
            var peices = elem.html().split("***");
            // clear out the element
            elem.html("");
            $.each(peices, function (i, v) {
                // odd peices are highlighted
                if (i % 2) {
                    var span = $("<span>" + v + "</span>");
                    span.addClass("highlight");
                    elem.append(span);
                // even peices are not highlighted, append the HTMLified text
                } else {
                    elem.append(v);
                }
            });
            return elem.html();
        }
        oData = YAHOO.lang.JSON.parse(oData);
        var cell = $(elCell);
        cell.html("");
        $.each(oData, function(i, v) {
            var div = $("<div>");
            div.append('<b>' + highlightAndEscape(v[0]) + '</b>');
            div.append(" on ");
            div.append(highlightAndEscape(v[1]));
            div.append(":<br/>");
            div.append(highlightAndEscape(v[2]));
            div.appendTo(cell);
        });
    }
};
