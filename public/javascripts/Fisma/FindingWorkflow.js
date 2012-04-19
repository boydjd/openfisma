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
 * @fileoverview This file contains related javascript code for the finding workflow feature
 *
 * @author    Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

Fisma.FindingWorkflow = {
    /**
     * Customizing the new item
     *
     * @param string listId The logic id of the list. (htmlId = logicId + 'Container')
     * @param strong itemId The HTML id of the <li> item.
     */
    appendHandler : function(listId, itemId) {
        var newItem = jQuery('#' + itemId);

        newItem.find('span.stepName').text('(new step)');
        newItem.find('input[name$="_name"]').val('(new step)');
        newItem.find('input, textarea, select').attr('name', function(index, oldId) {
            return itemId + '_' + oldId.split('_').pop();
        });

        Fisma.FindingWorkflow.toggleDetailPanel(newItem.find('span.linkBar > a').get(0));
        Fisma.FindingWorkflow.addChangeLogEntry('(new step) added.');

        return false;
    },

    /**
     * Only allow submission if triggered by the "Save" button
     */
    submitHandler : function() {
        return jQuery('input[name="forceSubmit"]').val() === 'true';
    },

    /**
     * Register the submission as triggered by the "Save" button
     */
    forceSubmit : function() {
        if (!Fisma.FindingWorkflow.validateForm()) {
            return false;
        }

        var alertMessage = "Processing changes... This may take up to several minutes. Please be patient."
                         + "<p style='text-align:center'><img src='/images/loading_bar.gif' /></p>";
        var alertDialog = Fisma.Util.getDialog(false);

        alertDialog.setHeader("WARNING");
        alertDialog.setBody(alertMessage);

        alertDialog.render(document.body);
        alertDialog.show();

        jQuery('input[name="forceSubmit"]').val(true);
        document.forms.finding_workflow.submit();
    },

    /**
     * Validate the form
     */
    validateForm : function() {
        var message = "Validation failed:<br/>";
        var error = false;
        // Empty workflow already validated by titleChangeHandler

        // Validate Chart Label
        var emptyLabels = jQuery('input[name$="nickname"]').filter('[value=""]').not('[name*="skeleton"]');
        jQuery.each(emptyLabels, function(index, element) {
            var name = jQuery(element).parents('li').children('span.stepName').text();
            message += "Chart Label for " + name + " cannot be empty.<br/>";
            error = true;
        });

        // Validate On Time
        var onTimes = jQuery('input[name$="due"]');
        jQuery.each(onTimes, function(index, element) {
            var name = jQuery(element).parents('li').children('span.stepName').text();
            if (jQuery(element).val() === '') {
                message += "On Time period for " + name + " cannot be empty.<br/>";
                error = true;
            } else if (isNaN(parseInt(jQuery(element).val(), 10))) {
                message += "On Time period for " + name + " must be a whole number.<br/>";
                error = true;
            } else if (parseInt(jQuery(element).val(), 10) < 0) {
                message += "On Time period for " + name + " cannot be negative.<br/>";
                error = true;
            }

            jQuery(element).val(parseInt(jQuery(element).val(), 10));
        });

        if (error) {
            Fisma.Util.message(message, 'warning', true);
        }

        return !error;
    },

    /**
     * Handle the onChange of "title" input and reflect the change
     */
    titleChangeHandler : function(element) {
        var newTitle = jQuery(element).val().trim();
        jQuery(element).val(newTitle);
        var oldTitle = jQuery(element).parents('li').children('.stepName').text().trim();
        if (newTitle === '') {
            jQuery(element).val(oldTitle);
            Fisma.Util.showAlertDialog('Workflow Title cannot be blank.');
            return false;
        }
        jQuery(element).parents('li').find('.stepName').text(newTitle).hide().fadeIn();
        Fisma.FindingWorkflow.addChangeLogEntry(oldTitle + ' renamed to ' + newTitle + ".");
    },

    /**
     * Handle the onChange of "chart label" input and validate its uniqueness
     */
    chartLabelChangeHandler : function(element) {
        var newLabel = jQuery(element).val().trim();
        jQuery(element).val(newLabel);
        var countDuplication = jQuery('input[name$="nickname"]').filter(function(i, e) {
            return (jQuery(e).val().toUpperCase() === newLabel.toUpperCase());
        }).length;
        if (countDuplication > 1) {
            jQuery(element).val('');
            Fisma.Util.showAlertDialog('Chart Label must be unique regardless of letter case.');
            return false;
        }

        var reserved = ['NEW', 'DRAFT', 'MSA', 'EN', 'EA', 'CLOSED'];
        if (jQuery.inArray(newLabel.toUpperCase(), reserved) >= 0) {
            jQuery(element).val('');
            Fisma.Util.showAlertDialog('Chart Label "' + newLabel + '" cannot be used because it is reserved by the system.');
            return false;
        }
    },

    /**
     * Handle the toggling of the detailPanel
     *
     * @param (HTMLElement) element The element that triggers the event
     */
    toggleDetailPanel : function(element) {
        var parentElement = jQuery(element).parents('li');
        if (jQuery(element).text().indexOf('View') >= 0) {
            jQuery(element).text('[Close Details]');
        } else if (jQuery(element).text().indexOf('Close') >= 0) {
            jQuery(element).text('[View Details]');
        }

        parentElement.find('div.stepDetail').fadeToggle('fast');
        return false;
    },

    /**
     * Append the handling of InteractiveOrderedList endDrag event
     *
     * @param (HTMLElement) source  The <li> element being dragged
     * @param (boolean)     moved   Whether the source was actually moved to another position on the list
     */
    endDragHandler : function(source, moved) {
        if (moved) {
            var title = jQuery(source).find('.stepName').first().text();
            Fisma.FindingWorkflow.addChangeLogEntry(title + ' moved.');
        }
    },

    /**
     * Insert an entry into the Change Log Window
     *
     * @param (string) message  The message to insert
     */
    addChangeLogEntry : function(message) {
        var entry = document.createElement("div");
        entry.appendChild(document.createTextNode(message));
        entry.className = 'approvalStep';
        entry.style.display = 'none';
        jQuery('#changeQueue div.approval').append(entry);
        jQuery(entry).slideToggle("fast");
    },

    /**
     * Pop-up a panel to select assigned roles
     *
     * @param linkElement The link that triggers this function, used as a reference point back to the step
     * @return boolean  false, to prevent form submission
     */
    showRoleDialog : function(linkElement) {
        var panel = Fisma.UrlPanel.showPanel(
                'Select Roles',
                '/finding/workflow/select-roles',
                function(){
                    var roles = jQuery(linkElement).next().val().split('|');
                    var role;
                    for (role in roles) {
                        jQuery('#finding_workflow_select_roles input[name="' + roles[role] + '"]'
                            ).attr('checked', true);
                    }

                    var closeButton = new YAHOO.widget.Button('dialog_close');
                    YAHOO.util.Event.addListener("dialog_close", "click", function (){
                        panel.destroy();
                        return false;
                    });

                    var confirmButton = new YAHOO.widget.Button('dialog_confirm');
                    YAHOO.util.Event.addListener("dialog_confirm", "click", function (){
                        var inputs = jQuery('#finding_workflow_select_roles input:checked');

                        jQuery(linkElement).prev().html(
                            jQuery.map(
                                inputs.parents('span').text().split("\n"),
                                function(val) {
                                    return (val === '') ? null : $("<div>").text(val).html();
                                }
                            ).join("<br/>")
                        );

                        var roles = "";
                        jQuery.each(inputs, function(i, e){roles += e.name + "|";});
                        jQuery(linkElement).next().val(roles);

                        var stepName = jQuery(linkElement).parents('li').children('.stepName').text();
                        Fisma.FindingWorkflow.addChangeLogEntry(stepName + " role assignment updated.");

                        panel.destroy();
                        return false;
                    });

                }
            );

        return false;
    },

    /**
     * Pop-up a panel to select a destination step to migrate findings from the deleted step
     *
     * @param linkElement The link that triggers this function, used as a reference point back to the step
     * @return boolean  false, to prevent form submission
     */
    showRemoveStepDialog : function(linkElement) {
        if (jQuery(linkElement).parents('ul.dragList').find('input[name$="destinationId"]').filter(function(i, e){
            return (jQuery(e).val() === '' && e.name.indexOf('skeleton') < 0);
        }).length <= 1) { // Only 1 remaining step
            Fisma.Util.showAlertDialog("There must be at least one approval for this workflow step.");
            return false;
        } else if (!Fisma.FindingWorkflow.validateForm()) {
            Fisma.Util.showAlertDialog("Please resolve validation error(s).");
            return false;
        } else {
        var panel = Fisma.UrlPanel.showPanel(
                'Remove Step',
                '/finding/workflow/remove-step',
                function(){
                    // Construct step list from client state
                    var steps = jQuery(linkElement).parents('ul.dragList').find('li');
                    jQuery.each(steps.children('.stepName'), function(index, element){
                        var isSkeleton = (jQuery(element).parents('li').attr('id').indexOf('Skeleton') >= 0);
                        var isDeleted = (jQuery(element).parents('li')
                                            .find('input[name$="destinationId"]').val() !== "");
                        if (!isSkeleton && !isDeleted) {
                            var stepName = jQuery(element).text();
                            var stepId = steps.eq(index).find('input[name$="databaseId"]').attr('name').split('_')[1];
                            var stepList = document.getElementById('step_list');
                            var currentStepName = jQuery(linkElement).parents('li').children('.stepName').text();

                            if (currentStepName !== stepName) {
                                var stepRadio = document.createElement("input");
                                stepRadio.type = "radio";
                                stepRadio.name = "target_step";
                                stepRadio.value = stepId;

                                var stepLabel = document.createElement("span");
                                stepLabel.appendChild(document.createTextNode(stepName));

                                stepList.appendChild(stepRadio);
                                stepList.appendChild(stepLabel);
                                stepList.appendChild(document.createElement("br"));
                            }
                        }
                    });
                    jQuery('#step_list > input:first-child').attr('checked', true);

                    // Add handler for Cancel button
                    document.getElementById('dialog_close').onclick = function (){
                        panel.destroy();
                        return false;
                    };

                    // Add handler for Confirm button
                    document.getElementById('dialog_confirm').onclick = function (){
                        var input = jQuery('input[name="target_step"]:checked');

                        jQuery(linkElement).parents('li').find('input[name$="destinationId"]').val(input.val());
                        jQuery(linkElement).parents('li').fadeOut("slow", function(){
                            // Move the step to the end of the stack (to ensure the order of deletions)
                            jQuery(linkElement).parents('ul').append(jQuery(linkElement).parents('li'));
                        });

                        var stepName = jQuery(linkElement).parents('li').children('.stepName').text();
                        var destinationName = jQuery(linkElement).parents('ul').find(
                                'input[name$="' + input.val() + '_name"]'
                            ).val();
                        Fisma.FindingWorkflow.addChangeLogEntry(stepName + " migrated to " + destinationName + ".");
                        panel.destroy();

                        return false;
                    };
                }
            );
        }
        return false;
    }
};
