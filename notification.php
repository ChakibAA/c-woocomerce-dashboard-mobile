<?php

// Get admin tokens

function get_tokens()
{
    $token_notif_list = get_option('admin_token');

    return $token_notif_list;
}

// Detect when new order
function send_push_notification_on_new_order($order_id)
{
    $devices_tokens = get_tokens();

    if ($devices_tokens) {
        send_push_notification($devices_tokens);
    }

}


add_action('woocommerce_new_order', 'send_push_notification_on_new_order');


// Check user meta || update user meta


function send_push_notification($devices_tokens)
{
    $server_key = 'AAAAfEFduUs:APA91bHtgro_dXgnwr4yRXicar7AyZRgpgu24cK1icUWoRPl-zl3n5_-gPDw67wHMd2_u8hGntKlL4IKdjP4osYzWUUaPGYz5LBSFOWR80GRmRyznCNpvJ2khr0z2U_stcNuOmnJ5i5N';

    $url = 'https://fcm.googleapis.com/fcm/send';

    $headers = array(
        'Authorization' => 'key=' . $server_key,
        'Content-Type' => 'application/json'
    );

    $notification = array(
        'title' => 'C Woocomerce Dashboard',
        'body' => 'Vous avez une nouvelle commande',
    );


    foreach ($devices_tokens as $device_token) {
        $payload = array(
            'to' => $device_token,
            'notification' => $notification,
        );

        $args = array(
            'headers' => $headers,
            'body' => json_encode($payload)
        );

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return error_log('Error sending push notification: ' . $response->get_error_message());
        }
    }


}