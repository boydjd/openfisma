/** @todo add general description of this file */

/* When a form containing editable fields is loaded (such as the tabs on the remediation detail page), this function
 * is used to add the required click handler to all of the editable fields.
 */ 
function setupEditFields() {
    var editable = YAHOO.util.Selector.query('.editable');
    YAHOO.util.Event.on(editable, 'click', function (o){
        removeHighlight(document);
        var t_name = this.getAttribute('target');
        YAHOO.util.Dom.removeClass(this, 'editable'); 
        this.removeAttribute('target');
        if(t_name) {
             var target = document.getElementById(t_name);
             var name = target.getAttribute('name');
             var type = target.getAttribute('type');
             var url = target.getAttribute('href');
             var eclass = target.className;
             var cur_val = target.innerText ? target.innerText : target.textContent;
             var cur_html = target.innerHTML;
             if (type == 'text') {
                 target.outerHTML = '<input name="'+name+'" id="'+t_name+'" class="'+eclass+'" type="text" value="'+cur_val.trim()+'" />';
                 if (eclass == 'date') {
                     var target = document.getElementById(t_name);
                     target.onfocus = function () {this.blur()};
                     var btn = document.createElement('BUTTON');
                     btn.id = t_name + '_show';
                     if (window.HTMLElement) btn.type = 'button';
                     btn.title = 'Show Calendar';
                     btn.innerHTML = '<img src="/images/calendar.gif" width="18" height="18" alt="Calendar" >';
                     target.parentNode.appendChild(btn);
                     YAHOO.util.Event.on(t_name+'_show', "click", function() {
                        showCalendar(t_name, t_name+'_show');
                     });
                 }
             } else if( type == 'textarea' ) {
                 var row = target.getAttribute('rows');
                 var col = target.getAttribute('cols');
                 target.outerHTML = '<textarea id="'+name+'" rows="'+row+'" cols="'+col+'" name="'+name+'">' + cur_html+ '</textarea>';
                 tinyMCE.execCommand("mceAddControl", true, name);
             } else {
                 YAHOO.util.Connect.asyncRequest('GET', url+'value/'+cur_val.trim(), {
                        success: function(o) {
                             if(type == 'select'){
                                 target.outerHTML = '<select name="'+name+'">'+o.responseText+'</select>';
                             }
                        },
                        failure: function(o) {alert('Failed to load the specified panel.');}
                    }, null);
             }
        }
    });
}

function validateEcd() {
    var obj = document.getElementById('action_est_date');
    var inputDate = obj.value;
    var oDate= new Date();
    var Year = oDate.getFullYear();
    var Month = oDate.getMonth();
    Month = Month + 1;
    if (Month < 10) {Month = '0'+Month;}
    var Day = oDate.getDate();
    if (Day < 10) {Day = '0' + Day;}
    if (inputDate <= parseInt(""+Year+""+Month+""+Day)) {
        //@todo english
        alert("The ECD date can'be in the past!");
    }
}

if (window.HTMLElement) {
    HTMLElement.prototype.__defineSetter__("outerHTML",function(sHTML){
        var r=this.ownerDocument.createRange();
        r.setStartBefore(this);
        var df=r.createContextualFragment(sHTML);
        this.parentNode.replaceChild(df,this);
        return sHTML;
        });

    HTMLElement.prototype.__defineGetter__("outerHTML",function(){
    var attr;
        var attrs=this.attributes;
        var str="<"+this.tagName.toLowerCase();
        for(var i=0;i<attrs.length;i++){
            attr=attrs[i];
            if(attr.specified)
                str+=" "+attr.name+'="'+attr.value+'"';
            }
        if(!this.canHaveChildren)
            return str+">";
        return str+">"+this.innerHTML+"</"+this.tagName.toLowerCase()+">";
        });

    HTMLElement.prototype.__defineGetter__("canHaveChildren",function(){
    switch(this.tagName.toLowerCase()){
            case "area":
            case "base":
            case "basefont":
            case "col":
            case "frame":
            case "hr":
            case "img":
            case "br":
            case "input":
            case "isindex":
            case "link":
            case "meta":
            case "param":
            return false;
        }
        return true;
     });
}
