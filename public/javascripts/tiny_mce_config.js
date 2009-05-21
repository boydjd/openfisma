// configuration file for tiny_mce, configuration options may be found at the following website
// http://wiki.moxiecode.com/index.php/TinyMCE:Configuration

tinyMCE.init({
	theme : "advanced",
	mode : "textareas",
	plugins : "spellchecker, searchreplace, insertdatetime, print, fullscreen",
	plugin_insertdate_dateFormat : "%Y-%m-%d",
	plugin_insertdate_timeFormat : "%H:%M:%S",
	browsers : "msie,gecko,safari,opera",
	theme_advanced_buttons1 : "bold, italic, underline, |, bullist, numlist, |, outdent, indent, |, cut, copy, paste, |, undo, redo, |, spellchecker, |, search, replace, |, insertdate, inserttime, link, unlink, |, print, fullscreen",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	theme_advanced_statusbar_location : "bottom",
	theme_advanced_resizing : true,
	spellchecker_languages : "+English=en"
});