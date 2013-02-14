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
    var FE = {};

    /**
     * Handle the onclick event of the pencil icon
     */
    FE.handleClickEvent = function(o) {
        var val;
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
            var editableObj = null;
            var target = document.getElementById(t_name);
            var type = target.getAttribute('type');
            if (type === 'text') {
                editableObj = new FE.Text(target);
            } else if (type === 'textarea') {
                editableObj = new FE.Textarea(target);
            } else if (type === 'autocomplete') {
                editableObj = new FE.Autocomplete(target);
            } else if (type === 'select') {
                editableObj = new FE.Select(target);
            } else if (type === 'checked') {
                editableObj = new FE.Checked(target);
            } else if (type === 'multiselect') {
                editableObj = new FE.Multiselect(target);
            }

            $(this).append('<span class="editresponse">' +
                ' <button onclick="Fisma.Editable.commit(this);" title="Save"><img src="/images/ok.png" style="vertical-align:text-top" /></button>' +
                ' <button onclick="Fisma.Editable.discard(this);" title="Discard"><img src="/images/no_entry.png" style="vertical-align:text-top" /></button>' +
            '</span>').attr('tabindex', null);
            $('.editresponse button').button();
        }
    };

    /**
     * Replace editable fields with appropriate form elements
     */
    FE.setupEditFields = function(editable) {
        editable = (editable) ? $(editable).focus() : '.editable';
        $(editable)
            .attr('title', '(click to edit)')
            .attr('tabindex', 0)
            .click(Fisma.Editable.handleClickEvent)
            .keypress(function(e) {
                if (YAHOO.util.Event.getCharCode(e) === YAHOO.util.KeyListener.KEY.ENTER) {
                    Fisma.Editable.handleClickEvent.call( YAHOO.util.Event.getTarget(e), e);
                }
            });
    };

    /**
     * Handle the onclick event of the discard icon
     */
    FE.discard = function (element, parent) {
        parent = parent || $(element).parents('[target]');
        parent.attr('tabindex', 0).focus();
        var target = parent.attr('target');
        parent.addClass('editable2').find('.editresponse').remove();
        setTimeout(function() {
            $('.editable2').removeClass('editable2').addClass('editable');
        }, 0);
        if ($('#' + target).attr('type') === 'textarea') {
            tinyMCE.execCommand("mceRemoveControl", true, 'txt_' + target);
        }
        $('#' + target).replaceWith(parent.data('old_object'));
        if (parent.attr('id') === target) {
            Fisma.Editable.setupEditFields('#' + target);
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
            form        = $('form').not(function() { return $(this).parents('div#toolbarForm').length > 0; }).eq(0),
            csrf        = form.find('input[name=csrf]').val(),
            action      = form.attr('action'),
            value       = null,
            refreshUrl  = Fisma.tabView.get('activeTab').get('dataSrc'),
            affected = target.attr("affected");
        switch (type) {
            case 'select':
            case 'checked':
                value = target.find('select').val();
                break;
            case 'text':
            case "multiselect":
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
                    parent.addClass('editable').attr('tabindex', 0).focus();
                    var errorMsg = $(data).filter('script.priority-messenger-warning');
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
                                    var newTarget = $(data).find("#" + t_name);
                                    target.replaceWith(newTarget);
                                    target = newTarget;
                                    if (target.hasClass('editable')) {
                                        Fisma.Editable.setupEditFields(target);
                                    }
                                    if (affected) {
                                        $("#"+affected).html($(data).find("#"+affected).html());
                                    }
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
        $('.yui-content > div').not('.yui-hidden').find('.editable').click();
        $('#editMode').hide();
        $('#saveChanges, #discardChanges').css('display', 'inline-block');
    };

    FE.Text = function(target) {
        var jqTarget = $(target),
            jqInput = $("<input />"),
            oldWidth = jqTarget.outerWidth();
        jqInput.attr({
            length: 50,
            id: "txt_" + target.id,
            name: jqTarget.attr("name"),
            "class": jqTarget.attr("class"),
            type: "text",
            value: jqTarget.text().trim()
        });
        jqTarget.empty().append(jqInput);

        if (oldWidth < 200) {
            oldWidth = 200;
        }

        jqInput.width(oldWidth - 10);
        if (jqInput.hasClass("date")) {
            Fisma.Calendar.addCalendarPopupToTextField(jqInput.get(0));
        }

        jqInput.focus();
    };

    FE.Textarea = function(target) {
        var jqTarget = $(target),
            jqFormEl = $("<textarea/>"),
            oldHeight = jqTarget.outerHeight(),
            oldWidth = jqTarget.outerWidth();
        jqFormEl.attr({
            id: "txt_" + target.id,
            name: jqTarget.attr("name"),
            rows: jqTarget.attr("rows"),
            cols: jqTarget.attr("cols"),
            value: jqTarget.html()
        });
        jqTarget.empty().append(jqFormEl);

        jqFormEl.height(oldHeight);
        jqFormEl.width(oldWidth);

        tinyMCE.execCommand("mceAddControl", true, jqFormEl.attr("id"));
        setTimeout(function() {
            tinyMCE.execCommand('mceFocus', false, jqFormEl.attr("id"));
        }, '500');
    };

    FE.Autocomplete = function (element) {
        var container = $("<span/>"),
            hiddenTextField = $("<input/>"),
            autocompleteTextField = $("<input/>"),
            autocompleteResultsDiv = $("<div/>"),
            spinner = $("<img/>");

        container.attr({
            id: element.id,
            type: "autocomplete"
        }).addClass("yui-ac");

        hiddenTextField.attr({
            type: "hidden",
            name: element.getAttribute("name"),
            value: element.getAttribute("value"),
            id: YAHOO.util.Dom.generateId()
        }).appendTo(container);

        autocompleteTextField.attr({
            type: "text",
            name: "autocomplete_" + element.id,
            value: element.getAttribute("defaultValue"),
            id: YAHOO.util.Dom.generateId()
        }).appendTo(container);

        autocompleteResultsDiv.attr("id", YAHOO.util.Dom.generateId())
            .appendTo(container);

        spinner.attr({
            src: "/images/spinners/small.gif",
            id: autocompleteResultsDiv.attr("id") + "Spinner" // required by AC API
        }).addClass("spinner")
            .appendTo(container);

        $(element).replaceWith(container);

        // Set up the autocomplete hooks on the new form control
        YAHOO.util.Event.onDOMReady(
            Fisma.AutoComplete.init,
            {
                schema: [element.getAttribute("schemaObject"), element.getAttribute("schemaField")],
                xhr: element.getAttribute("xhr"),
                fieldId: autocompleteTextField.attr("id"),
                containerId: autocompleteResultsDiv.attr("id"),
                hiddenFieldId: hiddenTextField.attr("id"),
                queryPrepend: element.getAttribute("queryPrepend"),
                setupCallback: element.getAttribute('setupCallback'),
                autofocus: true
            }
        );
    };

    FE.Select = function(target) {
        var jqTarget = $(target),
            val = jqTarget.val() || jqTarget.attr("value") || jqTarget.text(),
            href = jqTarget.attr("href") + "value/" + encodeURI(val.trim()),
            select = $("<select/>");
        jqTarget.empty();
        select.attr({
            id: jqTarget.attr("id") + "-select",
            name: jqTarget.attr("name")
        });
        jqTarget.html(select);
        select.load(href).focus();
    };

    FE.Checked = function(target) {
        var jqTarget = $(target),
            val = ($(target).text().trim() === 'YES');
        jqTarget.empty();

        $('<select/>')
            .attr('name', name)
            .append(
                $('<option/>')
                    .attr('value', 1)
                    .text('YES')
                    .attr('selected', val)
            )
            .append(
                $('<option/>')
                    .attr('value', 0)
                    .text('NO')
                    .attr('selected', !val)
            )
            .prependTo(target)
            .focus()
        ;
    };

    FE.Multiselect = function(target) {
        target = $(target);
        var that = this,
            val = target.attr("value"),
            valArray = [],
            jsonUrl = target.attr("json") + "value/" + encodeURI(val.trim()),
            selected = this.selected = $("<span>"),
            addMenu = this.addMenu = $("<div>").css("position", "absolute").zIndex(1).hide(),
            addImg = this.addImg = $("<img>").attr({
                "src": "/images/add.png",
                "alt": "Add",
                "tabindex": 0,
                width: 16,
                height: 16
            }).css("vertical-align", "text-bottom"),
            inputElement = this.inputElement = $("<input>")
                .attr({name: target.attr("name"), type: "hidden"})
                .val(val);
        try {
            valArray = JSON.parse(val);
        } catch(e) {
            // probably empty string/null - do nothing
        }

        switch ($.type(valArray)) {
            case 'array':
                $.each(valArray, function(k, v) {
                    that._addSelected(v, v);
                });
                break;
            case 'object':
                $.each(valArray, function(k, v) {
                    that._addSelected(v, k);
                });
                break;
        }

        target.empty();
        target.append(inputElement, selected, addImg, addMenu);
        addMenu.menu({
            menus: "div.menu",
            select: $.proxy(this, "_onMenuSelect")
        });

        if (target.attr('json')) {
            $.getJSON(jsonUrl, null, function(data, text, xhr) {
                $.each(data.options, function(k, v) {
                    if ($.inArray(v, valArray) < 0) {
                        addMenu.append(that._buildMenuItem(v));
                    }
                });
                addMenu.menu("refresh");
            });
        } else if (target.data('options')) {
            $.each(target.data('options'), function() {
                if (!valArray.hasOwnProperty($(this).attr('value'))) {
                    addMenu.append(that._buildMenuItem($(this).text(), $(this).attr('value')));
                }
            });
            addMenu.menu("refresh");
        }

        addImg.on({
            click: function(event) {
                event.stopPropagation();
                addMenu.show();
                addMenu.position({my: "left top", at: "left bottom", of: addImg});
                addMenu.menu("focus", null, addMenu.find(".ui-menu-item:first"));
            },
            keydown: function(event) {
                switch (event.keyCode) {
                case $.ui.keyCode.ENTER:
                case $.ui.keyCode.SPACE:
                    addMenu.show();
                    addMenu.menu("focus", null, addMenu.find(".ui-menu-item:first"));
                    break;
                }
            }

        });
        $(document).on({
            click: function( event ) {
                if ( !$.contains(addMenu.get(0), event.target)) {
                    addMenu.hide();
                }
            },
            keydown: function(event) {
                switch (event.keyCode) {
                case $.ui.keyCode.ESCAPE:
                    addMenu.hide();
                    break;
                }
            }
        });
    };
    FE.Multiselect.prototype._buildMenuItem = function (label, value, submenu) {
        var a = $("<a>").attr("href", "#"),
            d = $("<div>").append(a).attr('value', ((value) ? value : label));
        ($.type(label) === 'string' ? a.text : a.html).call(a, label);
        if (submenu) {
            d.append(submenu);
        }
        return d;
    };
    FE.Multiselect.prototype._addSelected = function (itemText, itemValue) {
        var item, anchor;
        anchor = $("<a>")
            .append(
                $("<img>").attr({src: "/images/trash_recyclebin_empty_closed.png", alt: "Remove"})
            ).attr({
                "href": "#",
                "title": "Remove"
            }).css({
                position: "static",
                "float": "right"
            }).on({
                click: $.proxy(this, "_onRemove")
            });
        item = $("<div>")
            .css({
                display: "inline-block",
                padding: "0.2em"
            })
            .text(itemText)
            .attr('value', itemValue)
            .append(anchor);
        this.selected.append(item);
        this._refreshInputElement();
    };
    FE.Multiselect.prototype._onMenuSelect = function (event, ui) {
        this._addSelected(ui.item.text(), ui.item.attr('value'));
        ui.item.remove();
        this.addMenu.hide();
    };
    FE.Multiselect.prototype._onRemove = function (event) {
        var item, text, value, newItem;
        event.stopPropagation();
        item    = $(event.target).parents("div").first();
        text    = item.text();
        value   = item.attr('value');
        item.remove();
        newItem = this._buildMenuItem(text, value);
        // insert in sorted order
        this.addMenu.children().each(function () {
            if (text.toLowerCase() < $(this).text().toLowerCase()) {
                newItem.insertBefore(this);
                newItem = null;
                return false;
            }
        });
        if (newItem) {
            this.addMenu.append(newItem);
        }
        this.addMenu.menu("refresh");
        this._refreshInputElement();
    };
    FE.Multiselect.prototype._refreshInputElement = function() {
        Fisma.Util.registerJSON();
        var values = [];
        this.selected.children().each(function() {
            values.push($(this).attr('value'));
        });
        this.inputElement.val(JSON.stringify(values));
    };

    Fisma.Editable = FE;
    Fisma.Editable.editMode = false;
}());
