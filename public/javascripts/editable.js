/** @todo add general description of this file */

/* When a form containing editable fields is loaded (such as the tabs on the remediation detail page), this function
 * is used to add the required click handler to all of the editable fields.
 */ 
function setupEditFields() {
     $(".editable").click(function(){
         removeHighlight(document);
         var t_name = $(this).attr('target');
         $(this).removeClass('editable');
         $(this).removeAttr('target');
         if( t_name ) {
         
             var target = $('#'+t_name);
             var name = target.attr('name');
             var type = target.attr('type');
             var url = target.attr('href');
             var eclass = target.attr('class');
             var cur_val = target.text();
             var cur_html = target.html();
             var cur_span = target;
             if (type == 'text') {
                 cur_span.replaceWith( '<input name='+name+' class="'+eclass+'" type="text" value="'+cur_val.trim()+'" />');
                 $('input.date').datepicker({
                         dateFormat:'yymmdd',
                         showOn: 'both',
                         onClose: showJustification,
                         buttonImageOnly: true,
                         buttonImage: '/images/calendar.gif',
                         buttonText: 'Calendar'});
             } else if( type == 'textarea' ) {
                 var row = target.attr('rows');
                 var col = target.attr('cols');
                 cur_span.replaceWith( '<textarea id="'+name+'" rows="'+row+'" cols="'+col+'" name="'+name+'">'+
                         cur_html+ '</textarea>');
                 tinyMCE.execCommand("mceAddControl", true, name);
             } else {
                 $.get(url,{value:cur_val.trim()},
                 function(data){
                     if(type == 'select'){
                         cur_span.replaceWith('<select name="'+name+'">'+data+'</select>');
                     }
                 });
             }
         }
     });
}
