<?php
session_start();
require __DIR__ . '/inc.config.php';

// Handle login callback
// https://developer.github.com/apps/building-oauth-apps/authorization-options-for-oauth-apps/
if(isset($_GET["code"]) && $_GET["state"] === $_SESSION["tmpcode"]) {
    $post = 'client_id=' . CLIENT_ID .
            '&client_secret=' . CLIENT_SECRET .
            '&code=' . $_GET["code"] .
            '&state=' . $_SESSION["tmpcode"];

    $c = curl_init('https://github.com/login/oauth/access_token');
    curl_setopt($c, CURLOPT_POST, 1);
    curl_setopt($c, CURLOPT_POSTFIELDS, $post);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_HTTPHEADER, array('Accept: application/json'));

    $response = curl_exec($c);
    if(!$response) {
        echo curl_error($c);
        die;
    }
    curl_close($c);

    $json = json_decode($response, true);
    if($json["error"]) {
        $_SESSION["error"] = $json["error_description"];
    } else {
        $_SESSION["token"] = $json["access_token"];
    }
}
header("Location:/");
exit;