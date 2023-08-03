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



// Create Rest API

$token_prv = 'chakib';

add_action('rest_api_init', 'restApiEndpoints');


function restApiEndpoints()
{
    // Get orders endpoint
    register_rest_route(
        'dashboard/v1',
        '/orders/',
        array(
            'methods' => WP_REST_SERVER::READABLE,
            'callback' => 'getOrdersAPI',
        )
    );
    register_rest_route(
        'dashboard/v1',
        '/orders/status',
        array(
            'methods' => WP_REST_SERVER::EDITABLE,
            'callback' => 'updateOrdersStatusAPI',
        )
    );

}

// Get orders function
function updateOrdersStatusAPI(WP_REST_Request $request)
{
    $params = $request->get_params();
    $token = $params['token'];
    $order_id = $params['order_id'];
    $new_order_status = $params['new_order_status'];

    // Check if the token is valid
    if ($token != 'chakib') {
        return new WP_Error('unauthorized', 'Invalid token.', array('status' => 401));
    }

    if (!isset($new_order_status) || !isset($order_id)) {
        return new WP_Error('Missing', 'Missing fields.', array('status' => 400));
    }

    $order = wc_get_order($order_id);

    if (!$order) {
        return new WP_Error('no_order', 'No order found.', array('status' => 404));

    }

    $order->update_status($new_order_status);

    $order->save();


    $result = array(
        'message' => 'order updated succefuly',
    );


    return new WP_REST_Response($result, 200, array('Content-Type' => 'application/json'));

}
function getOrdersAPI(WP_REST_Request $request)
{
    $params = $request->get_params();
    $token = $params['token'];

    // Check if the token is valid
    if ($token != 'chakib') {
        return new WP_Error('unauthorized', 'Invalid token.', array('status' => 401));
    }

    $params = $request->get_params();
    $status = isset($params['status']) ? $params['status'] : 'any';
    $per_page = isset($params['per_page']) ? absint($params['per_page']) : 10;
    $page = isset($params['page']) ? absint($params['page']) : 1;
    $offset = ($page - 1) * $per_page;

    // Get all orders
    $orders = wc_get_orders(
        array(
            'status' => $status,
            'limit' => $per_page,
            'offset' => $offset,
        )
    );


    if (!empty($orders)) {
        $result = array();
        foreach ($orders as $order) {
            $order_data = array(
                'id' => $order->get_id(),
                'status' => $order->get_status(),
                'currency' => $order->get_currency(),
                'created_at' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'shipping_method' => $order->get_shipping_method(),
                'shipping_cost' => $order->get_shipping_total(),
                'total' => $order->get_total(),
                'billing' => $order->get_address('billing'),
                'shipping' => $order->get_address('shipping'),
                'payment_method' => $order->get_payment_method(),
            );

            // Get products of the order
            $line_items = $order->get_items();
            $products = array();
            foreach ($line_items as $line_item) {
                $product_id = $line_item->get_product_id();
                $product = wc_get_product($product_id);

                if ($product) {
                    $product_data = array(
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'quantity' => $line_item->get_quantity(),
                    );

                    $products[] = $product_data;
                }
            }

            $order_data['products'] = $products;

            $result[] = $order_data;
        }
        return new WP_REST_Response($result, 200, array('Content-Type' => 'application/json'));
    } else {
        return new WP_Error('no_orders', 'No orders found.', array('status' => 404));
    }

}