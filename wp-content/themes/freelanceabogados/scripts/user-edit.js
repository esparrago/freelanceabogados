jQuery( function( $ ) {

	$.fn.exists = function () {
		return this.length !== 0;
	}

	if ( $('#profile-form').exists() ) {

		$('#profile-form').validate({
			errorElement: "small",
			rules: {
				hrb_email: {
					required: true,
					email: true,
				}
			}
		});

	}

    if ( $('#location').exists() ) {

		var options = {
			details: "form",
			detailsAttribute: "data-geo",
		};

		// merge geocomplete base options with dynamic options in settings page
		if ( typeof app_user_edit_i18n.geocomplete_options !== "undefined" ) {
			options = $.extend( options, app_user_edit_i18n.geocomplete_options );
		}

		$('#location').geocomplete( options );

		$("#location").geocomplete("autocomplete");

   }

	/* Multi-Select */

	// parent/child check-uncheck all
	$('li.parent-list .parent').on( 'click, change', function () {
		$(this).closest('li').find(':checkbox').prop( 'checked', this.checked );

		// refresh the document
		if ( $.prototype.foundation ) {
			$(document).foundation();
		}
	});

	// parent/child check/uncheck parent
	$('li.parent-list .child').on( 'click, change', function () {
		var state = this.checked;
		var parent = $(this).attr('parent');

		// only check parent if all descendants are also checked
		if ( $('input[parent='+parent+']').not(':checked').length > 0 && state ) {
			state = false;
		}

		$('input.parent[value='+parent+']').prop( 'checked', state );

		// refresh the document
		if ( $.prototype.foundation ) {
			$(document).foundation();
		}
	});


});
