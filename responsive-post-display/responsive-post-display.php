<?php
/**
 * Plugin Name: Responsive Post Display by Category
 * Plugin URI: https://cantercrow.com/
 * Description: A plugin to display posts by category in a responsive grid layout.
 * Version: 2.0
 * Author: Julian Haines
 * Author URI: https://cantercrow.com/
 * License: GPL2
 */

// Enqueue styles and scripts
function rpd_enqueue_styles_scripts() {
    wp_enqueue_style('rpd-styles', plugin_dir_url(__FILE__) . 'assets/styles.css');
}
add_action('wp_enqueue_scripts', 'rpd_enqueue_styles_scripts');


//////////////////////////////////////////
// Register shortcode
/////////////////////////////////////////
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
            // Title with a custom class
	   echo '<h3 class="rpd-title" style="font-size: ' . esc_attr($atts['title_font_size']) . ';">' . get_the_title() . '</h3>';

            
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

/////////////////////////////////////////////////////////
// Settings Page
////////////////////////////////////////////////////////

// Hook to add admin menu
add_action('admin_menu', 'rpd_add_admin_menu');

function rpd_add_admin_menu() {
    add_menu_page(
        'Responsive Posts Display', // Page title
        'RPD Settings',             // Menu title
        'manage_options',           // Capability
        'rpd-settings',             // Menu slug
        'rpd_settings_page',        // Callback function
        'dashicons-admin-generic',  // Icon
        20                          // Position
    );
}

// Callback function for the settings page

function rpd_settings_page() {

    // Save settings when form is submitted
    if (isset($_POST['rpd_save_settings'])) {
        update_option('rpd_columns', sanitize_text_field($_POST['rpd_columns']));
        update_option('rpd_image_width', sanitize_text_field($_POST['rpd_image_width']));
        update_option('rpd_image_height', sanitize_text_field($_POST['rpd_image_height']));
        update_option('rpd_posts_per_page', sanitize_text_field($_POST['rpd_posts_per_page']));
        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }


    // Get saved options
    $columns = get_option('rpd_columns', 3);
    $image_width = get_option('rpd_image_width', 300);
    $image_height = get_option('rpd_image_height', 200);
    $posts_per_page = get_option('rpd_posts_per_page', 6);
    ?>

    <div class="wrap">
        <h1>Responsive Posts Display Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rpd_columns">Number of Columns</label></th>
                    <td><input type="number" name="rpd_columns" id="rpd_columns" value="<?php echo esc_attr($columns); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rpd_image_width">Image Width</label></th>
                    <td><input type="number" name="rpd_image_width" id="rpd_image_width" value="<?php echo esc_attr($image_width); ?>" class="small-text"> px</td>
                </tr>
                <tr>
                    <th scope="row"><label for="rpd_image_height">Image Height</label></th>
                    <td><input type="number" name="rpd_image_height" id="rpd_image_height" value="<?php echo esc_attr($image_height); ?>" class="small-text"> px</td>
                </tr>
                <tr>
                    <th scope="row"><label for="rpd_posts_per_page">Posts Per Page</label></th>
                    <td><input type="number" name="rpd_posts_per_page" id="rpd_posts_per_page" value="<?php echo esc_attr($posts_per_page); ?>" class="small-text"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="rpd_save_settings" id="submit" class="button button-primary" value="Save Settings">
            </p>
        </form>
        
        <h2>Plugin Information</h2>
        <p><strong>Shortcode Usage:</strong></p>
        <code>[responsive_posts category="news" columns="3" image_width="300" image_height="200" posts_per_page="6"]</code>
        <p>Use this shortcode to display posts dynamically, you can override global settings by specifying attributes in the shortcode.</p>
        <p></p>
        <p>Written by Julian Haines <a href="https://cantercrow.com">https://cantercrow.com</a></p>
    </div>
    <?php
}

