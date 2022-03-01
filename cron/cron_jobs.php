<?php

// Connect to the database

$servername = "localhost";

$username = "ryanaird_swapbot";

$password = "@C4I%G%+61z&";

$dbname = "ryanaird_info";

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Delete all data from the table

$truncate_sql = "DELETE FROM Airport_Duty";

mysqli_query($conn, $truncate_sql);

/* Erase swaps that are due already (aka the same day or before).
// Deactivated for now, but it might become active depends on the usage it gets

$swaps_due_sql = "DELETE FROM Swaps WHERE Day <= DATETIME()";

mysqli_query($conn, $swaps_due_sql); */

// Close the connection

mysqli_close($conn);

?>