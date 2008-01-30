/* Swedish initialisation for the jQuery UI date picker plugin. */
/* Written by Anders Ekdahl ( anders@nomadiz.se). */
$(document).ready(function(){
    $.datepicker.regional['sv'] = {clearText: 'Rensa', closeText: 'Stäng',
        prevText: '&laquo;Förra', nextText: 'Nästa&raquo;', currentText: 'Idag', 
        weekHeader: 'Ve', dayNames: ['Sö','Må','Ti','On','To','Fr','Lö'],
        monthNames: ['Januari','Februari','Mars','April','Maj','Juni', 
        'Juli','Augusti','September','Oktober','November','December'],
        dateFormat: 'YMD-', firstDay: 0};
    $.datepicker.setDefaults($.datepicker.regional['sv']); 
});
