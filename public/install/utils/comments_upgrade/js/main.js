$(document).ready(function() {
    $('#update').click(function(){
        var data = new Object;
        $('select[@name="guess"]').each(function(){
            if ($(this).val()!=''){
                var href = (window.location.href).substr(-13);
                if ((href == 'index.php?s=2') || (href == 'ndex.php?s=2#')){
                    data[$(this).parent().next().html()] = $(this).val();
                }
                else{
                    data[$(this).parent().nextAll('td:last').html()] = $(this).val();
                }
            }
        });
        var url = "update.php";
        $.post(url, data, function(r,t,x){
            if(r == 'OK') {
                alert('Update success.');
                window.location.href="index.php?s=2";
            }
            else{
                alert('Update fail.');
                window.location.href="index.php?s=1";
            }
        });
    });
    
    $('a.change_all').click(function(){
        var action = $(this).attr('id');
         $('select[@name="guess"]').each(function(){
             $(this).val(action);
         });
    });
});