/**
 * Copyright (c) 2013 Endeavor Systems, Inc.
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
 * @author    Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2013 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Workflow = {
    addWorkflow: function(event) {
        var module = Fisma.tabView.get('activeTab').get('id').toLowerCase();
        Fisma.UrlPanel.showPanel(
            'New ' + module + ' workflow', //title
            '/workflow/new/format/html/type/' + module, //url
            function(panel) { //callback
                //Setup and activate editable fields
                Fisma.Editable.setupEditFields();
                $('.editable', panel.body).click();

                //Hide inline submission controls
                $('.editresponse', panel.body).hide();
                $('div[role=tooltip]').remove();

                //Execute scripts
                $('script', panel.body).appendTo($('body'));

                //Ensure tinyMce exits cleanly
                panel.subscribe("hide", function() {
                    $('.editresponse button[title=Discard]', panel.body).click();
                    $('div[role=tooltip]').remove();
                });
            }
        );
    },

    addStep: function(event) {
        var workflowId = $('input#workflowId').val();
        Fisma.UrlPanel.showPanel(
            'New workflow step', //title
            '/workflow/save-step/format/html/workflowId/' + workflowId, //url
            function(panel) { //callback
                //Execute scripts
                $('script', panel.body).appendTo($('body'));
            }
        );
    },

    stepFormSubmitHandler: function(event) {
        /* debug
        event.preventDefault();
        // end debug */

        var jqForm  = $(this),
            table   = Fisma.Registry.get('transition-table'),
            trans   = $.map(table.getRecordSet().getRecords(), function(value) {
                return value.getData();
            }),
            jqInput = $('<input/>').attr({
                'type':     'hidden',
                'name':     'step[transitions]',
                'value':    JSON.stringify(trans)
            });
        jqInput.appendTo(jqForm);
    },

    setDefaultWorkflow: function(event) {
        var workflowId = $(this).data('record').id;
        Fisma.Util.formPostAction(null, '/workflow/set-default', workflowId);
    },

    deleteWorkflow: function(event) {
        var workflowId = $(this).data('record').id;
        Fisma.Util.formPostAction(null, '/workflow/delete', workflowId);
    },

    deleteStep: function(event) {
        event.preventDefault();
        var workflowId = $('input#workflowId').val();
        var stepId = $(this).data('record').id;
        Fisma.Util.formPostAction(null, '/workflow/delete-step/workflowId/' + workflowId, stepId);
    },

    editWorkflow: function(event) {
        var workflowId = $(this).data('record').id;
        window.location = '/workflow/view/id/' + workflowId;
    },

    editStep: function(event) {
        event.preventDefault();
        var stepId = $(this).data('record').id;
        Fisma.UrlPanel.showPanel(
            'Edit workflow step', //title
            '/workflow/save-step/format/html/id/' + stepId, //url
            function(panel) { //callback
                //Execute scripts
                $('script', panel.body).appendTo($('body'));
            }
        );
    },

    moveStepFirst: function(event) {
        event.preventDefault();
        var workflowId = $('input#workflowId').val();
        var stepId = $(this).data('record').id;
        Fisma.Util.formPostAction(null, '/workflow/move-step/direction/first/workflowId/' + workflowId, stepId);
    },

    moveStepUp: function(event) {
        event.preventDefault();
        var workflowId = $('input#workflowId').val();
        var stepId = $(this).data('record').id;
        Fisma.Util.formPostAction(null, '/workflow/move-step/direction/up/workflowId/' + workflowId, stepId);
    },

    moveStepDown: function(event) {
        event.preventDefault();
        var workflowId = $('input#workflowId').val();
        var stepId = $(this).data('record').id;
        Fisma.Util.formPostAction(null, '/workflow/move-step/direction/down/workflowId/' + workflowId, stepId);
    },

    moveStepLast: function(event) {
        event.preventDefault();
        var workflowId = $('input#workflowId').val();
        var stepId = $(this).data('record').id;
        Fisma.Util.formPostAction(null, '/workflow/move-step/direction/last/workflowId/' + workflowId, stepId);
    },

    addTransition: function(event) {
        var workflowId = $('input#workflowId').val();
        Fisma.UrlPanel.showPanel(
            'New transition', //title
            '/workflow/transition-form/workflowId/' + workflowId + '/format/html', //url
            function(panel) { //callback
                //Execute scripts
                $('script', panel.body).appendTo($('body'));
                var jqForm = $('form', panel.body);
                jqForm.submit(function(event) {
                    event.preventDefault();

                    var data = {
                        'name': $('input[name=name]', jqForm).val(),
                        'destination': $('input[name=destination]:checked', jqForm).val(),
                        'roles': $('input[name=roles]', jqForm).val(),
                        'actions': '[{"label":"edit","icon":"/images/edit.png","handler":"Fisma.Workflow.editTransition"},{"label":"delete","icon":"/images/trash_recyclebin_empty_open.png","handler":"Fisma.Workflow.deleteTransition"}]',
                        'customDestination': $('select[name=customDestination]', jqForm).val()
                    };
                    var table = Fisma.Registry.get('transition-table');
                    if (table) {
                        table.addRow(data);
                    }

                    panel.hide();
                    panel.destroy();
                });
            },
            null, //specific container
            { //config object
                'width': '200',
                'modal': true
            }
        );
    },

    deleteTransition: function (event) {
        event.preventDefault();
        var table = Fisma.Registry.get('transition-table');
        if (table) {
            table.deleteRow($(this).parents('tr.yui-dt-rec').get(0));
        }
    },

    editTransition: function (event) {
        event.preventDefault();
        var table       = Fisma.Registry.get('transition-table'),
            parentRow   = $(this).parents('tr.yui-dt-rec').get(0),
            record      = table.getRecord(parentRow)._oData,
            workflowId  = $('input#workflowId').val();
        Fisma.UrlPanel.showPanel(
            'Edit transition', //title
            '/workflow/transition-form/workflowId/' + workflowId + '/format/html', //url
            function(panel) { //callback
                //Execute scripts
                $('script', panel.body).appendTo($('body'));
                var jqForm = $('form', panel.body);
                $('input[name=name]', jqForm).val(record.name);
                $('input[name=destination][value=' + record.destination + ']', jqForm).attr('checked', true);
                $('input[name=roles]', jqForm).val(record.roles);
                $('input[name=roles]', jqForm).parents('#roles').data('multiselectControl')._refreshFromInputElement();
                $('select[name=customDestination]', jqForm).val(record.customDestination);

                jqForm.submit(function(event) {
                    event.preventDefault();

                    var data = {
                        'name': $('input[name=name]', jqForm).val(),
                        'destination': $('input[name=destination]:checked', jqForm).val(),
                        'roles': $('input[name=roles]', jqForm).val(),
                        'actions': record.actions,
                        'customDestination': $('select[name=customDestination]', jqForm).val()
                    };
                    if (table) {
                        table.updateRow(parentRow, data);
                    }

                    panel.hide();
                    panel.destroy();
                });
            },
            null, //specific container
            { //config object
                'width': '200',
                'modal': true
            }
        );
    },

    /**
     * Complete a workflow step via ajax
     *
     * @param Event event Natively provided
     * @param array args Expecting stepId, objectId, transitionName, and url
     */
    completeStep: function(event, args) {
        event.preventDefault();
        $(this)
            .attr('disabled', 'disabled')
            .after('<img src="/images/spinners/small.gif" style="vertical-align:text-top"/>')
        ;
        var that = this,
            thatEvent = event,
            thatArgs = args;
        $.post(
            args.url,
            {
                'csrf': $('input[name=csrf]').val(),
                'id': args.objectId,
                'comment': $('textarea[name=stepComment]').val(),
                'expirationDate': $('input[name=stepExpirationDate]').val(),
                'transitionName': args.transitionName
            },
            function(data) {
                if (data.err) {
                    $(that).removeAttr('disabled');
                    $(that).next('img').remove();
                    if (data.err.indexOf('Expiration Date') >= 0) {
                        var nextStepName = data.nextStepName;
                        Fisma.Util.showInputDialog(
                            'Allotted time for ' + nextStepName, //title
                            'Number of days', //query
                            { //callback
                                'continue': function(event, args) {
                                    event.preventDefault();
                                    var input = Number(args.textField.value.trim());
                                    if (isFinite(input) && input > 0) {
                                        $('input[name=stepExpirationDate]').val(input);
                                        Fisma.Workflow.completeStep(thatEvent, thatArgs);
                                        args.panel.hide();
                                        args.panel.destroy();
                                    } else { // input NaN
                                        args.errorDiv.innerHTML = "Please enter a single, positive ID number.";
                                    }
                                }
                            },
                            '30' //default
                        );
                    } else {
                        Fisma.Util.showAlertDialog(data.err);
                    }
                } else {
                    $('#workflowTabContent').replaceWith(data);
                }
            }
        );
    }
};
