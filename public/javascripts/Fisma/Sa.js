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
    SELECTED_CONTROLS_ACTIONS:
        '<a href="#" onclick="Fisma.Sa.removeControl(event, this)"><i class="icon-remove"></i> Remove</a><br/>' +
        '<a href="#" onclick="Fisma.Sa.setCommonControl(event, this)"><i class="icon-star"></i> Set common</a><br/>' +
        '<a href="#" onclick="Fisma.Sa.selectEnhancements(event, this)">' +
            '<i class="icon-list-alt"></i> Enhancements</a><br/>',

    fiveColDefs: [
        {"sWidth": "10%", "aTargets": [ 0 ] },
        {"sWidth": "10%", "aTargets": [ 1 ] },
        {"sWidth": "64%", "aTargets": [ 2 ] },
        {"sWidth": "8%", "aTargets": [ 3 ] },
        {"sWidth": "8%", "aTargets": [ 4 ] }
    ],
    sixColDefs: [
        {"sWidth": "10%", "aTargets": [ 0 ] },
        {"sWidth": "10%", "aTargets": [ 1 ] },
        {"sWidth": "56%", "aTargets": [ 2 ] },
        {"sWidth": "8%", "aTargets": [ 3 ] },
        {"sWidth": "8%", "aTargets": [ 4 ] },
        {"sWidth": "8%", "aTargets": [ 5 ] }
    ],
    sixActionColDefs: [
        {"sWidth": "10%", "aTargets": [ 0 ] },
        {"sWidth": "10%", "aTargets": [ 1 ] },
        {"sWidth": "52%", "aTargets": [ 2 ] },
        {"sWidth": "8%", "aTargets": [ 3 ] },
        {"sWidth": "8%", "aTargets": [ 4 ] },
        {"sWidth": "12%", "aTargets": [ 5 ] }
    ],
    sevenActionColDefs: [
        {"sWidth": "10%", "aTargets": [ 0 ] },
        {"sWidth": "10%", "aTargets": [ 1 ] },
        {"sWidth": "44%", "aTargets": [ 2 ] },
        {"sWidth": "8%", "aTargets": [ 3 ] },
        {"sWidth": "8%", "aTargets": [ 4 ] },
        {"sWidth": "8%", "aTargets": [ 5 ] },
        {"sWidth": "12%", "aTargets": [ 6 ] }
    ],

    initCat: function(editable) {
        Fisma.Sa.assignedTypesTable = $('#assignedTypes').dataTable({
            "aoColumnDefs": ((editable) ? Fisma.Sa.sevenActionColDefs : Fisma.Sa.sixColDefs)
        });

        if (editable) {
            Fisma.Sa.addTypeTable = $('#addType').dataTable({
                "aoColumnDefs": Fisma.Sa.sixColDefs
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
                    localData   = Fisma.Sa.addTypeTable.fnGetData(currentRow);

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
                            $('#fipsCategory').text(data.fipsCategory);
                            Fisma.Sa.assignedTypesTable.fnAddData(localData);
                            $(Fisma.Sa.assignedTypesTable.fnGetNodes()).filter('tr:not([data-type-id])')
                                .attr('data-type-id', dataTypeId);
                            Fisma.Sa.addTypeTable.fnDeleteRow(currentRow);
                            Fisma.Util.message('<p>New Information Data Type assigned successfully.</p>', 'success');
                        } else {
                            Fisma.Util.message('<p>Unexpected error occurred.</p>', 'error', true);
                        }
                    }
                );
            });
        }
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
                    $('#fipsCategory').text(data.fipsCategory);
                    var localData = [
                        data.dataType.category,
                        data.dataType.subcategory,
                        data.dataType.description,
                        data.dataType.confidentiality,
                        data.dataType.integrity,
                        data.dataType.availability
                    ];
                    Fisma.Sa.addTypeTable.fnAddData(localData);
                    $('#addType tr:not([data-type-id])').attr('data-type-id', dataTypeId);
                    Fisma.Sa.assignedTypesTable.fnDeleteRow(currentRow);
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
                    $('#fipsCategory').text(data.fipsCategory);
                    var updatedData = [
                        data.dataType.category,
                        data.dataType.subcategory,
                        data.dataType.description,
                        data.dataType.confidentiality,
                        data.dataType.integrity,
                        data.dataType.availability,
                        Fisma.Sa.ASSIGNED_TYPES_ACTIONS
                    ];
                    Fisma.Sa.assignedTypesTable.fnUpdate(updatedData, currentRow);
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
        $(this).html('<img src="/images/loading_bar.gif" />').attr('disabled', true);
    },

    refreshAllDataType: function() {
        var systemId    = $('input#systemId').val();
        Fisma.Util.formPostAction(null, '/sa/security-authorization/refresh-all-type/', systemId);
        $(this).html('<img src="/images/loading_bar.gif" />').attr('disabled', true);
    },

    initSel: function(editable) {
        Fisma.Sa.importedControlsTable = $('#importedControls').dataTable({
            "aoColumnDefs": ((editable) ? Fisma.Sa.sixActionColDefs : Fisma.Sa.fiveColDefs)
        });

        Fisma.Sa.selectedControlsTable = $('#selectedControls').dataTable({
            "aoColumnDefs": ((editable) ? Fisma.Sa.sevenActionColDefs : Fisma.Sa.sixColDefs)
        });

        if (editable) {
            Fisma.Sa.addControlTable = $('#addControl').dataTable({
                "aoColumnDefs": Fisma.Sa.fiveColDefs
            });

            $('#importControlSection').hide();
            $('#importControlSection div.sectionHeader')
                .append($('<button/>', {'class': 'close pull-right'})
                    .html('&times;')
                    .click(function(event) {
                        event.preventDefault();
                        $('#importControlSection').fadeOut();
                    }
                ));

            $('#addControlSection').hide();
            $('#addControlSection div.sectionHeader')
                .append($('<button/>', {'class': 'close pull-right'})
                    .html('&times;')
                    .click(function(event) {
                        event.preventDefault();
                        $('#addControlSection').fadeOut();
                    }
                ));
            $('#addControl').on('click', 'tbody > tr', null, function(event) {
                var currentRow  = this,
                    dataTypeId  = $(currentRow).attr('data-type-id'),
                    systemId    = $('input#systemId').val(),
                    csrf        = $('input[name=csrf]').val(),
                    localData   = Fisma.Sa.addControlTable.fnGetData(currentRow);

                localData.push('<i class="common-flag icon-star-empty"></i> NO');
                localData.push(Fisma.Sa.SELECTED_CONTROLS_ACTIONS);
                $('#addControlSection').fadeOut();
                //AJAX manipulating
                $.post(
                    '/sa/security-authorization/add-control/',
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
                            $('#fipsCategory').text(data.fipsCategory);
                            Fisma.Sa.selectedControlsTable.fnAddData(localData);
                            $(Fisma.Sa.selectedControlsTable.fnGetNodes()).filter('tr:not([data-type-id])')
                                .attr('data-type-id', dataTypeId);
                            Fisma.Sa.addControlTable.fnDeleteRow(currentRow);
                            Fisma.Util.message('<p>Security Control Selection recorded successfully.</p>', 'success');
                        } else {
                            Fisma.Util.message('<p>Unexpected error occurred.</p>', 'error', true);
                        }
                    }
                );
            });

            $('#enhancementModal').on('click', '#saveEnhancements', null, function(event) {
                event.preventDefault();
                var currentRow  = $(this).data('currentRow'),
                    dataTypeId  = $(currentRow).attr('data-type-id'),
                    systemId    = $('input#systemId').val(),
                    csrf        = $('input[name=csrf]').val(),
                    localData   = [];
                $('#enhancementModal div.modal-body tr.row_selected td.enhancemendId').each(function() {
                    localData.push($(this).text());
                });
                console.log(localData);
                $('#enhancementModal').modal('hide');
                //AJAX manipulating
                $.post(
                    '/sa/security-authorization/save-control-enhancements/',
                    {
                        'id': systemId,
                        'dataTypeId': dataTypeId,
                        'csrf': csrf,
                        'format': 'json',
                        'selectedEnhancements': JSON.stringify(localData)
                    },
                    function(data) {
                        if (data.err) {
                            Fisma.Util.message('<p>Error occurred: ' + data.err + '</p>', 'error', true);
                            if (data.errStackTrace) {
                                Fisma.Util.message('<pre>' + data.errStackTrace + '</pre>', 'error');
                            }
                        } else if (data.success) {
                            Fisma.Util.message('<p>Control enhancements updated successfully.</p>', 'success');
                        } else {
                            Fisma.Util.message('<p>Unexpected error occurred.</p>', 'error', true);
                        }
                    }
                );
            });
        }
    },

    addControl: function(event, args) {
        $('#addControlSection').fadeIn();
    },

    removeControl: function(event, linkElement) {
        event.preventDefault();
        var currentRow  = $(linkElement).parents('tr[data-type-id]').get(0),
            dataTypeId  = $(currentRow).attr('data-type-id'),
            systemId    = $('input#systemId').val(),
            csrf        = $('input[name=csrf]').val();
        //AJAX manipulating
        $.post(
            '/sa/security-authorization/remove-control/',
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
                    var localData = [
                        data.securityControl.family,
                        data.securityControl.name,
                        data.securityControl.control,
                        data.securityControl.code,
                        data.securityControl.controlLevel
                    ];
                    Fisma.Sa.addControlTable.fnAddData(localData);
                    $('#addControl tr:not([data-type-id])').attr('data-type-id', dataTypeId);
                    Fisma.Sa.selectedControlsTable.fnDeleteRow(currentRow);
                    Fisma.Util.message('<p>Security Control removed successfully.</p>', 'success');
                } else {
                    Fisma.Util.message('<p>Unexpected error occurred.</p>', 'error', true);
                }
            }
        );
    },

    setCommonControl: function(event, linkElement) {
        event.preventDefault();
        var currentRow  = $(linkElement).parents('tr[data-type-id]').get(0),
            dataTypeId  = $(currentRow).attr('data-type-id'),
            systemId    = $('input#systemId').val(),
            csrf        = $('input[name=csrf]').val();
        //AJAX manipulating
        $.post(
            '/sa/security-authorization/set-common-control/',
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
                    if (data.common) {
                        $('i.common-flag', currentRow).parents('td').first()
                            .html('<i class="common-flag icon-star"></i> YES');
                        $('a:contains(common)', currentRow).html('<i class="icon-star-empty"></i> Unset common');
                    } else {
                        $('i.common-flag', currentRow).parents('td').first()
                            .html('<i class="common-flag icon-star-empty"></i> NO');
                        $('a:contains(common)', currentRow).html('<i class="icon-star"></i> Set common');
                    }
                } else {
                    Fisma.Util.message('<p>Unexpected error occurred.</p>', 'error', true);
                }
            }
        );
    },

    removeAllSecurityControl: function() {
        var systemId    = $('input#systemId').val();
        Fisma.Util.formPostAction(null, '/sa/security-authorization/remove-all-control/', systemId);
        $(this).html('<img src="/images/loading_bar.gif" />').attr('disabled', true);
    },

    importBaselineControl: function() {
        var systemId    = $('input#systemId').val();
        Fisma.Util.formPostAction(null, '/sa/security-authorization/import-baseline-control/', systemId);
        $(this).html('<img src="/images/loading_bar.gif" />').attr('disabled', true);
    },

    selectEnhancements: function(event, linkElement) {
        event.preventDefault();
        var currentRow  = $(linkElement).parents('tr[data-type-id]').get(0),
            dataTypeId  = $(currentRow).attr('data-type-id'),
            systemId    = $('input#systemId').val();

        $('#enhancementModal .modal-body').html('<p align="center"><img src="/images/loading_bar.gif" /></p>');
        $('#enhancementModal #saveEnhancements').data('currentRow', currentRow);
        $('#enhancementModal').modal();
        //AJAX manipulating
        $.get(
            '/sa/security-authorization/get-control-enhancements/',
            {
                'id': systemId,
                'dataTypeId': dataTypeId,
                'format': 'html'
            },
            function(data) {
                $('#enhancementModal .modal-body').html(data);
            }
        );
    },

    initGetControlEnhancements: function() {
        $('#enhancementTable').on('click', 'tbody > tr', null, function(event) {
            $(this).toggleClass('row_selected');
        });
    },

    importCommonControl: function(event) {
        event.preventDefault();
        var systemId    = $('input#systemId').val();

        $('#importControlSection').fadeIn()
            .find('.section').html('<p align="center"><img src="/images/loading_bar.gif" /></p>');

        $.get(
            '/sa/security-authorization/get-common-controls/',
            {
                'id': systemId,
                'format': 'html'
            },
            function(data) {
                $('#importControlSection .section').html(data);
            }
        );
    },

    initGetCommonControls: function() {
        Fisma.Sa.systemTable = $('#systemTable').dataTable();
        $('#systemTable').on('click', 'tbody > tr', null, function(event) {
            var currentRow  = this,
                systemId    = $('input#systemId').val(),
                dataTypeId  = $('td.systemId', this).text();

            if (dataTypeId) {
                $('#importControlSection').fadeOut();
                $('#importCommon').html('<img src="/images/loading_bar.gif" />').attr('disabled', true);

                Fisma.Util.formPostAction(
                    null,
                    '/sa/security-authorization/import-common-control/dataTypeId/' + dataTypeId,
                    systemId
                );
            }
        });
    }
};
