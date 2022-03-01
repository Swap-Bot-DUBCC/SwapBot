<?php

require '../../etc/Env.php'; // Require the environmental variables file
$user_agent = "Interactivity handler which will then redirect to the appropiate file";
$headers = getallheaders();
$raw_body = file_get_contents('php://input');
$x_slack_signature = $headers["X-Slack-Signature"];
$x_slack_timestamp = $headers["X-Slack-Request-Timestamp"];
$version = "v0";
$body = json_decode(urldecode(substr($raw_body, 8)), true);
$user_id = $body['user']['id'];
$interaction_type = $body['type'];

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

// If the file reaches here, then the request comes from Slack
switch ($interaction_type) {
    case "shortcut": // Used my Check-in or Check-out
        $callback_id = $body['callback_id'];
        include "Interactivity/shortcut.php";
        break;
    case "block_actions": // Clicked on a button in one of my scripts, or changed some text in the "Request a swap" form
        $type = $body['actions'][0]['type'];
        include "Interactivity/block_actions.php";
        break;
    case "view_submission": // Sent a form
        $form_title = $body['view']['title']['text'];
        $form_data = $body['view']['state']['values'];
        include "Interactivity/view_submission.php";
        break;
}
?>