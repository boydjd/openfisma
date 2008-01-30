/* Japanese (UTF-8) initialisation for the jQuery UI date picker plugin. */
/* Written by Milly. */
$(document).ready(function(){
	$.datepicker.regional['ja'] = {
		clearText: '&#21066;&#38500;',
		closeText: '&#38281;&#12376;&#12427;',
		prevText: '&lt;&#21069;&#26376;',
		nextText: '&#27425;&#26376;&gt;',
		currentText: '&#20170;&#26085;',
		weekHeader: 'Wk', 
		dayNames: ['&#26085;','&#26376;','&#28779;','&#27700;','&#26408;','&#37329;','&#22303;'],
		monthNames: ['1&#26376;','2&#26376;','3&#26376;','4&#26376;','5&#26376;','6&#26376;',
		'7&#26376;','8&#26376;','9&#26376;','10&#26376;','11&#26376;','12&#26376;'],
		dateFormat: 'YMD/',
		firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['ja']);
});