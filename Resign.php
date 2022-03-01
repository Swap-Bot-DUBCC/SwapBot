<?php

require '../../etc/Env.php'; // Require the environmental variables file
// First verify that the request comes from Slack by using the signing secret hash
$headers = getallheaders();
$raw_body = file_get_contents('php://input');
$body = json_decode($raw_body, true);
$x_slack_signature = $headers["X-Slack-Signature"];
$x_slack_timestamp = $headers["X-Slack-Request-Timestamp"];
$version = "v0";

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

// Define useful variables
$slack_post_message = "https://slack.com/api/chat.postmessage";
$user_id = $_POST['user_id'];
/* Resignation program. Will most likely show an ephemeral message with a red button that says "Are you sure?"
Alternatively, I could design a form with which I could gather the date when the person is leaving and get their account deactivated.
It's a shame I can't use the SCIM API to disable their accounts, so guess I'll have to do it manually. Shouldn't be much of a problem anyway. */
/*
$json_array = [
    'token' => $token,
    'channel' => $user_id,
    'text' => 'If you\'re resigning for sure, please send a DM to <@U010PDT5UM7> with your date of leaving Ryanair.',
    'blocks' => [
	











?>