<?php

require '../../etc/Env.php'; // Require environmental variables file

$slack_post_message = "https://slack.com/api/chat.postMessage";
$ts = $body['event']['ts']; // To respond to a message, we need its "ts" value
$thread_ts = $body['event']['thread_ts']; // We also want to make sure that the message is not a thread already
$channel = $body['event']['channel'];
$message_type = $body['event']['channel_type']; // Make sure it's a public channel, because I could also get texts from the app, and unfortunately, those can't be answered yet
$text = $body['event']['text'];

if ($message_type !== "channel") { // Making sure the message is on a public channel
    return;
}
if ($user_id === "U011GFXKF08") { // This line will avoid my app replying to itself
    return;
}

// file_put_contents("Output.txt", print_r($body, TRUE)); // DEBUG PURPOSES

/* First we have to filter out the contents of the message, I don't want my app replying to EVERY single message.
  I'm going to filter out the following expressions, that I can and will amend depending on usage:
  AD; Airport Duty, airport duty */

$ad_strings = [" AD", "Airport Duty", "airport duty", "Airport duty", "airport Duty"];

foreach ($ad_strings as $needle) {
    if (strpos($text, $needle) !== FALSE) {
        goto MatchFound;
    } else {
        continue;
    }
}
return;
MatchFound:

// Connect to the database and prepare a statement

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_error($conn)) {
    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

//Define the time frames for my app to respond accordingly

$ad_earlies_start = mktime(5, 0); // Early AD starts at 5:00
$ad_earlies_finish = mktime(13, 0); // Early AD finishes at 13:00
$ad_lates_start = mktime(12, 0); // Late AD starts at 12:00
$ad_lates_finish = mktime(20, 0); // Late AD finishes at 20:00
$now = time();
switch (true) {
    case (($now < $ad_earlies_start)): // Before 5am
        $msg = "Wait a few minutes, it's too early";
        break;
    case (($now >= $ad_earlies_start) && ($now <= $ad_lates_start)): // Between 5am and 12pm
        $airportduty = [];
        $sql = "SELECT UserID FROM Airport_Duty WHERE Shift='Earlies'";
        $msg = "The people on Airport Duty on earlies are: ";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 1) {
            while ($row = mysqli_fetch_assoc($result)) {
                array_push($airportduty, $row["UserID"]);
            }
            foreach ($airportduty as $userid) {
                $msg .= (next($airportduty)) ? "<@" . $userid . ">, " : "and <@" . $userid . ">";
            }
        } elseif (mysqli_num_rows($result) == 1) {
            while ($row = mysqli_fetch_assoc($result)) {
                $msg .= "<@" . $row["UserID"] . ">";
            }
        } else {
            $msg = "Either there's no one in Airport Duty on earlies, or they're sleeping";
            break;
        }
        break;
    case (($now > $ad_lates_start) && ($now < $ad_earlies_finish)): // Time is between 12:00 and 13:00
        $sql = "SELECT UserID FROM Airport_Duty";
        $msg = "The people on Airport Duty in both earlies and lates are: ";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 1) {
            while ($row = mysqli_fetch_assoc($result)) {
                array_push($airportduty, $row["UserID"]);
            }
            foreach ($airportduty as $userid) {
                $msg .= (next($airportduty)) ? "<@" . $userid . ">, " : "and <@" . $userid . ">";
            }
        } elseif (mysqli_num_rows($result) == 1) {
            while ($row = mysqli_fetch_assoc($result)) {
                $msg .= "<@" . $row["UserID"] . ">";
            }
        } else {
            $msg = "Neither earlies nor lates checked-in, or maybe there isn't anyone";
            break;
        }
        break;
    case (($now > $ad_earlies_finish) && ($now < $ad_lates_finish)): // Time is between 13:00 and 20:00
        $sql = "SELECT UserID FROM Airport_Duty WHERE Shift='Lates'";
        $msg = "The people on Airport Duty on lates are: ";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 1) {
            while ($row = mysqli_fetch_assoc($result)) {
                array_push($airportduty, $row["UserID"]);
            }
            foreach ($airportduty as $userid) {
                $msg .= (next($airportduty)) ? "<@" . $userid . ">, " : "and <@" . $userid . ">";
            }
        } elseif (mysqli_num_rows($result) == 1) {
            while ($row = mysqli_fetch_assoc($result)) {
                $msg .= "<@" . $row["UserID"] . ">";
            }
        } else {
            $msg = "Someone is taking a very long lunch break, or maybe there's no one";
            break;
        }
        break;
    case (($now > $ad_lates_finish)): // This should trigger after 20:00, but it's triggering every time.
        $msg = "It is too late. There isn't anyone in Airport Duty. Try again tomorrow, I guess";
        break;
}

// Now we've crafted the message we want to display to the user; it's now time to do so

if (isset($thread_ts)) { // If there is a thread_ts, the message is part of a thread
    if ($thread_ts !== $ts) { /*
     * If these values are different, the message is a child message *
     * I'll need to use the parent's $thread_ts instead. *
     * Also, I could straight up not answer, but I think it's quite stupid. *
     */
        $message_array = [
            'token' => $token,
            'channel' => $channel,
            'text' => $msg,
            'thread_ts' => $thread_ts,
        ];
    }
} else { // If there's no thread_ts, the message is not part of a thread (but will be soon)
    $message_array = [
        'token' => $token,
        'channel' => $channel,
        'text' => $msg,
        'thread_ts' => $ts,
    ];
}
$message_string = json_encode($message_array);
// file_put_contents("Output.txt", $message_string);
$slack_message = curl_init($slack_post_message);
curl_setopt($slack_message, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($slack_message, CURLOPT_POSTFIELDS, $message_string);
curl_setopt($slack_message, CURLOPT_CRLF, true);
curl_setopt($slack_message, CURLOPT_RETURNTRANSFER, true);
curl_setopt($slack_message, CURLOPT_VERBOSE, true);
curl_setopt($slack_message, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token,
    "Content-Length: " . strlen($message_string)]
);
$result = curl_exec($slack_message);
curl_close($slack_call);
return;
?>