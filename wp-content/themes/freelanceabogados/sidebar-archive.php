<div class="row sidebar-archive">
	<div class="large-12 columns">
		<div class="panel">

			<form method="get" class="custom" action="<?php echo esc_url( get_the_hrb_refine_search_base_url() ); ?>">

				<div class="section-head cf">
					<div class="row collapse">
					<div class="large-9 small-9 columns">
						<input type="text" placeholder="<?php echo __( 'Buscar', APP_TD ); ?>" name="refine_ls" class="text search" value="<?php esc_attr( hrb_output_search_query_var('ls') ); ?>" />
						<?php if ( ! hrb_get_search_query_var('st') ): ?>
							<input type="hidden" name="st" value="<?php echo HRB_PROJECTS_PTYPE; ?>">
						<?php endif; ?>
					</div>
					<div class="large-3 small-3 columns text-right ">
						<input type="submit" class="search-button" value="<?php esc_attr_e( 'Update', APP_TD ); ?>" />
					</div>
					</div>
				</div>

				<?php do_action('hrb_sidebar_refine_search'); ?>

				<?php if ( ! is_hrb_users_archive() && ! is_hrb_users_search() && ! is_tax( HRB_PROJECTS_CATEGORY ) ): ?>

					<div id="refine-categories">
						<h4><?php _e ( 'Categorias', APP_TD ); ?></h4>
						<?php the_hrb_refine_category_ui( HRB_PROJECTS_CATEGORY ); ?>
					</div>

				<?php endif; ?>

				<?php if ( ! is_tax( HRB_PROJECTS_SKILLS ) ): ?>

					<div id="refine-skills">
						<h4><?php _e ( 'Habilidades', APP_TD ); ?></h4>
						<?php the_hrb_refine_skills_ui(); ?>
					</div>

				<?php endif; ?>

				<div id="refine-location">

					<h4><?php _e ( 'ubicación', APP_TD ); ?></h4>

					<?php if ( ( ! empty( $_REQUEST['st'] ) && HRB_ROLE_FREELANCER == $_REQUEST['st'] ) || get_query_var('archive_freelancer') ): ?>

						<?php the_hrb_refine_location_ui( HRB_ROLE_FREELANCER, 'users' ); ?>

					<?php else: ?>

						<?php the_hrb_refine_location_ui( HRB_ROLE_EMPLOYER ); ?>

					<?php endif; ?>

				</div>

				<?php appthemes_pass_request_var( array( HRB_PROJECTS_CATEGORY, HRB_PROJECTS_SKILLS ) ); ?>
				<?php appthemes_pass_request_var('post_type'); ?>

				<?php appthemes_pass_request_var('ls'); ?>
				<?php appthemes_pass_request_var('orderby'); ?>
				<?php appthemes_pass_request_var('st'); ?>

				<?php do_action('hrb_sidebar_refine_search_hidden'); ?>

				<input type="submit" class="button" value="<?php esc_attr_e( 'Actualizar', APP_TD ); ?>" />
			</form>

		</div>
	</div>
</div>

<hr/>

<?php dynamic_sidebar('hrb-listings'); ?>
