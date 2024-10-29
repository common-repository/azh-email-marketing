<?php
/*
  Plugin Name: Email Marketing by AZEXO
  Description: Email Marketing by AZEXO
  Author: azexo
  Author URI: http://azexo.com
  Version: 1.27
  Text Domain: aze
 */

define('AZE_VERSION', '1.27');
define('AZE_URL', plugins_url('', __FILE__));
define('AZE_DIR', trailingslashit(dirname(__FILE__)));


include_once(AZE_DIR . 'integrations/email-subscribers.php' );
include_once(AZE_DIR . 'integrations/newsletter.php' );
include_once(AZE_DIR . 'integrations/mailpoet.php' );
include_once(AZE_DIR . 'integrations/mailster.php' );
if (file_exists(AZE_DIR . 'email_campaigns.php')) {
    include_once(AZE_DIR . 'email_campaigns.php' );
}


add_action('plugins_loaded', 'aze_plugins_loaded');

function aze_plugins_loaded() {
    load_plugin_textdomain('aze', FALSE, basename(dirname(__FILE__)) . '/languages/');
}

add_action('admin_notices', 'aze_admin_notices');

function aze_admin_notices() {
    if (!defined('AZH_VERSION')) {
        $plugin_data = get_plugin_data(__FILE__);
        print '<div class="updated notice error is-dismissible"><p>' . $plugin_data['Name'] . ': ' . __('please install <a href="https://wordpress.org/plugins/page-builder-by-azexo/">Page builder by AZEXO</a> plugin.', 'aze') . '</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . esc_html__('Dismiss this notice.', 'aze') . '</span></button></div>';
    }
    if (!defined('AZF_VERSION')) {
        $plugin_data = get_plugin_data(__FILE__);
        print '<div class="updated notice error is-dismissible"><p>' . $plugin_data['Name'] . ': ' . __('please install <a href="https://wordpress.org/plugins/cost-calculator-by-azexo/">Form builder by AZEXO</a> plugin.', 'aze') . '</p><button class="notice-dismiss" type="button"><span class="screen-reader-text">' . esc_html__('Dismiss this notice.', 'aze') . '</span></button></div>';
    }
}

add_action('admin_menu', 'aze_admin_menu');

function aze_admin_menu() {
    add_submenu_page('azh-settings', __('AZEXO Email Templates', 'aze'), __('Email Templates', 'aze'), 'manage_options', 'azh-email-templates-settings', 'aze_email_templates_page');
}

function aze_email_templates_page() {
    ?>

    <div class="wrap">
        <?php screen_icon(); ?>
        <h2><?php _e('AZEXO Email Templates', 'aze'); ?></h2>

        <form method="post" action="options.php" class="azh-form">
            <?php
            settings_errors();
            settings_fields('azh-email-templates-settings');
            do_settings_sections('azh-email-templates-settings');
            submit_button(__('Save Settings', 'aze'));
            ?>
        </form>
    </div>

    <?php
}

add_action('admin_init', 'aze_options');

function aze_options() {
    register_setting('azh-email-templates-settings', 'azh-email-templates-settings', array('sanitize_callback' => 'azh_settings_sanitize_callback'));

    add_settings_section(
            'azh_email_templates_section', // Section ID
            esc_html__('Email Templates', 'aze'), // Title above settings section
            'azh_general_options_callback', // Name of function that renders a description of the settings section
            'azh-email-templates-settings'                     // Page to show on
    );
    add_settings_field(
            'azh_email_template_upload', // Field ID
            __('ZIP file of email template in <a href="https://themeforest.net/tags/stampready" target="_blank">StampReady</a> format', 'aze'), // Label to the left
            'aze_email_template_upload', // Name of function that renders options on the page
            'azh-email-templates-settings', // Page to show on
            'azh_email_templates_section', // Associate with which settings section?
            array()
    );
}

function aze_email_template_upload() {
    ?>
    <p>
        <input id="aze-email-template-upload" type="file">
        <a href="#" class="button button-primary aze-email-template-upload">
            <?php esc_html_e('Click to start upload', 'aze'); ?>
        </a>
        <span class="aze-progress"><span class="aze-status"></span></span>
    </p>
    <p>
        <em>
            <?php _e('Template will be uploaded right after choose a template zip-file.<br> ZIP-filename must contain single folder with index.html file.<br> ZIP-file and folder must have same name - <b>template name</b>.', 'aze'); ?>
        </em>
    </p>
    <?php
}

function aze_get_templates() {
    $wp_upload_dir = wp_upload_dir();
    $templates = array();
    if (is_dir($wp_upload_dir['basedir'] . '/email_templates')) {
        $templates_iterator = new DirectoryIterator($wp_upload_dir['basedir'] . '/email_templates');
        foreach ($templates_iterator as $templateInfo) {
            if ($templateInfo->isDir() && !$templateInfo->isDot()) {
                $template_name = $templateInfo->getFilename();
                $templates[$template_name] = array(
                    'name' => $template_name,
                    'url' => file_exists($templateInfo->getPathname() . '/index.html') ? $wp_upload_dir['baseurl'] . '/email_templates/' . $template_name . '/index.html' : false,
                    'template_preview' => file_exists($templateInfo->getPathname() . '/index.jpg') ? $wp_upload_dir['baseurl'] . '/email_templates/' . $template_name . '/index.jpg' : false,
                    'styles' => file_exists($templateInfo->getPathname() . '/styles.css') ? $wp_upload_dir['basedir'] . '/email_templates/' . $template_name . '/styles.css' : false,
                    'stylesheets' => file_exists($templateInfo->getPathname() . '/stylesheets.html') ? $wp_upload_dir['basedir'] . '/email_templates/' . $template_name . '/stylesheets.html' : false,
                    'sections' => array(),
                );
                if (is_dir($templateInfo->getPathname() . '/sections')) {
                    $sections_iterator = new DirectoryIterator($templateInfo->getPathname() . '/sections');
                    foreach ($sections_iterator as $sectionInfo) {
                        if ($sectionInfo->isFile() && $sectionInfo->getExtension() == 'html') {
                            $preview = $wp_upload_dir['baseurl'] . '/email_templates/' . $template_name . '/sections/' . $sectionInfo->getBasename('.html') . '.jpg';
                            if (!file_exists($preview)) {
                                $preview = $wp_upload_dir['baseurl'] . '/email_templates/' . $template_name . '/sections/' . $sectionInfo->getBasename('.html') . '.png';
                            }
                            $templates[$template_name]['sections'][$sectionInfo->getBasename('.html')] = array(
                                'html' => $wp_upload_dir['baseurl'] . '/email_templates/' . $template_name . '/sections/' . $sectionInfo->getFilename(),
                                'preview' => $preview,
                            );
                        }
                    }
                }
            }
        }
    }
    return $templates;
}

add_action('admin_enqueue_scripts', 'aze_admin_scripts');

function aze_admin_scripts() {
    if (isset($_GET['page']) && $_GET['page'] == 'azh-email-templates-settings' || get_post_type() === 'aze_email_template') {
        wp_enqueue_style('aze_admin', plugins_url('css/admin.css', __FILE__));
        wp_enqueue_script('aze_admin', plugins_url('js/admin.js', __FILE__), array('jquery'), false, true);
        wp_localize_script('aze_admin', 'aze', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'jquery' => home_url() . wp_scripts()->registered['jquery-core']->src,
            'html2canvas' => plugins_url('js/html2canvas.js', __FILE__),
            'templates' => aze_get_templates(),
            'i18n' => array(
                'done' => __('Done', 'aze'),
            ),
        ));
    }
//    if (get_post_type() === 'aze_email_template') {
//        $settings = wp_enqueue_code_editor(array('type' => 'text/html'));
//        if (false === $settings) {
//            return;
//        }
//        wp_add_inline_script(
//                'code-editor', sprintf(
//                        'jQuery( function() { wp.codeEditor.initialize( "content", %s ); } );', wp_json_encode($settings)
//                )
//        );
//    }
}

add_action('wp_ajax_aze_upload_template', 'aze_upload_template');

function aze_upload_template() {
    $file_name = (isset($_SERVER['HTTP_X_FILENAME']) ? $_SERVER['HTTP_X_FILENAME'] : false);
    if ($file_name) {
        $file_path = get_temp_dir() . $file_name;
        $hSource = fopen('php://input', 'r');
        $hDest = fopen($file_path, 'w');
        while (!feof($hSource)) {
            $chunk = fread($hSource, 1024);
            fwrite($hDest, $chunk);
        }
        fclose($hSource);
        fclose($hDest);
        $wp_upload_dir = wp_upload_dir();
        if (unzip_file($file_path, $wp_upload_dir['basedir'] . '/email_templates')) {
            if ($_GET['format'] == 'stampready') {
                aze_process_stampready_template(pathinfo($file_name, PATHINFO_FILENAME));
            }
            //$templates = aze_get_templates();
            //print json_encode($templates[pathinfo($file_name, PATHINFO_FILENAME)]); //zip filename must have same name as template
        }
    }
    wp_die();
}

function aze_add_image_to_library($path) {
//    static $added = array();
//    if (!isset($added[$path]) && file_exists($path)) {
//        $added[$path] = true;
//        $wp_upload_dir = wp_upload_dir();
//        $new_file_path = $wp_upload_dir['path'] . '/' . basename($path);
//        $filetype = wp_check_filetype(basename($path), null);
//        $i = 1;
//        while (file_exists($new_file_path)) {
//            $i++;
//            $new_file_path = $wp_upload_dir['path'] . '/' . $i . '-' . basename($path);
//        }
//        if (move_uploaded_file($path, $new_file_path)) {
//            $attachment = array(
//                'guid' => $new_file_path,
//                'post_mime_type' => $filetype['type'],
//                'post_title' => preg_replace('/\.[^.]+$/', '', basename($path)),
//                'post_content' => '',
//                'post_status' => 'inherit'
//            );
//            wp_insert_attachment($attachment, $new_file_path);
//        }
//    }
}

function aze_process_stampready_template($name) {
    $wp_upload_dir = wp_upload_dir();
    $path = $wp_upload_dir['basedir'] . '/email_templates/' . $name . '/index.html';
    if (file_exists($path)) {
        global $wp_filesystem;
        azh_filesystem();
        $content = $wp_filesystem->get_contents($path);
        include_once(AZH_DIR . 'simple_html_dom.php' );
        $html = str_get_html($content);
        if ($html) {

            foreach ($html->find('comment') as $comment) {
                $comment->outertext = '';
            }

            $styles = '';
            foreach ($html->find('style') as $style) {
                $styles .= $style->innertext;
            }

            $stylesheets = '';
            foreach ($html->find('link[type="text/css"]') as $stylesheet) {
                $stylesheets .= $stylesheet->outertext;
            }

            foreach ($html->find('img[src]') as $img) {
                $url = $wp_upload_dir['baseurl'] . '/email_templates/' . $name . '/' . $img->src;
                if (strpos($img->src, 'http') === false) {
                    $img->src = $url;
                    $path = $wp_upload_dir['basedir'] . '/email_templates/' . $name . '/' . $img->src;
                    aze_add_image_to_library($path);
                }
            }

            foreach ($html->find('[background]') as $background) {
                $url = $wp_upload_dir['baseurl'] . '/email_templates/' . $name . '/' . $background->background;
                if (strpos($background->background, 'http') === false) {
                    $background->background = $url;
                    $path = $wp_upload_dir['basedir'] . '/email_templates/' . $name . '/' . $background->background;
                    aze_add_image_to_library($path);
                }
            }

            foreach ($html->find('[style*="background-image"]') as $background_image) {
                $background_image->style = preg_replace_callback('/(background-image\:[^;]*url\([\'\"]?)([^\'\"\)]+)([\'\"]?\))/i', function($m) use ($name, $wp_upload_dir) {
                    if (strpos($m[2], 'http') === false) {
                        $url = $wp_upload_dir['baseurl'] . '/email_templates/' . $name . '/' . $m[2];
                        $path = $wp_upload_dir['basedir'] . '/email_templates/' . $name . '/' . $m[2];
                        aze_add_image_to_library($path);
                        return $m[1] . $url . $m[3];
                    } else {
                        return $m[1] . $m[2] . $m[3];
                    }
                }, (string) $background_image->style);
            }

            foreach ($html->find('[style*="background:"]') as $background_image) {
                $background_image->style = preg_replace_callback('/(background\:[^;]*url\([\'\"]?)([^\'\"\)]+)([\'\"]?\))/i', function($m) use ($name, $wp_upload_dir) {
                    if (strpos($m[2], 'http') === false) {
                        $url = $wp_upload_dir['baseurl'] . '/email_templates/' . $name . '/' . $m[2];
                        $path = $wp_upload_dir['basedir'] . '/email_templates/' . $name . '/' . $m[2];
                        aze_add_image_to_library($path);
                        return $m[1] . $url . $m[3];
                    } else {
                        return $m[1] . $m[2] . $m[3];
                    }
                }, (string) $background_image->style);
            }

            $wp_filesystem->mkdir($wp_upload_dir['basedir'] . '/email_templates/' . $name . '/sections');
            $preview = false;
            foreach ($html->find('[data-module]') as $module) {
                if ($module->{'data-thumb'}) {
                    $thumbnail = $wp_upload_dir['basedir'] . '/email_templates/' . $name . '/thumbnails/' . $module->{'data-thumb'};
                    if (!file_exists($thumbnail)) {
                        $thumbnail = $wp_upload_dir['basedir'] . '/email_templates/' . $name . '/' . $module->{'data-thumb'};
                    }
                    $thumbnail = $wp_filesystem->get_contents($thumbnail);
                    if (!$preview) {
                        $preview = $thumbnail;
                    }
                    $ext = pathinfo($module->{'data-thumb'}, PATHINFO_EXTENSION);
                    $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . $name . '/sections/' . $module->{'data-module'} . '.' . $ext, $thumbnail);
                }
                $content = $module->outertext;
                $content = preg_replace('/ ([a-zA-Z]+):([a-zA-Z]+=[\'\"][^\'\"]*[\'\"])/', ' ', $content);
                $content = preg_replace('/ ([a-zA-Z]+):([a-zA-Z]+)/', ' ', $content);
                $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . $name . '/sections/' . $module->{'data-module'} . '.html', $content);
            }
            if ($preview) {
                $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . $name . '/index.jpg', $preview);
            }
            $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . $name . '/styles.css', $styles);
            $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . $name . '/stylesheets.html', $stylesheets);
        }
    }
}

add_action('wp_ajax_aze_upload_section', 'aze_upload_section');

function aze_upload_section() {
    global $wp_filesystem;
    azh_filesystem();
    $wp_upload_dir = wp_upload_dir();
    $img = sanitize_text_field($_REQUEST['preview']);
    $img = str_replace('data:image/jpeg;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $preview = base64_decode($img);
    if ($_REQUEST['name'] == 'index') {
        $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . sanitize_text_field($_REQUEST['template']) . '/index.jpg', $preview);
        $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . sanitize_text_field($_REQUEST['template']) . '/styles.css', stripslashes($_REQUEST['styles']));
        $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . sanitize_text_field($_REQUEST['template']) . '/stylesheets.html', stripslashes($_REQUEST['stylesheets']));
    } else {
        $wp_filesystem->mkdir($wp_upload_dir['basedir'] . '/email_templates/' . sanitize_text_field($_REQUEST['template']) . '/sections');
        $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . sanitize_text_field($_REQUEST['template']) . '/sections/' . sanitize_text_field($_REQUEST['name']) . '.jpg', $preview);
        $html = stripslashes($_REQUEST['html']);
        $html = preg_replace('/ ([a-zA-Z]+):([a-zA-Z]+=[\'\"][^\'\"]*[\'\"])/', ' ', $html);
        $wp_filesystem->put_contents($wp_upload_dir['basedir'] . '/email_templates/' . sanitize_text_field($_REQUEST['template']) . '/sections/' . sanitize_text_field($_REQUEST['name']) . '.html', $html);
    }

    wp_die();
}

add_filter('azh_directory', 'aze_directory', 20);

function aze_directory($dir) {
    if ((is_singular() || is_admin()) && in_array(get_post_type(), array('aze_email_template', 'azd_email_campaign'))) {
        $wp_upload_dir = wp_upload_dir();
        $template = isset($_GET['template']) && is_dir($wp_upload_dir['basedir'] . '/email_templates/' . sanitize_text_field($_GET['template'])) ? sanitize_text_field($_GET['template']) : false;
        if ($template) {
            $old_template = get_post_meta(get_the_ID(), '_template', true);
            if ($old_template != $template) {
                update_post_meta(get_the_ID(), '_template', $template);
                azh_set_post_content('', get_the_ID());
                update_post_meta(get_the_ID(), 'azh', 'azh');
                $templates = aze_get_templates();
                update_post_meta(get_the_ID(), '_stylesheets', $templates[$template]['stylesheets']);
                update_post_meta(get_the_ID(), '_styles', $templates[$template]['styles']);
            }
        } else {
            $template = get_post_meta(get_the_ID(), '_template', true);
        }
        if ($template) {
            return array(
                $wp_upload_dir['basedir'] . '/email_templates/' . $template => $wp_upload_dir['baseurl'] . '/email_templates/' . $template
            );
        } else {
            return array(
                $wp_upload_dir['basedir'] . '/email_templates/' => $wp_upload_dir['baseurl'] . '/email_templates/'
            );
        }
    }
    return $dir;
}

add_filter('azh_get_object', 'aze_get_object');

function aze_get_object($azh) {
    if ((is_singular() || is_admin()) && in_array(get_post_type(), array('aze_email_template', 'azd_email_campaign'))) {
        $azh['responsive'] = false;
        $azh['editor_toolbar'] = array('boldButton', 'italicButton', 'linkButton', 'sizeSelector', 'colorInput');
        $azh['elements_hierarchy'] = false;
        $azh['table_editor'] = false;
        $azh['recognition'] = true;
    }
    return $azh;
}

add_filter('azh_get_library', 'aze_get_library');

function aze_get_library($library) {
    if ((is_singular() || is_admin()) && in_array(get_post_type(), array('aze_email_template', 'azd_email_campaign'))) {
        foreach ($library['sections_categories'] as $category => $flag) {
            if (strpos($category, '/sections') === false) {
                unset($library['sections_categories'][$category]);
            }
        }
        foreach ($library['sections'] as $path => $name) {
            if (in_array($name, array('index.html', 'stylesheets.html'))) {
                unset($library['sections'][$path]);
            }
        }
    }
    return $library;
}

add_filter('azh_set_post_content', 'aze_set_post_content', 10, 2);

function aze_set_post_content($content, $post_id) {
    $post = get_post($post_id);
    if (in_array($post->post_type, array('aze_email_template', 'azd_email_campaign')) && ($post->post_author == get_current_user_id())) {
        include_once(AZH_DIR . 'simple_html_dom.php');
        $html = str_get_html($content);
        if ($html) {
            foreach ($html->find('a[href]') as $link) {
                $url = $link->href;
                if (strpos($url, 'http') !== false) {
                    if (strpos($url, '?') !== false) {
                        if (strpos($url, 'click=click') === false) {
                            $url .= '&click=click';
                        }
                    } else {
                        $url .= '?click=click';
                    }
                }
                $link->href = $url;
            }
            return $html->save();
        }
    }
    return $content;
}

add_filter('azh_meta_box_post_types', 'aze_meta_box_post_types');

function aze_meta_box_post_types($post_types) {
    $post_types[] = 'aze_email_template';
    $post_types[] = 'azd_email_campaign';
    return $post_types;
}

add_action('init', 'aze_init');

function aze_init() {
    if (defined('AZH_VERSION')) {
        register_post_type('aze_email_template', array(
            'labels' => array(
                'name' => __('Email template', 'aze'),
                'singular_name' => __('Email template', 'aze'),
                'add_new' => __('Add Email template', 'aze'),
                'add_new_item' => __('Add New Email template', 'aze'),
                'edit_item' => __('Edit Email template', 'aze'),
                'new_item' => __('New Email template', 'aze'),
                'view_item' => __('View Email template', 'aze'),
                'search_items' => __('Search Email templates', 'aze'),
                'not_found' => __('No Email template found', 'aze'),
                'not_found_in_trash' => __('No Email template found in Trash', 'aze'),
                'parent_item_colon' => __('Parent Email template:', 'aze'),
                'menu_name' => __('Email templates', 'aze'),
            ),
            'query_var' => true,
            'rewrite' => array('slug' => 'email_template'),
            'hierarchical' => true,
            'supports' => array('title', 'editor', 'custom-fields', 'author'),
            'show_ui' => true,
            'show_in_nav_menus' => true,
            'show_in_menu' => true,
            'public' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => true,
            'capabilities' => array(
                'edit_post' => 'edit_aze_email_template',
                'edit_posts' => 'edit_aze_email_templates',
                'edit_others_posts' => 'edit_other_aze_email_templates',
                'publish_posts' => 'publish_aze_email_templates',
                'edit_publish_posts' => 'edit_publish_aze_email_templates',
                'read_post' => 'read_aze_email_templates',
                'read_private_posts' => 'read_private_aze_email_templates',
                'delete_post' => 'delete_aze_email_template',
                'delete_posts' => 'delete_aze_email_templates'
            ),
            'capability_type' => array('aze_email_template', 'aze_email_templates'),
                )
        );
        $caps = array(
            'edit_aze_email_template',
            'edit_aze_email_templates',
            'publish_aze_email_templates',
            'edit_published_aze_email_templates',
            'read_aze_email_templates',
            'read_private_aze_email_templates',
            'delete_aze_email_template'
        );
        $author = get_role('author');
        foreach ($caps as $cap) {
            $author->add_cap($cap);
        }
        $administrator = get_role('administrator');
        foreach ($caps as $cap) {
            $administrator->add_cap($cap);
        }

        $settings = get_option('azh-settings');
        if (!isset($settings['post-types']['aze_email_template'])) {
            $settings['post-types']['aze_email_template'] = true;
            update_option('azh-settings', $settings);
        }
    }
}

add_filter('template_include', 'aze_template_include');

function aze_template_include($template) {
    if (is_singular() && get_post_type() == 'aze_email_template') {
        $template = locate_template('email-template.php');
        if (!$template) {
            $template = plugin_dir_path(__FILE__) . 'email-template.php';
        }
        return $template;
    }
    return $template;
}

add_action('add_meta_boxes', 'aze_add_meta_boxes', 10, 2);

function aze_add_meta_boxes($post_type, $post) {
    if ($post_type === 'aze_email_template') {
        add_meta_box('aze', __('Email template', 'aze'), 'aze_meta_box', $post_type, 'advanced', 'default');
    }
}

function aze_meta_box($post = NULL, $metabox = NULL, $post_type = 'page') {
    $templates = aze_get_templates();
    if (!empty($templates)) {
        ?>
        <div class="aze-templates">
            <?php
            foreach ($templates as $template) {
                ?>
                <a href="<?php print add_query_arg('template', $template['name'], get_edit_post_link()) ?>" class="aze-template <?php print ($template['name'] == get_post_meta($post->ID, '_template', true) ? 'aze-active' : ''); ?>">
                    <img src="<?php print $template['template_preview'] ?>"/>
                    <div class="aze-name"><?php print $template['name'] ?></div>
                </a>
                <?php
            }
            ?>
        </div>
        <?php
    } else {
        ?>
        <div class="wp-ui-text-notification"><?php printf(__('For email template creation you need <a href="%s">upload</a> base email template.', 'aze'), admin_url('admin.php?page=azh-email-templates-settings')); ?></div>
        <?php
    }
}

add_filter('admin_body_class', 'aze_admin_body_class');

function aze_admin_body_class($classes) {
    global $pagenow;
    if (in_array($pagenow, array('post.php', 'post-new.php')) && get_post_type() == 'aze_email_template') {
        $post = get_post();
        if (get_post_meta($post->ID, '_template', true)) {
            $classes .= ' aze-template';
        }
    }

    return $classes;
}

add_filter('user_can_richedit', 'aze_user_can_richedit');

function aze_user_can_richedit($default) {
    global $post;
    if ('aze_email_template' == get_post_type($post)) {
        return false;
    }
    return $default;
}

add_filter('azh_wp_post_content', 'aze_wp_post_content', 10, 3);

function aze_wp_post_content($override, $content, $post_id) {
    if (!empty($content) && 'aze_email_template' == get_post_type($post_id)) {
        $styles = '';
        $stylesheets = '';
        if (function_exists('azh_filesystem')) {
            azh_filesystem();
            global $wp_filesystem;
            $styles = get_post_meta($post_id, '_styles', true);
            if ($styles && file_exists($styles)) {
                $styles = $wp_filesystem->get_contents($styles);
            }
            $stylesheets = get_post_meta($post_id, '_stylesheets', true);
            if ($stylesheets && file_exists($stylesheets)) {
                $stylesheets = $wp_filesystem->get_contents($stylesheets);
            }
        }
        $fonts_url = azh_get_google_fonts_url(false, $content);
        if ($fonts_url) {
            $stylesheets .= '<link href="' . $fonts_url . '" rel="stylesheet" type="text/css" />';
        }

        $override = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'
                . '<html xmlns="http://www.w3.org/1999/xhtml">'
                . '<head>'
                . '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'
                . '<meta name="viewport" content="width=device-width" />'
                . $stylesheets
                . '<style type="text/css">'
                . $styles
                . '</style>'
                . '</head>'
                . '<body>'
                . $content
                . '</body>'
                . '</html>';
    }
    return $override;
}

class AZH_Subscription_Widget extends WP_Widget {

    public static function get_body() {
        return '<form class="az-form" action="#" method="post" data-azh-form="[azh_form]"  data-group="form" enctype="multipart/form-data" data-success="' . __('You subscribed successfully', 'aze') . '" data-error="" data-success-redirect="">
            <input type="hidden" name="form_title" value="subscription"/>
            <div data-cloneable="">
                <div data-element="">
                    <p>
                        <input class="az-field" name="email" type="email" value="" placeholder="Your email" required maxlength="" data-mask="" style="width: 100%"/>
                    </p>
                </div>
            </div>
            <button type="submit" style="width: 100%">
                ' . __('Subscribe', 'aze') . '
            </button>    
        </form>';
    }
            
    function __construct() {
        parent::__construct('azh_subscription_widget', __('AZEXO Subscription Widget', 'aze'));
    }

    function widget($args, $instance) {

        print $args['before_widget'];
        if ($title) {
            print $args['before_title'] . $title . $args['after_title'];
        }

        print do_shortcode(AZH_Subscription_Widget::get_body());

        print $args['after_widget'];
    }

}

add_action('widgets_init', 'aze_register_widgets');

function aze_register_widgets() {
    register_widget('AZH_Subscription_Widget');
}

add_filter('azh_get_forms_from_page', 'aze_get_forms_from_page', 10, 2);

function aze_get_forms_from_page($forms, $page) {
    return array_merge_recursive(azh_get_forms_from_content(AZH_Subscription_Widget::get_body()), $forms);
}
