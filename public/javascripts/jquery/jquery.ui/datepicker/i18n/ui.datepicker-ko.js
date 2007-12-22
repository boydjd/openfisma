/* Korean initialisation for the jQuery calendar extension. */
/* Written by DaeKwon Kang (ncrash.dk@gmail.com). */
$(document).ready(function(){
	$.datepicker.regional['ko'] = {clearText: '지우기', closeText: '닫기',
		prevText: '이전달', nextText: '다음달', currentText: '오늘',
		weekHeader: 'Wk', dayNames: ['일','월','화','수','목','금','토'],
		monthNames: ['1월(JAN)','2월(FEB)','3월(MAR)','4월(APR)','5월(MAY)','6월(JUN)',
			'7월(JUL)','8월(AUG)','9월(SEP)','10월(OCT)','11월(NOV)','12월(DEC)'],
		dateFormat: 'YMD-', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['ko']);
});