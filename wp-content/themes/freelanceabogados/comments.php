<?php
/**
 * The template for displaying Comments.
 */
?>

<?php $is_dispute = hrb_is_disputes_enabled() && APP_DISPUTE_PTYPE == get_post_type(); ?>

<div id="comments" class="row">
	<div class="columns-12">

		<?php if ( post_password_required() ) : ?>
			<p class="nopassword"><?php _e( 'This post is password protected. Enter the password to view any comments.', APP_TD ); ?></p>
		</div><!-- #comments -->
		<?php
				/* Stop the rest of comments.php from being processed,
				 * but don't kill the script entirely -- we still have
				 * to fully load the template.
				 */
				return;
			endif;
		?>

		<?php // You can start editing here -- including this comment! ?>

		<div class="section-head">
			<a id="add-review" name="add-review"></a>
			<h2 id="left-hanger-add-review"><?php _e( ( HRB_PROJECTS_PTYPE == get_post_type() ? __( 'Clarification Board', APP_TD ) : __( 'Discussion', APP_TD ) ), APP_TD ); ?></h2>
		</div>

		<?php appthemes_before_comments(); ?>

		<?php if ( have_comments() ) : ?>
			<h2 id="comments-title">
				<?php
					if ( HRB_PROJECTS_PTYPE != get_post_type() && ! $is_dispute ) {
						$type = __( 'thought', APP_TD );
					} else {
						$type = __( 'messages', APP_TD );
					}
					printf( _n( 'One %2$s on &ldquo;%3$s&rdquo;', '%1$s %2$s on &ldquo;%3$s&rdquo;', get_comments_number(), APP_TD ),
					number_format_i18n( get_comments_number() ), $type, '<span>' . get_the_title() . '</span>' );
				?>
			</h2>

			<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // are there comments to navigate through ?>
			<nav id="comment-nav-above">
				<h1 class="assistive-text"><?php _e( 'Comment navigation', APP_TD ); ?></h1>
				<div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', APP_TD ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', APP_TD ) ); ?></div>
			</nav>
			<?php endif; // check for comment navigation ?>

			<ol class="commentlist">
				<?php
					/* Loop through and list the comments. Tell wp_list_comments()
					 * to use twentyeleven_comment() to format the comments.
					 * If you want to overload this in a child theme then you can
					 * define twentyeleven_comment() and that will be used instead.
					 * See twentyeleven_comment() in twentyeleven/functions.php for more.
					 */
					wp_list_comments('avatar_size=75');
				?>
			</ol>

			<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // are there comments to navigate through ?>
			<nav id="comment-nav-below">
				<h1 class="assistive-text"><?php _e( 'Comment navigation', APP_TD ); ?></h1>
				<div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', APP_TD ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', APP_TD ) ); ?></div>
			</nav>
			<?php endif; // check for comment navigation ?>

		<?php
			/* If there are no comments and comments are closed, let's leave a little note, shall we?
			 * But we don't want the note on pages or post types that do not support comments.
			 */
			elseif ( ! comments_open() && ! is_page() && post_type_supports( get_post_type(), 'comments' ) ) :
		?>
			<p class="nocomments"><?php _e( 'Discussion is closed.', APP_TD ); ?></p>
		<?php endif; ?>

		<?php appthemes_after_comments(); ?>

		<?php appthemes_before_comments_form(); ?>

		<?php
			$post_id = null;
			$comment_form_args = array(
				'comment_field' => '<p class="comment-form-comment"><label for="comment">' . _x( 'Message', 'noun', APP_TD ) . '</label> <textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
				'logged_in_as'  => '<p class="logged-in-as">' . sprintf( __( 'Logged in as <a href="%1$s">%2$s</a><a href="%3$s" title="Log out of this account">Log out?</a>' ), get_edit_user_link(), $user_identity, wp_logout_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>',
				'label_submit'  => __( 'Submit', APP_TD ),
			);
		?>

		<?php
			// skip the login buttons for disputes comments
			if ( $is_dispute ) {
				$comment_form_args['logged_in_as'] = '';
			}
		?>

		<?php comment_form( $comment_form_args ); ?>

		<?php appthemes_after_comments_form(); ?>

	</div>
</div><!-- #comments -->
