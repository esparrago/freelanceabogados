<div id="freelancers">

	<!-- freelancers -->
	<div class="freelancers-header article-header row">

		<div class="article-title large-12 columns">

			<?php if ( is_hrb_users_archive() ): ?>

				<h3><?php echo __( 'Abogados', APP_TD ); ?></h3>

			<?php else: ?>

				<h3><?php echo __( 'Top Abogados', APP_TD ); ?></h3>

			<?php endif; ?>

		</div>

	</div>

	<div class="freelancer-filter row">

		<!-- <?php if ( $users->results ) : ?>

			<div class="large-12 columns project-dropdown">
				<?php the_hrb_users_sort_dropdown( get_the_hrb_users_base_url(), $attributes = array( 'id' => 'drop-freelancers-filter' ) ) ?>
			</div>

		<?php endif; ?> -->

	</div>

	<?php if ($users->results ) : ?>

		<?php appthemes_before_loop( HRB_FREELANCER_UTYPE ); ?>

		<?php foreach( $users->results as $user ): ?>

			<?php hrb_before_user( $user->ID ); ?>

			<?php appthemes_load_template( 'content-' . HRB_FREELANCER_UTYPE . '.php', array( 'user' => $user ) ); ?>

			<?php hrb_after_user( $user->ID ); ?>

		<?php endforeach; ?>

		<?php appthemes_after_loop( HRB_FREELANCER_UTYPE ); ?>

	<?php else: ?>

		<article class="freelancer content-no-results">
			<?php if ( is_search() && get_query_var('s') ) : ?>

				<h5 class="no-results"><?php printf( __( 'Sorry, no Freelancers were found named "%s".', APP_TD ), hrb_get_search_query_var( 'ls' ) ); ?></h5>

			<?php else: ?>

				<h5 class="no-results"><?php echo __( 'Sorry, no Freelancers found.', APP_TD ); ?></h5>

			<?php endif; ?>
		</article>

	<?php endif; ?>

</div><!-- end #freelancers -->

<!-- ad space -->
<?php hrb_display_ad_sidebar( 'hrb-user-ads' ); ?>

<!-- pagination -->
<?php
if ( $users->total_users > (int) $users->get('number') ) {
	hrb_output_pagination( $users, array( 'paginate_users' => true ), get_the_hrb_users_base_url() );
};
?>

<?php wp_reset_postdata(); ?>
