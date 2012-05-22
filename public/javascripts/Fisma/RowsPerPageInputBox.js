/**
 * Copyright (c) 2012 Endeavor Systems, Inc.
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
 * @ Text input type of YUI paginator
 *
 * @author    Mark Ma <mark.ma@reyosoft.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */
(function () {

    /**
     * ui Component to generate the rows-per-page input box. 
     *
     * @namespace YAHOO.widget.Paginator.ui
     * @class RowsPerPageInputbox
     * @for YAHOO.widget.Paginator
     *
     * @constructor
     * @param p {Pagintor} Paginator instance to attach to
     */
    YAHOO.widget.Paginator.ui.RowsPerPageInputBox = function (p) { 
        this.paginator = p; 
        this.construct(); 
    }; 

    /**
     * Decorates Paginator instances with new attributes. Called during
     * Paginator instantiation.
     * @param p {Paginator} Paginator instance to decorate
     * @static
     */
    YAHOO.widget.Paginator.ui.RowsPerPageInputBox.init = function (p) { 

        // Text label for the input box.
        p.setAttributeConfig('inputBoxLabel', { 
            value : 'Results Per Page', 
            validator : YAHOO.lang.isString 
            }); 
    }; 
	 
    YAHOO.widget.Paginator.ui.RowsPerPageInputBox.prototype = { 

        /**
         * input node
         */
        inputBox  : null, 
	 
        construct : function () { 

            // When rowsPerPerPage changes, update the UI 
            this.paginator.subscribe('rowsPerPageChange', this.update, this, true); 
	 
            // When myPaginator.destroy() is called, destroy this instance  UI 
            this.paginator.subscribe('beforeDestroy', this.destroy, this, true); 
	}, 
	 
        /**
         * Generate the label and input nodes and returns the label node.
         * @param id_base {string} used to create unique ids for generated nodes
         * @return {HTMLElement}
         */
        render : function (id_base) { 
	 
            this.inputBox = document.createElement('input');
            this.inputBox.type = 'text';
            this.inputBox.id = id_base + 'rowsPerPageBox';
            this.inputBox.style.marginLeft = '10px';            
            this.inputBox.style.width = '30px';            

            var labelEle = document.createElement('label');
               
            labelEle.innerHTML = this.paginator.get('inputBoxLabel');
            labelEle.appendChild(this.inputBox);

            this.initEvents();
            this.update(); 
	 
	    return labelEle; 

        }, 
	
        /**
         * Add the onChange and onKeydown event the inputBox.
         */
        initEvents : function() {
            YAHOO.util.Event.on(this.inputBox, 'change', this.onChange, this, true); 

            // IE does not handle [ENTER] keydown with onChange event, so, have to add onKeydown event.
            YAHOO.util.Event.on(this.inputBox, "keydown", this.onEnterKeyDown, this, true);
        },

        /**
         * Update the input box value if changed.
         * @param e {CustomEvent} The calling change event
         */
        update : function (e) { 
            if (e && e.prevValue === e.newValue) { 
                return; 
            } 
            this.inputBox.value = this.paginator.get('rowsPerPage');
        }, 
	 
        /**
         * Listener for the input's onchange event. Sent to setRowsPerPage method.
         * @param e {DOMEvent} The change event
         */
        onChange : function (e) { 
            var rows = parseInt(this.inputBox.value,10);
            YAHOO.util.Event.stopEvent(e); 
            
            if (!isNaN(rows)) {
                this.paginator.setRowsPerPage(rows);

                var rowsPerPageStorage = new Fisma.PersistentStorage('Fisma.RowsPerPage');
                rowsPerPageStorage.set('row', rows);
                rowsPerPageStorage.sync();
            }
        }, 
	 
        /**
         * Listener for the input's on [Enter] keydown event. Sent to setRowsPerPage method.
         * @param e {DOMEvent} The keydown event
         */
        onEnterKeyDown : function (e) { 
            if ((e.which && e.which === 13) || (e.keyCode && e.keyCode === 13)) {
               var rows = parseInt(this.inputBox.value,10);
              YAHOO.util.Event.stopEvent(e); 
            
                if (!isNaN(rows)) {
                    this.paginator.setRowsPerPage(rows);

                    var rowsPerPageStorage = new Fisma.PersistentStorage('Fisma.RowsPerPage');
                    rowsPerPageStorage.set('row', rows);
                    rowsPerPageStorage.sync();
                }
            }
        },
 
        /**
         * Removes the input node and clears event listeners
         */
        destroy : function () { 
            YAHOO.util.Event.purgeElement(this.inputBox, true); 
            if (this.inputBox && this.inputBox.parentNode) { 
                this.inputBox.parentNode.removeChild(this.inputBox); 
	    } 
            this.inputBox = null; 
        } 
    }; 
})();
