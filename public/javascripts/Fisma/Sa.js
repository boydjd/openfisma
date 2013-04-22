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

Fisma.Sa = {
    ASSIGNED_TYPES_ACTIONS:
        '<a href="#" onclick="Fisma.Sa.removeDataType(event, this)"><i class="icon-remove"></i> Remove</a><br/>' +
        '<a href="#" onclick="Fisma.Sa.refreshDataType(event, this)"><i class="icon-refresh"></i> Refresh</a>',

    initCat: function() {
        Fisma.Sa.assignedTable = $('#assignedTypes').dataTable({
            "aoColumnDefs": [
                {"sWidth": "10%", "aTargets": [ 0 ] },
                {"sWidth": "16%", "aTargets": [ 1 ] },
                {"sWidth": "50%", "aTargets": [ 2 ] },
                {"sWidth": "6%", "aTargets": [ 3 ] },
                {"sWidth": "6%", "aTargets": [ 4 ] },
                {"sWidth": "6%", "aTargets": [ 5 ] },
                {"sWidth": "6%", "aTargets": [ 6 ] }
            ]
        });

        Fisma.Sa.addTable = $('#addType').dataTable({
            "aoColumnDefs": [
                {"sWidth": "10%", "aTargets": [ 0 ] },
                {"sWidth": "16%", "aTargets": [ 1 ] },
                {"sWidth": "50%", "aTargets": [ 2 ] },
                {"sWidth": "8%", "aTargets": [ 3 ] },
                {"sWidth": "8%", "aTargets": [ 4 ] },
                {"sWidth": "8%", "aTargets": [ 5 ] }
            ]
        });
        $('#addTypeSection').hide();
        $('#addTypeSection div.sectionHeader')
            .append($('<button/>', {'class': 'close pull-right'})
                .html('&times;')
                .click(function(event) {
                    event.preventDefault();
                    $('#addTypeSection').fadeOut();
                }
            ));
        $('#addType').on('click', 'tbody > tr', null, function(event) {
            var currentRow  = this,
                dataTypeId  = $(currentRow).attr('data-type-id'),
                systemId    = $('input#systemId').val(),
                csrf        = $('input[name=csrf]').val(),
                localData   = Fisma.Sa.addTable.fnGetData(currentRow);

            localData.push(Fisma.Sa.ASSIGNED_TYPES_ACTIONS);
            $('#addTypeSection').fadeOut();

            //AJAX manipulating
            $.post(
                '/sa/security-authorization/add-type/',
                {
                    'id': systemId,
                    'dataTypeId': dataTypeId,
                    'csrf': csrf,
                    'format': 'json'
                },
                function(data) {
                    if (data.err) {
                        Fisma.Util.message('<p>Error occurred: ' + data.err + '</p>', 'error', true);
                        if (data.errStackTrace) {
                            Fisma.Util.message('<pre>' + data.errStackTrace + '</pre>', 'error');
                        }
                    } else if (data.success) {
                        Fisma.Sa.assignedTable.fnAddData(localData);
                        $('#assignedTypes tr:not([data-type-id])').attr('data-type-id', dataTypeId);
                        Fisma.Sa.addTable.fnDeleteRow(currentRow);
                        Fisma.Util.message('<p>New Information Data Type assigned successfully.</p>', 'success');
                    } else {
                        Fisma.Util.message('<p>Unexpected error occurred.</p>', 'error', true);
                    }
                }
            );
        });
    },

    addDataType: function(event, args) {
        $('#addTypeSection').fadeIn();
    },

    removeDataType: function(event, linkElement) {
        event.preventDefault();
        var currentRow  = $(linkElement).parents('tr[data-type-id]').get(0),
            dataTypeId  = $(currentRow).attr('data-type-id'),
            systemId    = $('input#systemId').val(),
            csrf        = $('input[name=csrf]').val();
        //AJAX manipulating
        $.post(
            '/sa/security-authorization/remove-type/',
            {
                'id': systemId,
                'dataTypeId': dataTypeId,
                'csrf': csrf,
                'format': 'json'
            },
            function(data) {
                if (data.err) {
                    Fisma.Util.message('<p>Error occurred: ' + data.err + '</p>', 'error', true);
                    if (data.errStackTrace) {
                        Fisma.Util.message('<pre>' + data.errStackTrace + '</pre>', 'error');
                    }
                } else if (data.success) {
                    Fisma.Sa.assignedTable.fnDeleteRow(currentRow);
                    Fisma.Util.message('<p>Information Data Type removed successfully.</p>', 'success');
                } else {
                    Fisma.Util.message('<p>Unexpected error occurred.</p>', 'error', true);
                }
            }
        );
    },

    refreshDataType: function(event, linkElement) {
        event.preventDefault();
        var currentRow  = $(linkElement).parents('tr[data-type-id]').get(0),
            dataTypeId  = $(currentRow).attr('data-type-id'),
            systemId    = $('input#systemId').val(),
            csrf        = $('input[name=csrf]').val();
        //AJAX manipulating
        $.post(
            '/sa/security-authorization/refresh-type/',
            {
                'id': systemId,
                'dataTypeId': dataTypeId,
                'csrf': csrf,
                'format': 'json'
            },
            function(data) {
                if (data.err) {
                    Fisma.Util.message('<p>Error occurred: ' + data.err + '</p>', 'error', true);
                    if (data.errStackTrace) {
                        Fisma.Util.message('<pre>' + data.errStackTrace + '</pre>', 'error');
                    }
                } else if (data.success) {
                    var updatedData = [
                        data.dataType.category,
                        data.dataType.subcategory,
                        data.dataType.description,
                        data.dataType.confidentiality,
                        data.dataType.integrity,
                        data.dataType.availability,
                        Fisma.Sa.ASSIGNED_TYPES_ACTIONS
                    ];
                    Fisma.Sa.assignedTable.fnUpdate(updatedData, currentRow);
                    Fisma.Util.message('<p>Information Data Type refreshed successfully.</p>', 'success');
                } else {
                    Fisma.Util.message('<p>Unexpected error occurred.</p>', 'error', true);
                }
            }
        );
    },

    removeAllDataType: function() {
        var systemId    = $('input#systemId').val();
        Fisma.Util.formPostAction(null, '/sa/security-authorization/remove-all-type/', systemId);
    },

    refreshAllDataType: function() {
        var systemId    = $('input#systemId').val();
        Fisma.Util.formPostAction(null, '/sa/security-authorization/refresh-all-type/', systemId);
    }
};
