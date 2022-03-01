<?php

// This file will handle the view.close payloads received when someone closes the form by aborting the process to delete the swap

require '../../etc/Env.php'; // Require the environmental variables file

$raw_body = file_get_contents('php://input');

file_put_contents("TestClose.txt", $raw_body); // See how the payload looks like when we close it, and from there we should be able to handle a specific format

?>