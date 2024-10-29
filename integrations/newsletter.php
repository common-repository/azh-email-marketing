<?php

add_action('wp_insert_post', 'aze_newsletter_insert_post', 10, 3);

function aze_newsletter_insert_post($post_id, $post, $update) {
    if ($post->post_type == 'aze_email_template') {
        if (class_exists('NewsletterEmails')) {
            $id = get_post_meta($post->ID, '_newsletter', true);
            $email = array();
            if ($id) {
                $email['id'] = $id;
            } else {
                $email['status'] = 'new';
                $email['track'] = 1;
                $email['token'] = NewsletterEmails::instance()->get_token();

                $email['message_text'] = 'This email requires a modern e-mail reader but you can view the email online here:
{email_url}.

Thank you, ' . wp_specialchars_decode(get_option('blogname'), ENT_QUOTES) . '

To change your subscription follow: {profile_url}.';

                $email['options'] = array();

                $email['type'] = 'message';
                $email['send_on'] = time();
                $email['query'] = "select * from " . NEWSLETTER_USERS_TABLE . " where status='C'";
            }
            $email['message'] = $post->post_content;
            $email['subject'] = $post->post_title;
            $email = Newsletter::instance()->save_email($email, ARRAY_A);
            if (!$id) {
                update_post_meta($post->ID, '_newsletter', $email['id']);
            }            
        }
    }
}
