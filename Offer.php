<?php

require '../../etc/Env.php'; // Require the environmental variables file
// Declare the variables we'll need
$user_agent = "SwapBotOffer/4.1 (https://ryanairdubcabincrew.slack.com; 23.jonathantadeoleiva@gmail.com)";
$command = "Offer";
$text = $_POST["text"];
$user_id = $_POST["user_id"];
$crewcode = "";
$duty = "OFF";
$rank = "";
$trigger_id = $_POST['trigger_id'];

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
 * For computing the signature, I need the following:  *
 *   1. $version                                       *
 *   2. $x_slack_timestamp                             *
 *   3. $raw_body                                      *
 * All of them appended to each other with a colon (:) *
 */
$signature_base_string = $version . ":" . $x_slack_timestamp . ":" . $raw_body;
$hash_signature = "v0=" . hash_hmac('sha256', $signature_base_string, $slack_signing_secret);
if (!hash_equals($x_slack_signature, $hash_signature)) {
    header("HTTP/1.1 400 Bad Request", true, 400);
    $err = ":warning: Request does not come from Slack!";
    die($err);
}

// Initialize the message variable as well as an error one so the append will work
$msg = '';
$err = '';

// Now we're gonna connect to the database, to check whether the crewcode is someone based in DUB or it's even registered in the database.
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (mysqli_error($conn)) {
    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

$stmt_check = mysqli_prepare($conn, "SELECT * FROM Crew WHERE UserID = ?");
mysqli_stmt_bind_param($stmt_check, "s", $user_id);
mysqli_stmt_execute($stmt_check);
if (mysqli_stmt_affected_rows($stmt_check) === 0) {
    die("Since you're not registered in the database, I don't know who this is. Please send a DM to <@U010PDT5UM7> with your crewcode, name and rank");
}
mysqli_stmt_close($stmt_check); // If the check was successful, close the statement and free memory for the next operations
if (empty($text)) { // Check if any info was introduced. If it wasn't, open a modal
    include "RequestSwaps/OfferSwap.php";

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
    die();
}

// Divide them into the different values we'll needed
$args = explode(" ", $text, 2);
$fecha = $args[0];
$earlieslates = ucfirst(strtolower($args[1]));

$sql = "SELECT Crewcode, Base, Rank, UserID FROM Crew WHERE UserID = ?";
$stmt_base = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt_base, "s", $user_id);
mysqli_stmt_execute($stmt_base);
$result_base = mysqli_stmt_get_result($stmt_base);

if (mysqli_stmt_affected_rows($stmt_base) > 0) {
    while ($row = mysqli_fetch_assoc($result_base)) {
        if ($row["Base"] != 'DUB') { // Check if they're based in DUB
            $err .= "You are not based in DUB at the moment. Right now, you're based in " . $row["Base"];
        }

        $crewcode = $row["Crewcode"]; // Store the crewcode for later

        $rank = $row["Rank"]; // Recover the rank for later usage
    }
} else {
    die("Since you're not registered in the database, I don't know who this is. Please send a DM to <@U010PDT5UM7> with your crewcode, name and rank");
}
// I'm closing the statement so I can set a new one later. There's no need to close the connection at this moment.
mysqli_stmt_close($stmt_base);

// We have to divide the argument "date" into day, month and year. Prior to dividing the date, check if it follows the format I've asked, which is the numbers separated by a dash (-)
$pattern = "/[^\d-]+/";

if (preg_match($pattern, $fecha)) {
    $err .= "\nPlease follow the correct format. It is dd-mm-yy. Notice the dashes between the numbers";
    goto SkipDate;
}

// Now we will divide them into Day, Month and Year.
$fechap = explode("-", $fecha, 3);
$dd = $fechap[0];
$mm = $fechap[1];
$yy = $fechap[2];

// We're now checking if any of the variables $mm or $yy are empty

if (empty($mm) OR empty($yy)) {
// If you're missing the month or the year (I could make year optional, to be honest, and then check for it's value; if it was empty, then assign this year's value)"
    $err .= "\nNeed to input a full date (dd-mm-yy)";
    goto SkipDate;
}

// This will check that the date exists
if (!checkdate($mm, $dd, $yy)) { // If the date is invalid (i.e. 29-02-21)
    $err .= "\nThe date you're trying to choose doesn't exist. Please choose an appropriate one :calendar:";
    goto SkipDate;
}

// Define the timeframe for the swap offer. If it's less than 12 days but more than 6, it will not be processed.
// These are the times in Epoch (seconds since 01Jan70)

$rosterlimitepoch = mktime(12, 0, 0, date("m"), date("d") + 30, date("y"));
$swaprequestepoch = mktime(12, 0, 0, $mm, $dd, $yy);
$swapnotedepoch = mktime(12, 0, 0, date("m"), date("d") + 12, date("y"));
$swaplimitbsepoch = mktime(12, 0, 0, date("m"), date("d") + 6, date("y"));
$todayepoch = mktime(12, 0, 0, date("m"), date("d"), date("y"));

// Compare to the date the user provided

switch (true) {
    case ($rosterlimitepoch < $swaprequestepoch): // If the date is >+30 days (this number might change)
        die("It is too soon to offer a swap. Please try again later");
        break;
    case ($swaprequestepoch >= $swapnotedepoch): // If the date is +14 days
        break;
    case (($swaprequestepoch < $swapnotedepoch) && ($swaprequestepoch >= $swaplimitbsepoch)): // If the date is between 6 and 14 days from today
        $err .= "\nIt might be too late to offer a swap. Your request won't be processed.";
        break;
    case (($swaprequestepoch < $swaplimitbsepoch) && ($swaprequestepoch > $todayepoch)): // If the date is less than 6 days from today
        $err .= "\nIt is too late to offer a swap";
        break;
    default: // Any other case scenario, which is a date being in the past
        $err .= "\nWhy do you put a date in the past?";
        break;
}

SkipDate:

/* There's no need to input a duty, since if you're offering a swap you'll be off.
 * The next thing to check will be whether you're after/before earlies/lates, as those affect your capacity to give a swap.
 * (i.e. Can't give a swap on your first day off after lates for an early shift) */

if (!array_key_exists(1, $args)) { // Check if a shift was introduced
    $msg .= "\nBy the way, you didn't input a shift; therefore we're assuming you have no preference";
    goto SkipEarliesLates;
}

switch ($earlieslates) { // See what the person chose, either be Earlies (E), Lates (L) or anything else (which it isn't a valid input)
    case "E":
    case "Earlies":
        $earlieslates = "Earlies";
        break;
    case "L":
    case "Lates":
        $earlieslates = "Lates";
        break;
    default;
        $earlieslates = "";
}

SkipEarliesLates:

if (!empty($err)) { // If there's any error message, display it and end the program without adding anything to the table.
    die($err);
}

// Once everything has been double checked, we will add the offer to the Swaps table

$fecha = $yy . "-" . $mm . "-" . $dd;

$sqlinsert = "INSERT INTO Swaps (Crewcode, Day, Duty, Command, Shift, Rank, UserID) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sqlinsert);
mysqli_stmt_bind_param($stmt, "sssssss", $crewcode, $fecha, $duty, $command, $earlieslates, $rank, $user_id);
mysqli_stmt_execute($stmt);

if (mysqli_error($conn) != "") {
    echo "\nYour offer couldn't be added at this time :disappointed:";
    echo "\n" . mysqli_error($conn);
} else {
    echo "\nYour offer has been successfully created! :simple_smile:" . $msg;
}

// Close the statement and the connection prior to ending the program
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>