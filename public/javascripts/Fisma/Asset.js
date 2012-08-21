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
        var jcell = $('td:contains("' + tag + '")');
        var row = jcell.parents('tr').get(0);
        var datatable = Fisma.Registry.get('assetServiceTagTable');
        datatable.selectRow(row);

        var Dom = YAHOO.util.Dom,
            Event = YAHOO.util.Event,
            Panel = YAHOO.widget.Panel,
            contentDiv = document.createElement("div"),
            errorDiv = document.createElement("div"),
            form = document.createElement('form'),
            textField = $('<input type="text"/>').get(0),
            button = $('<input type="submit" value="OK"/>').get(0),
            table = $('<table class="fisma_crud"><tbody><tr><td>New name: </td><td></td><td></td></tr></tbody></table>');
        table.appendTo(form);
        $("td", table).get(1).appendChild(textField);
        $("td", table).get(2).appendChild(button);
        contentDiv.appendChild(errorDiv);
        contentDiv.appendChild(form);

        // Make Go button YUI widget
        button = new YAHOO.widget.Button(button);

        // Add event listener
        var fn = function(ev, obj) {
            Event.stopEvent(ev);
            var input = Number(textField.value.trim());
            if (true) {
                errorDiv.innerHTML = "Renaming '" + tag + "'...";
                jcell.children('div').text(textField.value);
                panel.hide();
                panel.destroy();
            } else {
                errorDiv.innerHTML = "Error.";
            }
        };
        var param = {};
        Event.addListener(form, "submit", fn, param);

        // show the panel
        var panel = new Panel(Dom.generateId(), {modal: true});
        panel.setHeader("Rename '" + tag + "' ...");
        panel.setBody(contentDiv);
        panel.render(document.body);
        panel.center();
        panel.show();
        panel.subscribe("hide", function() { datatable.unselectRow(row); });
        textField.focus();
    }
};
