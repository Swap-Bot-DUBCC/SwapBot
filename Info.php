<?php

$user_agent = "SwapBotRequest/4.1 (https://ryanairdubcabincrew.slack.com; 23.jonathantadeoleiva@gmail.com)";

require '../../etc/Env.php'; // Require the environmental variables file
// First verify that the request comes from Slack by using the signing secret hash

$headers = getallheaders();

$raw_body = file_get_contents('php://input');

$body = json_decode($raw_body, true);

$x_slack_signature = $headers["X-Slack-Signature"];

$x_slack_timestamp = $headers["X-Slack-Request-Timestamp"];

$version = "v0";

$user_id = $_POST["user_id"];

$trigger_id = $_POST["trigger_id"];

$channel_id = $_POST["channel_id"];

$text = $_POST["text"];

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

/* This script will be to check people's information in order to see if information is up to date.
 * I will put two ways of checking the info: for admins and owners (so far Niccolo and me), I will allow to check on whoever
 * by passing an user to the function (i.e tagging someone). For everyone else, I will only let them check
 * their own info, regardless if they try to pass an argument or not
 */

// Connect to the database

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_error($conn)) {

    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

// Check if the user calling the function is an admin
// Let's make a call to the users.info API

$user_info_string = "token=" . $token . "&user=" . $user_id;
$user_info = curl_init("https://slack.com/api/users.info");
curl_setopt($user_info, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($user_info, CURLOPT_POSTFIELDS, $user_info_string);
curl_setopt($user_info, CURLOPT_CRLF, true);
curl_setopt($user_info, CURLOPT_RETURNTRANSFER, true);
curl_setopt($user_info, CURLOPT_HTTPHEADER, [
    "Content-Type: application/x-www-form-urlencoded",
    "Authorization: Bearer " . $token,
    "Content-Length: " . strlen($user_info_string)]
);
$user_response = curl_exec($user_info);
// Now we skim through the response, finding if it's an admin or not
$response_array = json_decode($user_response, true);
if ($response_array['ok'] === true) {
    if ($response_array['user']['is_admin'] === true) {
        // The user is an admin, so we allow them to pass an id to check
        if (!empty($text)) {
            $user_id_check_array = explode("|", $text);
            $user_id_check = trim($user_id_check_array[0], "<@>");
        } else {
            $user_id_check = $user_id;
        }
    } else {
        // The user is not an admin, so we check his own information
        $user_id_check = $user_id;
    }
} else {
    die("An error has ocurred. Please contact <@U010PDT5UM7> and pass the following error code: _" . $response_array['error'] . "_");
}

// We've determined what userID to check, now let's check it
$sql_check = "SELECT Crewcode, Base, Nombre, Rank FROM Crew WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql_check);
mysqli_stmt_bind_param($stmt, "s", $user_id_check);
mysqli_stmt_execute($stmt);
$id_check_result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($id_check_result) === 0) {
    die("The person you're trying to retrieve information from has not been registered in the database. Please DM <@U010PDT5UM7> with this message");
} else {
    while ($row = mysqli_fetch_assoc($id_check_result)) {
        $crewcode = $row['Crewcode'];
        $name = $row['Nombre'];
        $base = $row['Base'];
        $rank = $row['Rank'];
    }
    // Craft the message we're going to send
    $message_array = [
        'token' => $token,
        'channel' => $channel_id,
        'user' => $user_id,
        'blocks' => [
            0 => [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "Here's the information you've requested",
                    'emoji' => false,
                ],
            ],
            1 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "Crewcode: " . $crewcode . "\n"
                    . "Name: " . $name . "\n"
                    . "Base: " . $base . "\n"
                    . "Rank: " . $rank . "\n"
                ,
                ],
            ],
            2 => [
                'type' => 'divider',
            ],
            3 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '_Please contact <@U010PDT5UM7> if the information above is not correct_',
                ],
            ],
        ],
    ];
}

$json_string = json_encode($message_array);

// Open a cURL request to the ephemeral messages API
$slack_call = curl_init($slack_post_ephemeral);
curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);
curl_setopt($slack_call, CURLOPT_CRLF, true);
curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);
curl_setopt($slack_call, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token,
    "Content-Length: " . strlen($json_string)]
);
$result_curl = curl_exec($slack_call); // Store the result, in case there's any errors
// Clean up before closing the program
curl_close($slack_call);
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>