jQuery(document).ready(function($) {

	/* init validate */

	$('#manage_project').validate({
		errorElement: "small"
	});

	$('#end_work').on( 'click', function( e ) {

		var status = $('select[name=work_status]').val();

		if ( 'canceled' == status || 'incomplete' == status ) {
			$('#work_end_notes').addClass('required');
		} else {
			$('#work_end_notes').removeClass('required');
		}

	});

	$('#end_project').on( 'click', function( e ) {

		var status = $('select[name=project_status]').val();

		if ( 'canceled' == status || 'closed_incomplete' == status ) {
			$('#project_end_notes').addClass('required');

			if ( app_workspace_i18n.escrow ) {

				if ( ! app_workspace_i18n.work_complete || ! app_workspace_i18n.disputes_enabled ) {
					confirmation =  app_workspace_i18n.confirmation_escrow_cancel + "\r\n\r\n" + app_workspace_i18n.confirmation;
				} else {
					confirmation =  app_workspace_i18n.confirmation_possible_dispute + "\r\n\r\n" + app_workspace_i18n.confirmation;
				}

			} else {
				confirmation = app_workspace_i18n.confirmation;
			}

		} else {

			$('#project_end_notes').removeClass('required');

			if ( 'closed_complete' == status && app_workspace_i18n.escrow ) {
				confirmation = app_workspace_i18n.confirmation_escrow_complete + "\r\n\r\n" + app_workspace_i18n.confirmation;
			}

		}

		if ( confirmation ) {
			option = confirm( confirmation );

			if ( ! option ) {
				e.preventDefault;
				return false;
			}

		}

	});

	$('a[id*=review-user].review-user').on( 'click', function( e ) {
		$('.form-review-fieldset.' + $(this).prop('id')).toggle();

		e.preventDefault();
	});

	// disputes

	$('#raise-dispute-form').validate({
		errorElement: "small"
	});

	$('#raise-dispute').on( 'click', function( e ) {
		$('.form-raise-dispute-fieldset').toggle();

		e.preventDefault();
	});

	if ( $('#raise-dispute-form').hasClass('dispute-error') ) {
		$('.form-raise-dispute-fieldset').toggle();
		$('#raise-dispute-form').valid();
	}

});
