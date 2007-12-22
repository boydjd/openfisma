/* Russian (UTF-8) initialisation for the jQuery UI date picker plugin. */
/* Written by Andrew Stromnov (stromnov@gmail.com). */
$(document).ready(function(){
	$.datepicker.regional['ru'] = {clearText: 'Очистить', closeText: 'Закрыть',
		prevText: '&lt;Пред', nextText: 'След&gt;', currentText: 'Сегодня',
		weekHeader: 'Не', dayNames: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
		monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь',
		'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
		dateFormat: 'DMY.', firstDay: 1};
	$.datepicker.setDefaults($.datepicker.regional['ru']);
});