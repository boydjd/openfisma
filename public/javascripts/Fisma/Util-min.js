Fisma.Util={escapeRegexValue:function(b){var a=new RegExp("[.*+?|()\\[\\]{}\\\\]","g");return b.replace(a,"\\$&")},getObjectFromName:function(c){var b=c.split(".");var a=window;for(piece in b){a=a[b[piece]];if(a==undefined){throw"Specified object does not exist: "+c}}return a},positionPanelRelativeToElement:function(b,c){var a=5;b.cfg.setProperty("context",[c,YAHOO.widget.Overlay.TOP_LEFT,YAHOO.widget.Overlay.BOTTOM_LEFT,null,[0,a]])},getTimestamp:function(){var b=new Date();var a=b.getHours()+"";if(a.length==1){a="0"+a}var c=b.getMinutes()+"";if(c.length==1){c="0"+c}var d=b.getSeconds()+"";if(d.length==1){d="0"+d}return a+":"+c+":"+d},showConfirmDialog:function(c,a){var d=Fisma.Util.getDialog();var b=[{text:"Yes",handler:function(){if(a.url){document.location=a.url}else{if(a.func){var e=Fisma.Util.getObjectFromName(a.func);if(YAHOO.lang.isFunction(e)){if(a.args){e.apply(this,a.args)}else{e.call()}}}}this.destroy()}},{text:"No",handler:function(){this.destroy()}}];d.setHeader("Are you sure?");d.setBody(a.text);d.cfg.queueProperty("buttons",b);if(a.width){d.cfg.setProperty("width",a.width)}d.render(document.body);d.show();if(a.isLink){YAHOO.util.Event.preventDefault(c)}},showAlertDialog:function(e,b){var a=Fisma.Util.getDialog();var d=function(){this.destroy()};var c=[{text:"Ok",handler:d}];a.setHeader("WARNING");a.setBody(e);a.cfg.queueProperty("buttons",c);if(!YAHOO.lang.isUndefined(b)&&b.width){a.cfg.setProperty("width",b.width)}if(!YAHOO.lang.isUndefined(b)&&b.zIndex){a.cfg.setProperty("zIndex",b.zIndex)}a.render(document.body);a.show()},getDialog:function(){var a=new YAHOO.widget.SimpleDialog("warningDialog",{width:"400px",fixedcenter:true,visible:false,close:true,modal:true,icon:YAHOO.widget.SimpleDialog.ICON_WARN,constraintoviewport:true,draggable:false});return a},message:function(e,c,a){a=a||false;e=$P.stripslashes(e);var b=Fisma.Registry.get("messageBoxStack");var d=b.peek();if(d){if(a){d.setMessage(e)}else{d.addMessage(e)}if(c=="warning"){d.setErrorLevel(Fisma.MessageBox.ERROR_LEVEL.WARN)}else{d.setErrorLevel(Fisma.MessageBox.ERROR_LEVEL.INFO)}d.show()}},updateTimeField:function(b){var c=document.getElementById(b);var h=document.getElementById(b+"Hour");var i=document.getElementById(b+"Minute");var a=document.getElementById(b+"Ampm");var f=h.value;var e=i.value;var g=a.value;if("PM"==g){f=parseInt(f)+12}f=$P.str_pad(f,2,"0","STR_PAD_LEFT");e=$P.str_pad(e,2,"0","STR_PAD_LEFT");var d=f+":"+e+":00";c.value=d}};