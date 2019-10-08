$(function() {
	// Set up switch (on/off) elements
	var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
	elems.forEach(function(html) {
		var switchery = new Switchery(html, { color: '#35A035', secondaryColor: '#C74564', size: 'small' });
	});

 	$("#show_donation").on('change init_toggle', function () {
		$('.show_donation_toggle').toggle($('#show_donation').prop('id="show_donation" checked="checked"'));
	}).trigger('init_toggle');

});
