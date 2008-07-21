String.prototype.trim = function() {
        return this.replace(/^\s+|\s+$/g,"");
}

$(document).ready(function(){

   $("a[@name=select_all]").click(function(){
       $(":checkbox").attr( 'checked','checked' );
   });
   $("a[@name=select_none]").click(function(){
       $(":checkbox").attr( 'checked','' );
   });

   $('input.date').datepicker({dateFormat:'yymmdd',
                showOn: 'both', 
                buttonImageOnly: true,
                buttonImage: '/images/calendar.gif'
                });
        

    $("select[name='system']").change(function(){
        searchAsset();
    });

    asset_detail();
    
    $("input#search_asset").click(function(){
        searchAsset();
    }).trigger('click');

    $("input#search_product").click(function(){
        searchProduct();
    });

    getProdId();

    $("input#all_finding").click(function(){
        $('input[@type=checkbox]').attr('checked','checked');
    });

    $("input#none_finding").click(function(){
        $('input[@type=checkbox]').removeAttr('checked');
    });

    $(".editable").click(function(){
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
            var cur_span = target;
            if(type == 'text'){
                cur_span.replaceWith( '<input name='+name+' class="'+eclass+'" type="text" value="'+cur_val.trim()+'" />');
                $('input.date').datepicker({
                        dateFormat:'yymmdd',
                        showOn: 'both', 
                        buttonImageOnly: true,
                        buttonImage: '/images/calendar.gif',
                        buttonText: 'Calendar'});
            }else if( type == 'textarea' ){
                var row = target.attr('rows');
                var col = target.attr('cols');
                cur_span.replaceWith( '<textarea rows="'+row+'" cols="'+col+'" name="'+name+'">'+
                        cur_val.trim()+ '</textarea>');
            }else{
                $.get(url,{value:cur_val.trim()},
                function(data){
                    if(type == 'select'){
                        cur_span.replaceWith('<select name="'+name+'">'+data+'</select>');
                    }
                });
            }
        }
    });
    date = new Date();
    $("span[name=year]").html( date.getFullYear() );
    shortcut(0);

});

function shortcut(step){
    if( !isFinite(step) ){
        step = 0;
    }
    var year = $("span[name=year]").html();
    year = Number(year) + Number(step);
    var url = '/zfentry.php/panel/report/sub/fisma/s/search/y/'+year+'/';
    $("span[name=year]").html( year );
    $("span[name=year]").parent().attr( 'href', url);
    $("span[name=q1]").parent().attr( 'href', url+'q/1/' );
    $("span[name=q2]").parent().attr( 'href', url+'q/2/' );
    $("span[name=q3]").parent().attr( 'href', url+'q/3/' );
    $("span[name=q4]").parent().attr( 'href', url+'q/4/' );
}

function searchAsset( ){
    var trigger = $("select[name='system']");
    var sys = trigger.children("option:selected").attr('value');
    var param =  '';
    if( null != sys){
        param +=  '/sid/' + sys;
    }
    $("input.assets").each(function(){
        if( $(this).attr('value') ){
            param += '/' + $(this).attr('name') + '/' + $(this).attr('value');
        }
    });
    var url = trigger.attr("url") + param ;
    $("select[name='asset_list']").parent().load(url,null,function(){
        asset_detail();
    });
}

function asset_detail() {
    $("select[name='asset_list']").change(function(){
        var url = '/zfentry.php/asset/detail/id/'+ $(this).children("option:selected").attr('value');
        $("div#asset_info").load(url,null);
    });
}


function upload_evidence(){
    //$("#up_evidence").blur();
    var dw = $(document).width();
    var dh = $(document).height();
    $('<div id="full"></div>')
                .width(dw).height(dh)
                .css({backgroundColor:"#000000", marginTop:-1*dh, opacity:0, zIndex:10})
                .appendTo("body").fadeTo(1, 0.4);
    var content = $("#editorDIV").html();
    $('<div title="Upload Evidence"></div>').append(content).
        dialog({position:'top', width: 540, height: 200, resizable: true,modal:true,
            close:function(){
                $('#full').remove();
            }
        });
    return false;
}

function comment(formname){
    var dw = $(document).width();
    var dh = $(document).height();
    $('<div id="full"></div>')
                .width(dw).height(dh)
                .css({backgroundColor:"#000000", marginTop:-1*dh, opacity:0, zIndex:10})
                .appendTo("body").fadeTo(1, 0.4);
    var content = $("#comment_dialog").html();
    $('<div title="Upload Evidence"></div>').append(content).
        dialog({position:'top', width: 540, height: 240, resizable: true,modal:true,
            close:function(){
                $('#full').remove();
            },
            buttons:{
                'Cancel':function(){
                    $(this).dialog("close");
                },
                'Continue':function(){
                    var form1 = formname;
                    var topic = $("input[name=topic]",this).val();
                    var reason = $("textarea[name=reason]",this).val();
                    form1.elements['topic'].value = topic;
                    form1.elements['reject'].value = reason;
                    form1.elements['decision'].value = 'DENY';
                    form1.submit();
                }
            }
        });
}

function getProdId(){
    var trigger= $("select[name='prod_list']");
    trigger.change(function(){
        var prod_id= trigger.children("option:selected").attr('value');
        $("input[name='prod_id']").val(prod_id);
    });
}

function searchProduct(){
    var trigger = $("input#search_product");
    var url = trigger.attr('url') + '/view/list';
    $("input.product").each(function(){
        if($(this).attr('value')){
            url += '/' + $(this).attr('name') + '/' + $(this).attr('value');
        }
    });
    $("select[name='prod_list']").parent().load(url,null,function(){
        getProdId();
    });
}

function message( msg ,model){
    $("#msgbar").html(msg).css('font-weight','bold');
    if( model == 'warning')  {
        $("#msgbar").css('color','red');
    } else {
        $("#msgbar").css('color','green');
        $("#msgbar").css('border-color','green');
        $("#msgbar").css('background-color','lightgreen');
    }
    $("#msgbar").css('display','block');
}
