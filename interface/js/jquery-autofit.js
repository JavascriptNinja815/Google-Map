$(document).off('keydown click focus', 'textarea.autofit');
$(document).on('keydown click focus', 'textarea.autofit', function(e) {
	if(e.which == 13) {e.preventDefault();}; // Don't support line breaks.
	$(this).height(1);
	var totalHeight = $(this).prop('scrollHeight') - parseInt($(this).css('padding-top')) - parseInt($(this).css('padding-bottom'));
	$(this).height(totalHeight);
});
