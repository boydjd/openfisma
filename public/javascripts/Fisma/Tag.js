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

Fisma.Tag = {
    add: function(ev, obj) {
        var tagId = obj.tagId,
            addUrl = obj.addUrl,
            datatable = Fisma.Registry.get("tagTable");

        Fisma.Util.showInputDialog(
            "Add a tag ...",
            "Tag name",
            {
                'continue': function(ev, obj) {
                    YAHOO.util.Event.stopEvent(ev);
                    var input = obj.textField.value;
                    if (input !== "") {
                        obj.errorDiv.innerHTML = "Adding tag '" + input + "'...";

                        $.post(
                            addUrl,
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
                                        datatable.addRow(data.newRow);
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
                'cancel': function(ev, obj) {
                }
            }
        );
    },

    rename: function (tag, postUrl) {
        var cell = $('td').filter(function(index){
            return ($(this).text() === tag);
        });
        var datatable = Fisma.Registry.get('tagTable');
        var row = datatable.getTrEl(cell.get(0));
        datatable.selectRow(row);

        Fisma.Util.showInputDialog(
            "Rename '" + tag + "' ...",
            "New name",
            {
                'continue': function(ev, obj) {
                    YAHOO.util.Event.stopEvent(ev);
                    var input = obj.textField.value;
                    if (input === "") {
                        obj.errorDiv.innerHTML = "Tag name cannot be blank.";
                        return;
                    }

                    obj.errorDiv.innerHTML = "Renaming '" + tag + "'...";

                    $.post(
                        postUrl,
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
                                    console.log('row', row);
                                    console.log('data.row', data.row);
                                    datatable.updateRow(row, data.row);
                                }
                            } else {
                                Fisma.Util.showAlertDialog(data.result.message);
                            }
                            obj.panel.hide();
                            obj.panel.destroy();
                        }
                    );
                },
                'cancel': function(ev, obj) {
                    datatable.unselectRow(row);
                }
            }
        );

    }

};
