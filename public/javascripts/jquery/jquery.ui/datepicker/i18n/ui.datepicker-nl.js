/* Dutch (UTF-8) initialisation for the jQuery UI date picker plugin. */
$(document).ready(function(){
	$.datepicker.regional['nl'] = {clearText: 'Wissen', closeText: 'Sluiten',
		prevText: '&lt;Terug', nextText: 'Volgende&gt;', currentText: 'Vandaag',
		dayNames: ['Zo','Ma','Di','Wo','Do','Vr','Za'],
		monthNames: ['Januari','Februari','Maart','April','Mei','Juni',
		'Juli','Augustus','September','Oktober','November','December'],
		dateFormat: 'DMY.', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['nl']);
});