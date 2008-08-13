$().ready(function() {
    $("#systemform").validate({
        rules: {
            "system[name]": "required",
            "system[nickname]": "required",
            "system[primary_office]":"required",
            "system[confidentiality]": "required",
            "system[integrity]": "required",
            "system[availability]": "required",
            "system[type]": "required",
            "system[desc]": {
                maxLength:[200]},
            "system[criticality_justification]": {
                maxLength:[200]},
            "system[sensitivity_justification]": {
                maxLength:[200]},
            "sysgroup[]": {
                required: true,
                minLength: 1
                }
        },
        messages: {
            "system[name]": "Please enter system name",
            "system[nickname]": "Please enter system nickname",
            "system[primary_office]": "Please choice one option",
            "system[confidentiality]": "Please choice one option",
            "system[integrity]": "Please choice one option",
            "system[availability]": "Please choice one option",
            "system[integrity]": "Please choice one option",
            "system[type]": "Please choice one option",
            "system[desc]": "Must be at most 200 characters",
            "system[criticality_justification]": "Must be at most 200 characters",
            "system[sensitivity_justification]": "Must be at most 200 characters"
        }
    });
});