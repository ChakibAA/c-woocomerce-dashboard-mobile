<?php
/*

    Plugin Name: C Woocomerce dashboard mobile
    Description: Plugin compatible with mobile dashboard app
    Version: 1.0
    Author: Chakib
    Author URI: https://www.linkedin.com/in/chakib-ammar-aouchiche-a25150220/
    License: GPL-2.0+
    License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/


if (!defined('ABSPATH')) {
    exit;
}

include_once(plugin_dir_path(__FILE__) . 'rest-api.php');
include_once(plugin_dir_path(__FILE__) . 'admin-dashboard.php');
include_once(plugin_dir_path(__FILE__) . 'notification.php');


// To check if woocomerce is active
function is_woocommerce_active_mobile()
{
    return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

function woocommerce_inactive_notice_mobile()
{
    ?>
    <div id="message" class="error">
        <p>

            <?php
            deactivate_plugins(plugin_basename(__FILE__));
            print_r(__('<b>WooCommerce</b> plugin must be active for <b>C Checkout woocommerce</b> to work. '));
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
            ?>
        </p>
    </div>
    <?php
}

if (!is_woocommerce_active_mobile()) {
    add_action('admin_notices', 'woocommerce_inactive_notice_mobile');

    return;
}