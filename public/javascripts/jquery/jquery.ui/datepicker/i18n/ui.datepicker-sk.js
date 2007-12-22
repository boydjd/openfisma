/* Slovak initialisation for the jQuery UI date picker plugin. */
/* Written by Vojtech Rinik (vojto@hmm.sk). */
$(document).ready(function(){
	$.datepicker.regional['sk'] = {clearText: 'Zmazať', closeText: 'Zavrieť', 
		prevText: '&lt;Predchádzajúci', nextText: 'Nasledujúci&gt;', currentText: 'Dnes',
		weekHeader: 'Ty', dayNames: ['Ne','Po','Ut','St','Št','Pia','So'],
		monthNames: ['Január','Február','Marec','Apríl','Máj','Jún',
		'Júl','August','September','Október','November','December'],
		dateFormat: 'DMY.', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['sk']);
});
