<!-- Header ad space -->
<?php hrb_display_ad_sidebar( 'hrb-header', $position = 'header' ); ?>

<div class="top-navigation">
	<div class="row">
		<div class="large-12 columns">
			<nav class="top-bar">
				<ul class="title-area">
					<!-- Title Area -->
					<li class="name"></li>
					<li class="toggle-topbar menu-icon"><a href="#"><span><?php echo __( 'Menu', APP_TD ); ?></span></a></li>
				</ul>

				<section class="top-bar-section">
					<!-- Left Nav Section -->
					<ul class="left">
						<?php do_action( 'hrb_before_social_nav_links' ); ?>

						<?php the_hrb_social_nav_links(); ?>

						<?php do_action( 'hrb_after_social_nav_links' ); ?>
					</ul>
				</section>

				<section class="top-bar-section">
					<!-- Right Nav Section -->
					<ul class="right">
						<?php do_action( 'hrb_before_user_nav_links' ); ?>

						<?php the_hrb_user_nav_links(); ?>

						<?php do_action( 'hrb_after_user_nav_links' ); ?>
					</ul>
				</section>
			</nav>
		</div><!-- end columns -->
	</div><!-- end row -->
</div><!-- end top-navigation -->

<div class="row">
	<div class="large-4 columns branding">
		<?php the_hrb_logo(); ?>

		<?php if ( display_header_text() ) : ?>
			<h2 id="site-description" style="color:#<?php header_textcolor(); ?>;"><?php bloginfo( 'description' ); ?></h2>
		<?php endif; ?>
	</div>	<!-- end columns -->

	<div class="large-8 columns top-navigation-header">
		<div class="large-12 columns">

			<form method="get" action="<?php echo esc_url( trailingslashit( home_url() ) ); ?>">
				<div class="large-3 columns project-dropdown">
					<?php the_hrb_search_dropdown( array( 'name' => 'drop-search' ) ); ?>
				</div>
				<input type="hidden" id="st" name="st" value="<?php echo esc_attr( hrb_get_search_query_var('st') ? hrb_get_search_query_var('st') : HRB_PROJECTS_PTYPE ); ?>">

				<div class="large-9 columns search-field">
					<input type="text" id="search" placeholder="<?php echo __( 'Buscar', APP_TD ); ?>" name="ls" class="text search" value="<?php esc_attr( hrb_output_search_query_var('ls') ); ?>" />

					<div class="search-btn">
						<span class="search-button">
							<button type="submit" id="search-submit" class="search-button"><?php echo __( 'Buscar', APP_TD ); ?></button>
						</span>
					</div>
				</div>

			</form>

		</div><!-- end columns -->
	</div><!-- end columns -->


</div><!-- end row -->

<div class="main-navigation">
	<div class="row">
		<div class="large-12 columns">
			<nav class="top-bar lower-top-bar">
				<ul class="title-area">
					<!-- Title Area -->
					<li class="name"></li>
					<li class="toggle-topbar menu-icon"><a href="#"><span></span></a></li>
				</ul>
				<section class="top-bar-section">
					<!-- Left Nav Section -->
					<?php the_hrb_nav_menu(); ?>
				</section>
			</nav>
		</div><!-- end columns -->
	</div><!-- end row -->
</div>

<?php if ( ( 'front' == $hrb_options->custom_header_vis && is_front_page() ) || 'any' == $hrb_options->custom_header_vis ): ?>

	<!-- widgetized area below navbar -->
	<?php hrb_display_ad_sidebar( 'hrb-header-nav', $position = 'inside' ); ?>

<?php endif; ?>

<?php if ( $hrb_options->categories_menu['show'] && ! is_page_template('categories-list-project.php') ): ?>

	<!-- optional category lists -->
	<div class="row category-row categories-menu <?php echo ( 'click' == $hrb_options->categories_menu['show'] && ! wp_is_mobile() ? 'click-cat-menu' : '' ); ?>">
		<div class="large-12 columns">
			<?php the_hrb_project_categories_list('categories_menu'); ?>
		</div>
	</div>

<?php endif; ?>



