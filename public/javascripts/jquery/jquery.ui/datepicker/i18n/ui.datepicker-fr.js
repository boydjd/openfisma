/* French initialisation for the jQuery UI date picker plugin. */
/* Written by Keith Wood (kbwood@iprimus.com.au). */
$(document).ready(function(){
	$.datepicker.regional['fr'] = {clearText: 'Effacer', closeText: 'Fermer', 
		prevText: '&lt;Préc', nextText: 'Proch&gt;', currentText: 'En cours',
		weekHeader: 'Sm', dayNames: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
		monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin',
		'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
		dateFormat: 'DMY/', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['fr']);
});