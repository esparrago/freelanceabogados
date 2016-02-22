<div id="orders">

	<div class="dashboard-filters">

		<div class="row">
			<div class="large-12 columns dashboard-filter-sort">
				<div class="large-6 columns">
					<?php hrb_output_results_fdropdown( hrb_get_dashboard_url_for( 'payments', 'purchases' ) ); ?>
				</div>

				<div class="large-6 columns">
					<?php hrb_output_sort_fdropdown( hrb_get_dashboard_url_for( 'payments', 'purchases' ) ); ?>
				</div>
			</div>
			<div class="large-12 columns dashboard-filter-sort">
				<div class="large-12 columns">
					<?php hrb_output_statuses_fdropdown( $orders_no_filters, $attributes = array( 'name' => 'drop-filter-status', 'label' => __( 'Status', APP_TD ), 'base_link' => hrb_get_dashboard_url_for( 'payments', 'purchases' ) ) ); ?>
				</div>
			</div>
		</div>

	</div>

	<?php if ( $orders->post_count > 0 ): ?>

		<?php while( $orders->have_posts() ) : $orders->the_post(); ?>

			<?php $order = appthemes_get_order( get_the_ID() ); ?>

			<?php if ( ! $order->get_items() ) continue; ?>

			<article class="order">

				<div class="row order">
					<div class="large-12 columns">

						<div class="large-2 columns order-number">
							<span> #<?php echo get_the_ID(); ?></span>
							<p><small><?php echo ( $order->is_escrow() ? __( 'escrow', APP_TD ) : '' ); ?></small></p>
						</div>

						<div class="large-10 columns order-main">

							<div class="row order-title-row">
								<div class="large-8 small-8 columns">
									<span class="item-title cf">
										<h2><?php the_hrb_order_item_title( $order ); ?></h2>
									</span>
								</div>
								<div class="large-4 small-4 columns order-meta-info">
									<span data-tooltip title="<?php echo esc_attr( __( 'Status', APP_TD ) ); ?>" class="label right order-status <?php echo esc_attr( $order->get_status() ); ?>"><i class="icon i-status"></i><?php the_hrb_order_status( $order ); ?></span>
								</div>
							</div>

							<div class="row dashboard-order-info">
								<div class="large-6 small-6 columns dashboard-order-gateway">
									<span><?php the_hrb_order_gateway( $order ); ?></span>
								</div>

								<div class="large-6 small-6 columns dashboard-order-date">
									<span data-tooltip title="<?php _e( 'Order Date', APP_TD ); ?>"><i class="icon i-order-date"></i><?php echo get_the_date(); ?></span>
								</div>
							</div>

							<?php foreach( get_the_hrb_order_summary( $order ) as $plan ): ?>

									<div class="row dashboard-order-plan">
										<div class="large-12 columns">

											<div class="large-10 small-10 columns plan">
												<span><?php echo $plan['title']; ?></span>
											</div>
											<div class="large-2 small-2 columns plan-price">
												<span><?php echo $plan['price']; ?></span>
											</div>

										</div>
									</div>

									<?php if ( !empty( $plan['addons'] ) ): ?>

										<div class="row dashboard-order-addons">
											<div class="large-12 columns">

												<?php foreach( $plan['addons'] as $addon ): ?>

													<div class="large-10 small-10 columns addon-title">
														<span><?php echo $addon['title']; ?></span>
													</div>
													<div class="large-2 small-2 columns addon-price">
														<span><?php echo $addon['price']; ?></span>
													</div>

												<?php endforeach; ?>

											</div>
										</div>

									<?php endif; ?>

							<?php endforeach; ?>

							<div class="row dashboard-order-total">
								<div class="large-10 small-8 columns order-actions">
									<?php the_hrb_order_actions( $order ); ?>
								</div>
								<div class="large-2 small-4 columns order-price-total">
									<span><?php echo appthemes_display_price( $order->get_total() ); ?></span>
								</div>
							</div>
						</div>

					</div>

				</div><!-- end row -->

			</article>

		<?php endwhile; ?>

		<!-- pagination -->
		<?php
		if ( $orders->max_num_pages > 1 ) :
			hrb_output_pagination( $orders, '', hrb_get_dashboard_url_for('payments'), '#purchases' );
		endif;
		?>

	<?php else: ?>

		<h5 class="no-results"><?php echo __( 'You have no purchases at this time.', APP_TD ); ?></h5>

	<?php endif; ?>

</div>
