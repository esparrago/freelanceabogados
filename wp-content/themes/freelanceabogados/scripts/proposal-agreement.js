jQuery(document).ready(function($) {

	/* init validate */

	$('#proposal-agreement').validate({
		errorElement: "small",
		errorPlacement: function(error, element) {

			if ( element.is(":radio") || element.is(":checkbox") ) {
				error.insertAfter( element.parent().siblings('label').last() );
			 } else {
				element.after(error); // default error placement
			}

		}
	});

	/* Deciding */

	if (  undefined == $('input[name=candidate_decision]').val() ||  undefined == $('input[name=employer_decision]').val() ) {
		decision_reason( '', '' );
	}

	$('input[name=candidate_decision]').on( 'change', function() {
		decision_reason( $(this).val(), 'candidate_notes' );
		button_action( $(this).val(), 'candidate' );
	});

	$('input[name=employer_decision]').on( 'change', function() {
		decision_reason( $(this).val(), 'employer_notes' );
		button_action( $(this).val(), 'employer' );
	});


	/* Delete Candidate */

	$('input[name*=_delete]').on( 'change', function( e ) {

		if ( $(this).prop('checked') ) {
			$('#proposal_agreement').val( app_agreement_i18n.decline_agreement_text );
		} else {
			set_button_defaults();
		}

		// refresh the document
		$(document).foundation();

	});


	$('input[name=employer_candidate_delete]').on( 'change', function( e ) {

		if ( $(this).prop('checked') ) {

			if ( app_agreement_i18n.terms_decline == $('input[name=employer_decision]:checked').val() ) {
				$('#proposal_agreement').attr( 'agreement', 'employer_delete_candidate' );

				decision_reason( 'delete', 'employer_notes' );
			}

		}

	});

	$('input[name=self_candidate_delete]').on( 'change', function( e ) {

		if ( $(this).prop('checked') ) {

			if ( app_agreement_i18n.terms_decline == $('input[name=candidate_decision]:checked').val() ) {
				$('#proposal_agreement').attr( 'agreement', 'self_delete_candidate' );

				decision_reason( 'delete', 'candidate_notes' );
			}
		}

	});


	/* Init current values */

	set_button_defaults();

	toggle_candidate_delete();

	$('input[name*=_decision]:checked').trigger('change');


	/* Dyanmic Agreement Button */

	$('#proposal_agreement').on( 'click', function( e ) {

		var note = '';

		if ( 'agreement' == $(this).attr('agreement') ) {
			note = app_agreement_i18n.agreement_note;
        } else if ( 'employer_delete_candidate' == $(this).attr('agreement') ) {
			note = app_agreement_i18n.delete_candidate_employer;
		} else if ( 'self_delete_candidate' == $(this).attr('agreement') ) {
			note = app_agreement_i18n.delete_candidate_self;
        }

		if ( '' != note && ! confirm( note ) ) {
			e.preventDefault();
			return false;
		}

	});

	/* Functions */

	function decision_reason( value, name ) {

		if ( app_agreement_i18n.terms_decline != value ) {
			$('textarea[name="'+name+'"]').removeClass('required error');
			$('textarea[name="'+name+'"] > .error ').remove();
		} else if( app_agreement_i18n.terms_decline == value ) {
			$('textarea[name="'+name+'"]').addClass('required');
		}

		if ( app_agreement_i18n.terms_propose == value ) {
			$('textarea[name=proposal_terms]').addClass('required');
		} else {
			$('textarea[name=proposal_terms]').removeClass('required error');
			$('textarea[name=proposal_terms] > .error ').remove();
		}

		// refresh the document
		$(document).foundation();
	}

	function set_button_defaults(){
		$('#proposal_agreement').val( app_agreement_i18n.submit_for_approval_text );
		$('#proposal_agreement').attr( 'agreement', '' );
	}

	function toggle_candidate_delete( visible ) {

		if ( visible ) {
			$('#candidate_delete').show();
		} else {
			$('#candidate_delete').hide();
			$('#candidate_delete > input[type=checkbox]').prop( 'checked', false );
		}

		// refresh the document
		$(document).foundation();
	}

	function button_action( value, role ){

		set_button_defaults();

		toggle_candidate_delete( value && app_agreement_i18n.terms_decline == value );

		if ( value && value == $('input[name=decision]').val() ) {

			if ( app_agreement_i18n.terms_accept == value ) {
				$('#proposal_agreement').val( app_agreement_i18n.submit_agreement_text );
				$('#proposal_agreement').attr( 'agreement', 'agreement' );
			} else if( app_agreement_i18n.terms_decline == value ) {
				$('#proposal_agreement').val( app_agreement_i18n.decline_agreement_text );
				$('#proposal_agreement').attr( 'agreement', 'agreement_declined' );
			}

		}

		$('#candidate_delete').trigger('change');
	}

});
