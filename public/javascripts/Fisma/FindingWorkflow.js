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
        newItem.find('input').attr('name', function(index, oldId) {
            return oldId.replace(/.*_.*_/, itemId + '_');
        });

        Fisma.FindingWorkflow.toggleDetailPanel(newItem.find('span.linkBar > a').get(0));

        return false;
    },

    submitHandler : function() {
        return document.forms['finding-workflow'].forceSubmit = true;
    },

    forceSubmit : function() {
        document.forms['finding-workflow'].forceSubmit = true;
        document.forms['finding-workflow'].submit();
    },

    titleChangeHandler : function(element) {
        title = jQuery(element).val();parentElement.find('input[name$="_name"]').val();
        jQuery(element).parents('li').find('span.stepName').text(title).hide().fadeIn();
    },

    /**
     * Handle the toggling of the detailPanel
     *
     * @param (HTMLElement) element The element that triggers the event
     */
    toggleDetailPanel : function(element) {
        parentElement = jQuery(element).parents('li');
        /*if (edited) {
            var logMessage = Fisma.FindingWorkflow.getSelfText(jQuery(element).parents("li")) + ' modified.';
            Fisma.FindingWorkflow.addChangeLogEntry(logMessage);
        }*/
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
            title = jQuery(source).find('.stepName').first().text();
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

    showRoleDialog : function(linkElement) {
        var panel = Fisma.UrlPanel.showPanel(
                'Select Roles',
                linkElement.href,
                function(){
                    var roles = jQuery(linkElement).next().val().split('|');
                    for (var role in roles) {
                        jQuery('#finding_workflow_select_roles input[name="' + roles[role] + '"]').attr('checked', true);
                    }


                    document.getElementById('dialog_close').onclick = function (){
                        panel.destroy();
                        return false;
                    }
                    document.getElementById('dialog_confirm').onclick = function (){
                        var inputs = jQuery('#finding_workflow_select_roles input:checked');

                        jQuery(linkElement).prev().html(inputs.parents('span').text().replace(/.\n/g, '<br/>'));

                        var roles = "";
                        jQuery.each(inputs, function(i, e){roles += e.name + "|";});
                        jQuery(linkElement).next().val(roles);

                        panel.destroy();
                        return false;
                    }
                }
            );

        return false;
    }
}
