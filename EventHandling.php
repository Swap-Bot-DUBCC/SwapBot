<?php

require '../../etc/Env.php'; // Require the environmental variables file
$user_agent = "Events API handler which will then redirect to the appropiate file";
$headers = getallheaders();
$raw_body = file_get_contents('php://input');
$body = json_decode($raw_body, true);
$x_slack_signature = $headers["X-Slack-Signature"];
$x_slack_timestamp = $headers["X-Slack-Request-Timestamp"];
$version = "v0";
$user_id = $body['event']['user'];
$event_type = $body['event']['type'];

// Check if the timestamp from Slack and the actual time differ in more than 300s (5min). If so, discard this message
if (abs($x_slack_timestamp - time()) > 300) {
    header("HTTP/1.1 400 Bad Request", true, 400);
    $err = ":warning: Old request. Discarding";
    die($err);
}
/*

  For computing the signature, I need the following:

  1. $version
  2. $x_slack_timestamp
  3. $raw_body

  All of them appended to each other with a colon (:)

 */

$signature_base_string = $version . ":" . $x_slack_timestamp . ":" . $raw_body;
$hash_signature = "v0=" . hash_hmac('sha256', $signature_base_string, $slack_signing_secret);
if (!hash_equals($x_slack_signature, $hash_signature)) {
    header("HTTP/1.1 400 Bad Request", true, 400);
    $err = ":warning: Request does not come from Slack!";
    die($err);
}

// If the file reaches here, then include the pertinent file to handle the event
switch ($event_type) {
    case "app_home_opened": // Someone opened my app's home or message tab
        include "Events/AppHome.php";
        break;
    case "team_join": // Someone joined my workspace, either be it by invitation or magic link
        include "Events/TeamJoin.php";
        break;
    case "message":
        include "Events/MessageChannels.php";
        break;
}
?>