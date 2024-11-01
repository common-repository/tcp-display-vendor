(function($) {

	// quick edit
	$('#the-list').on('click', '.editinline', function() {
		var post_id = $(this).closest('tr').attr('id');
		post_id = post_id.replace('post-', '');
		var vid = $('#inline_' + post_id).find('.product_vendor').text();
		var set_selected = function() {
			var option = $('select[name="tcpdv_vid"] option' + (vid ? '[value="' + vid + '"]' : ''), '#edit-' + post_id);
			if (option.length) {
				if (vid) {
					option.attr('selected', 'selected');
				}
				$('select[name="tcpdv_vid"]', '#edit-' + post_id).select2();
			} else {
				setTimeout(set_selected, 200); // field not exist inside DOM yet, retry
			}
		};
		set_selected();
	});

	// bulk edit
	$('#bulk-edit select[name="tcpdv_vid"]').select2();

	// meta box
	$('#tcpdv-assign select[name="tcpdv_vid"]').select2();

})(jQuery);