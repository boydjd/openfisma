/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see {@link http://www.gnu.org/licenses/}.
 *
 * @fileoverview When a form containing editable fields is loaded (such as the tabs on the
 *               remediation detail page), this function is used to add the required click
 *               handler to all of the editable fields.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license   http://www.openfisma.org/content/license
 */

function setupEditFields() {
    var editable = YAHOO.util.Selector.query('.editable');
    YAHOO.util.Event.on(editable, 'click', function (o){
        var t_name = this.getAttribute('target');
        YAHOO.util.Dom.removeClass(this, 'editable'); 
        this.removeAttribute('target');
        if(t_name) {
             var target = document.getElementById(t_name);
             var name = target.getAttribute('name');
             var type = target.getAttribute('type');
             var url = target.getAttribute('href');
             var eclass = target.className;
             var oldWidth = target.offsetWidth;
             var oldHeight = target.offsetHeight;
             var cur_val = target.innerText ? target.innerText : target.textContent;
             var cur_html = target.innerHTML;
             if (type == 'text') {
                 target.outerHTML = '<input length="50" name="'+name+'" id="'+t_name+'" class="'+eclass+'" type="text" />';
                 textEl = document.getElementById(t_name);
                 // set value attribute using JS call instead of string concatenation so we don't have to worry about escaping special characters
                 textEl.setAttribute('value', cur_val.trim());
                 if (oldWidth < 200) {
                     oldWidth = 200;
                 }
                 textEl.style.width = (oldWidth - 10) + "px";
                 if (eclass == 'date') {
                     var target = document.getElementById(t_name);
                     target.onfocus = function () {showCalendar(t_name, t_name+'_show');};
                 }
             } else if( type == 'textarea' ) {
                 var row = target.getAttribute('rows');
                 var col = target.getAttribute('cols');
                 target.outerHTML = '<textarea id="'+name+'" rows="'+row+'" cols="'+col+'" name="'+name+'"></textarea>';
                 var textareaEl = document.getElementById(name);
                 textareaEl.value = cur_html;
                 textareaEl.style.width = oldWidth + "px";
                 textareaEl.style.height = oldHeight + "px";
                 tinyMCE.execCommand("mceAddControl", true, name);
             } else {
                 if (val = target.getAttribute('value')) {
                     cur_val = val;
                 }
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
    var obj = document.getElementById('expectedCompletionDate');
    var inputDate = obj.value;
    var oDate= new Date();
    var Year = oDate.getFullYear();
    var Month = oDate.getMonth();
    Month = Month + 1;
    if (Month < 10) {Month = '0'+Month;}
    var Day = oDate.getDate();
    if (Day < 10) {Day = '0' + Day;}
    if (inputDate.replace(/\-/g, "") <= parseInt(""+Year+""+Month+""+Day)) {
        alert("Warning: You entered an ECD date in the past.");
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
