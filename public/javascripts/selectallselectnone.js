// function used in any form or page that requires a select all and select none buttons
$(function(){
    $(":button[name=select_all]").click(function(){
        $(":checkbox").attr( 'checked','checked' );
    });
    $(":button[name=select_none]").click(function(){
        $(":checkbox").attr( 'checked','' );
    });
})
