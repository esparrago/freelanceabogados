<h2><i class="icon i-notifications"></i><?php echo __( 'Notifications', APP_TD  ); ?></h2>

<div class="dashboard-filters">

	<div class="row">
		<div class="large-12 columns dashboard-filter-sort">

			<div class="large-6 small-12 columns">
				<?php hrb_output_results_fdropdown( hrb_get_dashboard_url_for( 'notifications' ) ); ?>
			</div>

			<div class="large-6 columns">
				<?php hrb_output_sort_fdropdown(); ?>
			</div>

			<div class="large-12 columns">
				<?php hrb_output_notif_types_fdropdown( $notifications_no_filters, $attributes = array( 'name' => 'drop-notification-types', 'label' => __( 'Types', APP_TD ), 'base_link' => hrb_get_dashboard_url_for('notifications') ) ); ?>
			</div>

		</div>
	</div>

</div>

<div class="row notifications">
	<div class="large-12 columns">

		<?php if ( $notifications->results ): ?>

			<form id="manage_notifications" name="manage_notifications" method="post" class="custom entry">

				<table width="100%">
					<thead>
						<tr>
							<th width="5%">&nbsp;</th>
							<th width="70%" class="message"><?php echo __( 'Message', APP_TD ); ?></th>
							<th width="15%">&nbsp;</th>
							<th width="5%"><?php echo html( 'input', array( 'type' => 'checkbox', 'id' => 'bulk_select' ) ); ?></th>
						</tr>
					</thead>
					<tbody>

						<?php foreach( $notifications->results as $notification ): ?>
							<tr>
								<td><?php echo ( 'unread' == $notification->status ? html( 'span', array( 'class' => 'new-notification' ), '<i class="icon i-new-notification"></i>' ) : '' ); ?></td>
								<td>
									<div class="message"><?php echo ( 'unread' == $notification->status ? html( 'strong', $notification->message ) : $notification->message ); ?></div>
									<div class="message-time"><small class="notification-time"><?php echo appthemes_display_date( $notification->time ); ?></small></div>
								</td>
								<td>
									<?php if ( $notification->action ): ?>
										<div class="notification-action">
											<a data-tooltip title="<?php echo __( 'View', APP_TD ); ?>" class="button tiny" href="<?php echo esc_url( $notification->action ); ?>"><i class="icon i-view"></i></a>
										</div>
									<?php endif; ?>
								</td>
								<td><?php echo html( 'input', array( 'type' => 'checkbox', 'name' => 'notification_id[]', 'value' => $notification->id, 'class' => 'notification' ) ); ?></td>
							</tr>
						<?php endforeach; ?>

					</tbody>
				</table>

				<input id="bulk_delete" name="bulk_delete" type="submit" class="button small" value="<?php echo esc_attr__( 'Delete Selected', APP_TD ); ?>">

				<?php wp_nonce_field( 'hrb_manage_notifications' ); ?>

				<?php
					hrb_hidden_input_fields(
						array( 'action' => 'manage_notifications' )
					);
				?>

			</form>

			<!-- pagination -->
			<?php
				if ( $notifications->found > 1 ) {
					hrb_output_pagination( $notifications->results, array( 'total' => $notifications->found ), hrb_get_dashboard_url_for('notifications') );
				};
			?>

		<?php else: ?>

			<h5 class="no-results"><?php echo __( 'No messages.', APP_TD ); ?></h5>

		<?php endif; ?>

	</div>
</div>