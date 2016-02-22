<!-- ad space -->
<?php if ( is_active_sidebar( $sidebar_id ) ) : ?>

	<?php if ( 'header' == $position ): ?>

		<div id="header-ad">
			<div class="ad-space">
				<?php dynamic_sidebar( $sidebar_id ); ?>
			</div>
		</div>

	<?php elseif( 'listing' == $position ): ?>

		<div class="ad-space large-8 columns large-centered row">
			<?php dynamic_sidebar( $sidebar_id ); ?>
		</div>

	<?php else: ?>

		<!-- widgetized area inside content -->
		<div class="top-widgets">
			<div class="below-navigation">
				<div class="cf">
					<?php dynamic_sidebar( $sidebar_id ); ?>
				</div>
			</div>
		</div>

	<?php endif; ?>

<?php endif; ?>