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
 * @fileoverview Validate ldap configuration.
 *
 * @author    Jackson Yang <yangjianshan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

function validateLDAP () {
    if (Fisma.WaitingSpinner.isWorking()) {
        return;
    }
    var element = document.getElementById('validateLdap');
    Fisma.WaitingSpinner.init(element, element.parentNode);
    Fisma.WaitingSpinner.show();
    var form = document.getElementById('ldapUpdate');
    YAHOO.util.Connect.setForm(form);
    YAHOO.util.Connect.asyncRequest('POST', '/config/validate-ldap/format/html', {
        success:function (o) {
            message(o.responseText);
            Fisma.WaitingSpinner.destory();
        },
        failure: handleFailure});
}
