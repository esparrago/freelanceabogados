jQuery(document).ready(function($) {

	/* init validate */

	$('#create-proposal-form').validate({
		errorElement: "small",
		rules: {
			amount: {
			  required: true,
			  number: true
			},
			delivery: {
			  required: true,
			  number: true
			}
		  }
	});

	$('#featured').on( 'change', function() {

		var credits_feature = parseInt( hrb_proposal_i18n.feature_proposal_req_c );
		var credits_required = parseInt( $('input[name=credits_required]').val() );

		if ( $(this).attr('checked') ) {
			credits_required += credits_feature;
		} else {
			credits_required -= credits_feature;
		}

		$('input[name=credits_required]').val( credits_required );
		$('.credits-required').html( credits_required );

		if ( hrb_proposal_i18n.credits_balance < credits_required ) {
			$('.no-credits-warning').show();
			$('#submit_proposal').prop( 'disabled', true );
			$('#accept_site_terms').unbind( 'change', terms_handler );
		} else {
			$('.no-credits-warning').hide();
			$('#accept_site_terms').bind( 'change', terms_handler );
		}

		$('#accept_site_terms').trigger('change');

	} );

	terms_handler = function() {

		if ( $(this).attr('checked') ) {
			$('#submit_proposal').removeAttr('disabled');
		} else {
			$('#submit_proposal').prop( 'disabled', 'disabled' );
		}

		Foundation.libs.forms.refresh_custom_select( $('#submit_proposal'), true );
	};

	$('#accept_site_terms').on( 'change', terms_handler );

	/* custom forms */

	function loadFormFields() {

		var data = {
			action: 'app-render-proposal-form',
			proposal_id: $('input[name=ID]').val(),
		};

		$('#project-form-custom-fields').html('<img class="loading-custom-fields" src = "' + hrb_i18n.loading_img + '"> ' + hrb_i18n.loading_msg );

		$.post( hrb_i18n.ajaxurl, data, function(response) {
			$('#project-form-custom-fields').html( response );
		});
	}

	loadFormFields();

});
