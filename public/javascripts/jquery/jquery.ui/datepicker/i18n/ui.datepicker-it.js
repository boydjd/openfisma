/* Italian initialisation for the jQuery UI date picker plugin. */
/* Written by Apaella (apaella@gmail.com). */
$(document).ready(function(){
	$.datepicker.regional['it'] = {clearText: 'Svuota', closeText: 'Chiudi',
		prevText: '&lt;Prec', nextText: 'Succ&gt;', currentText: 'Oggi',
		weekHeader: 'Sm', dayNames: ['Do','Lu','Ma','Me','Gio','Ve','Sa'],
		monthNames: ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',
		'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'],
		dateFormat: 'DMY/', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['it']);
});
