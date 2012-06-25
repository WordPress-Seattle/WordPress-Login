<?php
/*
 Plugin Name: External WordPress Authentication Server
 Plugin URI: http://www.noneyet.com/
 Description: Provides authentication to the local WordPress site from an external WordPress installation
 Version: 0.5
 Author: Ben Lobaugh
 Author URI: http://ben.lobaugh.net
 */

$ext_service = "http://localhost/wordpress";
$query_var = "ext_auth";
$auth_url = "$ext_service?$query_var";

// Check to see if we need to do any authentication and do it
add_action( 'init', 'extwpauth_check_for_auth_request' );
function extwpauth_check_for_auth_request() {
    global $query_var;
    if( get_option( 'extwpauth-enabled' ) == 1 && isset( $_GET[$query_var] ) && $_GET['user'] != 'admin') {
        $user = sanitize_user( $_GET['user'] );
        $pass = sanitize_user( $_GET['pass'] );
        $user = ltrim( $user, 'fm_' );
        $result = wp_authenticate( $user, $pass );
        
        $json = array();
        if( is_wp_error( $result ) ) {
            // Send back the errors
            $json = $result->errors;
            $json['result'] = 0;
        } else {
            // Gather the info to send to the remote site
            $json['data'] = (array)$result->data;
            unset( $json['data']['user_pass'] );
            $json['roles'] = (array)$result->roles;
            $json['result'] = 1;
        }
        die( json_encode( $json ) );
    }
}

// Turn on authentication with plugin activation
register_activation_hook( __FILE__, 'extwpauth_activate' );
function extwpauth_activate() {
    if( !get_option( 'extwpauth-enabled' )) {
        update_option( 'extwpauth-enabled', 1 );
    }
}


// Setup the external auth
//add_filter( 'authenticate', 'extwpauth_authenticate', 10, 3 );
//function extwpauth_authenticate( $user, $username, $password) {
//    global $auth_url;
//    // Make sure a username and password are present for us to work with
//    if($username == '' || $password == '') return;
// 
//    $response = wp_remote_get( "$auth_url&user=$username&pass=$password" );
//   
//    $ext_auth = json_decode( $response['body'], true );
// 
//     if( $ext_auth['result']  == 0 ) {
//        // User does not exist,  send back an error message
//        $user = new WP_Error( 'denied', __("<strong>Invalid External Login</strong>: User/pass bad") );
// 
//     } else if( $ext_auth['result'] == 1 ) {
//         // External user exists, try to load the user info from the WordPress user table
//         $userobj = new WP_User();
//         $user = $userobj->get_data_by( 'login', 'fm_'.$ext_auth['data']['user_login'] ); // Does not return a WP_User object <img src='http://ben.lobaugh.net/blog/wp-includes/images/smilies/icon_sad.gif' alt=':(' class='wp-smiley' />
//         $user = new WP_User($user->ID); // Attempt to load up the user with that ID
//
//         if( $user->ID == 0 ) {
//             // The user does not currently exist in the WordPress user table.
//             // You have arrived at a fork in the road, choose your destiny wisely
// 
//             // If you do not want to add new users to WordPress if they do not
//             // already exist uncomment the following line and remove the user creation code
//             //$user = new WP_Error( 'denied', __("<strong>ERROR</strong>: Not a valid user for this system") );
// 
//             // Setup the minimum required user information for this example
//             $userdata = array( 'user_email' => $ext_auth['data']['email'],
//                                'user_login' => "fm_".$ext_auth['data']['user_login'],
//                                //'first_name' => $ext_auth['first_name'],
//                                //'last_name' => $ext_auth['last_name']
//                                'user_nicename' => "FM_" . $ext_auth['data']['user_nicename'],
//                                'role' => $ext_auth['roles'][0]
//                                );
//             $new_user_id = wp_insert_user( $userdata ); // A new user has been created
// 
//             // Load the new user info
//             $user = new WP_User ($new_user_id);
//         } 
// 
//     }
// 
//     // Comment this line if you wish to fall back on WordPress authentication
//     // Useful for times when the external service is offline
//     //remove_action('authenticate', 'wp_authenticate_username_password', 20);
// 
//     return $user;
//}