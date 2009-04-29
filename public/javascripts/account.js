function GeneratePassword () {
    var generatePasswordButton = document.getElementById('generate_password');
    YAHOO.util.Connect.asyncRequest('GET',
                                    '/account/generatepassword/format/html',
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

