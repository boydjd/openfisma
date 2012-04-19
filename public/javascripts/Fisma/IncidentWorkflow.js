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
 * @author    Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

(function() {
    /**
     * Manages the IR workflow editor using dynamic client-side code.
     *
     * @param {Array} data.steps Array of object literals containing the properties for each of the initial workflow steps.
     * @param {Object} data.roles Object literal mapping role IDs to their nicknames.
     * @param {String} templateId ID of the DOM element containing the template to use when adding new workflow steps.
     * @class IncidentWorkflow
     * @constructor
     */
    var FIW = function(data, templateId) {
        this._stepTemplate = $("#" + templateId + " tr").first();
        this._addOptionsToRoleSelect(data.roles);
        var lastTr = $("table.fisma_crud tr").first().siblings().last(),
            that = this;
        $.each(data.steps, function(index, value) {
            that.addStepBelow(lastTr, value);
            lastTr = lastTr.next();
        });
    };
    FIW.prototype = {
        /**
         * @property _stepTemplate
         * @description A jQuery collection referring to the step template root node.
         * @protected
         * @type jQuery
         */
        _stepTemplate: null,

        /**
         * @method _addOptionsToRoleSelect
         * @description Populate the step template's select element with roles.
         * @protected
         * @param {Object} Associative array of roleIds to nicknames.
         */
        _addOptionsToRoleSelect: function(roleData) {
            var select = $("select", this._stepTemplate);
            select.append("<option value=''></option>");
            $.each(roleData, function(key, value) {
                var newOption = $("<option/>");
                newOption.attr("value", key);
                newOption.text(value);
                select.append(newOption);
            });
        },

        /**
         * @method _renumberSteps
         * @description Renumber the step labels in the left hand column.  Used to correct numbering after adding or removing a step.
         * @protected
         */
        _renumberSteps: function() {
            // The selector may seem overly specific, but all parts ARE needed
            $("table.fisma_crud tr.incidentStep > td:first-child").text(function(i) { return "Step " + (i+1); });
        },

        /**
         * @method addStepAbove
         * @description Add empty step above the provided TR element.  The step may optionally be populated with the provided data.
         * @public
         * @param {String|HTMLElement|jQuery} tr Element above which to place the new step.
         * @param {Object} data (Optional) Data with which to populate the new step.
         */
        addStepAbove: function (tr, data) {
            this.addStepBelow($(tr).prev().get(0), data);
            this._renumberSteps();
        },

        /**
         * @method addStepBelow
         * @description Add empty step below the provided TR element.  The step may optionally be populated with the provided data.
         * @public
         * @param {String|HTMLElement|jQuery} tr Element below which to place the new step.
         * @param {Object} data (Optional) Data with which to populate the new step.
         */
        addStepBelow: function (tr, data) {
            // clone new TR from template and set up values
            var newTr = this._stepTemplate.clone(),
                buttons = $("div.button", newTr).get(),
                textareaId = tinyMCE.DOM.uniqueId(),
                newTextarea = $('<textarea name="stepDescription[]" rows="8" cols="100" />'),
                selectButton = $("input[type='button']", newTr).get(),
                selectMenu = $("select", newTr).get(),
                fn;
            $("span.templateDescription", newTr).replaceWith(newTextarea);
            newTextarea.attr("id", textareaId);
            if (data) {
                $('input[name^=stepName]', newTr).val(data.name);
                $("select", newTr).val(data.roleId);
                newTextarea.val(data.description);
            }

            $(buttons[0]).attr('id', textareaId + "_addAbove");
            var addStepAboveButton = new YAHOO.widget.Button(buttons[0], {label: "Add Step Above"});

            $(buttons[1]).attr('id', textareaId + "_addBelow");
            var addStepBelowButton = new YAHOO.widget.Button(buttons[1], {label: "Add Step Below"});

            $(buttons[2]).attr('id', textareaId + "_remove");
            var removeStepButton = new YAHOO.widget.Button(buttons[2], {label: "Remove Step"});

            $(selectMenu).attr('id', textareaId + '_menu');
            $(selectButton).attr('id', textareaId + '_button');
            // add it to the table
            $(tr).after(newTr);

            // Fetch currently selected item
            var selectedLabel = (data) ? $("option:selected", selectMenu).html() : '';

            // doubly-escape option text because YUI menus are stupid
            $("option", newTr).each(function() {
                $(this).text($(this).html());
            });

            // Create a Button using an existing <input> and <select> element
            var oMenuButton = new YAHOO.widget.Button(textareaId + '_button', {
                label: selectedLabel,
                type: "menu",
                menu: textareaId + '_menu'
            });

            // Register "click" event listener for the Button's Menu instance
            oMenuButton.getMenu().subscribe("click", function (p_sType, p_aArgs) {
                var oEvent = p_aArgs[0],       // DOM event
                    oMenuItem = p_aArgs[1]; // MenuItem target of the event
                if (oMenuItem) {
                    oMenuButton.set('label', oMenuItem.cfg.getProperty("text"));
                }
            });

            YAHOO.util.Event.addListener(
                textareaId + "_addAbove",
                "click",
                function() { this.addStepAbove(newTr); },
                null,
                this);
            YAHOO.util.Event.addListener(
                textareaId + "_addBelow",
                "click",
                function() { this.addStepBelow(newTr); },
                null,
                this);
            YAHOO.util.Event.addListener(
                textareaId + "_remove",
                "click",
                function() { this.removeStep(newTr); },
                null,
                this);

            // tell tinyMCE to render it now
            tinyMCE.execCommand ('mceAddControl', false, textareaId);
            this._renumberSteps();
        },

        /**
         * @method removeStep
         * @description Remove step of the provided TR element from the workflow.
         * @public
         * @param {String|HTMLElement|jQuery} tr Element to be removed.
         */
        removeStep: function(tr) {
            $(tr).remove();
            this._renumberSteps();
        }
    };
    Fisma.IncidentWorkflow = FIW;
}());
