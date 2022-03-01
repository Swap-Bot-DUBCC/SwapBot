<?php

require '../../etc/Env.php'; // Require the environmental variables file

$user_agent = "Interactivity handler which will then redirect to the appropiate file";

$headers = getallheaders();

$raw_body = file_get_contents('php://input');

$x_slack_signature = $headers["X-Slack-Signature"];

$x_slack_timestamp = $headers["X-Slack-Request-Timestamp"];

$version = "v0";

$body = urldecode(substr($raw_body, 8));

$body = json_decode($body, true);

$user_id = $body['user']['id'];

$interaction_type = $body['type'];

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

// If the file reaches here, then the request comes from Slack

switch ($interaction_type) {
    case "shortcut": // Used my Check-in or Check-out
        $callback_id = $body['callback_id'];
        include "Interactivity/shortcut.php";
        break;
    case "block_actions": // Clicked on a button in one of my scripts, or changed some text in the "Request a swap" form
        $type = $body['actions']['type'];
        include "Interactivity/block_actions.php";
        break;
    case "view_submission": // Sent a form
        $form_title = $body['view']['title']['text'];
        $form_data = $body['view']['state']['values'];
        // include "Interactivity/view_submission.php";
        $err_array = array('response_action' => 'errors',); // Initialize error array
        $errors = array();
        $msg = ""; // Initialize message variable
        // Define the common variables in both forms
        $shift = ucfirst($form_data['shift']['select_shift']['selected_option']['value']);
        $fecha = $form_data['date']['select_date']['selected_date'];

        switch ($form_title) {
            case "Request a swap":
                $duty = $form_data['duty']['input_duty']['value'];

                // Connect to the database and prepare a statement
                $conn = mysqli_connect($servername, $username, $password, $dbname);
                $sql = "SELECT Crewcode, Rank FROM Crew WHERE UserID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_stmt_num_rows($stmt) > 0) { // In case the user isn't registered in my database (which I doubt, but we'll never know)
                    while ($row = mysqli_fetch_assoc($result)) {
                        $crewcode = $row['Crewcode'];
                        $rank = $row['Rank'];
                    }
                } else {
                    $err = "It seems like an error has occurred. Most likely it is because you haven't been registered in the database. Please contact <@U010PDT5UM7>";
                }
                mysqli_stmt_close($stmt);

                // Check what rank is the person, and define the radio buttons in consequence
                switch ($rank) {
                    case "JU (NEW)":
                    case "JU":
                        break;
                    case "JU (PU)":
                        $pu = $form_data['PU']['radio_buttons-action']['selected_option']['value'];
                        if (!empty($pu)) {
                            switch ($pu) {
                                case "yes":
                                    $comments = "No.1";
                                    break;
                                case "no":
                                    $comments = "";
                                    break;
                            }
                        } else {
                            // $err .= "Need to select if it's a 'PU' flight";
                            $errors['PU'] = "Need to select if it's a 'PU' flight";
                        }
                        break;
                    case "PU (TC)":
                    case "PU":
                    case "PU (DS)":
                    case "PU (BS)":
                    case "PU (LC)":
                    case "PU (INS)":
                    case "PU (SEP)":
                        $lck = $form_data['LCK']['radio_buttons-action']['selected_option']['value'];
                        if (!empty($lck)) {
                            switch ($lck) {
                                case "iclck":
                                    $comments = "ICLCK";
                                    break;
                                case "ilck":
                                    $comments = "ILCK";
                                    break;
                                case "no":
                                    $comments = "";
                                    break;
                            }
                        } else {
                            // $err .= "Need to select whether it's a ILCK, ICLCK or none";
                            $errors['LCK'] = "Need to select whether it's a ILCK, ICLCK or none";
                        }
                        break;
                }

                // Check the date is within range
                $fechap = explode("-", $fecha);
                $yy = $fechap[0];
                $mm = $fechap[1];
                $dd = $fechap[2];
                $rosterlimitepoch = mktime(12, 0, 0, date("m"), date("d") + 20, date("y"));
                $swaprequestepoch = mktime(12, 0, 0, $mm, $dd, $yy);
                $swapnotedepoch = mktime(12, 0, 0, date("m"), date("d") + 12, date("y"));
                $swaplimitbsepoch = mktime(12, 0, 0, date("m"), date("d") + 6, date("y"));
                $todayepoch = mktime(12, 0, 0, date("m"), date("d"), date("y"));

                switch (true) {
                    case ($rosterlimitepoch < $swaprequestepoch): // If the date is > +20 days
                        $scheduledmessagelimit = mktime(12, 0, 0, date("m"), date("d") + 120, date("y"));
                        $scheduledmessage = mktime(12, 0, 0, $mm, $dd - 20, $yy);
                        if ($scheduledmessagelimit < $scheduledmessage) {
                            // $err .= "The swap request is too far away in time. Please try again in a later time";
                            $errors['date'] = 'The swap request is too far away in time. Please try again in a later time';
                        }
                        $json_data_array = array(
                            'token' => $token,
                            'channel' => $user_id,
                            'post_at' => $scheduledmessage,
                            'text' => "You have a swap request pending on the " . $fecha . ". The roster should be published soon.\n
							Please remember to update your request by sending a DM to <@U010PDT5UM7>",
                        );
                        $json_string = json_encode($json_data_array);
                        break;
                    case ($swaprequestepoch >= $swapnotedepoch): // If the date is +12 days
                        break;
                    case (($swaprequestepoch < $swapnotedepoch) && ($swaprequestepoch >= $swaplimitbsepoch)): // If the date is between 6 and 12 days from today
                        // $err = "\nIt might be too late for a swap. Please contact your Base Supervisor / Crew Control";
                        $errors['date'] = 'It might be too late for a swap. Please contact your Base Supervisor / Crew Control';
                        break;
                    case (($swaprequestepoch < $swaplimitbsepoch) && ($swaprequestepoch > $todayepoch)): // If the date is less than 6 days from today
                        // $err = "\nIt is too late for a swap";
                        $errors['date'] = 'It is too late to ask for a swap';
                        break;
                    default: // Any other case scenario, which is a date being in the past
                        // $err = "\nWhy do you put a date in the past?";
                        $errors['date'] = "You can't select a date in the past";
                        break;
                }

                // Check if the duty is valid
                $duty = strtoupper($duty); // Turn it into uppercase
                $dutysplits = explode("/", $duty, 3); // Separate the airports into their 3 letter codes, using the slashes (/) as a delimiter
                foreach ($dutysplits as $airportcheck) {
                    $airportcheck = strtoupper($airportcheck); // Turn them into uppercase letters
                    switch ($airportcheck) {
                        case "UNKNOWN": // You don't know the duty yet. In this case you'll still be allowed to process the swap, but if the date shows in the roster and the request is not updated, it will be deleted.
                            $msg = "Your duty is unknown for the moment. Please remember to update this as soon as possible. Otherwise, your request will be deleted.";
                        case "HSBY": // It's a hsby, either earlies or lates.
                        case "AD": //It's an Airport Duty
                            goto SkipDuty;
                    }
                    $sqlinsert = "INSERT INTO Airport_Check VALUES (?)"; // Create the query
                    $stmt = mysqli_prepare($conn, $sqlinsert);
                    mysqli_stmt_bind_param($stmt, "s", $airportcheck);
                    mysqli_stmt_execute($stmt);
                }
                unset($dutysplits, $airportcheck); // Unset the references for the next person who runs the script
                mysqli_stmt_close($stmt);

                // All values have been introduced into the temporary table
                $sqlcompare = "SELECT Airport_Check.AirportCode AS AirportCode, CASE WHEN Airports.AirportCode IS NULL THEN 'Not valid' ELSE 'Valid' END AS Valid FROM Airport_Check LEFT JOIN Airports ON Airport_Check.AirportCode = Airports.AirportCode";
                $result = mysqli_query($conn, $sqlcompare); // Run the comparison
                while ($row = mysqli_fetch_assoc($result)) {
                    if ($row["Valid"] != 'Valid') {
                        // $err .= "\n" . $row["AirportCode"] . " is an invalid airport :warning:"; // Display an error if the airport introduced isn't valid
                        $errors['duty'] = $row["AirportCode"] . ' is an invalid airport';
                    }
                }
                $sqldel = "DELETE FROM Airport_Check"; // Delete the requests for checking whether the airports were valid or not
                mysqli_query($conn, $sqldel);
                SkipDuty:

                /* DEBUG OUTPUT
                  $debug_array = Array (
                  'Crewcode' => $crewcode,
                  'Fecha' => $fecha,
                  'Duty' => $duty,
                  'Comments' => $comments,
                  'Command' => 'Request',
                  'Shift' => $shift,
                  'Rank' => $rank,
                  'UserID' => $user_id,
                  );
                  $debug_string = print_r($debug_array, TRUE);
                  $debug_string .= "\n" . $err;
                  file_put_contents("TestOutput.txt", $debug_string);
                 */

                // Insert the values into the 'Swaps' table
                if (empty($err)) {
                    $sql = "INSERT INTO Swaps VALUES (?, ?, ?, ?, 'Request', ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssssss", $crewcode, $fecha, $duty, $comments, $shift, $rank, $user_id);
                    mysqli_stmt_execute($stmt);
                    $stmt_rows = mysqli_stmt_affected_rows($stmt);
                    if ($stmt_rows = 0) {
                        $msg = "Your query was not processed because of the following error.\n" . mysqli_error($conn);
                    } else {
                        $msg .= "\nYour request was successfully created :simple_smile:";
                        if (isset($json_data_array)) { // Check if the scheduled message array is defined (the swap is for a perior further than 20 days)
                            $slack_call = curl_init("https://slack.com/api/chat.scheduleMessage");
                            curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
                            curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);
                            curl_setopt($slack_call, CURLOPT_CRLF, true);
                            curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(
                                "Content-Type: application/json",
                                "Authorization: Bearer " . $token,
                                "Content-Length: " . strlen($json_string))
                            );
                            $result = curl_exec($slack_call); // Store the result, in case there's any errors
                            curl_close($slack_call); // Close the curl connection
                        }
                        $message = array(
                            'channel' => $user_id,
                            'text' => $msg
                        );
                        mysqli_stmt_close($stmt); // Close the statement
                    }
                } else {
                    /* $err .= "\nPlease correct the errors above and try again";
                      $message = array (
                      'channel' => $user_id,
                      'text' => $err
                      ); */
                    $err_array['errors'] = $errors;
                    // $json_error = json_encode($err_array);
                    // file_put_contents("TestOutput.txt", $json_error);
                    $response = array(
                        'status' => 200,
                        'message' => $err_array
                    );
                    $encoded_response = json_encode($response);
                    return $encoded_response;
                }
                $json = json_encode($message);
                $slack_call = curl_init($slack_post_message);
                curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json);
                curl_setopt($slack_call, CURLOPT_CRLF, true);
                curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $token,
                    "Content-Length: " . strlen($json))
                );
                $result = curl_exec($slack_call); // Store the result, in case there's any errors
                curl_close($slack_call); // Close the curl connection
                // Close the connection
                mysqli_close($conn);
                unset($crewcode, $fecha, $duty, $comments, $shift, $rank, $user_id);
                break;
            case "Offer a swap":

                // Check the rank of the person offering the swap
                $conn = mysqli_connect($servername, $username, $password, $dbname);
                $sql = "SELECT Crewcode, Rank FROM Crew WHERE UserID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($result)) {
                    $crewcode = $row['Crewcode'];
                    $rank = $row['Rank'];
                }
                mysqli_stmt_close($stmt);

                // Check the date and make sure it's within range
                $fechap = explode("-", $fecha);
                $yy = $fechap[0];
                $mm = $fechap[1];
                $dd = $fechap[2];

                $rosterlimitepoch = mktime(12, 0, 0, date("m"), date("d") + 20, date("y"));
                $swaprequestepoch = mktime(12, 0, 0, $mm, $dd, $yy);
                $swapnotedepoch = mktime(12, 0, 0, date("m"), date("d") + 12, date("y"));
                $swaplimitbsepoch = mktime(12, 0, 0, date("m"), date("d") + 6, date("y"));
                $todayepoch = mktime(12, 0, 0, date("m"), date("d"), date("y"));

                switch (true) {
                    case ($rosterlimitepoch < $swaprequestepoch): // If the date is > +20 days
                        $scheduledmessagelimit = mktime(12, 0, 0, date("m"), date("d") + 120, date("y"));
                        $scheduledmessage = mktime(12, 0, 0, $mm, $dd - 20, $yy);
                        if ($scheduledmessagelimit < $scheduledmessage) {
                            $err .= "The swap offer is too far away in time. Please try again in a later time";
                        }
                        $json_data_array = array(
                            'token' => $token,
                            'channel' => $user_id,
                            'post_at' => $scheduledmessage,
                            'text' => "You have a swap offer pending on the " . $fecha . ". If you're not available anymore, please send a DM to <@U010PDT5UM7>",
                        );
                        $json_string = json_encode($json_data_array);
                        break;
                    case ($swaprequestepoch >= $swapnotedepoch): // If the date is +12 days
                        break;
                    case (($swaprequestepoch < $swapnotedepoch) && ($swaprequestepoch >= $swaplimitbsepoch)): // If the date is between 6 and 12 days from today
                        $err = "\nIt might be too late to offer a swap";
                        break;
                    case (($swaprequestepoch < $swaplimitbsepoch) && ($swaprequestepoch > $todayepoch)): // If the date is less than 6 days from today
                        $err = "\nIt is too late for a swap";
                        break;
                    default: // Any other case scenario, which is a date being in the past
                        $err = "\nWhy do you put a date in the past?";
                        break;
                }
                // Only thing we need to check is the shift, and it can only be two values (Earlies or Lates), so let's get right into this
                if (empty($err)) {
                    $sql = "INSERT INTO Swaps VALUES (?, ?, 'OFF', '', 'Offer', ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "sssss", $crewcode, $fecha, $shift, $rank, $user_id);
                    mysqli_stmt_execute($stmt);
                    $stmt_rows = mysqli_stmt_affected_rows($stmt);
                    if ($stmt_rows > 0) {
                        $msg .= "\nYour offer was successfully created :simple_smile:";
                        if (isset($json_data_array)) { // Check if the scheduled message array is defined (the swap is for a perior further than 20 days)
                            $slack_call = curl_init("https://slack.com/api/chat.scheduleMessage");

                            curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");

                            curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);

                            curl_setopt($slack_call, CURLOPT_CRLF, true);

                            curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);

                            curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(
                                "Content-Type: application/json",
                                "Authorization: Bearer " . $token,
                                "Content-Length: " . strlen($json_string))
                            );

                            $result = curl_exec($slack_call); // Store the result, in case there's any errors

                            curl_close($slack_call); // Close the curl connection
                        }
                        $message = array(
                            'channel' => $user_id,
                            'text' => $msg
                        );
                        mysqli_stmt_close($stmt); // Close the statement
                    } else {
                        $msg = "Your request was not processed because of the following error:\n" . mysqli_stmt_error($stmt);
                        if (isset($json_data_array)) { // Check if the scheduled message array is defined (the swap is for a perior further than 20 days)
                            $slack_call = curl_init("https://slack.com/api/chat.scheduleMessage");

                            curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");

                            curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);

                            curl_setopt($slack_call, CURLOPT_CRLF, true);

                            curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);

                            curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(
                                "Content-Type: application/json",
                                "Authorization: Bearer " . $token,
                                "Content-Length: " . strlen($json_string))
                            );

                            $result = curl_exec($slack_call); // Store the result, in case there's any errors

                            curl_close($slack_call); // Close the curl connection
                        }
                        $message = array(
                            'channel' => $user_id,
                            'text' => $msg
                        );
                        mysqli_stmt_close($stmt); // Close the statement */
                    }
                } else {
                    $err .= "\nPlease correct the errors above and try again";
                    $message = array(
                        'channel' => $user_id,
                        'text' => $err
                    );
                }
                $json = json_encode($message);
                $slack_call = curl_init($slack_post_message);
                curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json);
                curl_setopt($slack_call, CURLOPT_CRLF, true);
                curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($slack_call, CURLOPT_HTTPHEADER, array(
                    "Content-Type: application/json",
                    "Authorization: Bearer " . $token,
                    "Content-Length: " . strlen($json))
                );
                $result = curl_exec($slack_call); // Store the result, in case there's any errors
                curl_close($slack_call); // Close the curl connection
                // Close the connection
                mysqli_close($conn);
                unset($crewcode, $fecha, $duty, $comments, $shift, $rank, $user_id);
                break;
        }
        break;
    // case "
}
?>