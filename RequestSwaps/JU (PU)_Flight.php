<?php

$modal_array = [
    'token' => $token,
    'trigger_id' => $trigger_id,
    'view' => [
        'callback_id' => 'request_flight_' . $user_id,
        'type' => 'modal',
        'title' => [
            'type' => 'plain_text',
            'text' => 'Request for a flight',
            'emoji' => true,
        ],
        'submit' => [
            'type' => 'plain_text',
            'text' => 'Request',
            'emoji' => true,
        ],
        'close' => [
            'type' => 'plain_text',
            'text' => 'Cancel',
            'emoji' => true,
        ],
        'blocks' => [
            0 => [
                'block_id' => 'date',
                'type' => 'input',
                'element' => [
                    'action_id' => 'select_date',
                    'type' => 'datepicker',
                    'initial_date' => date('Y-m-d', strtotime("+12 days")),
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Select a date',
                        'emoji' => true,
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Select a date for the flight',
                    'emoji' => true,
                ],
            ],
            1 => [
                'block_id' => 'desired_duty',
                'type' => 'input',
                'element' => [
                    'action_id' => 'input_desired_duty',
                    'type' => 'plain_text_input',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Write the flight you want to operate here',
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'What flight do you want to operate?',
                    'emoji' => true,
                ],
            ],
            2 => [
                'block_id' => 'duty',
                'type' => 'input',
                'element' => [
                    'action_id' => 'input_duty',
                    'type' => 'plain_text_input',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Write the flight you\'re operating here',
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'What flight are you operating?',
                    'emoji' => true,
                ],
            ],
            3 => [
                'block_id' => 'PU',
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'Is it a \'PU\' flight?',
                ],
                'accessory' => [
                    'type' => 'radio_buttons',
                    'options' => [
                        0 => [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'Yes',
                                'emoji' => false,
                            ],
                            'value' => 'yes',
                        ],
                        1 => ['text' => [
                                'type' => 'plain_text',
                                'text' => 'No',
                                'emoji' => false,
                            ],
                            'value' => 'no',
                        ],
                    ],
                    'initial_option' => [
                        'value' => 'no',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => 'No',
                            'emoji' => false,
                        ],
                    ],
                    'action_id' => 'radio_buttons-action',
                ],
            ],
            4 => [
                'block_id' => 'shift',
                'type' => 'input',
                'element' => [
                    'action_id' => 'select_shift',
                    'type' => 'static_select',
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Shift',
                        'emoji' => true,
                    ],
                    'options' => [
                        0 => [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'Earlies',
                                'emoji' => false,
                            ],
                            'value' => 'earlies',
                        ],
                        1 => [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'Lates',
                                'emoji' => false,
                            ],
                            'value' => 'lates',
                        ],
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Select a shift',
                    'emoji' => true,
                ],
            ],
        ],
    ]
];

$json_string = json_encode($modal_array);
return $json_string;
?>