
$(document).ready(function() {
	if($('input[type="radio"][name="print"][value="product"]').is(':checked')) {
		$('#itembin .item-number').show();

		console.log('in1:', $('input[type="radio"][name="print"][value="product"]').is(':checked'));
	};
});

if($('input[type="radio"][name="print"][value="product"]').is(':checked')) {
	$('#itembin .item-number').show();
	console.log('in2:', $('input[type="radio"][name="print"][value="product"]').is(':checked'));
};

console.log('out:', $('input[type="radio"][name="print"][value="product"]').is(':checked'));