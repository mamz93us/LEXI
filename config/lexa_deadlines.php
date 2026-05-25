<?php

declare(strict_types=1);

/**
 * Egyptian-litigation challenge-window day counts.
 *
 * !!! VERIFY WITH A PRACTICING EGYPTIAN LAWYER BEFORE ANY PRODUCTION DEPLOY !!!
 *
 * These values are placeholders so the deadline engine can compile, tests
 * can run, and the UI shows real countdowns. They are NOT legal advice.
 * Egyptian Civil Procedure and Code of Criminal Procedure set the real
 * windows; they vary by case type, court degree, and judgment finality.
 *
 * Window keys:
 *   first_instance_to_appeal — days from a first-instance judgment within
 *     which an appeal must be filed.
 *   appeal_to_cassation — days from an appellate ruling within which
 *     cassation must be filed.
 *
 * Case-type codes match the seeded `case_types.code` values.
 */
return [
    'appeal_windows_days' => [

        // VERIFY: civil procedure code window for appealing first-instance rulings.
        'first_instance_to_appeal' => [
            'civil' => 40,
            'commercial' => 40,
            'economic' => 40,
            'labor' => 40,
            'personal_status' => 40,
            'family' => 40,
            'rent' => 40,
            'enforcement' => 40,
            'summary_urgent' => 15,
            'administrative' => 60,
            'criminal_misdemeanor' => 10,
            'criminal_felony' => 60,
        ],

        // VERIFY: cassation window from appellate rulings.
        'appeal_to_cassation' => [
            'civil' => 60,
            'commercial' => 60,
            'economic' => 60,
            'labor' => 60,
            'personal_status' => 60,
            'family' => 60,
            'rent' => 60,
            'enforcement' => 60,
            'summary_urgent' => 60,
            'administrative' => 60,
            'criminal_misdemeanor' => 60,
            'criminal_felony' => 60,
        ],
    ],

    /**
     * When a deadline is created, reminders fire at these offsets relative
     * to the due date (negative = days before, 0 = day of).
     */
    'reminder_offsets_days' => [-14, -7, -3, -1, 0],
];
