<div class="row">
	<div class="large-12 columns">
		<div class="credits-balance">
			<h3><?php echo __( 'Credits Balance', APP_TD ); ?></h3>
			<p><?php echo __( 'Credits:', APP_TD ); ?> <span class="credits"><?php echo hrb_get_user_credits(); ?></span></p>
		</div>
	</div>
</div>

<div class="row">
	<div class="large-12 columns">
		<a href="<?php echo esc_url( hrb_get_credits_purchase_url() ); ?>" class="button purchase-credits"><?php echo __( 'Purchase Credits', APP_TD )?></a>
	</div>
</div>

