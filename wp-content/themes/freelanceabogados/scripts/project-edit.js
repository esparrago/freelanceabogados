jQuery(document).ready(function($) {

	/* init validate */

	$('#create-project-form').validate({
		errorElement: "small",
		errorPlacement: function(error, element) {
			if ( element.hasClass('category-dropdown') ) {
				$('div.custom.dropdown.category-dropdown').after(error); // default error placement
			} else {

				if ( element.is(":radio") || element.is(":checkbox") ) {
					error.insertAfter( element.parent().siblings().last() );
				 } else {
					element.after(error); // default error placement
				}

			}
		},
		rules: {
			budget_price: {
			  required: true,
			  number: true
			},
		}
	});

	$('.locked').attr( 'disabled', 'disabled' );


	/* custom fields file upload validate */

	$('#create-project-form').submit( function( event ) {

		// check for file required file upload fields - simulates jquery validate
		if ( $('.file-upload.required .no-media').is(':visible') ) {

			var error = hrb_i18n.file_upload_required;

			$('.file-upload-error').remove()
			$('.file-upload.required .media_placeholder').after('<a class="file-upload-error" href="#"></a><span class="file-upload-error error">' + error + '</span>');
			$('.file-upload-error').show();

			$('html,body').animate( {scrollTop: $('a.file-upload-error').offset().top-100} );

			event.preventDefault();
		}

	});


	/* custom forms */

	function loadFormFields() {
		if ( ! $(this).val() ) {
			return;
		}

		var data = {
			action: 'app-render-project-form',
			category: $(this).val(),
			listing_id: $('input[name=ID]').val(),
		};

		$.post( hrb_i18n.ajaxurl, data, function(response) {
			$('#project-form-custom-fields').html( response );

			$('.file-upload .upload_button').on( 'click', function() {
				$('.file-upload-error').remove();
			});

			// refresh the <select> content
			$(document).foundation('forms');
		});
	}

	$('#category, #sub_category')
		.change( loadFormFields )
		.find('option').eq(0).val(''); // needed for jQuery.validate()


	/* budget */

	$('#budget_type').change( function(){

		if ( 'hourly' == $(this).val() ) {
			$('.budget-min-hours').fadeIn();
		} else {
			$('.budget-min-hours').fadeOut();
		}

	} );

	$('#budget_currency').change( function() {

		var budget_currency = $('option:selected',this).attr('currency-symbol');
		$('.selected-currency').html( budget_currency );

	} );

	$('#budget_type').trigger('change');

	$('#budget_currency').trigger('change');


	/* categories & sub-categories */

	var sel_category = 0;

	$('#category').change( function() {

		// only enable sub-category when a category is selected
		if ( 0 != $(this).val() ) {
			$('#sub_category').removeAttr('disabled');
		} else {
			$('#sub_category').attr('disabled','disabled');
		}

		if ( sel_category == $(this).val() ) {
			return true;
		}

		$('label[for=sub_category]').append( hrb_i18n.ajaxloader );

		var data = {
			action: 'hrb_output_subcategories',
			selected: $('#sub_category').attr('pre-selected'),
			category: $(this).val(),
            _ajax_nonce: hrb_i18n.ajax_nonce,
		};

        $.ajax( {
			url: hrb_i18n.ajaxurl,
			data: data,
			type: 'POST'
			}).done( function( response ) {

				$('#sub_category').html( response ).trigger('change');
				$('.processing').hide();

				// refresh the <select> content
				Foundation.libs.forms.refresh_custom_select( $( '#sub_category' ), true );
        });

		sel_category = $( '#category option:selected' ).val();

	});

	$('#category').trigger('change');


	/* skills dropdown - uses select2 */

	// init select2 JS
	$('#skills').select2({
		maximumSelectionSize: app_project_edit_i18n.maximum_skills_selection,
		placeholder: app_project_edit_i18n.skills_placeholder,
	});

	// dynamic padding for the skills dropdown
	$('#skills').on( 'select2-open', function() {

		$('.select2-results li').each( function() {

			var classes = $(this).attr('class');
			var level = classes.match( /level-[0-99]/g );

			if ( level ) {
				level_n = level[0].split('-');
				$( '.select2-result-label', this ).css( 'padding-left', level_n[1] * 20 + 'px' );
			}

		});

	});


	/* multiple tags - uses tagsManager.js */

	$("#tags").tagsManager({
		prefilled: $("#tags").val(),
		tagsContainer: '.tags-tags',
	});

	$( "body" ).on( 'keydown', 'input#tags', function( e ) {
	  if ( 9 == e.which ) {
		e.preventDefault();
		// force focus on the next valid input on Tab keypress
		$('input[tabindex=12]').focus();
	  }

	});

	/* location */

	$('#location_type').change( function() {

		if ( 'local' != $(this).val() ) {
			$('.custom-location').hide();
			$('.custom-location input').val('');
		} else {
			$('.custom-location').fadeIn();

			var options = {
                details: "form",
                detailsAttribute: "data-geo",
			};

			// merge geocomplete base options with dynamic options in settings page
			if ( typeof app_project_edit_i18n.geocomplete_options !== "undefined" ) {
				options = $.extend( options, app_project_edit_i18n.geocomplete_options );

				$('#location').geocomplete( options );

				var geocomplete = $("#location").geocomplete("autocomplete");

				if ( typeof app_project_edit_i18n.geocomplete_options['componentRestrictions'] !== "undefined" ) {
					geocomplete.setComponentRestrictions( app_project_edit_i18n.geocomplete_options['componentRestrictions'] );
				}

				if ( typeof app_project_edit_i18n.geocomplete_options['types'] !== "undefined" ) {
					geocomplete.setTypes( app_project_edit_i18n.geocomplete_options['types'] );
				}

			}
		}

	} );

	$('#location_type').trigger('change');

});
