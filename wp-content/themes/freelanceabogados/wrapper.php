<!DOCTYPE html>
<!--[if IE 8]>	<html class="no-js lt-ie9" <?php language_attributes(); ?> > <![endif]-->
<!--[if gt IE 8]><!-->	<html class="no-js" <?php language_attributes(); ?> > <!--<![endif]-->

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
  	<meta name="viewport" content="width=device-width" />

  	<title><?php wp_title(''); ?></title>

	<?php wp_head(); ?>

	<link rel="stylesheet" href="<?php echo esc_url( get_bloginfo( 'stylesheet_url' ) ); ?>" />
</head>

<body <?php body_class('preload'); ?> >

	<?php appthemes_before(); ?>

	<?php appthemes_before_header(); ?>

	<?php get_header( app_template_base() ); ?>

	<?php appthemes_after_header(); ?>

	<div class="full-width">

		<?php do_action( 'hrb_content_container_top' ); ?>

		<div class="row">
			<div class="large-12 columns wrap">
				<?php do_action( 'appthemes_notices' ); ?>

				<?php load_template( app_template_path() ); ?>
			</div><!-- end columns -->
		</div><!-- end row -->
	</div>

	<?php appthemes_before_footer(); ?>

	<?php get_footer( app_template_base() ); ?>

	<?php appthemes_after_footer(); ?>

	<?php appthemes_after(); ?>

	<?php wp_footer(); ?>

	<script>
		( function( $ ) {
			$( document ).foundation();
		}) ( jQuery );
	</script>

</body>
</html>
