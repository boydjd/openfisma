/* German initialisation for the jQuery UI date picker plugin. */
/* Written by Milian Wolff (mail@milianw.de). */
$(document).ready(function(){
	$.datepicker.regional['de'] = {clearText: 'Löschen', closeText: 'Schließen',
		prevText: '&lt;Zurück', nextText: 'Vor&gt;', currentText: 'Heute',
		weekHeader: 'Wo', dayNames: ['So','Mo','Di','Mi','Do','Fr','Sa'],
		monthNames: ['Januar','Februar','März','April','Mai','Juni',
		'Juli','August','September','Oktober','November','Dezember'],
		dateFormat: 'DMY.', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['de']);
});