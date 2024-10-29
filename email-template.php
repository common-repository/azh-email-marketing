<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
        <title><?php the_title(); ?></title>
        <?php
        if (function_exists('azh_filesystem')) {
            azh_filesystem();
            global $wp_filesystem;
            $stylesheets = get_post_meta(get_the_ID(), '_stylesheets', true);
            if ($stylesheets) {
                print $wp_filesystem->get_contents($stylesheets);
            }
        }
        ?>
        <style>
<?php
$styles = get_post_meta(get_the_ID(), '_styles', true);
if ($styles) {
    print $wp_filesystem->get_contents($styles);
}
print azh_container_widths();
?>
            .az-container {
                padding-right: 0;
                padding-left: 0;
            }
        </style>
        <?php
        if (function_exists('azh_builder_scripts')) {
            $user = wp_get_current_user();
            $post = get_post();
            if (in_array('administrator', (array) $user->roles) || ($post && isset($post->post_author) && $post->post_author == get_current_user_id())) {
                wp_enqueue_script('azh_admin_frontend', AZH_URL . '/js/admin-frontend.js', array('jquery', 'underscore'), false, true);
                wp_localize_script('azh_admin_frontend', 'azh', azh_get_object());
                if (isset($_GET['azh']) && $_GET['azh'] == 'customize') {
                    azh_builder_scripts();
                }
                wp_enqueue_script('aze-frontend-customization-options', AZE_URL . '/frontend-customization-options.js', array('azh_admin_frontend'), false, true);
            }
            global $post;
            $fonts_url = azh_get_google_fonts_url(false, azh_get_post_content($post));
            if ($fonts_url) {
                wp_enqueue_style('azh-fonts', $fonts_url, array(), null);
            }
            wp_print_styles(array('azh_admin_frontend', 'azh-fonts', 'dashicons'));
        }
        ?>
    </head>
    <body class="azh-customize" style="margin: 0">  
        <div class="az-container">
            <?php while (have_posts()) : the_post(); ?>
                <?php the_content(); ?>
            <?php endwhile; ?>            
        </div>
        <?php
        wp_print_scripts(array('jquery-core'));
        ?>        
        <script>
            (function($) {
                $(window).on("azh-customizer-after-init", function(event, data) {
                    data.azh.controls_options = azh.controls_options;
                    data.azh.modal_options = azh.modal_options;
                    $('.azh-content-wrapper').children().each(function() {
                        data.azh.section_customization_init($(this));
                    });
                    data.azh.changed = false;
                    //$('#azexo-html-library .azh-library-actions .azh-style').remove();                        
                });
            })(window.jQuery);
        </script>
        <?php
        wp_print_scripts(array('aze-frontend-customization-options', 'azh_admin_frontend', 'simplemodal', 'azh_admin', 'azh_htmlparser', 'azh_html_editor', 'jquery-ui-sortable', 'jquery-ui-autocomplete', 'jquery-ui-draggable'));
        if (function_exists('azh_footer')) {
            azh_footer();
        }
        ?>
    </body>
</html>