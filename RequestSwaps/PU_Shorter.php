<?php

$modal_array = [
    'trigger_id' => $trigger_id,
    'view' => [
        'callback_id' => 'request_short_' . $user_id,
        'type' => 'modal',
        'title' => [
            'type' => 'plain_text',
            'text' => 'Request a shorter day',
            'emoji' => false,
        ],
        'submit' => [
            'type' => 'plain_text',
            'text' => 'Request',
            'emoji' => false,
        ],
        'close' => [
            'type' => 'plain_text',
            'text' => 'Cancel',
            'emoji' => false,
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
                        'emoji' => false,
                    ]
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Select a date for the swap',
                    'emoji' => false,
                ]
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
                    'text' => 'Introduce your duty here (i.e. LPL/MAN)',
                    'emoji' => false,
                ]
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
                'block_id' => 'LCK',
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'Is it a LCK of any kind (ICLCK, ILCK)?',
                ],
                'accessory' => [
                    'type' => 'radio_buttons',
                    'options' => [
                        0 => [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'ICLCK',
                                'emoji' => false,
                            ],
                            'value' => 'iclck',
                        ],
                        1 => [
                            'text' => [
                                'type' => 'plain_text',
                                'text' => 'ILCK',
                                'emoji' => false,
                            ],
                            'value' => 'ilck',
                        ],
                        2 => [
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
                        ]
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
                        'emoji' => false,
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
                        ]
                    ]
                ],
                'label' => [
                    'type' => 'plain_text',
                    'text' => 'Select a shift',
                    'emoji' => false,
                ],
            ],
        ],
    ],
];

$json_string = json_encode($modal_array);
return $json_string;
?>

