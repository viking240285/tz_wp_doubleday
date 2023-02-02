<?php
/*
Plugin Name: MU plugin - Post Views Counter
Description: A MU-plugin for counting post views
Version: 1.0.0
Author: Evgeniy Zhukov
*/

if (!defined('ABSPATH')) {
	exit;
}

if (class_exists('PostViewsCounter')) {

	$post_views_counter = new PostViewsCounter();
	$post_views_counter->register();
}

class PostViewsCounter
{

	public function register()
	{

		add_action('add_meta_boxes', [$this, 'post_views_counter_add_metabox']);

		add_action('save_post', [$this, 'save_post_views_counter'], 10, 2);

		add_filter('manage_posts_columns', [$this, 'add_post_views_counter_column'], 10, 2);

		add_action('manage_post_posts_custom_column',  [$this, 'fill_post_views_counter_column'], 5, 2);

		add_filter('manage_edit-post_sortable_columns', [$this, 'sortable_post_views_counter_column']);

		add_filter('request', [$this, 'orderby_post_views_counter_column']);

		add_filter("wp_head", [$this, "inc_post_views_counter"]);

	}

	public function post_views_counter_add_metabox()
	{

		add_meta_box(
			'post_views_counter',
			__('Post Views Count', 'translaters'),
			[$this, 'post_views_counter_callback'],
			'post',
			'side',
			'default'
		);
	}

	public function post_views_counter_callback($post)
	{

		$count = get_post_meta($post->ID, 'views_counter', true);

		$count = (empty($count)) ? 0 : $count;

		wp_nonce_field('post_views_counter_nonce', 'post_views_counter_nonce');

		echo '<label for="post_views_count">' . __('Post Views Count:', 'post-views-counter') . '</label>';

		echo '<input type="number" min="0" id="views_counter" name="views_counter" value="' . absint($count) . '">';
	}

	public function save_post_views_counter($post_id)
	{

		if (!isset($_POST['post_views_counter_nonce']) || !wp_verify_nonce($_POST['post_views_counter_nonce'], 'post_views_counter_nonce')) {
			return;
		}

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		if (isset($_POST['views_counter'])) {

			update_post_meta($post_id, 'views_counter', sanitize_text_field($_POST['views_counter']));
		}

	}

	public function add_post_views_counter_column($post_columns, $post_type)
	{

		if ($post_type == 'post') {
			$new_columns = array();
			foreach ($post_columns as $key => $title) {
				$new_columns[$key] = $title;
				if ($key == 'title') {
					$new_columns['post_views_counter'] = __('Просмотры', 'translaters');
				}
			}
			return $new_columns;
		}
		return $post_columns;
	}

	public function fill_post_views_counter_column($colname, $post_id)
	{

		if ($colname === 'post_views_counter') {

			echo get_post_meta($post_id, 'views_counter', true);
		}
	}

	public function sortable_post_views_counter_column($sortable_columns)
	{

		$sortable_columns['post_views_counter'] = ['views_counter', false];

		return $sortable_columns;
	}

	public function orderby_post_views_counter_column($vars)
	{

		if (isset($vars['orderby']) && 'views_counter' == $vars['orderby']) {

			$vars = array_merge($vars, array(

				'meta_key' => 'views_counter',
				'orderby' => 'meta_value_num',

			));
		}

		return $vars;
	}

	public function inc_post_views_counter()
	{


		global $post;

		if (is_singular('post')) {

			$views_counter = get_post_meta($post->ID, 'views_counter', true);

			if (empty($views_counter) || $views_counter === 0) {

				update_post_meta($post->ID, 'views_counter', 1);
			} else {

				update_post_meta($post->ID, 'views_counter', ++$views_counter);
			}
		}
	}
	/*
	public function enqueue_admin(){

		wp_enqueue_style('muPluginsStyle', plugins_url( '/assets/admin/style.css',__FILE__ ));
		
	}
*/
}
