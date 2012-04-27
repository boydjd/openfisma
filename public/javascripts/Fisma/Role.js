/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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
 * @fileoverview Provides client-side behavior for the AttachArtifacts behavior
 * 
 * @author    Dale Frey <dale.frey@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2011 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */
 
Fisma.Role = {

    rolePrivChanges : {},

    dataTableCheckboxClick : function (oArgs) {

        var checkboxObj = oArgs.target;
        var column = this.getColumn(checkboxObj);
        var roleName = column.key;

        // The 2nd column (hidden) has the privilegeId
        var privilegeCell = this.getRow(checkboxObj).childNodes[1].childNodes[0].childNodes[0];
        var privilegeId = $(privilegeCell).text();

        // Update array of changes to apply
        var newChange = {
            'roleName': roleName,
            'privilegeId': privilegeId,
            'newValue': checkboxObj.checked
        };

        Fisma.Role.rolePrivChanges[roleName + privilegeId] = newChange;

        // Put change list into the form to be submitted
        YAHOO.util.Dom.get('rolePrivChanges').value = YAHOO.lang.JSON.stringify(Fisma.Role.rolePrivChanges);
    }
};
