<?php
register_activation_hook(__FILE__, 'aze_activate');

function aze_activate() {
    if (!wp_next_scheduled('aze_process_campaigns')) {
        wp_schedule_event(time(), 'every_one_minute', 'aze_process_campaigns');
    }
}

add_filter('cron_schedules', 'aze_cron_schedules');

function aze_cron_schedules($schedules) {
    $schedules['every_one_minute'] = array('interval' => 1 * MINUTE_IN_SECONDS, 'display' => __('Every one minute', 'aze'));
    return $schedules;
}

add_action('init', 'aze_campaign');

function aze_campaign() {
    if (defined('AZF_VERSION')) {
        register_post_type('aze_email_campaign', array(
            'labels' => array(
                'name' => __('Email campaign', 'aze'),
                'singular_name' => __('Email campaign', 'aze'),
                'add_new' => __('Add Email campaign', 'aze'),
                'add_new_item' => __('Add New Email campaign', 'aze'),
                'edit_item' => __('Edit Email campaign', 'aze'),
                'new_item' => __('New Email campaign', 'aze'),
                'view_item' => __('View Email campaign', 'aze'),
                'search_items' => __('Search Email campaigns', 'aze'),
                'not_found' => __('No Email campaign found', 'aze'),
                'not_found_in_trash' => __('No Email campaign found in Trash', 'aze'),
                'parent_item_colon' => __('Parent Email campaign:', 'aze'),
                'menu_name' => __('Email campaigns', 'aze'),
            ),
            'query_var' => true,
            'rewrite' => array('slug' => 'campaign'),
            'hierarchical' => true,
            'supports' => array('title'),
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'show_in_menu' => true,
            'public' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
                )
        );
    }
}

function aze_lead_nonce($action, $id) {
    if ($id) {
        $hash = get_post_meta($id, '_hash', true);
        return substr(wp_hash($action . '|' . $id . '|' . $hash, 'nonce'), -12, 10);
    }
    return '';
}

function aze_get_receivers($campaign_id, $meta_query = array()) {
    $user_id = get_current_user_id();
    $page = get_post_meta($campaign_id, 'page', true);
    $form_title = get_post_meta($campaign_id, 'form_title', true);
    $email_field = get_post_meta($campaign_id, '_email_field', true);
    $args = array(
        'post_type' => 'azf_submission',
        'post_status' => 'publish',
        'author' => $user_id,
        'ignore_sticky_posts' => 1,
        'offset' => 0,
        'posts_per_page' => 10,
        'meta_query' => array(
            array(
                'key' => $email_field,
                'compare' => 'EXISTS'
            )
        )
    );
    if (!empty($page)) {
        $args['post_parent__in'] = (array) $page;
    }
    if (!empty($form_title)) {
        $args['meta_query'][] = array(
            'key' => 'form_title',
            'value' => (array) $form_title,
            'compare' => 'IN'
        );
    }
    foreach ($meta_query as $mq) {
        $args['meta_query'][] = $mq;
    }

    $query = new WP_Query($args);

    return $query;
}

add_action('phpmailer_init', 'aze_phpmailer_init');

function aze_phpmailer_init($phpmailer) {
    $phpmailer->Sender = $phpmailer->From;
}

function aze_send_email($campaign, $lead) {
    $lead_id = false;
    $lead_meta = false;
    if (is_object($lead)) {
        $lead_id = $lead->ID;
        $lead_meta = get_post_meta($lead->ID);
        foreach ($lead_meta as $key => &$value) {
            $value = reset($value);
        }
    }
    $email_field = get_post_meta($campaign->ID, '_email_field', true);
    $email_template = get_post_meta($campaign->ID, '_email_template', true);
    if (!$email_template) {
        return false;
    }
    $email_template = get_post($email_template);
    $subject = $email_template->post_title;
    $from_email = get_post_meta($campaign->ID, '_from_email', true);
    $from_name = get_post_meta($campaign->ID, '_from_name', true);
    $reply_to = get_post_meta($campaign->ID, '_reply_to', true);
    $unsubscribe_page_id = get_post_meta($campaign->ID, '_unsubscribe_page', true);
    $unsubscribe_url = add_query_arg(array('unsubscribe' => aze_lead_nonce('aze-unsubscribe', $lead_id), 'lead_id' => $lead_id, 'campaign' => $campaign->ID), get_permalink($unsubscribe_page_id));

    //prevent doubles
    if (isset($lead_meta[$email_field])) {
        $leads = get_posts(array(
            'post_type' => 'azf_submission',
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => $email_field,
                    'value' => $lead_meta[$email_field]
                ),
                array(
                    'key' => '_email_campaign_' . $campaign->ID,
                    'value' => 'sent'
                ),
            )
        ));
        if (!empty($leads)) {
            update_post_meta($lead->ID, '_email_campaign_' . $campaign->ID, 'sent');
            return true;
        }
    }

    $styles = false;
    $stylesheets = false;
    static $styles_cache = array();
    if (isset($styles_cache[$email_template->ID])) {
        $styles = $styles_cache[$email_template->ID];
    }
    static $stylesheets_cache = array();
    if (isset($stylesheets_cache[$email_template->ID])) {
        $stylesheets = $stylesheets_cache[$email_template->ID];
    }
    if ((!$styles || !$stylesheets) && function_exists('azh_filesystem')) {
        azh_filesystem();
        global $wp_filesystem;
        $styles = get_post_meta($email_template->ID, '_styles', true);
        if ($styles) {
            $styles = $wp_filesystem->get_contents($styles);
            $styles_cache[$email_template->ID] = $styles;
        }
        $stylesheets = get_post_meta($email_template->ID, '_stylesheets', true);
        if ($stylesheets) {
            $stylesheets = $wp_filesystem->get_contents($stylesheets);
            $stylesheets_cache[$email_template->ID] = $stylesheets;
        }
    }
    $content = azh_get_post_content($email_template);

    if (is_object($lead)) {
        $content = apply_filters('aze_send_email_content', $content, $campaign, $lead, $lead_meta);
    }

    if ($lead_meta) {
        foreach ($lead_meta as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
    }

    $unsubscribe = true;
    if (strpos($content, '{unsubscribe}') !== false) {
        $content = str_replace('{unsubscribe}', $unsubscribe_url, $content);
        $unsubscribe = false;
    }
    if (strpos($content, 'sr_unsubscribe') !== false) {
        $content = str_replace('sr_unsubscribe', $unsubscribe_url, $content); //StampReady
        $unsubscribe = false;
    }


    $tracking_url = add_query_arg(array('open' => aze_lead_nonce('aze-open', $lead_id), 'lead_id' => $lead_id, 'campaign' => $campaign->ID), get_permalink($email_template));
    $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
            . '<html xmlns="http://www.w3.org/1999/xhtml">'
            . '<head>'
            . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
            . '<meta name="viewport" content="width=device-width" />'
            . '<title>' . $subject . '</title>'
            . $stylesheets
            . '<style type="text/css">'
            . $styles
            . '</style>'
            . '</head>'
            . '<body>'
            . $content
            . ($unsubscribe ? ('<a href="' . $unsubscribe_url . '">' . __('Unsubscribe', 'aze') . '</a>') : '')
            . '<img src="' . $tracking_url . '" alt="" width="1" height="1">'
            . '</body>'
            . '</html>';


    $headers = array();
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    if ($from_email) {
        if ($from_name) {
            $headers[] = 'From: "' . $from_name . '" <' . $from_email . '>';
        } else {
            $headers[] = 'From: ' . $from_email;
        }
    }
    if ($reply_to) {
        $headers[] = 'Reply-to: ' . $reply_to;
    }
    //$headers[] = 'Return-path: <' . $bounce_to . '>'; //bounce email
    $headers[] = 'Precedence: bulk';
    $headers[] = 'X-Message-ID: ' . get_post_meta($lead_id, '_hash', true);
    $headers[] = 'List-unsubscribe: <mailto:' . $reply_to . '?subject=' . __('Unsubscribe', 'aze') . '>, <' . add_query_arg(array('unsubscribe' => aze_lead_nonce('aze-unsubscribe', $lead_id), 'lead_id' => $lead_id, 'campaign' => $campaign->ID), get_permalink($unsubscribe_page_id)) . '>';
    if (is_object($lead)) {
        if (!empty($lead_meta[$email_field]) && wp_mail($lead_meta[$email_field], $subject, $message, $headers)) {
            update_post_meta($lead->ID, '_email_campaign_' . $campaign->ID, 'sent');
            update_post_meta($lead->ID, '_email_campaign_' . $campaign->ID . '_sent', time());
            $sent = get_post_meta($campaign->ID, '_sent', true);
            $sent++;
            update_post_meta($campaign->ID, '_open', $sent);
        } else {
            update_post_meta($lead->ID, '_email_campaign_' . $campaign->ID, 'failed');
        }
    } else {
        if (is_string($lead)) {
            return wp_mail($lead, $subject, $message, $headers);
        }
    }
}

add_action('aze_process_campaigns', 'aze_process_campaigns');

function aze_process_campaigns() {
    $send_delay = 0.1;
    $send_start_time = microtime(true);


    $campaigns = get_posts(array(
        'post_type' => 'aze_email_campaign',
        'post_status' => 'publish',
        'ignore_sticky_posts' => 1,
        'no_found_rows' => 1,
        'posts_per_page' => -1,
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_status',
                'value' => 'running'
            )
        )
    ));
    if (!empty($campaigns)) {
        foreach ($campaigns as $campaign) {
            $leads = aze_get_receivers($campaign->ID, array(
                array(
                    'key' => '_email_campaign_' . $campaign->ID,
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_unsubscribed',
                    'compare' => 'NOT EXISTS'
                ),
            ));
            if ($leads->post_count) {
                foreach ($leads->posts as $lead) {

                    aze_send_email($campaign, $lead);

                    if ($send_delay) {
                        usleep(max(1, round(( $send_delay - ( microtime(true) - $send_start_time )), 3) * 1000000));
                    }
                }
            } else {
                update_post_meta($campaign->ID, '_status', 'finished');
            }
        }
    }
}

add_action('init', 'aze_open');

function aze_open() {
    if (isset($_GET['open']) && isset($_GET['lead_id']) && is_numeric($_GET['lead_id']) && isset($_GET['campaign']) && is_numeric($_GET['campaign'])) {
        if ($_GET['open'] == aze_lead_nonce('aze-open', (int) $_GET['lead_id'])) {
            if (!get_post_meta((int) $_GET['lead_id'], '_email_campaign_' . (int) $_GET['campaign'] . '_open', true)) {
                update_post_meta((int) $_GET['lead_id'], '_email_campaign_' . (int) $_GET['campaign'] . '_open', time());

                if (isset($_GET['campaign']) && is_numeric($_GET['campaign'])) {
                    $open = get_post_meta((int) $_GET['campaign'], '_open', true);
                    $open++;
                    update_post_meta((int) $_GET['campaign'], '_open', $open);
                }
            }

            header('Content-Type: image/png');
            print base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
            exit;
        }
    }
}

add_filter('aze_send_email_content', 'aze_click_replace', 10, 3);

function aze_click_replace($content, $campaign, $lead) {
    $content = str_replace('click=click', 'campaign=' . $campaign->ID . '&lead_id=' . $lead->ID . '&click=' . aze_lead_nonce('aze-click', $lead->ID), $content);
    return $content;
}

add_action('template_redirect', 'aze_click');

function aze_click() {
    if (isset($_GET['click']) && isset($_GET['campaign']) && is_numeric($_GET['campaign']) && isset($_GET['lead_id']) && is_numeric($_GET['lead_id'])) {
        if ($_GET['click'] == aze_lead_nonce('aze-click', (int) $_GET['lead_id'])) {
            if (!get_post_meta((int) $_GET['lead_id'], '_email_campaign_' . (int) $_GET['campaign'] . '_click', true)) {
                update_post_meta((int) $_GET['lead_id'], '_email_campaign_' . (int) $_GET['campaign'] . '_click', time());
                if (isset($_GET['campaign']) && is_numeric($_GET['campaign'])) {
                    $click = get_post_meta((int) $_GET['campaign'], '_clicks', true);
                    $click++;
                    update_post_meta((int) $_GET['campaign'], '_clicks', $click);
                }

                if (isset($_GET['redirect'])) {
                    exit(wp_redirect($_GET['redirect']));
                }
            }
        }
    }
}

add_action('init', 'aze_unsubscribe');

function aze_unsubscribe() {
    if (isset($_GET['unsubscribe'])) {
        if (isset($_GET['lead_id']) && is_numeric($_GET['lead_id'])) {
            if ($_GET['unsubscribe'] == aze_lead_nonce('aze-unsubscribe', (int) $_GET['lead_id'])) {
                if (!get_post_meta((int) $_GET['lead_id'], '_unsubscribed', true)) {
                    update_post_meta((int) $_GET['lead_id'], '_unsubscribed', time());
                    if (isset($_GET['campaign']) && is_numeric($_GET['campaign'])) {
                        $unsubscribes = get_post_meta((int) $_GET['campaign'], '_unsubscribes', true);
                        $unsubscribes++;
                        update_post_meta((int) $_GET['campaign'], '_unsubscribes', $unsubscribes);
                    }
                }
            }
        }
    }
}

add_action('wp_mail_failed', 'aze_mail_failed');

function aze_mail_failed($error) {
    global $aze_mail_failed;
    $aze_mail_failed = $error->get_error_message();
}

add_action('wp_ajax_aze_test_campaign', 'aze_test_campaign');

function aze_test_campaign() {
    $user_id = get_current_user_id();
    if ($user_id) {
        if (isset($_POST['campaign']) && is_numeric($_POST['campaign'])) {
            $campaign = get_post((int) $_POST['campaign']);
            $current_user = wp_get_current_user();
            if (aze_send_email($campaign, $current_user->user_email)) {
                print $current_user->user_email;
            } else {
                global $aze_mail_failed;
                if ($aze_mail_failed) {
                    print $aze_mail_failed;
                }
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_aze_run_campaign', 'aze_run_campaign');

function aze_run_campaign() {
    if (isset($_POST['campaign']) && is_numeric($_POST['campaign'])) {
        $user_id = get_current_user_id();
        if ($user_id) {
            $campaign = get_post((int) $_POST['campaign']);
            if ($campaign->post_author == $user_id) {
                update_post_meta((int) $_POST['campaign'], '_status', 'running');
                print 'running';
            }
        }
    }
    wp_die();
}

add_action('wp_ajax_aze_pause_campaign', 'aze_pause_campaign');

function aze_pause_campaign() {
    if (isset($_POST['campaign']) && is_numeric($_POST['campaign'])) {
        $user_id = get_current_user_id();
        if ($user_id) {
            $campaign = get_post((int) $_POST['campaign']);
            if ($campaign->post_author == $user_id) {
                update_post_meta((int) $_POST['campaign'], '_status', 'paused');
                print 'paused';
            }
        }
    }
    wp_die();
}

add_action('wp_insert_post', 'aze_campaign_insert_post', 10, 3);

function aze_campaign_insert_post($post_id, $post, $update) {
    if ($post->post_type == 'aze_email_campaign') {
        $receivers = aze_get_receivers($post->ID)->found_posts;
        update_post_meta($post->ID, '_receivers', $receivers);
        $sent = aze_get_receivers($post->ID, array(array('key' => '_email_campaign_' . $post->ID, 'value' => 'sent')))->found_posts;
        update_post_meta($post->ID, '_sent', $sent);
        $open = aze_get_receivers($post->ID, array(array('key' => '_email_campaign_' . $post->ID . '_open', 'compare' => 'EXISTS')))->found_posts;
        update_post_meta($post->ID, '_open', $open);
        $clicks = aze_get_receivers($post->ID, array(array('key' => '_email_campaign_' . $post->ID . '_click', 'compare' => 'EXISTS')))->found_posts;
        update_post_meta($post->ID, '_clicks', $clicks);
        $unsubscribes = aze_get_receivers($post->ID, array(array('key' => '_unsubscribed', 'compare' => 'EXISTS')))->found_posts;
        update_post_meta($post->ID, '_unsubscribes', $unsubscribes);
    }
}

add_filter('manage_aze_email_campaign_posts_columns', 'aze_email_campaign_columns');

function aze_email_campaign_columns($columns) {
    $columns['status'] = __('Status', 'aze');
    $columns['receivers'] = __('Receivers', 'aze');
    $columns['sent'] = __('Sent', 'aze');
    $columns['open'] = __('Open', 'aze');
    $columns['clicks'] = __('Clicks', 'aze');
    $columns['unsubscribes'] = __('Unsubscribes', 'aze');
    return $columns;
}

add_action('manage_aze_email_campaign_posts_custom_column', 'aze_email_campaign_custom_columns', 10, 2);

function aze_email_campaign_custom_columns($column, $post_id) {
    switch ($column) {
        case 'status' :
            ?>
            <div data-campaign="<?php print $post_id; ?>" data-status="<?php print get_post_meta($post_id, '_status', true); ?>">
                <div class="aze-draft"><b><?php esc_html_e('Draft', 'aze'); ?></b></div>
                <div class="aze-running"><b><?php esc_html_e('Running', 'aze'); ?></b></div>
                <div class="aze-paused"><b><?php esc_html_e('Paused', 'aze'); ?></b></div>
                <div class="aze-finished"><b><?php esc_html_e('Finished', 'aze'); ?></b></div>
                <div class="aze-pause button button-primary button-large"><?php esc_html_e('Pause', 'aze'); ?></div>
                <div class="aze-run button button-primary button-large"><?php esc_html_e('Run', 'aze'); ?></div>
            </div>
            <?php
            break;
        case 'receivers' :
            $receivers = get_post_meta($post_id, '_receivers', true);
            print $receivers ? $receivers : 0;
            break;
        case 'sent' :
            $sent = get_post_meta($post_id, '_sent', true);
            print $sent ? $sent : 0;
            break;
        case 'open' :
            $open = get_post_meta($post_id, '_open', true);
            print $open ? $open : 0;
            break;
        case 'clicks' :
            $clicks = get_post_meta($post_id, '_clicks', true);
            print $clicks ? $clicks : 0;
            break;
        case 'unsubscribes' :
            $unsubscribes = get_post_meta($post_id, '_unsubscribes', true);
            print $unsubscribes ? $unsubscribes : 0;
            break;
    }
}

add_action('add_meta_boxes', 'aze_campaign_meta_boxes', 10, 2);

function aze_campaign_meta_boxes($post_type, $post) {
    if ($post_type === 'aze_email_campaign') {
        add_meta_box('aze', __('Campaign settings', 'aze'), 'aze_campaign_meta_box', $post_type, 'advanced', 'default');
    }
}

add_action('save_post', 'aze_save_post', 10, 3);

function aze_save_post($post_id, $post, $update) {
    if (!isset($_POST['_aze_nonce']) || !wp_verify_nonce($_POST['_aze_nonce'], basename(__FILE__))) {
        return;
    }
    update_post_meta($post_id, '_from_email', sanitize_text_field($_POST['_from_email']));
    update_post_meta($post_id, '_from_name', sanitize_text_field($_POST['_from_name']));
    update_post_meta($post_id, '_reply_to', sanitize_text_field($_POST['_reply_to']));
    update_post_meta($post_id, '_email_template', sanitize_text_field($_POST['_email_template']));
    update_post_meta($post_id, '_form_title', sanitize_text_field($_POST['_form_title']));
    update_post_meta($post_id, '_email_field', sanitize_text_field($_POST['_email_field']));
    update_post_meta($post_id, '_unsubscribe_page', sanitize_text_field($_POST['_unsubscribe_page']));
}

add_action('admin_enqueue_scripts', 'aze_campaign_admin_scripts');

function aze_campaign_admin_scripts() {
    if (isset($_GET['post_type']) && $_GET['post_type'] == 'aze_email_campaign') {
        wp_enqueue_style('aze_admin', plugins_url('css/admin.css', __FILE__));
        wp_enqueue_script('aze_admin', plugins_url('js/admin.js', __FILE__), array('jquery'), false, true);
        wp_localize_script('aze_admin', 'aze', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'i18n' => array(
                'running' => __('Running', 'aze'),
                'paused' => __('Paused', 'aze'),
                'finished' => __('Finished', 'aze'),
            ),
        ));
    }
}

function aze_campaign_meta_box($post = NULL, $metabox = NULL, $post_type = 'page') {
    wp_enqueue_style('aze_admin', plugins_url('css/admin.css', __FILE__));
    wp_enqueue_script('aze_admin', plugins_url('js/admin.js', __FILE__), array('jquery'), false, true);
    $forms = azh_get_forms_from_pages();
    wp_nonce_field(basename(__FILE__), '_aze_nonce');
    $from_email = get_post_meta($post->ID, '_from_email', true);
    if (empty($from_email)) {
        $from_email = get_bloginfo('admin_email');
    }
    $from_name = get_post_meta($post->ID, '_from_name', true);
    if (empty($from_name)) {
        $from_name = get_bloginfo('name');
    }
    $reply_to = get_post_meta($post->ID, '_reply_to', true);
    if (empty($reply_to)) {
        $reply_to = get_bloginfo('admin_email');
    }
    ?>
    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <?php esc_html_e('From email', 'aze'); ?>
                </th>
                <td>
                    <input type="email" name="_from_email" value="<?php print $from_email; ?>"/>
                </td>
            </tr>            
            <tr>
                <th>
                    <?php esc_html_e('From name', 'aze'); ?>
                </th>
                <td>
                    <input type="text" name="_from_name" value="<?php print $from_name; ?>"/>
                </td>
            </tr>            
            <tr>
                <th>
                    <?php esc_html_e('Reply to', 'aze'); ?>
                </th>
                <td>
                    <input type="email" name="_reply_to" value="<?php print $reply_to; ?>"/>
                </td>
            </tr>            
            <tr>
                <th>
                    <?php esc_html_e('Email template', 'aze'); ?>
                </th>
                <td>
                    <?php
                    $templates = get_posts(array(
                        'post_type' => 'aze_email_template',
                        'post_status' => 'publish',
                        'ignore_sticky_posts' => 1,
                        'no_found_rows' => 1,
                        'posts_per_page' => -1,
                        'numberposts' => -1
                    ));
                    ?>
                    <?php
                    if (!empty($templates)) {
                        ?>
                        <select name="_email_template">
                            <?php
                            foreach ($templates as $template) {
                                ?>
                                <option value="<?php print $template->ID; ?>" <?php selected($template->ID, get_post_meta($post->ID, '_email_template', true)); ?>><?php print $template->post_title; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <?php
                    } else {
                        ?>
                        <div class="wp-ui-text-notification"><?php printf(__('You do not have email templates. Please <a href="%s">create</a> at least one.', 'aze'), admin_url('post-new.php?post_type=aze_email_template')); ?></div>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Unsubscribe page', 'aze'); ?>
                </th>
                <td>
                    <?php
                    $pages = get_posts(array(
                        'post_type' => 'page',
                        'post_status' => 'publish',
                        'ignore_sticky_posts' => 1,
                        'no_found_rows' => 1,
                        'posts_per_page' => -1,
                        'numberposts' => -1
                    ));
                    ?>
                    <select name="_unsubscribe_page">
                        <?php
                        if (!empty($pages)) {
                            foreach ($pages as $page) {
                                ?>
                                <option value="<?php print $page->ID; ?>" <?php selected($page->ID, get_post_meta($post->ID, '_unsubscribe_page', true)); ?>><?php print $page->post_title; ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                    <em><?php esc_html_e('Use {unsubscribe} in email template for unsubscription URL', 'aze'); ?></em>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Receivers', 'aze'); ?>
                </th>
                <td>
                    <?php if (!empty($forms)): ?>
                        <p>
                            <label><?php esc_html_e('Submitters of form:', 'aze'); ?></label>
                            <select name="_form_title">
                                <?php
                                if (!empty($forms)) {
                                    foreach ($forms as $form_title => $fields) {
                                        ?>
                                        <option value="<?php print $form_title; ?>" <?php selected($form_title, get_post_meta($post->ID, '_form_title', true)); ?>><?php print $form_title; ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </p>
                        <p>
                            <label><?php esc_html_e('Who left their email in the field:', 'aze'); ?></label>
                            <select name="_email_field">
                                <?php
                                if (!empty($forms)) {
                                    foreach ($forms as $form_title => $fields) {
                                        foreach ($fields as $name => $field) {
                                            if ($name != 'form_title') {
                                                ?>
                                                <option value="<?php print $name; ?>" data-form-title="<?php print $form_title; ?>" <?php selected($name, get_post_meta($post->ID, '_email_field', true)); ?>><?php print $name; ?></option>
                                                <?php
                                            }
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </p>
                        <p>
                            <label><?php esc_html_e('Available data for this receivers to use in email template:', 'aze'); ?></label>
                        <div class="aze-tokens"></div>
                        </p>                    
                    <?php else: ?>
                        <div class="wp-ui-text-notification"><?php printf(__('You do not have forms in your pages and widgets. Please <a href="%s">create</a> at least one.', 'aze'), admin_url('post-new.php?post_type=azh_widget')); ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Total receivers', 'aze'); ?>
                </th>
                <td>
                    <div id="aze-receivers">
                        <?php print aze_get_receivers($post->ID)->found_posts; ?>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <p>
        <?php
        if (isset($_GET['aze']) && $_GET['aze'] == 'test') {
            if (aze_send_email($post, get_bloginfo('admin_email'))) {
                esc_html_e('Test email was sent to: ', 'aze');
                print get_bloginfo('admin_email');
            } else {
                esc_html_e('Sending email error: ', 'aze');
                global $aze_mail_failed;
                if ($aze_mail_failed) {
                    print $aze_mail_failed;
                }
            }
        } else {
            ?>
            <a href="<?php print add_query_arg('aze', 'test'); ?>" class="button button-primary button-large">
                <?php
                esc_html_e('Send test email to: ', 'aze');
                print get_bloginfo('admin_email');
                ?>
            </a>
        <?php } ?>
    <p>
        <?php
    }
    