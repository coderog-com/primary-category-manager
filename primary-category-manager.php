<?php
/*
Plugin Name: Primary Category Manager
Description: Display and change the primary category on the WordPress post list.
Version: 1.0
Author: CodeROG
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Primary_Category_Manager {

    public function __construct() {
        add_filter('manage_posts_columns', array($this, 'add_primary_category_column'));
        add_action('manage_posts_custom_column', array($this, 'show_primary_category_column'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_primary_category_script'));
        add_action('wp_ajax_update_primary_category', array($this, 'update_primary_category'));
    }

    public function add_primary_category_column($columns) {
        $columns['primary_category'] = 'Primary Category';
        return $columns;
    }

    public function show_primary_category_column($column_name, $post_id) {
        if ($column_name == 'primary_category') {
            $primary_category = $this->get_post_primary_category($post_id); // WP core
            
            // $categories = get_the_category($post_id);
            $categories = get_categories();

            // if ($primary_category) {
            //     echo '<span class="primary-category-name"><b>WP Core: </b>' . $primary_category . '</span><br>';
            //     echo '<select class="primary-category-dropdown" data-post-id="' . $post_id . '" style="display:non;">';
            //     foreach ($categories as $category) {
            //         echo '<option value="' . $category->term_id . '"' . ($category->name == $primary_category ? ' selected' : '') . '>' . $category->name . '</option>';
            //     }
            //     echo '</select><br>';
            // } else {
            //     echo '—';
            // }
            
            if ($this->is_active_yoast()) { // Yoast
                
                echo '<div class="primary-category-name-div">';
                echo '<span class="service-bypassed"><span class="dashicons dashicons-wordpress-alt pcm-brand-icon"></span><b>WP Core: </b><span class="primary-category-name">' . $primary_category . '</span><br></span>';
                echo '</div>';
                
                $primary_category_yoast = $this->get_yoast_primary_category($post_id); // Yoast
                
                if (empty($primary_category_yoast)) {
                    $primary_category_yoast = null;
                }
                
                echo '<div class="primary-category-name-div primary-category-name-div-yoast">
                <svg class="pcm-yoast-icon pcm-brand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M91.3 76h186l-7 18.9h-179c-39.7 0-71.9 31.6-71.9 70.3v205.4c0 35.4 24.9 70.3 84 70.3V460H91.3C41.2 460 0 419.8 0 370.5V165.2C0 115.9 40.7 76 91.3 76zm229.1-56h66.5C243.1 398.1 241.2 418.9 202.2 459.3c-20.8 21.6-49.3 31.7-78.3 32.7v-51.1c49.2-7.7 64.6-49.9 64.6-75.3 0-20.1 .6-12.6-82.1-223.2h61.4L218.2 299 320.4 20zM448 161.5V460H234c6.6-9.6 10.7-16.3 12.1-19.4h182.5V161.5c0-32.5-17.1-51.9-48.2-62.9l6.7-17.6c41.7 13.6 60.9 43.1 60.9 80.5z"/></svg>
                <b>Yoast: </b><span class="primary-category-name-yoast" data-post-id="' . $post_id . '">' . $primary_category_yoast . '</span><br></div>';
                
                echo '<div class="primary-category-dropdown-div" data-post-id="' . $post_id . '">';
                // echo '<div>';
                echo '<select class="primary-category-dropdown" data-post-id="' . $post_id . '" style="display:non;">';
                echo '<option value=""' . (!$primary_category_yoast ? ' selected' : '') . '> - </option>';

                foreach ($categories as $category) {
                    echo '<option value="' . $category->term_id . '"' . ($category->name == $primary_category_yoast ? ' selected' : '') . '>' . $category->name . '</option>';
                }
                
                echo '</select>';
                echo '<img class="primary-category-dropdown-loading-img" data-post-id="' . $post_id . '" src="https://coderog.com/blog/wp-includes/images/spinner.gif" />';
                
                echo '<span class="primary-category-dropdown-status-img dashicons" data-post-id="' . $post_id . '"></span>';
                echo '</div><br>';
                
            }
            
        }
    }

    private function get_post_primary_category($post_id) {
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            return $categories[0]->name;
        }
        return false;
    }

    public function enqueue_primary_category_script($hook) {
        if ('edit.php' != $hook) {
            return;
        }
        wp_enqueue_script('primary-category-script', plugin_dir_url(__FILE__) . 'js/primary-category.js', array('jquery'), null, true);
        wp_enqueue_style('primary-category-style', plugin_dir_url(__FILE__) . 'css/primary-category.css');
        wp_localize_script('primary-category-script', 'primaryCategoryAjax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function update_primary_category() {
        $post_id = intval($_POST['post_id']);
        $category_id = intval($_POST['category_id']);

        if ($post_id && $category_id) {
            $current_categories = wp_get_post_categories($post_id);
            $current_categories[] = $category_id;

            $current_categories = $this->get_reindexed_category_array($current_categories, $category_id);
            $current_categories = array_unique($current_categories);
            
            wp_set_post_categories($post_id, $current_categories, false);
            
            // yoast
            $this->update_yoast_primary_category($post_id, $category_id);
            
            echo 'success';
        } else {
            echo 'error';
        }
        wp_die();
    }
    
    function update_yoast_primary_category($post_id, $category_id) {
        // Check if Yoast SEO plugin is active
        if (class_exists('WPSEO_Primary_Term')) {
            // Set the primary category term ID
            $primary_term = new WPSEO_Primary_Term('category', $post_id);
            $primary_term->set_primary_term($category_id);
        }
    }
    
    function get_yoast_primary_category($post_id) {
        $primary_category = '';
        
        // Check if Yoast SEO plugin is active
        if (class_exists('WPSEO_Primary_Term')) {
            // Get the primary category term ID
            $primary_term = new WPSEO_Primary_Term('category', $post_id);
            $primary_term_id = $primary_term->get_primary_term();
    
            if ($primary_term_id) {
                $primary_category = get_term($primary_term_id);
            }
        }
        
        if (!empty($primary_category)) {
            return $primary_category->name;
        }
        
        return false;
    }
    
    //  public function update_primary_category() {
    //     $post_id = intval($_POST['post_id']);
    //     $category_id = intval($_POST['category_id']);

    //     if ($post_id && $category_id) {
    //         wp_set_post_categories($post_id, array($category_id), false);
    //         update_post_meta($post->ID,’_yoast_wpseo_primary_category’,$term_id);
    // wpseo_primary_term_taxonomies
    //         echo 'success';
    //     } else {
    //         echo 'error';
    //     }
    //     wp_die();
    // }
    
    function get_reindexed_category_array($current_categories, $category_id) {
        // Remove the $category_id if it already exists to avoid duplication
        if (($key = array_search($category_id, $current_categories)) !== false) {
            unset($current_categories[$key]);
        }
        
        // Add $category_id at the beginning of the array
        // array_unshift($current_categories, $category_id);
        array_push($current_categories, $category_id);
        
        // Re-index the array to ensure the keys are consecutive integers
        $current_categories = array_values($current_categories);
        
        return $current_categories;
        
        // Now $current_categories has $category_id as the first element
    }
    
    function is_active_yoast() {
        if ( class_exists('WPSEO_Meta') ) {
            return true;
        } else {
            return false;
        }
    }

}

new Primary_Category_Manager();
