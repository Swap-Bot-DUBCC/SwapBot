<?php

require '../../../etc/Env.php'; // Require the environmental variables file

/* This file will be a part time partner finder.
 * Then we'll process the ranks and whatever shift availability they've got (i.e someone looking for earlies or lates)
 * I could actually use this file for looking for FLEXI partners as well (full time earlies only or lates only)
 * In order to do this, I will have to design the table first and I'll write this program based on whatever I design
 * I could execute this bimonthly (once every 2 months), but frequency can be adjusted as we go
 */

// Connect to the database and recover all the list of applicants for part time or full time flexi
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (mysqli_error($conn)) {
    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");
}

$sql_dump_part_time = "SELECT * FROM Part_Time";
$result_part_time = mysqli_query($conn, $sql_dump_part_time);

?>