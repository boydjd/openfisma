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
    showInputDialog: function(title, query, callbacks) {
        var Dom = YAHOO.util.Dom,
            Event = YAHOO.util.Event,
            Panel = YAHOO.widget.Panel,
            contentDiv = document.createElement("div"),
            errorDiv = document.createElement("div"),
            form = document.createElement('form'),
            textField = $('<input type="text"/>').get(0),
            button = $('<input type="submit" value="OK"/>').get(0),
            table = $('<table class="fisma_crud"><tbody><tr><td>' + query + ': </td><td></td><td></td></tr></tbody></table>');
        table.appendTo(form);
        $("td", table).get(1).appendChild(textField);
        $("td", table).get(2).appendChild(button);
        contentDiv.appendChild(errorDiv);
        contentDiv.appendChild(form);

        // Make Go button YUI widget
        button = new YAHOO.widget.Button(button);

        // Prepare the panel
        var panel = new Panel(Dom.generateId(), {modal: true});
        panel.setHeader(title);
        panel.setBody(contentDiv);
        panel.render(document.body);
        panel.center();

        // Add event listener
        Event.addListener(form, "submit", callbacks.continue, {panel: panel, errorDiv: errorDiv, textField: textField});
        panel.subscribe("hide", callbacks.cancel);

        // Show the panel
        panel.show();
        textField.focus();
    },

    renameTag: function(tag) {
        var jcell = $('td:contains("' + tag + '")');
        var row = jcell.parents('tr').get(0);
        var datatable = Fisma.Registry.get('assetServiceTagTable');
        datatable.selectRow(row);

        Fisma.Asset.showInputDialog(
            "Rename '" + tag + "' ...",
            "New name",
            {
                continue: function(ev, obj) {
                    YAHOO.util.Event.stopEvent(ev);
                    var input = obj.textField.value;
                    if (input != "") {
                        obj.errorDiv.innerHTML = "Renaming '" + tag + "'...";
                        jcell.children('div').text(input);
                        jcell.siblings().eq(1).find('div a')
                            .attr('href', 'javascript:Fisma.Asset.renameTag("' + input + '")');
                        obj.panel.hide();
                        obj.panel.destroy();
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

        Fisma.Asset.showInputDialog(
            "Add a tag ...",
            "Tag name",
            {
                continue: function(ev, obj) {
                    YAHOO.util.Event.stopEvent(ev);
                    var input = obj.textField.value;
                    if (input != "") {
                        obj.errorDiv.innerHTML = "Adding tag '" + input + "'...";
                        datatable.addRow({
                            'Tag': input,
                            'Assets': {displayText: '0', url: '/asset/list?q=/serviceTag/textExactMatch/' + input},
                            'Edit': "javascript:Fisma.Asset.renameTag('" + input + "')",
                            'Delete': ''
                        });
                        obj.panel.hide();
                        obj.panel.destroy();
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
