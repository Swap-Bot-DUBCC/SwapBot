<?php

$modal_array = [
    'trigger_id' => $trigger_id,
    'view' => [
        'callback_id' => 'request_short_' . $user_id,
        'type' => 'modal',
        'title' => [
            'type' => 'plain_text',
            'text' => 'Request a shorter day',
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
                        'text' => 'Choose a date',
                        'emoji' => true,
                    ],
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Select a date for the swap',
                    'emoji' => true,
                ],
            ],
            1 => [
                'block_id' => 'duty',
                'type' => 'input',
                'element' => [
                    'action_id' => 'input_duty',
                    'type' => 'plain_text_input',
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Introduce your duty here (i.e. LIS/NTE)',
                    'emoji' => true,
                ],
            ],
            2 => [
                'block_id' => 'time',
                'type' => 'input',
                'element' => [
                    'type' => 'timepicker',
                    'initial_time' => date("H:i"),
                    'placeholder' => [
                        'type' => 'plain_text',
                        'text' => 'Select time',
                        'emoji' => true,
                    ],
                    'action_id' => 'select_time',
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'What time would you like to land at / start at?',
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
                        1 => [
                            'text' => [
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
    ],
];

$json_string = json_encode($modal_array);
return $json_string;
?>