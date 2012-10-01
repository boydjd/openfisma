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

    var FE = {};

    /**
     * Handle the onclick event of the pencil icon
     */
    FE.handleClickEvent = function(o) {
        // ignore clicks on anchor tags
        var targetElement = o.target || o.srcElement;
        if (targetElement.tagName === "A") {
            return;
        }

        if (!$(this).hasClass('editable')) {
            return;
        }

        var t_name = $(this).attr('target');
        $(this).data('old_object', $('#' + t_name).clone());
        $(this).removeClass('editable');

        if (t_name) {
            var target = document.getElementById(t_name);
            var name = target.getAttribute('name');
            var type = target.getAttribute('type');
            var url = target.getAttribute('href');
            var eclass = target.className;
            var oldWidth = target.offsetWidth;
            var oldHeight = target.offsetHeight;
            var cur_val = target.innerText || target.textContent;
            var cur_html = target.innerHTML;
            var editable = this;
            if (type === 'text') {
                jQuery(target).html(
                    '<input length="50" name="' + name + '" id="txt_' + t_name + '" class="' + eclass + '" type="text" />'
                );
                var textEl = document.getElementById('txt_' + t_name);

                // set value attribute using JS call instead of string concatenation
                // so we don't have to worry about escaping special characters
                textEl.setAttribute('value', cur_val.trim());
                if (oldWidth < 200) {
                    oldWidth = 200;
                }

                textEl.style.width = (oldWidth - 10) + "px";
                if (eclass === 'date') {
                    Fisma.Calendar.addCalendarPopupToTextField(textEl);
                }
            } else if (type === 'textarea') {
                var row = target.getAttribute('rows');
                var col = target.getAttribute('cols');
                jQuery(target).html(
                    '<textarea id="txt_' + t_name + '" rows="' + row + '" cols="' + col + '" name="' + name + '"></textarea>'
                );
                var textareaEl = document.getElementById('txt_' + t_name);
                textareaEl.value = cur_html;
                textareaEl.style.width = oldWidth + "px";
                textareaEl.style.height = oldHeight + "px";
                tinyMCE.execCommand("mceAddControl", true, 'txt_' + t_name);
            } else if (type === 'autocomplete') {
                //this.parentNode.removeChild(this);
                Fisma.Editable.makeAutocomplete(target);
            } else {
                var val = target.getAttribute('value');
                if (val) {
                    cur_val = val;
                }
                YAHOO.util.Connect.asyncRequest('GET', url + 'value/' + cur_val.trim(), {
                    success: function(o) {
                        if (type === 'select') {
                            var innerHTML = o.responseText.replace(/&lt;/g, "&amp;lt;");
                            var targetHTML = '<input type="button" id="' + t_name + '-button"/>'
                                           + '<select id="' + t_name + '-select" name="' + name + '">'
                                           + innerHTML + '</select>';
                            if ($(editable).attr('id') === $(target).attr('id')) {
                                targetHTML += $(target).find('.editresponse').html();
                            }
                            jQuery(target).html(targetHTML);

                            YAHOO.util.Event.onContentReady(t_name + "-button", function () {
                                // Fetch currently selected item
                                var selectElement = document.getElementById(t_name + '-select');
                                var selectedOption = null;
                                var selectedLabel = '';
                                if (selectElement.options.length > 0) {
                                    selectedOption = selectElement.options[selectElement.selectedIndex];
                                    selectedLabel = selectedOption.innerHTML;
                                    if (selectedOption.parentElement.nodeName === "OPTGROUP") {
                                        selectedLabel = $("<div />").text($(selectedOption).parent().attr("label")).html() + " &#9658; " + selectedLabel;
                                    }
                                }

                                // Create a Button using an existing <input> and <select> element
                                var oMenuButton = new YAHOO.widget.Button(t_name + "-button", {
                                    label: selectedLabel.replace(/&amp;/g, "&"),
                                    type: "menu",
                                    menu: t_name + "-select"
                                });

                                // Register "click" event listener for the Button's Menu instance
                                oMenuButton.getMenu().subscribe('click', function (p_sType, p_aArgs) {
                                    if (p_aArgs[1]) {
                                        var parentName, childName;
                                        var children = p_aArgs[1].cfg.getProperty('submenu');
                                        if (!children) {
                                            // this is the child
                                            childName = p_aArgs[1].cfg.getProperty('text');
                                            if (p_aArgs[1].parent.parent) {
                                                parentName = p_aArgs[1].parent.parent.cfg.getProperty('text');
                                            }
                                        } else {
                                            // otherwise, this is the parent
                                            parentName = p_aArgs[1].cfg.getProperty('text');
                                            var firstChild = children.getItem(0);
                                            childName = firstChild.cfg.getProperty('text');
                                            // also set the configuration to reflect the first child
                                            oMenuButton.set('selectedMenuItem', firstChild);
                                        }
                                        var buttonLabel =
                                            ((parentName) ? ($("<div/>").text(parentName).html() + " &#9658; ") : '') +
                                            $("<div/>").text(childName).html();
                                        oMenuButton.set('label', buttonLabel);

                                        var f = selectElement.onchange;
                                        if (f) {
                                            selectElement.value = p_aArgs[1].srcElement.value;
                                            jQuery(selectElement).trigger('change');
                                        }
                                    }
                                });
                            });
                        }
                    },
                    failure: function(o) {
                        Fisma.Util.showAlertDialog('Failed to load the specified panel.');
                    }
                }, null);
            }
            $(this).append('<span class="editresponse">' +
                ' <img src="/images/ok.png" style="vertical-align:text-top" onclick="Fisma.Editable.commit(this);"/>' +
                ' <img src="/images/no_entry.png" style="vertical-align:text-top" onclick="Fisma.Editable.discard(this);"/>' +
            '</span>');
        }
    };

    /**
     * Replace editable fields with appropriate form elements
     */
    FE.setupEditFields = function() {
        var editable = YAHOO.util.Selector.query('.editable');
        YAHOO.util.Event.on(editable, 'click', Fisma.Editable.handleClickEvent);
    };

    /**
     * Convert an element into an autocomplete text field
     */
    FE.makeAutocomplete = function (element) {

        // Create an autocomplete form control
        var container = document.createElement('span');
        container.id = element.id;
        container.className = "yui-ac";
        $(container).attr('type', 'autocomplete');
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
                xhr: element.getAttribute("xhr"),
                fieldId: autocompleteTextField.id,
                containerId: autocompleteResultsDiv.id,
                hiddenFieldId: hiddenTextField.id,
                queryPrepend: element.getAttribute("queryPrepend"),
                setupCallback: element.getAttribute('setupCallback')
            }
        );
    };

    /**
     * Handle the onclick event of the discard icon
     */
    FE.discard = function (element, parent) {
        parent = parent || $(element).parents('[target]');
        var target = parent.attr('target');
        parent.addClass('editable2').find('.editresponse').remove();
        setTimeout("$('.editable2').removeClass('editable2').addClass('editable');", 0);
        if ($('#' + target).attr('type') === 'textarea') {
            tinyMCE.execCommand("mceRemoveControl", true, 'txt_' + target);
        }
        $('#' + target).replaceWith(parent.data('old_object'));
        if (parent.attr('id') === target) {
            YAHOO.util.Event.on(YAHOO.util.Selector.query('#' + target), 'click', Fisma.Editable.handleClickEvent);
        }
    };

    /**
     * Handle the onclick event of the commit icon
     */
    FE.commit = function (element) {
        var parent      = $(element).parents('[target]'),
            t_name      = parent.attr('target'),
            target      = $('#' + t_name),
            type        = target.attr('type'),
            field_name  = target.attr('name'),
            form        = $('form').eq(0),
            csrf        = form.find('input[name=csrf]').val(),
            action      = form.attr('action'),
            value       = null,
            refreshUrl  = Fisma.tabView.get('activeTab').get('dataSrc');
        switch (type) {
            case 'select':
                var item = YAHOO.widget.Button.getButton(t_name + '-button').get('selectedMenuItem');
                if (item) {
                    value = item.value;
                }
                break;
            case 'text':
                value = target.find('input').val();
                break;
            case 'textarea':
                tinyMCE.execCommand("mceRemoveControl", true, 'txt_' + t_name);
                value = target.find('textarea').val();
                break;
            case 'autocomplete':
                value = target.find('input[type=hidden]').val();
                field_name = target.find('input[type=hidden]').attr('name');
                break;

        }
        if (value !== null) {
            parent.find('.editresponse').remove();
            target.html('Saving, please wait... <img src="/images/spinners/small.gif" style="vertical-align:text-top"/>');
            var data = {
                'csrf': csrf
            };
            data[field_name] = value;
            Fisma.Editable.query = data;
            $.ajax({
                url: action,
                data: data,
                type: 'POST',
                success: function(data, textStatus, request) {
                    Fisma.Editable.result = data;
                    parent.addClass('editable');
                    var errorMsg = $(data)
                            .filter('script')
                            .filter(function(){
                                if ($(this).html().match(/Fisma\.Util\.message\(.*, "warning"\)/)) {
                                    return true;
                                }
                            });
                    if (errorMsg.length > 0) {
                        errorMsg.appendTo($(this));
                        Fisma.Editable.discard(element, parent);
                    } else {
                        if ($(data).filter('title').text().indexOf('Error - Error') >= 0) {
                            Fisma.Editable.discard(null, parent);
                            Fisma.Util.showAlertDialog("An error has occured. Please contact administrator.");
                        } else {
                            $.ajax({
                                url: refreshUrl,
                                success: function(data, textStatus, request) {
                                    target.html($(data).find('#' + t_name).html());
                                },
                                failure: function(data, textStatus, request) {
                                    Fisma.Editable.discard(element);
                                }
                            });
                        }
                    }
                },
                failure: function(data, textStatus, request) {
                    Fisma.Editable.discard(element);
                }
            });
        } else {
            Fisma.Editable.discard(element);
        }
    };

    /**
     * Turn all editable fields into edit mode
     */
    FE.turnAllOn = function() {
        Fisma.Editable.editMode = true;
        $('.yui-content > div:not(.yui-hidden) .editable').click();
        $('#editMode').hide();
        $('#saveChanges, #discardChanges').show();
    };

    Fisma.Editable = FE;
    Fisma.Editable.editMode = false;
}());
