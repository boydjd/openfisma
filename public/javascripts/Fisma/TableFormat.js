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
      }
};