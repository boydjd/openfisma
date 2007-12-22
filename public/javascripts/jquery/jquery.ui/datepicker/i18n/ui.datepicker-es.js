/* Inicialización en español para la extensión 'UI date picker' para jQuery. */
/* Traducido por Vester (xvester@gmail.com). */
$(document).ready(function(){
	$.datepicker.regional['es'] = {clearText: 'Limpiar', closeText: 'Cerrar',
		prevText: '&lt;Ant', nextText: 'Sig&gt;', currentText: 'Hoy',
		weekHeader: 'Sm', dayNames: ['Do','Lu','Ma','Mi','Ju','Vi','S&aacute;'],
		monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
		'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
		dateFormat: 'DMY/', firstDay: 0};
	$.datepicker.setDefaults($.datepicker.regional['es']);
});