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
 * @author    Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

Fisma.Asset = {
    renameTag: function(tag) {
        var jcell = $('td').filter(function(index){
            return ($(this).text() === tag);
        });
        var row = jcell.parents('tr').get(0);
        var datatable = Fisma.Registry.get('assetServiceTagTable');
        datatable.selectRow(row);

        Fisma.Util.showInputDialog(
            "Rename '" + tag + "' ...",
            "New name",
            {
                continue: function(ev, obj) {
                    YAHOO.util.Event.stopEvent(ev);
                    var input = obj.textField.value;
                    if (input != "") {
                        obj.errorDiv.innerHTML = "Renaming '" + tag + "'...";

                        $.post(
                            '/asset/rename-service-tag',
                            {
                                format: 'json',
                                oldTag: tag,
                                newTag: input,
                                csrf: $('[name=csrf]').val()
                            },
                            function(data) {
                                $('[name=csrf]').val(data.csrfToken);

                                if (data.result.success) {
                                    if (data.result.message) {
                                        Fisma.Util.showAlertDialog(data.result.message);
                                    } else {
                                        datatable.updateRow(row, {
                                            'Tag': input,
                                            'Assets': {displayText: jcell.siblings().eq(0).find('div a').text(), url: '/asset/list?q=/serviceTag/textExactMatch/' + input},
                                            'Edit': "javascript:Fisma.Asset.renameTag('" + input + "')",
                                            'Delete': '/asset/remove-service-tag/tag/' + input
                                        });
                                    }
                                } else {
                                    Fisma.Util.showAlertDialog(data.result.message);
                                }
                                obj.panel.hide();
                                obj.panel.destroy();
                            }
                        );

                    } else {
                        obj.errorDiv.innerHTML = "Tag name cannot be blank.";
                    }
                },
                cancel: function(ev, obj) {
                    datatable.unselectRow(row);
                }
            }
        );
    },

    addTag: function() {
        var datatable = Fisma.Registry.get('assetServiceTagTable');

        Fisma.Util.showInputDialog(
            "Add a tag ...",
            "Tag name",
            {
                continue: function(ev, obj) {
                    YAHOO.util.Event.stopEvent(ev);
                    var input = obj.textField.value;
                    if (input != "") {
                        obj.errorDiv.innerHTML = "Adding tag '" + input + "'...";

                        $.post(
                            '/asset/add-service-tag/',
                            {
                                format: 'json',
                                tag: input,
                                csrf: $('[name=csrf]').val()
                            },
                            function(data) {
                                $('[name=csrf]').val(data.csrfToken);

                                if (data.result.success) {
                                    if (data.result.message) {
                                        Fisma.Util.showAlertDialog(data.result.message);
                                    } else {
                                        datatable.addRow({
                                            'Tag': input,
                                            'Assets': {displayText: '0', url: '/asset/list?q=/serviceTag/textExactMatch/' + input},
                                            'Edit': "javascript:Fisma.Asset.renameTag('" + input + "')",
                                            'Delete': '/asset/remove-service-tag/tag/' + input
                                        });
                                    }
                                } else {
                                    Fisma.Util.showAlertDialog(data.result.message);
                                }
                                obj.panel.hide();
                                obj.panel.destroy();
                            }
                        );
                    } else {
                        obj.errorDiv.innerHTML = "Tag name cannot be blank.";
                    }
                },
                cancel: function(ev, obj) {
                }
            }
        );
    }
};
