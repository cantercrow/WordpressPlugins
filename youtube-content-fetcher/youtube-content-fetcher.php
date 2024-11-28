<?php
/**
 * Plugin Name: YouTube Content Fetcher
 * Description: Fetches videos from YouTube and posts them to your WordPress site.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class YouTubeContentFetcher {

    private $api_key = 'AIzaSyARV6bUc6WgcBjQ7lfUkYeDEe_5i-GFA_s'; // Replace with your API key.
    private $base_url = 'https://www.googleapis.com/youtube/v3/';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_post_fetch_youtube_videos', [ $this, 'fetch_and_post_videos' ] );
    }

    public function add_admin_menu() {
        add_menu_page(
            'YouTube Content Fetcher',
            'YouTube Fetcher',
            'manage_options',
            'youtube-content-fetcher',
            [ $this, 'admin_page_content' ],
            'dashicons-youtube',
            100
        );
    }

    public function admin_page_content() {
        ?>
        <div class="wrap">
            <h1>YouTube Content Fetcher</h1>
            <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                <input type="hidden" name="action" value="fetch_youtube_videos">
                <label for="channel_id">YouTube Channel ID:</label>
                <input type="text" name="channel_id" id="channel_id" required>
                <br><br>
                <label for="max_results">Number of Videos:</label>
                <input type="number" name="max_results" id="max_results" value="5" min="1" max="50">
                <br><br>
                <?php submit_button( 'Fetch Videos' ); ?>
            </form>
        </div>
        <?php
    }

    public function fetch_and_post_videos() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        $channel_id = sanitize_text_field( $_POST['channel_id'] );
        $max_results = intval( $_POST['max_results'] );

        $url = $this->base_url . 'search?part=snippet&channelId=' . $channel_id . '&maxResults=' . $max_results . '&type=video&key=' . $this->api_key;

        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) {
            wp_die( 'Failed to fetch data from YouTube.' );
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $data['items'] ) ) {
            foreach ( $data['items'] as $item ) {
                $title = sanitize_text_field( $item['snippet']['title'] );
                $description = sanitize_textarea_field( $item['snippet']['description'] );
                $video_id = sanitize_text_field( $item['id']['videoId'] );
                $video_url = 'https://www.youtube.com/watch?v=' . $video_id;

                // Check if a post with the same video ID already exists
                $existing_post = get_posts( [
                    'meta_key' => '_youtube_video_id',
                    'meta_value' => $video_id,
                    'post_type' => 'post',
                    'post_status' => 'publish',
                    'numberposts' => 1
                ] );

                if ( $existing_post ) {
                    continue;
                }

                // Create a new post
                $post_id = wp_insert_post( [
                    'post_title'   => $title,
                    'post_content' => $description . "\n\nWatch on YouTube: <a href='$video_url'>$video_url</a>",
                    'post_status'  => 'publish',
                    'post_author'  => get_current_user_id(),
                ] );

                if ( $post_id ) {
                    // Add a meta field to store the YouTube video ID
                    add_post_meta( $post_id, '_youtube_video_id', $video_id, true );
                }
            }

            wp_redirect( admin_url( 'admin.php?page=youtube-content-fetcher&success=1' ) );
            exit;
        } else {
            wp_die( 'No videos found for the given channel.' );
        }
    }
}

new YouTubeContentFetcher();
