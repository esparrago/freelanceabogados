<div class="disputes">

	<div class="row">
		<div class="large-12 columns">

			<?php foreach( $disputes as $dispute ): ?>

				<?php
					$meta = p2p_get_meta( $dispute->p2p_id );
					$disputer = get_the_author_meta( 'display_name', $meta['disputer'][0] );
					$disputee = get_the_author_meta( 'display_name', $meta['disputee'][0] );
				?>

				<fieldset>
					<legend><?php echo __( 'Opened By', APP_TD ); ?></legend>
					<p><?php echo $disputer; ?></p>
				</fieldset>

				<fieldset>
					<legend><?php echo __( 'Reason', APP_TD ); ?></legend>
					<p><?php echo $dispute->post_content; ?></p>
				</fieldset>

				<fieldset>
					<legend><?php echo __( 'Status', APP_TD ); ?></legend>
					<p><span class="label dispute-status <?php echo esc_attr( $dispute->post_status . ( get_current_user_id() == $dispute->post_author ? ' disputer' : ''  ) ); ?>"><?php echo appthemes_get_disputes_statuses_verbiages( $dispute->post_status ); ?></span></p>

					<?php if ( 'publish' != $dispute->post_status ) : ?>

						<fieldset>
							<legend><?php echo __( 'Official Response', APP_TD ); ?></legend>
							<div><?php the_hrb_dispute_decision( $dispute->ID ); ?></div>
						</fieldset>

					<?php endif; ?>

				</fieldset>


				<?php appthemes_comments_template( get_post( $dispute->ID ) ); ?>



			<?php endforeach; ?>



		</div>
	</div>

</div>