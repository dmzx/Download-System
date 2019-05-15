$(function() {
	// Set up switch (on/off) elements
	var elems = Array.prototype.slice.call(document.querySelectorAll('.js-switch'));
	elems.forEach(function(html) {
		var switchery = new Switchery(html, { color: '#35A035', secondaryColor: '#C74564', size: 'small' });
	});
});

