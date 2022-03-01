<?php

require '../../etc/Env.php'; // Require the environmental variables file
// Check the request is valid

$headers = getallheaders();

$raw_body = file_get_contents('php://input');

$body = json_decode($raw_body, true);

$x_slack_signature = $headers["X-Slack-Signature"];

$x_slack_timestamp = $headers["X-Slack-Request-Timestamp"];

$version = "v0";

$user_id = $_POST['user_id']; // Get the user ID for checking in or out
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

$command = ucfirst(strtolower($_POST['text'])); // Recover whatever the user posted

$ad_earlies_checkin_start = mktime(4, 30, 0); // Check-in for Early AD opens at 4:30

$ad_earlies_checkin_end = mktime(11, 0); // Check-in for Early AD closes at 11:00

$ad_earlies_start = mktime(5, 0); // Early AD starts at 5:00

$ad_earlies_finish = mktime(13, 0); // Early AD finishes at 13:00

$ad_lates_checkin_start = mktime(11, 30, 0); // Check-in for Late AD opens at 11:30

$ad_lates_start = mktime(12, 0); // Late AD starts at 12:00

$ad_lates_finish = mktime(20, 0); // Late AD finishes at 20:00

$now = time(); // Recover the time the command is used, to see whether you should check-in on earlies or lates
// Connect to the database

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_error($conn)) {

    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

switch ($command) {
    case "In": // The person put "In", so get the time the command was called and check them in the appropiate list
        switch (true) {
            case (($now >= $ad_earlies_checkin_start) && ($now < $ad_earlies_checkin_end)): // Actual time is between 4:30 and 11:00
                $sql = "INSERT INTO Airport_Duty VALUES (?, 'Earlies')";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $user_id);
                mysqli_stmt_execute($stmt);
                if (mysqli_errno($conn) == 0) {
                    echo "You have checked in for early Airport Duty";
                } elseif (mysqli_errno($conn) == 1062) {
                    echo "You're already checked in for early Airport Duty";
                } else {
                    echo "There was an error processing your request:\n" . mysqli_error($conn);
                }
                break;
            case (($now >= $ad_lates_checkin_start) && ($now < $ad_lates_finish)): // Actual time is between 11:30 and 20:00
                $sql = "INSERT INTO Airport_Duty VALUES (?, 'Lates')";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $user_id);
                mysqli_stmt_execute($stmt);
                if (mysqli_errno($conn) == 0) {
                    echo "You have checked in for late Airport Duty";
                } elseif (mysqli_errno($conn) == 1062) {
                    echo "You're already checked in for late Airport Duty";
                } else {
                    echo "There was an error processing your request:\n" . mysqli_error($conn);
                }
                break;
            default:
                echo "The time for checking in isn't within the allocated time frame";
                break;
        }
        mysqli_stmt_close($stmt);
        break;
    case "Out": // The person put "Out", so get the time the command was called and delete them from the list (pretty much taken for a flight)
        /* I have no way to verify whether the reason why you're checking out is because you were taken for a flight, or because you just don't want to
          appear on my list. Anyway, I guess I'll try to trust in the people, and if I see there's abuse of the system, I will straight up remove the option altogether */
        if (($now > $ad_earlies_start) && ($now < $ad_lates_finish)) { // Actual time is between 5:00 and 20:00
            $sql = "DELETE FROM Airport_Duty WHERE UserID = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $user_id);
            mysqli_stmt_execute($stmt);
            if (mysqli_error($conn)) { // Check for errors in the query
                echo "There was an error processing your request:\n" . mysqli_error($conn);
            } else {
                if (mysqli_affected_rows($conn) > 0) { // Check if any rows were deleted
                    echo "You have been checked out successfully";
                } else {
                    echo "You haven't checked in. Can't check out in this case";
                }
            }
        } else {
            echo "You are trying to check out outside the allocated time frame\n Check out is automatically performed at 21:00";
        }
        mysqli_stmt_close($stmt);
        break;
    default: // Recover the list of people who are checked in (aka the full Airport_Duty table), and display them
        $airportduty = [];
        switch (true) { // Check what's the time, and retrieve the list accordingly
            case ($now < $ad_lates_start): // Before 12:00
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
                    echo "Either there's no one in Airport Duty on earlies, or they're sleeping";
                    break;
                }
                echo $msg;
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
                    echo "Neither earlies nor lates checked-in, or maybe there isn't anyone";
                    break;
                }
                echo $msg;
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
                    echo "Someone is taking a very long lunch break, or maybe there's no one";
                    break;
                }
                echo $msg;
                break;
            default: // Time is after 20:00
                echo "Airport Duty on lates finished at 20:00. There isn't anybody around";
                break;
        }
}

// Close the connection prior to ending the program
mysqli_close($conn);
?>