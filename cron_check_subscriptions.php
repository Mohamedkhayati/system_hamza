<?php
// cron_check_subscriptions.php
// Run daily via cron. PHP 5.4 compatible (mysql_*).

require_once 'db.php';
date_default_timezone_set('Africa/Tunis');

$today = date('Y-m-d');

// 1) Mark subscriptions expired
mysql_query("UPDATE subscriptions SET active = 0 WHERE end_date < '$today' AND active = 1");

// 2) Mark subscriptions active if today inside range
mysql_query("UPDATE subscriptions SET active = 1 WHERE start_date <= '$today' AND end_date >= '$today' AND active = 0");

// 3) Update members.active based on whether they have any active subscription
// Set to 1 if member has at least one active subscription, else 0
// MySQL supports CASE with subquery
$sql = "
    UPDATE members m
    SET m.active = (
        CASE
            WHEN (SELECT COUNT(*) FROM subscriptions s WHERE s.member_id = m.id AND s.active = 1) > 0 THEN 1
            ELSE 0
        END
    )
";
mysql_query($sql);

// Optional: cleanup or logging
// echo mysql_error();
?>
