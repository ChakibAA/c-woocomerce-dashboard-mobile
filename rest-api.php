<?php
// Rest APIs

$token_prv = 'chakib';

add_action('rest_api_init', 'rest_api_endpoints');

// Endpoints
function rest_api_endpoints()
{
    // Get orders endpoint
    register_rest_route(
        'dashboard/v1',
        '/orders/',
        array(
            'methods' => WP_REST_SERVER::READABLE,
            'callback' => 'get_orders_api',
        )
    );
    // Get order endpoint 
    register_rest_route(
        'dashboard/v1',
        '/order',
        array(
            'methods' => WP_REST_SERVER::READABLE,
            'callback' => 'get_order_id_api',
        )
    );
    // Update order status endpoiint
    register_rest_route(
        'dashboard/v1',
        '/orders/status',
        array(
            'methods' => WP_REST_SERVER::EDITABLE,
            'callback' => 'store_admin_token_notif',
        )
    );


    // Store admins tokens for notification endpoint
    register_rest_route(
        'dashboard/v1',
        '/admin/token',
        array(
            'methods' => WP_REST_SERVER::EDITABLE,
            'callback' => 'store_admin_token_notif',
        )
    );

}

// Store admins tokens function
function store_admin_token_notif(WP_REST_Request $request)
{
    // Get params
    $params = $request->get_params();
    $token = $params['token'];
    $token_notif = $params['token_notif'];

    // Check if the token is valid
    if ($token != 'chakib') {
        return new WP_Error('unauthorized', 'Invalid token.', array('status' => 401));
    }
    // Check if params not null
    if (!isset($token_notif)) {
        return new WP_Error('Missing', 'Missing fields.', array('status' => 400));
    }

    $token_notif_list = get_option('admin_token');

    $token_notif_list[] = $token_notif;

    update_option('admin_token', $token_notif_list);

    return new WP_REST_Response('Token added successfully', 200, array('Content-Type' => 'application/json'));

}

// Update order status function
function update_orders_status_api(WP_REST_Request $request)
{
    // Get params
    $params = $request->get_params();
    $token = $params['token'];
    $order_id = $params['order_id'];
    $new_order_status = $params['new_order_status'];

    // Check if the token is valid
    if ($token != 'chakib') {
        return new WP_Error('unauthorized', 'Invalid token.', array('status' => 401));
    }

    // Check if params not null
    if (!isset($new_order_status) || !isset($order_id)) {
        return new WP_Error('Missing', 'Missing fields.', array('status' => 204));
    }

    // Get order
    $order = wc_get_order($order_id);


    if (!$order) {
        return new WP_Error('no_order', 'No order found.', array('status' => 204));

    }

    $order->update_status($new_order_status);

    $order->save();


    $result = array(
        'message' => 'order updated succefuly',
    );


    return new WP_REST_Response($result, 200, array('Content-Type' => 'application/json'));

}

// Get orders function
function get_orders_api(WP_REST_Request $request)
{
    $params = $request->get_params();
    $token = $params['token'];

    // Check if the token is valid
    if ($token != 'chakib') {
        return new WP_Error('unauthorized', 'Invalid token.', array('status' => 401));
    }

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
                $variation_id = $line_item->get_variation_id();
                $product = wc_get_product($product_id);

                if ($product) {
                    $product_data = array(
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'quantity' => $line_item->get_quantity(),
                    );
                    if ($variation_id) {

                        $variation = wc_get_product($variation_id);

                        $product_data = array(
                            'id' => $product_id,
                            'name' => $product->get_name(),
                            'price' => $product->get_price(),
                            'quantity' => $line_item->get_quantity(),
                            'variation_id' => $variation_id,
                            'variation_attributes' => $variation->get_variation_attributes()

                        );
                    } else {
                        $product_data = array(
                            'id' => $product_id,
                            'name' => $product->get_name(),
                            'price' => $product->get_price(),
                            'quantity' => $line_item->get_quantity(),
                        );
                    }


                    $products[] = $product_data;
                }
            }

            $order_data['products'] = $products;

            $result[] = $order_data;
        }
        return new WP_REST_Response($result, 200, array('Content-Type' => 'application/json'));
    } else {

        $result = array(
            'message' => 'No orders found'
        );
        return new WP_REST_Response($result, 204, array('Content-Type' => 'application/json'));
    }

}

// Get order by ID
function get_order_id_api(WP_REST_Request $request)
{
    $params = $request->get_params();
    $token = $params['token'];

    // Check if the token is valid
    if ($token != 'chakib') {
        return new WP_Error('unauthorized', 'Invalid token.', array('status' => 401));
    }

    $query_id = $params['query_id'];

    if (!isset($query_id)) {
        return new WP_Error('Invalid', 'Ivalid Id.', array('status' => 204));


    }

    // Get order
    $order = wc_get_order(
        $query_id
    );


    if (!empty($order)) {

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
            $variation_id = $line_item->get_variation_id();
            $product = wc_get_product($product_id);

            if ($product) {
                $product_data = array(
                    'id' => $product_id,
                    'name' => $product->get_name(),
                    'price' => $product->get_price(),
                    'quantity' => $line_item->get_quantity(),
                );
                if ($variation_id) {

                    $variation = wc_get_product($variation_id);

                    $product_data = array(
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'quantity' => $line_item->get_quantity(),
                        'variation_id' => $variation_id,
                        'variation_attributes' => $variation->get_variation_attributes()

                    );
                } else {
                    $product_data = array(
                        'id' => $product_id,
                        'name' => $product->get_name(),
                        'price' => $product->get_price(),
                        'quantity' => $line_item->get_quantity(),
                    );
                }


                $products[] = $product_data;
            }
        }

        $order_data['products'] = $products;

        return new WP_REST_Response($order_data, 200, array('Content-Type' => 'application/json'));
    } else {

        $result = array(
            'message' => 'Order not found or invalid order ID.'
        );
        return new WP_REST_Response($result, 204, array('Content-Type' => 'application/json'));
    }

}