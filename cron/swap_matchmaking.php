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
 * 
 * What I'll do now is to recourse through the arrays and see if there are any dates matching.
 * If there are, we can then try and see if the swap could be done (rank and shift dependant). */

foreach ($swaps_table_requests as $swapr) {
    foreach ($swaps_table_offers as $swapo) {
        if ($swapo["Day"] === $swapr["Day"]) {
            // In theory this means the dates are equal, and so there is a request pending that match a swap offering
            // Let's filter out by shift and then rank
            switch (true) {
                case ($swapo["Shift"] === $swapr["Shift"]):
                    // This means the shift chosen is the same (either earlies or lates, so 2nd check has been completed succesfully)
                    break;
                case (($swapo["Shift"] === "Earlies") and ($swapr["Shift"] === "Lates")):
                    /* Here the swap offer is for an early shift and the request is for a late shift.
                     * Most likely this will be rejected due to insufficent rest.
                     * The only case where this COULD be viable is if the person is on their last day off before lates and doesn't mind doing an early shift */
                    continue 2;
                case (($swapo["Shift"] === "Lates") and ($swapr["Shift"] === "Earlies")):
                    /* This is the opposite of the previous case. Offer for a late and request on earlies.
                     * This could not be possible if the swap is on the first day off after lates.
                     * This COULD be viable, but of course it's always up to the preference of the offering crewmember.
                     * If they don't want an early shift, too bad for the one in need
                     * However, I'll offer the option to contact the crewmember offering the swap to see if they could do or not. */
                    continue 2;
            }

            // Let's define the list of ranks that are compatible and I save myself the hassle of having to rewrite the list multiple times
            $ju_compatible = ['JU (NEW)', 'JU', 'JU (AH)', 'JU (PU)'];
            $pu_compatible = ['JU (AH)', 'JU (PU)', 'PU (TC)', 'PU', 'PU (DS)', 'PU (BS)', 'PU (LC)', 'PU (TH. INS)', 'PU (INS)', 'PU (SEP)'];
            switch ($swapr["Rank"]) {
                /* Assuming the way I coded the shift checker works, all that's left is to see whether the ranks are compatible.
                 * The easiest way to do this I believe it is with a switch statement */
                case "JU (NEW)":
                case "JU":
                    if (in_array($swapo["Rank"], $ju_compatible) === true) {
                        /* Reaching here means that:
                         * 1. The date of the swap request and the swap offering are the same
                         * 2. The shift is compatible
                         * 3. The ranks are compatible */
                        echo "A swap was found!";
                        // Craft the message, which is going to be real simple
                        $msg_array = [
                            'token' => $token,
                            'channel' => $swapr["UserID"],
                            'blocks' => [
                                0 => [
                                    'type' => 'header',
                                    'text' => [
                                        'type' => 'plain_text',
                                        'text' => ':tada: Good news!! :tada:',
                                        'emoji' => true,
                                    ],
                                ],
                                1 => [
                                    'type' => 'section',
                                    'text' => [
                                        'type' => 'mrkdwn',
                                        'text' => "<@" . $swapo["UserID"] . "> has offered a swap on the " . $swapo["Day"] . ".\n"
                                        . "Click the name tag at the beginning of the message to send them a DM.\n"
                                        . "_Remember to delete your swap request once it's approved by using `/swaps delete`_",
                                    ],
                                ],
                            ],
                        ];
                        goto SendMessage;
                    } else {
                        echo "No compatible swaps were found!";
                    }
                    break;
                case "JU (AH)":
                case "JU (PU)":
                    if ($swapr["Comments"] === "No.1") {
                        if (in_array($swapo["Rank"], $pu_compatible) === true) {
                            $msg_array = [
                                'token' => $token,
                                'channel' => $swapr["UserID"],
                                'blocks' => [
                                    0 => [
                                        'type' => 'header',
                                        'text' => [
                                            'type' => 'plain_text',
                                            'text' => ':tada: Good news!! :tada:',
                                            'emoji' => true,
                                        ],
                                    ],
                                    1 => [
                                        'type' => 'section',
                                        'text' => [
                                            'type' => 'mrkdwn',
                                            'text' => "<@" . $swapo["UserID"] . "> has offered a swap on the " . $swapo["Day"] . ".\n"
                                            . "Click the name tag at the beginning of the message to send them a DM.\n"
                                            . "_Remember to delete your swap request once it's approved by using `/swaps delete`_",
                                        ],
                                    ],
                                ],
                            ];
                            goto SendMessage;
                        } else {
                            if (in_array($swapo["Rank"], $ju_compatible) === true) {
                                $msg_array = [
                                    'token' => $token,
                                    'channel' => $swapr["UserID"],
                                    'blocks' => [
                                        0 => [
                                            'type' => 'header',
                                            'text' => [
                                                'type' => 'plain_text',
                                                'text' => ':tada: Good news!! :tada:',
                                                'emoji' => true,
                                            ],
                                        ],
                                        1 => [
                                            'type' => 'section',
                                            'text' => [
                                                'type' => 'mrkdwn',
                                                'text' => "<@" . $swapo["UserID"] . "> has offered a swap on the " . $swapo["Day"] . ".\n"
                                                . "Click the name tag at the beginning of the message to send them a DM.\n"
                                                . "_Remember to delete your swap request once it's approved by using `/swaps delete`_",
                                            ],
                                        ],
                                    ],
                                ];
                                goto SendMessage;
                            }
                        }
                    }
                    break;
                case "PU (TC)":
                case "PU (BS)":
                case "PU (DS)":
                case "PU":
                case "PU (LC)":
                case "PU (TH. INS)":
                case "PU (INS)":
                case "PU (SEP)":
                    if (in_array($swapo["Rank"], $pu_compatible) === true) {
                        echo "A compatible swap was found!";
                        // Write code here to text the person involved
                        $msg_array = [
                            'token' => $token,
                            'channel' => $swapr["UserID"],
                            'blocks' => [
                                0 => [
                                    'type' => 'header',
                                    'text' => [
                                        'type' => 'plain_text',
                                        'text' => ':tada: Good news!!',
                                        'emoji' => true,
                                    ],
                                ],
                                1 => [
                                    'type' => 'section',
                                    'text' => [
                                        'type' => 'mrkdwn',
                                        'text' => "<@" . $swapo["UserID"] . "> has offered a swap on the " . $swapo["Day"] . "\n"
                                        . "Click the name tag at the beginning of the message to send them a DM.\n" . "_Remember to delete your swap request once it's approved by Ryanair usign `/swaps delete`_",
                                    ],
                                ],
                            ],
                        ];
                        goto SendMessage;
                    } else {
                        echo "No compatible swap found!";
                    }
                    break;
            }
        } else {
            continue 2;
        }
        
        SendMessage:
        if (isset($msg_array)) {
            $json_string = json_encode($msg_array);
            $slack_message = curl_init($slack_post_message);

            curl_setopt($slack_message, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($slack_message, CURLOPT_POSTFIELDS, $json_string);
            curl_setopt($slack_message, CURLOPT_CRLF, true);
            curl_setopt($slack_message, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($slack_message, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . $token,
                "Content-Length: " . strlen($json_string)]
            );
            $result_message = curl_exec($slack_message); // Store the result, in case there's any errors
            curl_close($slack_message);
            // echo $result_message;
        }
    }
}
/* This next part is to test whether the arrays are properly populated
 * file_put_contents("SwapRequests.txt", print_r($swaps_table_requests, true));
 * file_put_contents("SwapOffers.txt", print_r($swaps_table_offers, true)); */

// At the very end, we can close the connection since we don't need the database any longer
mysqli_close($conn);
?>