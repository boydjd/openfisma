Fisma.AttachArtifacts={sampleInterval:1000,apcId:null,yuiProgressBar:null,pollingTimeoutId:null,lastAsyncRequest:null,pollingEnabled:false,config:null,yuiPanel:null,showPanel:function(d,b){Fisma.AttachArtifacts.config=b;var a=new YAHOO.widget.Panel("panel",{modal:true,close:false});a.setHeader("Upload Artifact");a.setBody("Loading...");a.render(document.body);a.center();a.show();Fisma.AttachArtifacts.yuiPanel=a;var c="/artifact/upload-form";if(b.form){c+="/form/"+encodeURIComponent(b.form)}YAHOO.util.Connect.asyncRequest("GET",c,{success:function(e){e.argument.setBody(e.responseText);e.argument.center()},failure:function(e){e.argument.setBody("The content for this panel could not be loaded.");e.argument.center()},argument:a},null)},trackUploadProgress:function(){var b=document.getElementById("uploadButton");b.disabled=true;var c=this;var e=document.getElementById("progress_key");if(e){this.apcId=e.value;var h=document.getElementById("progressBarContainer");var a=parseInt(YAHOO.util.Dom.getStyle(h,"width"));var g=parseInt(YAHOO.util.Dom.getStyle(h,"height"));YAHOO.util.Dom.removeClass(h,"attachArtifactsProgressBar");while(h.hasChildNodes()){h.removeChild(h.firstChild)}var f=new YAHOO.widget.ProgressBar();f.set("width",a);f.set("height",g);f.set("ariaTextTemplate","Upload is {value}% complete");f.set("anim",true);var d=f.get("anim");d.duration=2;d.method=YAHOO.util.Easing.easeNone;f.render("progressBarContainer");YAHOO.util.Dom.addClass(h,"attachArtifactsProgressBar");this.yuiProgressBar=f;this.pollingEnabled=true;setTimeout(function(){c.getProgress.call(c)},this.sampleInterval)}document.getElementById("progressBarContainer").style.display="block";document.getElementById("progressTextContainer").style.display="block";setTimeout(function(){c.postForm.call(c)},0);return false},postForm:function(){var a=this;var b="/"+encodeURIComponent(this.config.server.controller)+"/"+encodeURIComponent(this.config.server.action)+"/id/"+encodeURIComponent(this.config.id)+"/format/json";YAHOO.util.Connect.setForm("uploadArtifactForm",true);YAHOO.util.Connect.asyncRequest("POST",b,{upload:function(c){a.handleUploadComplete.call(a,c)},failure:function(c){alert("Document upload failed.")}},null)},getProgress:function(){var a=this;if(this.pollingEnabled){this.lastAsyncRequest=YAHOO.util.Connect.asyncRequest("GET","/artifact/upload-progress/format/json/id/"+this.apcId,{success:function(c){var b=YAHOO.lang.JSON.parse(c.responseText);var d=Math.round((b.progress.current/b.progress.total)*100);a.yuiProgressBar.set("value",d);var e=document.getElementById("progressTextContainer").firstChild;e.nodeValue=d+"%";a.pollingTimeoutId=setTimeout(function(){a.getProgress.call(a)},a.sampleInterval)}},null)}},handleUploadComplete:function(c){var b=YAHOO.lang.JSON.parse(c.responseText);if(!b.success){alert(b.message)}this.pollingEnabled=false;clearTimeout(this.pollingTimeoutId);YAHOO.util.Connect.abort(this.lastAsyncRequest);if(this.yuiProgressBar){this.yuiProgressBar.get("anim").duration=0.5;this.yuiProgressBar.set("value",100)}var e=document.getElementById("progressTextContainer").firstChild;e.nodeValue="Verifying file.";var d=Fisma[this.config.callback.object];if(typeof d!="Undefined"){var a=d[this.config.callback.method];if(typeof a=="function"){a.call(d,this.yuiPanel)}}}};