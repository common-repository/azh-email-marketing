<?php

add_action('wp_insert_post', 'aze_email_subscribers_insert_post', 10, 3);

function aze_email_subscribers_insert_post($post_id, $post, $update) {
    if ($post->post_type == 'aze_email_template') {
        if (class_exists('es_cls_sendmail')) {
            $id = get_post_meta($post->ID, '_email_subscribers', true);
            if ($id) {
                wp_update_post(array(
                    'ID' => $id,
                    'post_title' => $post->post_title,
                    'post_content' => $post->post_content,
                ));
            } else {
                $es_post = array(
                    'post_title' => $post->post_title,
                    'post_content' => $post->post_content,
                    'post_status' => 'publish',
                    'post_type' => 'es_template',
                    'meta_input' => array('es_template_type' => 'Newsletter')
                );
                $last_inserted_id = wp_insert_post($es_post);
                update_post_meta($post->ID, '_email_subscribers', $last_inserted_id);
            }
        }
    }
}
