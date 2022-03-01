<?php

require '../../etc/Env.php'; // Require environmental variables file

$slack_home_publish = "https://slack.com/api/views.publish";

// Connect to the database

$conn = mysqli_connect($servername, $username, $password, $dbname);

$sql_person = "SELECT Nombre, Rank FROM Crew WHERE UserID = ?";
$stmt_person = mysqli_prepare($conn, $sql_person);
mysqli_stmt_bind_param($stmt_person, "s", $user_id);
mysqli_stmt_execute($stmt_person);
$result_person = mysqli_stmt_get_result($stmt_person);

/* Define the useful variables, but only if at least one row was retrieved (meaning they're registered in my system)
  Otherwise, publish a rather simple view explaining them that they need to submit their data to be added */
if (mysqli_num_rows($result_person) > 0) {
    while ($row_person = mysqli_fetch_row($result_person)) {
        $name = $row_person[0];
        $rank = $row_person[1];
    }
} else {
    $json_data = [
        'token' => $token,
        'user_id' => $user_id,
        'view' => [
            'type' => 'home',
            'blocks' => [
                0 => [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => ':warning: Oops, it seems like an error has occurred',
                        'emoji' => true
                    ],
                ],
                1 => [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'This app works better once you also input your data into the system. This has an easy fix, fortunately.'
                    ],
                ],
                2 => [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => 'Click here to send a DM to the author. In it, please write your Crewcode, name and rank',
                    ],
                    'accessory' => [
                        'type' => 'button',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => 'Send DM',
                            'emoji' => false,
                        ],
                        'value' => 'click_me_123',
                        'url' => 'slack://user?team=T010Z07J85A&id=U010PDT5UM7',
                        'style' => 'primary',
                    ],
                ],
            ],
        ],
    ];

    $json_string = json_encode($json_data);

    goto SendView;
}

/* If they were registered, then we can create the customized app home for them. 
  First, let's get the list of people (if any), on Airport Duty. Here I won't make a difference between Earlies or Lates.
  Maybe once I fix the issues it has, specially the one where it won't recover anyone between 12:00 and 13:00, I'll consider it */

$sql_airport = "SELECT * FROM Airport_Duty ORDER BY Shift ASC;";

$result_airport = mysqli_query($conn, $sql_airport); // Recover the list of people

$airportduty = Array();

$airportlist = "";

$no_one = ["It's a bit lonely in here",
    "It's sad, ain't it?",
    "Break the ice",
    "So quiet you could hear a pin drop",
    "It seems no one's home",
];

if (mysqli_num_rows($result_airport) > 0) {
    while ($row_airport = mysqli_fetch_assoc($result_airport)) {
        array_push($airportduty, $row_airport);
    }
} else {
    // Add here the list of random sentences I'll use
    $airportlist = $no_one[random_int(0, (count($no_one) - 1))];
    goto SwapTime;
}

foreach ($airportduty as $airportperson) {
    $airportlist .= "<@" . $airportperson["UserID"] . "> is on " . strtolower($airportperson["Shift"]) . "\n";
}

unset($airportduty, $airportperson);

SwapTime:
// Now we have to get the list of swaps and display them.
switch ($rank) { // Check what the rank is
    case "JU (NEW)":
    case "JU":
        $sqlrank = " AND Rank IN ('JU', 'JU (PU)', 'JU (AH)') AND Comments NOT LIKE '%No.1%'";
        // Recover JU and JU (PU) swaps in which the comments do not contain "No.1"
        break;
    case "JU (AH)":
    case "JU (PU)":
        $sqlrank = " AND (Comments NOT LIKE '%LCK%')";
        // Recover all swaps in which the comments do not contain LCK. There are two codes: ILCK or ICLCK. None of them can be done by ad-hocs
        break;
    case "PU (TC)":
    case "PU (BS)":
    case "PU (DS)":
    case "PU":
        $sqlrank = " AND (((Rank IN ('JU (PU)','JU (AH)') AND Comments LIKE '%No.1') OR (Rank LIKE 'PU%' AND Comments NOT LIKE '%ILCK%')))";
        // Recover all swaps from JU (PU) or JU (AH) that contain "No.1" in the comments and all swaps from any PU rank that DO NOT contain ILCK in the comments, since not all PU are able to do them
        break;
    case "PU (LC)":
    case "PU (TH. INS)":
    case "PU (INS)":
    case "PU (SEP)":
        $sqlrank = " AND (Rank LIKE 'PU%') OR (Rank IN ('JU (PU)','JU (AH)') AND Comments LIKE '%No.1%')";
        // This is the easiest one. From PU (LC) onwards they can do any No.1 flight. I wanted to find a way to sort them out in this case as to prioritize flights in which they're really needed, but I don't know any way yet
        break;
}

// Now we create the query we will send to the database, including parameters

$sqlquery1 = "SELECT * FROM Swaps WHERE Command LIKE 'Request%' AND UserID != ?";
// First part of all queries.
// What this part does is: Select all the entries in which the command is similar to "Request" (for the shorter day requests)
// and that DO NOT match your UserID (for obvious reasons)

$sqlquery2 = " ORDER BY Day ASC"; // End of all queries (orders by date; closest ones first)

$sqlqueryfinal = $sqlquery1 . $sqlrank . $sqlquery2; // Final query
// Create prepared statement, bind values to it and execute. Then retrieve the results, close the statement and the connection to the database to free resources

$stmt = mysqli_prepare($conn, $sqlqueryfinal);
mysqli_stmt_bind_param($stmt, "s", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Now we have to work on the resulting message

if ((mysqli_affected_rows($conn)) > 0) {
    // Use the result of the query to display the swap requests.
    while ($row = mysqli_fetch_assoc($result)) {
        switch ($row["Command"]) {
            case "Request_Off":
                $swaplist .= "<@" . $row["UserID"] . "> needs a day off on the " . $row["Day"] . ". The duty is " . $row["Duty"] . " on " . strtolower($row["Shift"]) . ". Comments: " . $row["Comments"] . "\n";
                break;
            case "Request_Short":
                if ($row["Shift"] == "Earlies") {
                    $swaplist .= "<@" . $row["UserID"] . "> needs a shorter day on the " . $row["Day"] . ". The duty is " . $row["Duty"] . " on earlies. They would like to finish at " . $row["MaxTime"] . ". Comments: " . $row["Comments"] . "\n";
                } else {
                    $swaplist .= "<@" . $row["UserID"] . "> needs a shorter day on the " . $row["Day"] . ". The duty is " . $row["Duty"] . " on lates. They would like to start at " . $row["MaxTime"] . ". Comments: " . $row["Comments"] . "\n";
                }
                break;
            case "Request_Flight":
                $swaplist .= "<@" . $row["UserID"] . "> wants to operate the flight to " . $row["RequestedFlight"] . " on the " . $row["Day"] . " on " . $row["Shift"] . ". They are currently doing " . $row["Duty"] . "\n";
                break;
        }
    }
} else {
    $swaplist = "At the moment, there is no one looking for a swap";
}

$json_data = [
    'token' => $token,
    'user_id' => $user_id,
    'view' => [
        'type' => 'home',
        'blocks' => [
            0 => [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => 'Welcome, ' . $name,
                ],
            ],
            1 => [
                'type' => 'divider',
            ],
            2 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '_*How to use the app*_',
                ],
            ],
            3 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "The app has some simple commands. These commands can be started from any conversation:\n_(Just have to type them as if you were going to send a message)_",
                ],
            ],
            4 => [
                'type' => 'section',
                'fields' => [
                    0 => [
                        'type' => 'mrkdwn',
                        'text' => '• `/airport in`: check you in for AD',
                    ],
                    1 => [
                        'type' => 'mrkdwn',
                        'text' => '• `/request shorter`: request a shorter day',
                    ],
                    2 => [
                        'type' => 'mrkdwn',
                        'text' => '• `/airport out`: check you out from AD',
                    ],
                    3 => [
                        'type' => 'mrkdwn',
                        'text' => '• `/request flight`: request to do a flight',
                    ],
                    4 => [
                        'type' => 'mrkdwn',
                        'text' => '• `/airport`: list the people on AD',
                    ],
                    5 => [
                        'type' => 'mrkdwn',
                        'text' => '• `/swaps [Earlies/Lates]`: list swaps',
                    ],
                    6 => [
                        'type' => 'mrkdwn',
                        'text' => '• `/request off`: request a day off',
                    ],
                    7 => [
                        'type' => 'mrkdwn',
                        'text' => '• `/swaps delete`: delete your swaps',
                    ],
                ],
            ],
            5 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "(Easy check-in and check-out from Airport Duty are also available from any conversation. Tap the :zap: button, then Swap Bot and choose whether you want to Check-in or Check-out)",
                ],
            ],
            6 => [
                'type' => 'divider',
            ],
            7 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*_List of people on Airport Duty_*',
                ],
            ],
            8 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $airportlist,
                ],
            ],
            9 => [
                'type' => 'divider',
            ],
            10 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*_List of swaps up for grabs for your rank: ' . $rank . '_*',
                ],
            ],
            11 => [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $swaplist,
                ],
            ],
        /* 12 => [
          'type' => 'section',
          'text' => [
          'type' => 'mrkdwn',
          'text' => 'No more swaps until the 6th of January. Also, Merry Christmas! :christmas_tree: :santa:',
          ],
          ], */
        ],
    ],
];

$json_string = json_encode($json_data); // Encode JSON data

SendView:

// Start the cURL request to send the view to the user

$slack_call = curl_init($slack_home_publish);

curl_setopt($slack_call, CURLOPT_CUSTOMREQUEST, "POST");

curl_setopt($slack_call, CURLOPT_POSTFIELDS, $json_string);

curl_setopt($slack_call, CURLOPT_CRLF, true);

curl_setopt($slack_call, CURLOPT_RETURNTRANSFER, true);

curl_setopt($slack_call, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token,
    "Content-Length: " . strlen($json_string)]
);

$result_curl = curl_exec($slack_call); // Store the result, in case there's any errors
// Clean up the program before leaving; close everything
curl_close($slack_call);
mysqli_stmt_close($stmt);
mysqli_close($conn);
file_put_contents("AppHome.txt", $result_curl);
?>