<?phprequire '../../etc/Env.php'; // Require the environmental variables file// Define variables$slack_post_message = "https://slack.com/api/chat.postMessage";$ad_earlies_checkin_start = mktime(4, 30, 0); // Check-in for Early AD opens at 4:30$ad_earlies_checkin_end = mktime(11, 0); // Check-in for Early AD closes at 11:00$ad_earlies_start = mktime(5, 0); // Early AD starts at 5:00$ad_earlies_finish = mktime(13, 0); // Early AD finishes at 13:00$ad_lates_checkin_start = mktime(11, 30, 0); // Check-in for Late AD opens at 11:30$ad_lates_start = mktime(12, 0); // Late AD starts at 12:00$ad_lates_finish = mktime(20, 0); // Late AD finishes at 20:00$now = time(); // Recover the time the command is used, to see whether you should check-in on earlies or lates// Connect to the database$conn = mysqli_connect($servername, $username, $password, $dbname);if (mysqli_error($conn)) {    die("Connection failed: " . mysqli_error($conn) . ":x::earth_africa:");}switch ($callback_id) {    case "airport_duty_in":        switch (true) {            case (($now >= $ad_earlies_checkin_start) && ($now < $ad_earlies_checkin_end)): // Actual time is between 4:30 and 11:00                $sql = "INSERT INTO Airport_Duty VALUES (?, 'Earlies')";                $stmt = mysqli_prepare($conn, $sql);                mysqli_stmt_bind_param($stmt, "s", $user_id);                mysqli_stmt_execute($stmt);                if (mysqli_error($conn)) {                    $msg = "There was an error processing your request:\n" . mysqli_error($conn);                } else {                    $msg = "You have checked in for Early AD";                }                break;            case (($now >= $ad_lates_checkin_start) && ($now < $ad_lates_finish)): // Actual time is between 11:30 and 20:00                $sql = "INSERT INTO Airport_Duty VALUES (?, 'Lates')";                $stmt = mysqli_prepare($conn, $sql);                mysqli_stmt_bind_param($stmt, "s", $user_id);                mysqli_stmt_execute($stmt);                if (mysqli_error($conn)) {                    $msg = "There was an error processing your request:\n" . mysqli_error($conn);                } else {                    $msg = "You have checked in for Late AD";                }                break;            default:                $msg = "The time for checking in isn't within the allocated time frame";                break;        }        mysqli_stmt_close($stmt);        break;    case "airport_duty_out":        if (($now > $ad_earlies_start) && ($now < $ad_lates_finish)) { // Actual time is between 5:00 and 20:00            $sql = "DELETE FROM Airport_Duty WHERE UserID = ?";            $stmt = mysqli_prepare($conn, $sql);            mysqli_stmt_bind_param($stmt, "s", $user_id);            mysqli_stmt_execute($stmt);            if (mysqli_error($conn)) { // Check for errors in the query                $msg = "There was an error processing your request:\n" . mysqli_error($conn);            } else {                if (mysqli_affected_rows($conn) > 0) { // Check if any rows were deleted                    $msg = "You have been checked out successfully";                } else {                    $msg = "You haven't checked in. Can't check out in this case";                }            }        } else {            $msg = "You are trying to check out outside the allocated time frame\n Check out is automatically performed at 21:00";        }        mysqli_stmt_close($stmt);        break;    case "swap_request":        $trigger_id = $body['trigger_id'];        // file_put_contents("TestOutput.txt", print_r($body, true)); // DEBUG PURPOSES        $sql = "SELECT Rank FROM Crew WHERE UserID = ? ";        $stmt = mysqli_prepare($conn, $sql);        mysqli_stmt_bind_param($stmt, "s", $user_id);        mysqli_stmt_execute($stmt);        $result_text = mysqli_stmt_get_result($stmt);        if (mysqli_stmt_affected_rows($stmt) > 0) {            while ($row = mysqli_fetch_row($result_text)) {                $rank = $row[0];            }        } else {            die("I'm sorry. An error has ocurred. However, this error is because you're not registered in the database. Send a DM to <@U010PDT5UM7> with your name, rank and crewcode and it will be sorted promptly");        }        mysqli_stmt_close($stmt);        mysqli_close($conn);        switch ($rank) {            case "JU (NEW)":            case "JU":                include "Forms/JU_Off.php";                break;            case "JU (AH)":            case "JU (PU)":                include "Forms/JU (PU)_Off.php";                break;            case "PU (TC)":            case "PU":            case "PU (DS)":            case "PU (BS)":            case "PU (LC)":            case "PU (TH. INS)":            case "PU (INS)":            case "PU (SEP)":                include "Forms/PU_Off.php";                break;        }        $slack_call = curl_init($slack_open_dialog);        curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");        curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);        curl_setopt($slack_call, CURLOPT_CRLF, true);        curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);        curl_setopt($slack_call, CURLOPT_HTTPHEADER, [            "Content-Type: application/json",            "Authorization: Bearer " . $token,            "Content-Length: " . strlen($json_string)]        );        $result = curl_exec($slack_call);        curl_close($slack_call);        return;}mysqli_close($conn);$message_array = [    'token' => $token,    'channel' => $user_id,    'text' => $msg,];$json_string = json_encode($message_array);$post_message = curl_init($slack_post_message);curl_setopt($post_message, CURLOPT_CUSTOMREQUEST, "POST");curl_setopt($post_message, CURLOPT_POSTFIELDS, $json_string);curl_setopt($post_message, CURLOPT_CRLF, true);curl_setopt($post_message, CURLOPT_RETURNTRANSFER, true);curl_setopt($post_message, CURLOPT_HTTPHEADER, [    "Content-Type: application/json",    "Authorization: Bearer " . $token,    "Content-Length: " . strlen($json_string)]);$result = curl_exec($post_message);curl_close($post_message);?>