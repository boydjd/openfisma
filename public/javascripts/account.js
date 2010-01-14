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
 * @fileoverview Used for generate a complicated password and check account when create,
 *               update user and check user account when authentication is LDAP
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 * @version   $Id$
 */

function GeneratePassword () {
    var generatePasswordButton = document.getElementById('generate_password');
    YAHOO.util.Connect.asyncRequest('GET',
                                    '/user/generate-password/format/html',
                                    {
                                        success: function(o) {
                                            document.getElementById('password').value = o.responseText;
                                            document.getElementById('confirmPassword').value = o.responseText;
                                        },
                                        failure: function(o) {alert('Failed to generate password: ' + o.statusText);}
                                    },
                                    null);
    return false;
}

var check_account = function () {
    var account = document.getElementById('username').value;
    account = encodeURIComponent(account);
    var url = "/user/check-account/format/json/account/" + account;
    YAHOO.util.Connect.asyncRequest('GET',
                                    url,
                                    {
                                        success: function(o) {
                                            var data = YAHOO.lang.JSON.parse(o.responseText);
                                            message(data.msg, data.type);
                                        },
                                        failure: function(o) {alert('Failed to generate password: ' + o.statusText);}
                                    },
                                    null);
    return false;
};
