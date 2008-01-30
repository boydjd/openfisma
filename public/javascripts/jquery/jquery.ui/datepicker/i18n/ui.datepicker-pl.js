/* Polish initialisation for the jQuery UI date picker plugin. */
/* Written by Jacek Wysocki (jacek.wysocki@gmail.com). */
$(document).ready(function(){
	$.datepicker.regional['pl'] = {clearText: 'Czyść', closeText: 'Zamknij',
		prevText: '&lt;Poprzedni', nextText: 'Następny&gt;', currentText: 'Teraz',
		weekHeader: 'Ty', dayNames: ['Pn','Wt','Śr','Czw','Pt','So','Nie'],
		monthNames: ['Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
		'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'],
		dateFormat: 'DMY/', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['pl']);
});
