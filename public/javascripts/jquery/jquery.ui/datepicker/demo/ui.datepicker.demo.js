// Initialise the date picker demonstrations
$(document).ready(function () {
	// initialize tab interface
	tabs.init();
	// replace script tags with HTML code
	$(".demojs").each(function () {
		$(this).before( '<pre style="padding-top:0 !important"><code class="javascript">' + $(this).html() + "</code></pre>" );
		eval( $(this).html() );
	});
	// Localization
	if ($.browser.safari) {
		$('#language,#l10nDatepicker').attr({ disabled: 'disabled' });
	} else {
		$('#language').change(localise);
		$('#l10nDatepicker').datepicker();
		localise();
	}
	// Stylesheets
	$('#altStyle').datepicker({buttonImage: 'img/calendar2.gif'});
	$('#button3').click(function() { 
		$.datepicker.dialogDatepicker($('#altDialog').val(),
		setAltDateFromDialog, {prompt: 'Choose a date', speed: ''});
	});
});

// Load and apply a localisation package for the date picker
function localise() {
	var language = $('#language').val();
	$.localise('i18n/ui.datepicker', {language: language});
	$.datepicker.reconfigureFor('#l10nDatepicker', $.datepicker.regional[language]).
		setDefaults($.datepicker.regional['']); // Reset for general usage
}

// Create a Date from a string value
function getDate(value) {
	fields = value.split('/');
	return (fields.length < 3 ? null :
		new Date(parseInt(fields[2], 10), parseInt(fields[0], 10) - 1, parseInt(fields[1], 10)));
}

// Demonstrate a callback from inline configuration
var days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
function showDay(input) {
	var date = getDate(input.value);
	$('#inlineDay').empty().html(date ? days[date.getDay()] : 'blank');
}

// Format a date for display
function formatDate(date) {
	var day = date.getDate();
	var month = date.getMonth() + 1;
	return (day < 10 ? '0' : '') + day + '/' +
		(month < 10 ? '0' : '') + month + '/' + date.getFullYear();
}

// Display a date selected in a "dialog"
function setAltDateFromDialog(date) {
	$('#altDialog').val(date);
}

// Custom Tabs written by Marc Grabanski
var tabs = 
{
	init : function () 
	{
		// Setup tabs
		$("div[@class^=tab_group]").hide();
		$("div[@class^=tab_group]:first").show().id;
		$("ul[@id^=tab_menu] a:eq(0)").addClass('over');

		// Slide visible up and clicked one down
		$("ul[@id^=tab_menu] a").each(function(i){
			$(this).click(function () {
				$("ul[@id^=tab_menu] a.over").removeClass('over');
				$(this).addClass('over');
				$("div[@class^=tab_group]:visible").hide();
				$( $(this).attr("href") ).fadeIn();
				tabs.stylesheet = $(this).attr("href") == "#styles" ? 'alt' : 'default';
				$('link').each(function() {
					this.disabled = (this.title != '' && this.title != tabs.stylesheet);
				});
				return false;
			});
		});
	}
}
