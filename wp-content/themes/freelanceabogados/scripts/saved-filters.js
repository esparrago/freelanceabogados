jQuery(document).ready(function($) {

	$('#saved-filter-slug').change( function() {
		$('#load-saved-filter-form').submit();
	});

	$('#delete-saved-filter').click( function( event ) {
		event.preventDefault();

		if ( confirm( 'Are you sure?') ) {
			$('#load-saved-filter-form input[name=action]').val('delete-saved-filter');
		}
		$('#load-saved-filter-form').submit();
	});

	$('#edit-saved-filter').click( function( event ) {

		event.preventDefault();

		var data = {
			action: 'hrb_render_saved_filter',
			user_id: hrb_i18n.user_id,
			search_slug: $('#saved-filter-slug').val(),
            _ajax_nonce: hrb_i18n.ajax_nonce,
		};

        $.ajax( {
			url: hrb_i18n.ajaxurl,
			data: data,
			type: 'POST',
			}).done( function( response ) {
				// replace form data with the dynamic content
				$('#save-filter-modal form').replaceWith( $( response ).html() );
				$('#save-filter-modal').foundation( 'reveal', 'open' );
				// make sure form elements like <select> are refreshed
				$('#save-filter-modal').foundation('forms');
        });

	});

	// save search form

	$('#save-filter-form').validate({
		errorElement: "small"
	});

	$( document.body ).on( 'click', '#save-filter', function() {
		$('#save-filter-form').submit();
	});

	$( document.body ).on( 'click', '#cancel-save-filter', function() {
		$('a.close-reveal-modal').click();
	});

});