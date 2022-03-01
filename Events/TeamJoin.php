<?php

require '../../etc/Env.php'; // Require the environmental variables file

$slack_post_message = "https://slack.com/api/chat.postMessage";

$json_message_array = [
    'token' => $token,
    'channel' => $user_id["id"],
    'blocks' => [
        0 => [
            'type' => 'header',
            'block_id' => '3fw2q',
            'text' => [
                'type' => 'plain_text',
                'text' => 'Hello, and welcome here',
                'emoji' => true,
            ],
        ],
        1 => [
            'type' => 'section',
            'block_id' => '/kGI',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'I\'m an app, called SwapBot and I was created to accomodate swaps between crewmembers.',
                'verbatim' => false,
            ],
        ],
        2 => [
            'type' => 'section',
            'block_id' => '=Mgi',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'My creator is Jonathan, and as such, if you have *any* questions or suggestions, feel free to DM him',
                'verbatim' => false,
            ],
        ],
        3 => [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'This will be a quick introduction to the main features of a Slack workspace:',
            ],
        ],
        4 => [
            'type' => 'divider',
            'block_id' => 'uEiI',
        ],
        5 => [
            'type' => 'section',
            'block_id' => 'DGzE',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '_*Channels*_',
                'verbatim' => false,
            ],
        ],
        6 => [
            'type' => 'section',
            'block_id' => 'vht',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'In this platform you have channels, such as <#C010N0QUNJF>, <#C010N0Z9Y9Z> or <#C011174PC56>. Their purpose is to keep everything organized, so as to not put everything in one place',
                'verbatim' => false,
            ],
        ],
        7 => [
            'type' => 'section',
            'block_id' => 'AJwo8',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '_*Threads*_',
                'verbatim' => false,
            ],
        ],
        8 => [
            'type' => 'section',
            'block_id' => 'CjDe2',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "Threads are what make Slack organized. They keep the main channels clutter free. To start threads, simply click on a message and write your reply. Users involved in any thread will be notified of new messages in it, *unless they choose not to*.\nTo unfollow a thread, long tap (if on a phone), and choose \"Unfollow thread\"",
                'verbatim' => false,
            ],
        ],
        9 => [
            'type' => 'section',
            'block_id' => 'D=Q',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '_*Reactions*_',
                'verbatim' => false,
            ],
        ],
        10 => [
            'type' => 'section',
            'block_id' => '=mhJl',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "In Slack you can use emojis to interact with a message. You can use *any* emoji; to do so, tap on a message and click the emoji button with a plus sign. There's no limit to how many reactions you can add.\nFor instance, this message has a reaction so you can see how they work",
                'verbatim' => false,
            ],
        ],
        11 => [
            'type' => 'divider',
            'block_id' => 'ZRN',
        ],
        12 => [
            'type' => 'section',
            'block_id' => 'qgX',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '_*Functionality*_',
                'verbatim' => false,
            ],
        ],
        13 => [
            'type' => 'section',
            'block_id' => 'ysZ7I',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "I've got 2 main functions: managing whoever is on Airport Duty and organizing the swaps between crewmembers.\nFor more information on it, visit <slack://app?team=T010Z07J85A&id=A0111JQ9HEW&tab=home|my home page>",
                'verbatim' => false,
            ],
        ],
        14 => [
            'type' => 'divider',
            'block_id' => 'it=',
        ],
        15 => [
            'type' => 'section',
            'block_id' => 'zESh',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '_*Closing thoughts*_',
                'verbatim' => false,
            ],
        ],
        16 => [
            'type' => 'section',
            'block_id' => '15H',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'Before you go, there\'s one little task to do: send a DM to <@U010PDT5UM7> with your crewcode and rank. This app relies on the link between Slack UserID\'s and crewcodes to properly work.',
                'verbatim' => false,
            ],
        ],
    ],
];

$json_message_string = json_encode($json_message_array);

$slack_message = curl_init($slack_post_message);

curl_setopt($slack_message, CURLOPT_CUSTOMREQUEST, "POST");

curl_setopt($slack_message, CURLOPT_POSTFIELDS, $json_message_string);

curl_setopt($slack_message, CURLOPT_CRLF, true);

curl_setopt($slack_message, CURLOPT_RETURNTRANSFER, true);

curl_setopt($slack_message, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token,
    "Content-Length: " . strlen($json_message_string)]
);

$result_message = curl_exec($slack_message); // Store the result, in case there's any errors

curl_close($slack_message);

$result_array = json_decode($result_message, TRUE);

$dm = $result_array["channel"];

$ts = $result_array["ts"];

// Add the reaction

$json_reaction_array = [
    'token' => $token,
    'channel' => $dm,
    'name' => 'ryr',
    'timestamp' => $ts
];

$json_reaction_string = json_encode($json_reaction_array);

$slack_reaction = curl_init("https://slack.com/api/reactions.add");

curl_setopt($slack_reaction, CURLOPT_CUSTOMREQUEST, "POST");

curl_setopt($slack_reaction, CURLOPT_POSTFIELDS, $json_reaction_string);

curl_setopt($slack_reaction, CURLOPT_CRLF, true);

curl_setopt($slack_reaction, CURLOPT_RETURNTRANSFER, true);

curl_setopt($slack_reaction, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token,
    "Content-Length: " . strlen($json_reaction_string)]
);
$result = curl_exec($slack_reaction); // Store the result, in case there's any errors

curl_close($slack_reaction);
?>