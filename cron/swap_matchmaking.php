<?php

require '../../../etc/Env.php'; // Require the environmental variables file

/* This .php file will be an idea I have had about a swap matchmaking tool.
 * I will have to organize the way to do it
 * Definitely I will have to import from other files a rank comparison tool (switch statement)
 * I'll also have to inherit a shift selector
 */

// Connect to the database

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (mysqli_error($conn)) {
    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

$sql_dump_swaps = "SELECT * FROM Swaps ORDER BY Day";
$result_swaps = mysqli_query($conn, $sql_dump_swaps);
$swaps_table_requests = [];
$swaps_table_offers = [];
while ($row_swaps_dump = mysqli_fetch_assoc($result_swaps)) {
    if (strpos($row_swaps_dump["Command"], "Request") === 0) {
        array_push($swaps_table_requests, $row_swaps_dump);
    } else {
        array_push($swaps_table_offers, $row_swaps_dump);
    }
}

/* Now the table swaps has been dumped into 2 arrays; one of them being the requests and the other one the offerings.
 * Now I need to find a way to compare them. I guess the first thing I am going to compare is the date, and from there we can work our way up 
 * onto more "complicated" filters, such as the shift and the rank compatibility.
 * I shouldn't need the connection to the dabatabase any longer, so to free resources I will close the connection now */
mysqli_close($conn);

file_put_contents("SwapRequests.txt", print_r($swaps_table_requests, true));
file_put_contents("SwapOffers.txt", print_r($swaps_table_offers, true));
