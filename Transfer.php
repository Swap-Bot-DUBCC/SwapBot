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

if (abs($x_slack_timestamp - time() > 300)) {
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

// This will be a prototype of a base updater, that way I don't need to be on top of this

$user_id = $_POST['user_id'];

$base = $_POST['text'];

//Initialize the err and msg variables

$msg = '';

$err = '';

// Connect to the database

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_error($conn)) {
    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

if (empty($base)) {
    die ("No base has been introduced");
}

// Verify that the airport you're transferring to is valid
$sql = "INSERT INTO Airport_Check VALUES (?)";
$stmt_insert = mysqli_stmt_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt_insert, "s", $base);
mysqli_stmt_execute($stmt_insert);
if (mysqli_stmt_affected_rows($stmt_insert) === 1) {
    $sqlcompare = "SELECT Airport_Check.AirportCode AS AirportCode, CASE WHEN Airports.AirportCode IS NULL THEN 'Not valid' ELSE 'Valid' END AS Valid FROM Airport_Check LEFT JOIN Airports ON Airport_Check.AirportCode = Airports.AirportCode";
    $result = mysqli_query($conn, $sqlcompare); // Run the comparison
    while ($row = mysqli_fetch_assoc($result)) {
	if ($row["Valid"] != 'Valid') {
		die($row["AirportCode"] . " is an invalid airport :warning:"); // Display an error if the airport introduced isn't valid
	}	
    }
} else {
    die($base . " is an invalid airport :warning:");
}
mysqli_stmt_close($stmt_insert);

$sqldel = "DELETE FROM Airport_Check"; // Delete the requests for checking whether the airports were valid or not
mysqli_query($conn, $sqldel);

// Update the base in the database
$sql_update = "UPDATE Crew SET Base = ? WHERE UserID = ?";
$stmt_update = mysqli_stmt_prepare($conn, $sql_update);
mysqli_stmt_bind_param($stmt_update, "ss", $base, $user_id);
mysqli_stmt_execute($stmt_update); // Change the base
if (mysqli_stmt_affected_rows($stmt_update) > 0) {
    echo "Your transfer has been successfully processed.\nPlease be aware that transferring base will disallow you from using the swap system until you're back in DUB";
    // Add some code that will text me whenever anybody runs this script so I can verify the base transfer
    
} else {
    echo mysqli_error($conn);
}
mysqli_stmt_close($stmt_update); // Close the statement
mysqli_close($conn); // Close the connection

?>