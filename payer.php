<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode non autorisée');
}

if (!isset($_POST['id'])) {
    die('ID manquant');
}

$id = intval($_POST['id']);
$id_safe = mysql_real_escape_string($id);

// Fetch member
$q = "SELECT * FROM members WHERE id = $id_safe LIMIT 1";
$res = mysql_query($q);
if (!$res) die('Erreur SQL: ' . mysql_error());
$m = mysql_fetch_assoc($res);
if (!$m) die('Membre non trouvé');

// Today's date
$today = date('Y-m-d');

// Archive the old subscription if present (store start/end)
if (!empty($m['subscription_start']) || !empty($m['subscription_end'])) {
    $old_start = $m['subscription_start'] ? $m['subscription_start'] : null;
    $old_end = $m['subscription_end'] ? $m['subscription_end'] : null;

    // Insert into subscription_archive
    $old_start_sql = $old_start ? "'" . mysql_real_escape_string($old_start) . "'" : "NULL";
    $old_end_sql = $old_end ? "'" . mysql_real_escape_string($old_end) . "'" : "NULL";
    $note = mysql_real_escape_string('Archived by paye action');

    $ins_arch = "
        INSERT INTO subscription_archive (member_id, start_date, end_date, archived_at, note)
        VALUES ($id_safe, $old_start_sql, $old_end_sql, NOW(), '$note')
    ";
    mysql_query($ins_arch);
}

// Compute new end date = today + 1 month (handle month overflow)
$start_dt = date_create($today);
date_add($start_dt, date_interval_create_from_date_string('1 months'));
$new_end = date_format($start_dt, 'Y-m-d');

// Insert new row into subscriptions table
$plan = mysql_real_escape_string('1 mois');
$start_sql = "'" . mysql_real_escape_string($today) . "'";
$end_sql = "'" . mysql_real_escape_string($new_end) . "'";
$ins_sub = "
    INSERT INTO subscriptions (member_id, start_date, end_date, plan_name, active)
    VALUES ($id_safe, $start_sql, $end_sql, '$plan', 1)
";
mysql_query($ins_sub);

// Update members table: subscription_start, subscription_end, active
$upd = "
    UPDATE members
    SET subscription_start = $start_sql,
        subscription_end = $end_sql,
        active = 1
    WHERE id = $id_safe
";
mysql_query($upd);

// Redirect back to profile page
header('Location: member_profile.php?id=' . $id);
exit;
