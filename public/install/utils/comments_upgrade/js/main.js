$.fn.check = function(mode) {
	var mode = mode || 'on'; // if mode is undefined, use 'on' as default
	return this.each(function() {
		switch(mode) {
		case 'on':
			this.checked = true;
			break;
		case 'off':
			this.checked = false;
			break;
		case 'toggle':
			this.checked = !this.checked;
			break;
		}
	});
};
$(document).ready(function() {
    // about the checkboxes ...
    $('span.playall').html('<a href="#">Play All</a>').click(function(){
        $('#playall').submit();
    }).hide();
    $(':checkbox').check('off');
    $(':checkbox:not(.all)').change(function(){
        ($(':checkbox:not(.all)[@checked]').length > 0)?
            $('span.playall').show()
            :$('span.playall').hide();
    });
    $(':checkbox.all').change(function(){
        if(this.checked){
            $(':checkbox').check('on');
            $('span.playall').show();
        }
        else{
            $('span.playall').hide();
            $(':checkbox').check('off');
        }
        this.blur();
    });
    // set link blur action
    $('a').click(function(){this.blur()});
    // on click song,singer,album tds ..
    $('td[@abbr="song"],td[@abbr="singer"],td[@abbr="album"]').click(function(){
        window.location = 'index.php?k='+$(this).attr('abbr')+'&v='+$(this).html();
    }).css('cursor','pointer');
    // help button
    $('div#help').hide();
    $('span.help').click(function(){
        $('div#help').slideToggle('slow');
    });
});