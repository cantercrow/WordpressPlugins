<?php
/**
 * Plugin Name: Responsive Post Display by Category
 * Plugin URI: https://example.com
 * Description: A plugin to display posts by category in a responsive grid layout.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

// Enqueue styles and scripts
function rpd_enqueue_styles_scripts() {
    wp_enqueue_style('rpd-styles', plugin_dir_url(__FILE__) . 'assets/styles.css');
}
add_action('wp_enqueue_scripts', 'rpd_enqueue_styles_scripts');

// Register shortcode

function rpd_display_posts($atts) {
    // Attributes with default values
    $atts = shortcode_atts([
        'category' => '',
        'columns' => 3,
        'image_width' => 300,
        'image_height' => 200,
        'posts_per_page' => 6,
        'excerpt_length' => 20,
        'title_font_size' => '18px', // Default title font size
        'post_padding' => '16px',    // Default padding for posts
        'image_padding' => '8px',    // Default padding for images
    ], $atts, 'responsive_posts');

    // Query posts
    $query = new WP_Query([
        'category_name' => $atts['category'],
        'posts_per_page' => $atts['posts_per_page'],
    ]);

    // Output posts
    ob_start();
    if ($query->have_posts()) {
        echo '<div class="rpd-grid" style="--columns: ' . intval($atts['columns']) . ';">';
        while ($query->have_posts()) {
            $query->the_post();
            $image_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
            $image_url = $image_url ?: 'https://via.placeholder.com/' . $atts['image_width'] . 'x' . $atts['image_height'];
            echo '<div class="rpd-item" style="padding: ' . esc_attr($atts['post_padding']) . ';">';
            echo '<a href="' . get_the_permalink() . '">';
            
            // Image with padding
            echo '<div style="padding: ' . esc_attr($atts['image_padding']) . ';">';
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr(get_the_title()) . '" style="width: ' . esc_attr($atts['image_width']) . 'px; height: ' . esc_attr($atts['image_height']) . 'px;">';
            echo '</div>';

            // Title
            echo '<h3 style="font-size: ' . esc_attr($atts['title_font_size']) . ';">' . get_the_title() . '</h3>';
            
            // Display excerpt with word limit
            $excerpt = wp_trim_words(get_the_excerpt(), intval($atts['excerpt_length']), '...');
            echo '<p class="rpd-excerpt">' . esc_html($excerpt) . '</p>';

            // Add "Read More" link
            echo '<a href="' . get_the_permalink() . '" class="rpd-read-more">Read More</a>';

            echo '</a>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>No posts found in this category.</p>';
    }
    wp_reset_postdata();

    return ob_get_clean();
}

add_shortcode('responsive_posts', 'rpd_display_posts');
