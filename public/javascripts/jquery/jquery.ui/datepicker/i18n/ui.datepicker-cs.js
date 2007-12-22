/* Czech initialisation for the jQuery UI date picker plugin. */
/* Written by Tomas Muller (tomas@tomas-muller.net). */
$(document).ready(function(){
	$.datepicker.regional['cs'] = {clearText: 'Smazat', closeText: 'Zavøít', 
		prevText: '&lt;Døíve', nextText: 'Pozdìji&gt;', currentText: 'Nyní',
		weekHeader: 'Tý', dayNames: ['Ne','Po','Út','St','Èt','Pá','So'],
		monthNames: ['Leden','Únor','Bøezen','Duben','Kvìten','Èerven',
		'Èervenec','Srpen','Záøí','Øíjen','Listopad','Prosinec'],
		dateFormat: 'DMY.', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['cs']);
});