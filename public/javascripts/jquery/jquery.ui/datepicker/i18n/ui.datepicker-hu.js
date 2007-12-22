/* Hungarian initialisation for the jQuery UI date picker plugin. */
/* Written by Istvan Karaszi (jquerycalendar@spam.raszi.hu). */
$(document).ready(function(){
	$.datepicker.regional['hu'] = {clearText: 'törlés', closeText: 'bezárás',
		prevText: '&laquo;&nbsp;vissza', nextText: 'előre&nbsp;&raquo;', currentText: 'ma',
		weekHeader: 'Hé', dayNames: ['V', 'H', 'K', 'Sze', 'Cs', 'P', 'Szo'],
		monthNames: ['Január', 'Február', 'Március', 'Április', 'Május', 'Június',
		'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'],
		dateFormat: 'YMD-', firstDay: 1};
	$.datepicker.setDefaults($.datepicker.regional['hu']);
});
