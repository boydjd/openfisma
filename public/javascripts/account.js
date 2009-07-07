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
    var url = "/user/check-account/format/html/account/"+account;
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