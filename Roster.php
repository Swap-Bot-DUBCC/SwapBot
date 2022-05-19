<?php

require '../../etc/Env.php'; // Require the environmental variables file

// Check that the request comes from Slack

$headers = getallheaders();

$raw_body = file_get_contents('php://input');

$body = json_decode($raw_body, true);

$x_slack_signature = $headers["X-Slack-Signature"];

$x_slack_timestamp = $headers["X-Slack-Request-Timestamp"];

$version = "v0";

$user_id = $_POST["user_id"];

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

// Prototype for a roster change (?

$roster = $_POST['text'];

// Check if it's a number and it's between 0 and 16 (both inclusive)

if ((ctype_digit($roster)) == false OR ($roster > 16 OR $roster < 0)){
	
	die("Please input a valid roster");
}

// Connect to the database

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_error($conn)) {

	die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");

}

$sql = "UPDATE Crew SET Roster = ? WHERE UserID = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "is", $roster, $user_id);

mysqli_stmt_execute($stmt);

// Because this is not a SELECT query, it will not return a result. We need to see if it was successful and to do so we need to call mysqli_errno()

$updated = mysqli_stmt_affected_rows($stmt);

if ($updated === 1) {
	echo "Your roster change has been processed. Please be advised you *may* be asked for proof in order to maintain accurate records";
} else {
	echo mysqli_error($conn);
}

mysqli_stmt_close($stmt); // Close the statement and the connection
mysqli_close($conn);
?>