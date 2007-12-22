/* Brazilian initialisation for the jQuery UI date picker plugin. */
/* Written by Leonildo Costa Silva (leocsilva@gmail.com). */
$(document).ready(function(){
	$.datepicker.regional['pt-BR'] = {clearText: 'Limpar', closeText: 'Fechar', 
		prevText: '&lt;Anterior', nextText: 'Pr&oacute;ximo&gt;', currentText: 'Hoje',
		weekHeader: 'Sm', dayNames: ['Dom','Seg','Ter','Qua','Qui','Sex','Sab'],
		monthNames: ['Janeiro','Fevereiro','Mar&ccedil;o','Abril','Maio','Junho',
		'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
		dateFormat: 'DMY/', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['pt-BR']);
});