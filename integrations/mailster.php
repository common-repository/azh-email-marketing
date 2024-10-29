<?php

add_action('wp_insert_post', 'aze_mailster_insert_post', 10, 3);

function aze_mailster_insert_post($post_ID, $post, $update) {
    if($post->post_type == 'aze_email_template') {
        
    }
}