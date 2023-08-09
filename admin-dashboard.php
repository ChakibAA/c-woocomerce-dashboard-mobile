<?php

function plugin_add_dashboard_page()
{
    add_menu_page(
        'C Woocomerce Dashboard Mobile API',
        // Page title
        'C Woocomerce Dashboard Mobile API',
        // Menu title
        'manage_options',
        // Capability required to access the menu
        'c-woocomerce-dashboard-mobile',
        // Menu slug
        'plugin_render_dashboard' // Callback function to render the dashboard page
    );
}
add_action('admin_menu', 'plugin_add_dashboard_page');

function plugin_render_dashboard()
{
    ?>
    <div class="wrap">
        <h1>C Woocomerce Dashboard Mobile</h1>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="save_token">
            <?php wp_nonce_field('your-plugin-save-token'); ?>
            <label for="token">Enter Token:</label>
            <input type="text" name="token" id="token" value="<?php echo esc_attr(get_option('plugin_token')); ?>">
            <?php submit_button('Save Token'); ?>
        </form>
    </div>
    <?php
}

function plugin_save_token()
{
    if (!current_user_can('manage_options') || !check_admin_referer('your-plugin-save-token')) {
        wp_die('Unauthorized access!');
    }

    if (isset($_POST['token'])) {
        $token = sanitize_text_field($_POST['token']);
        update_option('plugin_token', $token);
    }

    wp_redirect(admin_url('admin.php?page=your-plugin-dashboard'));
    exit;
}
add_action('admin_post_save_token', 'plugin_save_token');

function plugin_register_actions()
{
    add_action('admin_post_save_token', 'plugin_save_token');
}
add_action('admin_init', 'plugin_register_actions');