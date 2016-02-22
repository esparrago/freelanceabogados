jQuery(document).ready(function($) {
	
	/* notifications */

	update_submit_button();

	$('#bulk_select').on( 'change', function() {

		$('.notification').prop( 'checked', this.checked );

		update_submit_button();

		// refresh the document
		$(document).foundation();
	});

	 $('.notification').on( 'change', function() {

        if ( $('.notification').length == $('.notification:checked').length )
            $('#bulk_select').prop( 'checked', true );
        else
            $('#bulk_select').prop( 'checked', false );

		update_submit_button();

 		// refresh the document
		$(document).foundation();
    });

	function update_submit_button() {
		$('#bulk_delete').prop( 'disabled', ! $('.notification:checked').length );
	}

});