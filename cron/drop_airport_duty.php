<?php

// Connect to the database

$servername = "localhost";

$username = "ryanaird_swapbot";

$password = "@C4I%G%+61z&";

$dbname = "ryanaird_info";

$conn = mysqli_connect($servername, $username, $password, $dbname);

// Delete all data from the table

$sql = "TRUNCATE TABLE Airport_Duty";

mysqli_query($conn, $sql);

mysqli_close($conn);

?>