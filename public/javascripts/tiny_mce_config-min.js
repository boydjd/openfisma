tinyMCE.init({theme:"advanced",mode:"textareas",cleanup:false,element_format:"html",plugins:"paste, spellchecker, searchreplace, insertdatetime, print, fullscreen, table",plugin_insertdate_dateFormat:"%Y-%m-%d",plugin_insertdate_timeFormat:"%H:%M:%S",browsers:"msie,gecko,safari,opera",theme_advanced_buttons1:"bold, italic, underline, |, 	                           bullist, numlist, |, 	                           outdent, indent, |, 	                           spellchecker, search, replace, |, 	                           link, unlink, print, fullscreen",theme_advanced_buttons2:"tablecontrols",theme_advanced_buttons3:"",theme_advanced_toolbar_location:"top",theme_advanced_toolbar_align:"left",theme_advanced_statusbar_location:"bottom",theme_advanced_resizing:true,spellchecker_rpc_url:"/javascripts/tiny_mce/plugins/spellchecker/rpc.php",spellchecker_languages:"+English=en",table_styles:"Default=tinymce_table",setup:function(a){a.onClick.add(Fisma.SessionManager.onActivityEvent);a.onKeyPress.add(Fisma.SessionManager.onActivityEvent)}});