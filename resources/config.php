<?php

// Current Term
$CURRENT_TERM = "Fall2016";
$CURRENT_START_DATE = "2016-09-01";
$CURRENT_END_DATE = "2016-12-31";

// Checkins
$CHECKINS_PER_WEEK = array(
    "Single" => 1,
    "Standard" => 2,
    "Social" => 2,
    "Competition" => INF,
    "Full" => INF,
    "Summer" => INF,
);
$NUMBER_OF_FREE_CHECKINS = 2;

// Lesson Times
$BEGINNER_LESSON_TIME = "8:00pm";
$INTERMEDIATE_LESSON_TIME = "7:15pm";
$CHECK_IN_PERIOD = 30; // minutes


/*
 * Schedule of Competition team practices
 * (These must be input manually before the start of the semester)
 */
$COMP_PRACTICES_TABLE = array(
    "Spring2015" => array(
        "2015-02-03", "2015-02-05", "2015-02-06", // Feb
        "2015-02-10", "2015-02-12", "2015-02-13",
        "2015-02-17", "2015-02-19", "2015-02-20",
        "2015-02-24", "2015-02-26", "2015-02-27",
        "2015-03-03", "2015-03-05", "2015-03-06", // March
        "2015-03-10", "2015-03-12", "2015-03-13",
        // Spring Break
        "2015-03-24", "2015-03-26", "2015-03-27",
        "2015-03-31",
        "2015-04-02", "2015-04-03", // April
        "2015-04-07", "2015-04-09", "2015-04-10",
        "2015-04-14", "2015-04-16", "2015-04-17",
        "2015-04-21", "2015-04-23", "2015-04-24"
    ),
    "Fall2015" => array(
        "2015-09-22", "2015-09-24", "2015-09-25",
        "2015-09-29", "2015-10-01", "2015-10-02",
        "2015-10-06", "2015-10-08", "2015-10-09",
        "2015-10-13", "2015-10-15", "2015-10-16",
        "2015-10-20", "2015-10-22", "2015-10-23",
        "2015-10-27", "2015-10-29", "2015-10-30",
        "2015-11-03", "2015-11-05", "2015-11-06",
        "2015-11-10", "2015-11-12", "2015-11-13",
        "2015-11-17", "2015-11-19", "2015-11-20",
        "2015-11-24", // Thanksgiving
        "2015-12-01", "2015-12-03", "2015-12-04",
        "2015-12-08", "2015-12-10", "2015-12-11",
        "2015-12-15", "2015-12-17", "2015-12-18",
    ),
    "Spring2016" => array(
        "2016-01-26", "2016-01-28", "2016-01-29", // Jan
        "2016-02-02", "2016-02-04", "2016-02-05", // Feb
        "2016-02-09", "2016-02-11", "2016-02-12",
        "2016-02-16", "2016-02-18", "2016-02-19",
        "2016-02-23", "2016-02-25", "2016-02-26",
        "2016-03-01", "2016-03-03", "2016-03-04", // March
        "2016-03-08", "2016-03-10", "2016-03-11",
        // Spring Break
        "2016-03-22", "2016-03-24", "2016-03-25",
        "2016-03-29", "2016-03-31",
        "2016-04-01", // April
        "2016-04-05", "2016-04-07", // MichComp
        "2016-04-12", "2016-04-14", "2016-04-15",
        "2016-04-19", "2016-04-21", "2016-04-22",
        "2016-04-26", "2016-04-28", "2016-04-29",
    ),
    "Fall2016" => array(
        "2015-09-20", "2015-09-22", "2015-09-23", // Sept
        "2015-09-27", "2015-09-29", "2015-09-30",
        "2015-10-04", "2015-10-06", "2015-10-07", // Oct
        "2015-10-11", "2015-10-13", "2015-10-14",
        "2015-10-18", "2015-10-20", "2015-10-21",
        "2015-10-25", "2015-10-27", "2015-10-28",
        "2015-11-01", "2015-11-03", "2015-11-04", // Nov
        "2015-11-08", "2015-11-10", "2015-11-11",
        "2015-11-15", "2015-11-17", "2015-11-18",
        "2015-11-22", // Thanksgiving
        "2015-11-29", "2015-12-01", "2015-12-02", // Dec
        "2015-12-06", "2015-12-08", "2015-12-09",
    ),
);


/*
 * Due dates for comp team fees, based on maximum owed at certain dates
 * (These must be input manually before the start of the semester)
 */
$COMP_DUE_DATE_TABLE = array(
    // term -> fee_status -> date -> min_outstanding_at_date (i.e. cumulative)
    "Spring2015" => array(
        "StudentServicesFees" => array(
            "2015-02-12" => -9000,
            "2015-03-06" => -4500,
            "2015-04-07" => 0
        ),
        "URCMembership" => array(
            "2015-02-12" => -9000,
            "2015-03-06" => -4500,
            "2015-04-07" => 0
        ),
        "Affiliate" => array(
            "2015-02-12" => 0
        )
    ),
    "Fall2015" => array(
        "StudentServicesFees" => array(
            "2015-09-25" => -9000,
            "2015-10-16" => -4500,
            "2015-11-06" => 0
        ),
        "Affiliate" => array(
            "2015-09-25" => 0
        )
    ),
    "Spring2016" => array(
        "StudentServicesFees" => array(
            "2016-02-05" => -10000,
            "2016-02-19" => -5000,
            "2016-03-04" => 0
        ),
        "Affiliate" => array(
            "2015-02-05" => 0
        )
    ),
    "Fall2016" => array(
        "StudentServicesFees" => array(
            "2015-09-30" => -10000,
            "2016-10-14" => -5000,
            "2016-11-28" => 0,
        ),
        "Affiliate" => array(
            "2016-09-22" => 0
        )
    )
);


/*
 * Due dates for comp team fees, based on maximum owed at certain dates
 * (These must be input manually before the start of the semester)
 */
$COMP_DUE_DATE_TABLE = array(
    // term -> fee_status -> date -> min_outstanding_at_date (i.e. cumulative)
    "Spring2015" => array(
        "StudentServicesFees" => array(
            "2015-02-12" => -9000,
            "2015-03-06" => -4500,
            "2015-04-07" => 0
        ),
        "URCMembership" => array(
            "2015-02-12" => -9000,
            "2015-03-06" => -4500,
            "2015-04-07" => 0
        ),
        "Affiliate" => array(
            "2015-02-12" => 0
        )
    ),
    "Fall2015" => array(
        "StudentServicesFees" => array(
            "2015-09-25" => -9000,
            "2015-10-16" => -4500,
            "2015-11-06" => 0
        ),
        "Affiliate" => array(
            "2015-09-25" => 0
        )
    ),
    "Spring2016" => array(
        "StudentServicesFees" => array(
            "2016-02-05" => -10000,
            "2016-02-19" => -5000,
            "2016-03-04" => 0
        ),
        "Affiliate" => array(
            "2015-02-05" => 0
        )
    ),
    "Fall2016" => array(
        "StudentServicesFees" => array(
            "2015-09-30" => -10000,
            "2016-10-14" => -5000,
            "2016-11-28" => 0,
        ),
        "Affiliate" => array(
            "2016-09-22" => 0
        )
    )
);

// The late fee charged for comp team members, each practice that the fees are overdue
$LATE_FEE_AMOUNT = 200;

/*
 * Table listing fee amounts for membership types
 * fee-status => membership-type => amount
 */
$FEE_TABLE = array(
    'StudentServicesFees' => array(
        'Full' => 4000,
        'Competition' => 22000,
        'Standard' => 5000,
        'Single' => 2500,
        'Social' => 1500,
    ),
    'URCMembership' => array(
        'Standard' => 6000,
        'Single' => 3000,
        'Social' => 1800,
        'Competition' => 20000,
    ),
    'Affiliate' => array(
        'Full' => 6900,
        'Competition' => 6000,
    ),
    'Summer' => array(
        'Summer' => 0,
    ),
);

// Discount for new competition team members
$NEW_COMP_MEMBER_DISCOUNT = 3000;

// Prices for apparel
$PURCHASE_TABLE = array(
    "Jacket" => 4600,
    "Shoes_Men" => 3000,
    "Shoes_Women" => 2500
);