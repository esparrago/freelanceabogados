<nav class="fl-pagination">
	<div class="pagination-centered">
		<ul class="pagination">
			<?php if ( empty( $wp_query->is_frontpage ) ): ?>

				<?php the_hrb_pagination( $wp_query_object, $pagination_args ); ?>

			<?php else: ?>

				<li>
					<a href="<?php echo esc_url( $pagination_args['base_url'] ); ?>" title="<?php echo esc_attr( __( 'More...', APP_TD ) ); ?>"><?php echo esc_attr( __( 'More...', APP_TD ) ); ?></a>
				</li>

			<?php endif; ?>
		</ul>
	</div>
</nav>