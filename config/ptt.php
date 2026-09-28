<?php

return [
    /*
     | Session slots offered for every program. Kept in config (not the DB)
     | because they are fixed timetable slots, while individual dated classes
     | are created on demand in class_sessions.
     */
    'session_slots' => [
        '8:00 AM – 12:00 PM',
        '12:00 PM – 4:00 PM',
        '4:00 PM – 8:00 PM',
    ],

    // Seats per dated class unless an admin overrides that class.
    // Short tier names the operations screens use for each programme.
    'program_tiers' => [
        'plc-electrical-controls' => 'Core',
        'advanced-plc-automation' => 'Advanced',
    ],

    'seats_per_instructor' => (int) env('PTT_SEATS_PER_INSTRUCTOR', 8),

    'default_capacity' => 12,

    // How long checkout holds a seat before it is released.
    'seat_hold_minutes' => (int) env('SEAT_HOLD_MINUTES', 10),

    // Portion of tuition taken when the student chooses "pay deposit".
    'deposit_percent' => 30,

    // The school is closed Sundays (0 = Sunday, matching Carbon::dayOfWeek).
    'closed_weekdays' => [0],

    'admin_email' => env('MAIL_ADMIN_ADDRESS', 'admin@pacifictradetech.com'),
];
