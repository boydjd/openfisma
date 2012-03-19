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
 * @fileoverview When a form containing editable fields is loaded (such as the tabs on the
 *               remediation detail page), this function is used to add the required click
 *               handler to all of the editable fields.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    // Extending HTML Element
    if (window.HTMLElement) {
        Object.defineProperty(window.HTMLElement.prototype, "canHaveChildren", {
            get: function() {
                switch(this.tagName.toLowerCase()){
                    case "area":
                    case "base":
                    case "basefont":
                    case "col":
                    case "frame":
                    case "hr":
                    case "img":
                    case "br":
                    case "input":
                    case "isindex":
                    case "link":
                    case "meta":
                    case "param":
                    return false;
                }
                return true;
            }
        });
    }


    var FE = new Object();
    /**
     * Replace editable fields with appropriate form elements
     */
    FE.setupEditFields = function() {
        var editable = YAHOO.util.Selector.query('.editable');
        YAHOO.util.Event.on(editable, 'click', function (o){
            var t_name = this.getAttribute('target');
            YAHOO.util.Dom.removeClass(this, 'editable');
            this.removeAttribute('target');
            if(t_name) {
                var target = document.getElementById(t_name);
                var name = target.getAttribute('name');
                var type = target.getAttribute('type');
                var url = target.getAttribute('href');
                var eclass = target.className;
                var oldWidth = target.offsetWidth;
                var oldHeight = target.offsetHeight;
                var cur_val = target.innerText ? target.innerText : target.textContent;
                var cur_html = target.innerHTML;
                if (type == 'text') {
                    jQuery(target).replaceWith('<input length="50" name="' + name
                                             + '" id="'+t_name+'" class="' + eclass+'" type="text" />');
                    textEl = document.getElementById(t_name);
                    // set value attribute using JS call instead of string concatenation
                    // so we don't have to worry about escaping special characters
                    textEl.setAttribute('value', cur_val.trim());
                    if (oldWidth < 200) {
                        oldWidth = 200;
                    }
                    textEl.style.width = (oldWidth - 10) + "px";
                    if (eclass == 'date') {
                        var target = document.getElementById(t_name);
                        Fisma.Calendar.addCalendarPopupToTextField(target);
                    }
                } else if( type == 'textarea' ) {
                    var row = target.getAttribute('rows');
                    var col = target.getAttribute('cols');
                    jQuery(target).replaceWith('<textarea id="'+name+'" rows="'+row+'" cols="'+col
                                             + '" name="' + name + '"></textarea>');
                    var textareaEl = document.getElementById(name);
                    textareaEl.value = cur_html;
                    textareaEl.style.width = oldWidth + "px";
                    textareaEl.style.height = oldHeight + "px";
                    tinyMCE.execCommand("mceAddControl", true, name);
                } else if (type == 'autocomplete') {
                    this.parentNode.removeChild(this);
                    Fisma.Editable.makeAutocomplete(target);
                } else {
                    if (val = target.getAttribute('value')) {
                        cur_val = val;
                    }
                    YAHOO.util.Connect.asyncRequest('GET', url+'value/'+cur_val.trim(), {
                        success: function(o) {
                            if(type == 'select') {
                                innerHTML = o.responseText.replace(/&lt;/g, "&amp;lt;");
                                targetHTML = '<input type="button" id="' + name + '-button"/>'
                                           + '<select id="' + name + '-select" name="' + name + '">'
                                           + innerHTML + '</select>';
                                jQuery(target).replaceWith(targetHTML);

                                YAHOO.util.Event.onContentReady(name + "-button", function () {
                                    // Fetch currently selected item
                                    var selectElement = document.getElementById(name + '-select');
                                    var selectedLabel = selectElement.options[selectElement.selectedIndex].innerHTML;

                                    // Create a Button using an existing <input> and <select> element
                                    var oMenuButton = new YAHOO.widget.Button(name + "-button", {
                                        label: selectedLabel.replace(/&amp;/g, "&"),
                                        type: "menu",
                                        menu: name + "-select"
                                    });

                                    // Register "click" event listener for the Button's Menu instance
                                    oMenuButton.getMenu().subscribe("click", function (p_sType, p_aArgs) {
                                        var oEvent = p_aArgs[0],       // DOM event
                                            oMenuItem = p_aArgs[1]; // MenuItem target of the event
                                        if (oMenuItem) {
                                            oMenuButton.set('label', oMenuItem.cfg.getProperty("text"));
                                        }
                                    });
                                });
                            }
                        },
                        failure: function(o) {alert('Failed to load the specified panel.');}
                    }, null);
                }
            }
        });
    };

    /**
     * Convert an element into an autocomplete text field
     */
    FE.makeAutocomplete = function (element) {

        // Create an autocomplete form control
        var container = document.createElement('div');
        container.className = "yui-ac";
        YAHOO.util.Dom.generateId(container);

        var hiddenTextField = document.createElement('input');
        hiddenTextField.type = "hidden";
        hiddenTextField.name = element.getAttribute("name");
        hiddenTextField.value = element.getAttribute("value");
        YAHOO.util.Dom.generateId(hiddenTextField);
        container.appendChild(hiddenTextField);

        var autocompleteTextField = document.createElement('input');
        autocompleteTextField.type = "text";
        autocompleteTextField.name = "autocomplete_" + element.id;
        autocompleteTextField.value = element.getAttribute("defaultValue");
        YAHOO.util.Dom.generateId(autocompleteTextField);
        container.appendChild(autocompleteTextField);

        var autocompleteResultsDiv = document.createElement('div');
        YAHOO.util.Dom.generateId(autocompleteResultsDiv);
        container.appendChild(autocompleteResultsDiv);

        var spinner = document.createElement('img');
        spinner.src = "/images/spinners/small.gif";
        spinner.className = "spinner";
        spinner.id = autocompleteResultsDiv.id + "Spinner"; // required by AC API
        container.appendChild(spinner);

        element.parentNode.replaceChild(container, element);

        // Set up the autocomplete hooks on the new form control
        YAHOO.util.Event.onDOMReady(
            Fisma.AutoComplete.init,
            {
                schema: [element.getAttribute("schemaObject"), element.getAttribute("schemaField")],
                xhr : element.getAttribute("xhr"),
                fieldId : autocompleteTextField.id,
                containerId: autocompleteResultsDiv.id,
                hiddenFieldId: hiddenTextField.id,
                queryPrepend: element.getAttribute("queryPrepend"),
                setupCallback: element.getAttribute('setupCallback')
            }
        );
    };

    Fisma.Editable = FE;
})();
