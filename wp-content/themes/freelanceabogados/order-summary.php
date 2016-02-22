<?php
	appthemes_load_template( 'order-summary-template.php', array(
		'template'		=> 'order-summary-content.php',
		'sidebar'		=> str_replace( get_query_var('checkout'), 'chk-', '' ),
		'vars'			=> _hrb_get_order_summary_template_vars(),
	) );
