<h2><i class="icon i-payments"></i><?php echo __( 'Payments', APP_TD  ); ?></h2>

<div class="section-container auto section-tabs project-trunk" data-section>

	<?php if ( hrb_credits_enabled() && current_user_can('edit_bids') ): ?>

		<section class="<?php echo empty( $active ) ? $active = 'active' : ''; ?>">

			<p class="title" data-section-title="" style="left: 194px;"><a href="#wallet"><?php echo __( 'Wallet', APP_TD ); ?></a></p>

			<div class="content" data-section-content>

				<?php appthemes_load_template( 'dashboard-payments-section-wallet.php', array( 'credits_for' => $credits_for ) ); ?>

			</div>

		</section>

	<?php endif; ?>

	<?php if ( APP_Gateway_Registry::get_active_gateways( 'escrow' ) && hrb_is_escrow_enabled() && current_user_can('edit_bids') ): ?>

		<section class="<?php echo empty( $active ) ? $active = 'active' : ''; ?>">

			<p class="title" data-section-title="" style="left: 194px;"><a href="#escrow"><?php echo __( 'Escrow', APP_TD ); ?></a></p>

			<div class="content" data-section-content>

				<?php appthemes_load_template( 'dashboard-payments-section-escrow.php' ); ?>

			</div>

		</section>

	<?php endif; ?>

	<section class="<?php echo empty( $active ) ? $active = 'active' : ''; ?>">

		<p class="title" data-section-title="" style="left: 194px;"><a href="#purchases"><?php echo __( 'Purchases', APP_TD ); ?></a></p>

		<div class="content" data-section-content>

			<?php appthemes_load_template( 'dashboard-payments-section-purchases.php', array( 'orders' => $orders, 'orders_no_filters' => $orders_no_filters ) ); ?>

		</div>

	</section>

</div>
