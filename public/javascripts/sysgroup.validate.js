$().ready(function() {
    $("#sysgroupform").validate({
        rules: {
            "sysgroup[name]": "required",
            "sysgroup[nickname]": "required"
		},
        messages: {
            "sysgroup[name]": "Please enter System Group Name",
            "sysgroup[nickname]": "Please enter System Group Nickname"
        }
    });
});