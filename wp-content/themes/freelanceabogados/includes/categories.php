<?php
/**
 * Functions related with categories.
 *
 */

require_once(ABSPATH . 'wp-admin/includes/template.php');

### Other

/**
 * Extends the category checkbox walker to provide multiple selection.
 */
class HRB_Multi_Category_Walker extends Walker_Category_Checklist {

	private $args = array();

	function __construct( $args = array() ) {
		$this->args = $args;
	}

	function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {

		$args = wp_parse_args( $this->args, $args );

		extract($args);

		if ( empty($taxonomy) ) {
			$taxonomy = 'category';
		}

		if ( $taxonomy == 'category' ) {
			$name = 'post_category';
		} else {
			$name = 'tax_input['.$taxonomy.']';
		}

		if ( $depth === 0 ) {
			$input_type_class = 'parent';
			$parent_class = '';
		} else {
			$input_type_class = 'child';
			$parent_class = 'parent="'.$category->parent.'"';
		}

		$pop_class = in_array( $category->term_id, $popular_cats ) ? 'popular-category' : '';
		$class = ' class="hrb-taxonomy '.$pop_class.( $depth===0 ? ' parent-list' : '' ).'"';

		$output .= "\n<li id='{$taxonomy}-{$category->term_id}'$class>" . '<label class="selectit"><input '. $parent_class .' class="'. $input_type_class .'" value="' . $category->term_id . '" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy.'-' . $category->term_id . '"' . checked( in_array( $category->term_id, $selected_cats ), true, false ) . disabled( empty( $args['disabled'] ), false, false ) . ' /> ' . esc_html( apply_filters('the_category', $category->name )) .'</label>';

	}
}

/**
 * Extends the category dropdown walker to provide option groups.
 */
class HRB_OptGroup_Category_Walker extends Walker_CategoryDropdown {

  var $optgroup = false;

  function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {

	$pad = str_repeat( '&nbsp;', $depth * 3 );

	$cat_name = apply_filters( 'list_cats', $category->name, $category );

	$term_children = get_term_children( $category->term_id, $category->taxonomy );

	if ( 0 == $depth && $term_children ) {
		$output .= "<optgroup class=\"level-$depth\" label=\"".$cat_name."\" >";
	} else {
		$output .= "<option class=\"level-$depth\" value=\"".$category->term_id."\"";
		if ( in_array( $category->term_id, (array) $args['selected'] ) ) {
			$output .= ' selected="selected"';
		}

		$output .= '>';
		$output .= $cat_name;

		if ( $args['show_count'] ) {
			$output .= '&nbsp;&nbsp;('. $category->count .')';
		}
		$output .= "</option>";
	}

  }

  function end_el( &$output, $object, $depth = 0, $args = array() ) {

	if ( 0 == $depth ) {
	  $output .= '</optgroup>';
	}

  }

}