$().ready(function() {
    $("#accountform").validate({
        rules: {
            "user[name_first]": "required",
            "user[name_last]": "required",
            "user[phone_office]":{
                required:true,
                rangeLength:[5,13]},
            "user[email]": {
                required: true,
                email: true
            },
            "user[account]": {
                required: true,
                minlength: 4
            },
            "user[password]": {
                minLength: 5
            },
            "password_confirm": {
                minlength: 5,
                equalTo: "#user_password"
            },
            "system[]": {
                required: true,
                minLength: 1
            }
        },
        messages: {
            "user[name_first]": "Please enter your firstname",
            "user[name_last]": "Please enter your lastname",
            "user[phone_office]": "Phone number must be consisted of 5 to 13 characters",
            "user[email]": "Please enter a valid email address",
            "user[account]": "Your account must consist of at least 4 characters",
            "user[password]": "Your password must be at least 5 characters long",
            "password_confirm": {
                minlength:"Must be at least 5 characters long",
                equalTo:"Please enter the same password as above"
            }
        }
    });
});