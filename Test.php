<?php

require '../../etc/Env.php'; // Require the environmental variables file
// First verify that the request comes from Slack by using the signing secret hash

$headers = getallheaders();

$raw_body = file_get_contents('php://input');

$body = json_decode($raw_body, true);

$x_slack_signature = $headers["X-Slack-Signature"];

$x_slack_timestamp = $headers["X-Slack-Request-Timestamp"];

$version = "v0";

$channel_id = $_POST['channel_id'];

$user_id = $_POST['user_id'];

$trigger_id = $_POST['trigger_id'];

$text = ucfirst(strtolower($_POST['text']));

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

if ($user_id !== "U010PDT5UM7") {
    die("You're not authorized to use this command");
}

// Initialize the $msg and $err variables, so I can append things to them later

$msg = '';

$err = '';

// echo "Nothing to test now!";

// Connect to the database
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_error($conn)) {

    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

// First we check whether the person wants a day off or a shorter day. Then, we'll show the pertinent form. 

switch ($text) {
    case "O":
    case "Off":
        $sql = "SELECT Rank FROM Crew WHERE UserID = ? ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result_text = mysqli_stmt_get_result($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            while ($row = mysqli_fetch_row($result_text)) {
                $rank = $row[0];
            }
        } else {
           die("I'm sorry. An error has ocurred. However, this error is because you're not registered in the database. Send a DM to <@U010PDT5UM7> with your name, rank and crewcode and it will be sorted promptly");
        }
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        switch ($rank) {
            case "JU":
                include "Forms/JU_Off.php";
                break;
            case "JU (AH)":
            case "JU (PU)":
                include "Forms/JU (PU)_Off.php";
                break;
            case "PU (TC)":
            case "PU":
            case "PU (DS)":
            case "PU (BS)":
            case "PU (LC)":
            case "PU (INS)":
            case "PU (SEP)":
                include "Forms/PU_Off.php";
                break;
        }
        break;
    case "S":
    case "Shorter":
        $sql = "SELECT Rank FROM Crew WHERE UserID = ? ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result_text = mysqli_stmt_get_result($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            while ($row = mysqli_fetch_row($result_text)) {
                $rank = $row[0];
            }
        } else {
           die("I'm sorry. An error has ocurred. However, this error is because you're not registered in the database. Send a DM to <@U010PDT5UM7> with your name, rank and crewcode and it will be sorted promptly");
        }
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        switch ($rank) {
            case "JU":
                include "Forms/JU_Shorter.php";
                break;
            case "JU (AH)":
            case "JU (PU)":
                include "Forms/JU (PU)_Shorter.php";
                break;
            case "PU (TC)":
            case "PU":
            case "PU (DS)":
            case "PU (BS)":
            case "PU (LC)":
            case "PU (INS)":
            case "PU (SEP)":
                include "Forms/PU_Shorter.php";
                break;
        }
        break;
    case "F":
    case "Flight":
       $sql = "SELECT Rank FROM Crew WHERE UserID = ? ";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $user_id);
        mysqli_stmt_execute($stmt);
        $result_text = mysqli_stmt_get_result($stmt);
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            while ($row = mysqli_fetch_row($result_text)) {
                $rank = $row[0];
            }
        } else {
           die("I'm sorry. An error has ocurred. However, this error is because you're not registered in the database. Send a DM to <@U010PDT5UM7> with your name, rank and crewcode and it will be sorted promptly");
        }
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        switch ($rank) {
            case "JU":
                include "Forms/JU_Flight.php";
                break;
            case "JU (AH)":
            case "JU (PU)":
                include "Forms/JU (PU)_Flight.php";
                break;
            case "PU (TC)":
            case "PU":
            case "PU (DS)":
            case "PU (BS)":
            case "PU (LC)":
            case "PU (INS)":
            case "PU (SEP)":
                include "Forms/PU_Flight.php";
                break;
        }
        break;
}

$slack_call = curl_init($slack_open_dialog);
curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);
curl_setopt($slack_call, CURLOPT_CRLF, true);
curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);
curl_setopt($slack_call, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token,
    "Content-Length: " . strlen($json_string)]
);
$result = curl_exec($slack_call);
curl_close($slack_call);
?>