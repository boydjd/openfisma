/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * @fileoverview This function is unsafe because it selects all checkboxes on the page,
 *               regardless of what grouping they belong to.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 *
 * @todo Write a safe version of this function called selectAll that takes some kind
 *       of scope as a parameter so that it can be limited.
 */

function selectAllUnsafe() {
    var checkboxes = YAHOO.util.Dom.getElementsBy(
        function (el) {
            return (el.tagName == 'INPUT' && el.type == 'checkbox')
        }
    );
    for (i in checkboxes) {
        checkboxes[i].checked = 'checked';
    }
}

function selectAllByName(event, config) {
    $('input:checkbox[name="' + config.name + '"]').attr("checked","checked");
}

function selectNoneByName(event, config) {
    $('input:checkbox[name="' + config.name + '"]').attr("checked","unchecked");
}

function selectAll() {
    alert("Not implemented");
}

function selectNoneUnsafe() {
    var checkboxes = YAHOO.util.Dom.getElementsBy(
        function (el) {
            return (el.tagName == 'INPUT' && el.type == 'checkbox')
        }
    );
    for (i in checkboxes) {
        checkboxes[i].checked = '';
    }
}

function selectNone() {
    alert("Not implemented");
}

function elDump(el) {
    props = '';
    for (prop in el) {
        props += prop + ' : ' + el[prop] + '\n';
    }
    alert(props);
}
