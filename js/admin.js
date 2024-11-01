/* global tcpdv_lang */
(function($) {

	$('.link_delete').click(function(e) {
		e.preventDefault();
		if (confirm(tcpdv_lang.confirm_delete)) {
			$('#delete_vendor_form').submit();
		}
	});

})(jQuery);