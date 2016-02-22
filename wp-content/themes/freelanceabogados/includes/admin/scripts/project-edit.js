jQuery(document).ready(function($) {

	// Validate

	var validator = $('form[name=post]').validate({
		errorElement: "div",
		errorPlacement: function(error, element) {
			error.insertAfter( element.closest('label') );
		},
		rules: {
			_hrb_budget_price: {
				required: true,
				is_valid: true,
			}
		}
	});

	// custom validator to check for invalid fields
	$.validator.addMethod( 'is_valid', function( value, element ) {

		if ( value > 0 ) {
			return true;
		} else {
			return false;
		}

	}, HRB_admin_l18n.error_msg_empty );


	// Budget related

	$('input[name=_hrb_budget_type]').change( function() {

		var option = $(this).val();

		$('[class*=budget-]').closest('tr').hide();
		$('[class*=budget-'+option+']').closest('tr').fadeIn();
		$('[class*=budget-'+option+']').fadeIn().trigger('change');

	});

	$('select[name=_hrb_budget_currency]').change( function(){
		var full_currency = $('option:selected', this).text();
		var pattern = /\((.+?)\)/g;
		var currency = [];
		var match;

		while ( match = pattern.exec(full_currency) ) {
			currency.push( match[1] );
        }

		$('.currency').html( currency[0] );
	});

	$('.budget-fixed').change( function(){

			if ( 'custom' === $('option:selected', this).val() ) {
				$('.budget-custom-fixed').show();
            } else {
				$('.budget-custom-fixed').hide();
            }

	});

	$('input[name=_hrb_budget_type]:checked, #project-budget select').change();


	// Location

	$( '#project-address-pref' ).change( function() {

		if ( 'local' != $(this).val() ) {
			$('#project-address').parents( 'tr:first' ).hide();
			$('.custom-location input').val('');
		} else {
			$('#project-address').parents( 'tr:first' ).fadeIn();

			var options = {
                details: "form",
                detailsAttribute: "data-geo",
			};

			// merge geocomplete base options with dynamic options in settings page
			if ( typeof HRB_admin_l18n.geocomplete_options !== "undefined" ) {
				options = $.extend( options, HRB_admin_l18n.geocomplete_options );

				$('#project-address').geocomplete( options );

				$("#project-address").geocomplete("autocomplete");
			}
		}

	} );

	$( '#project-address-pref' ).trigger( 'change' );

});
