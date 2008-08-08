$().ready(function() {
    $("#roleform").validate({
        rules: {
            "role[name]": "required",
            "role[nickname]": "required",
            "role[desc]":{
                maxLength:[200]}
		},
        messages: {
            "role[name]": "Please enter role name",
            "role[nickname]": "Please enter role nickname",
            "role[desc]": "Description must be at most 200 characters"
        }
    });
});