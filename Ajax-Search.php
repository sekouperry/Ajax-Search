<?php

/**
 * Plugin Name: CodeWP Ajax Search
 * Plugin URI: https://codewp.ai
 * Description: An advanced Ajax search solution for WordPress.
 * Version: 1.0.0
 * Author: Sekou Perry
 * Author URI: https://codewp.ai
 * Text Domain: codewp
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Cwpai_Ajax_Search {

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('cwpai_ajax_search', [$this, 'render_search_form']);
        add_action('rest_api_init', [$this, 'register_rest_route']);
    }

    public function enqueue_assets() {
        wp_enqueue_script('cwpai-ajax-search-js', '', [], false, true);
        wp_add_inline_script('cwpai-ajax-search-js', "jQuery(document).ready(function($) {
            $('#cwpai-search-form').submit(function(e) {
                e.preventDefault();
                var searchTerm = $('#cwpai-search-input').val();
                $.ajax({
                    url: '/wp-json/cwpai/v1/search',
                    type: 'POST',
                    data: { term: searchTerm },
                    success: function(data) {
                        $('#cwpai-search-results').html(data);
                    }
                });
            });
        });");
        wp_enqueue_style('cwpai-ajax-search-css', false);
        wp_add_inline_style('cwpai-ajax-search-css', "#cwpai-search-form { /* Your CSS styles here */ }");
    }

    public function render_search_form() {
        return '<form id="cwpai-search-form"><input type="text" id="cwpai-search-input" /><input type="submit" value="Search" /></form><div id="cwpai-search-results"></div>';
    }

    public function register_rest_route() {
        register_rest_route('cwpai/v1', '/search', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_search_request'],
            'permission_callback' => '__return_true'
        ]);
    }

    public function handle_search_request($request) {
        global $wpdb;
        $term = $request['term'];
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->posts} WHERE post_content LIKE %s ORDER BY post_date DESC LIMIT 3", '%' . $wpdb->esc_like($term) . '%');
        $posts = $wpdb->get_results($sql);
        $output = '';
        foreach ($posts as $post) {
            $output .= '<div>' . esc_html($post->post_title) . '</div>';
        }
        return $output;
    }
}

new Cwpai_Ajax_Search();

