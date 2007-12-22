/* Romanian initialisation for the jQuery UI date picker plugin. */
/* Written by Edmond L. (ll_edmond@walla.com). */
$(document).ready(function(){
	$.datepicker.regional['ro'] = {clearText: 'sterge', closeText: 'inchide',
		prevText: '&laquo;&nbsp;inapoi', nextText: 'inainte&nbsp;&raquo;', currentText: 'Azi',
		weekHeader: 'Sm', dayNames: ['D', 'L', 'Ma', 'Mi', 'J', 'V', 'S'],
		monthNames: ['Januarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Junie',
		'Julie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'],
		dateFormat: 'YMD-', firstDay: 1};
	$.datepicker.setDefaults($.datepicker.regional['ro']);
});
