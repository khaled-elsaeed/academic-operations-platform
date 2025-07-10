<?php

return [
    'mode'                       => '',
    'format'                     => 'A4',
    'default_font_size'          => '12',
    'default_font'               => 'cairo',
    'margin_left'                => 15,
    'margin_right'               => 15,
    'margin_top'                 => 15,
    'margin_bottom'              => 15,
    'margin_header'              => 0,
    'margin_footer'              => 0,
    'orientation'                => 'P',
    'title'                      => 'Enrollment Document',
    'author'                     => 'Academic Operations Platform',
    'watermark'                  => '',
    'show_watermark'             => false,
    'show_watermark_image'       => false,
    'watermark_font'             => 'cairo',
    'display_mode'               => 'fullpage',
    'watermark_text_alpha'       => 0.1,
    'watermark_image_path'       => '',
    'watermark_image_alpha'      => 0.2,
    'watermark_image_size'       => 'D',
    'watermark_image_position'   => 'P',
    'custom_font_dir'            => base_path("resources/fonts"),
    'custom_font_data' => [
        'cairo' => [
            'R'  => 'Cairo-Regular.ttf',    // regular font
            'B'  => 'Cairo-Bold.ttf',       // optional: bold font
            'I'  => 'Cairo-Italic.ttf',     // optional: italic font
            'BI' => 'Cairo-BoldItalic.ttf', // optional: bold-italic font,
            'useOTL' => 0xFF,
            'useKashida' => 75,
        ]
    ],
    'auto_language_detection'    => true,
    'temp_dir'                   => base_path('temp'),
    'isRemoteEnabled'            => false,
    'isHtml5ParserEnabled'       => true,
    'isFontSubsettingEnabled'    => true,
];
