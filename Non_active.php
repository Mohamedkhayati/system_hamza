<?php
require_once 'db.php';
$today = date('Y-m-d');
date_default_timezone_set('Africa/Tunis');

// AUTO-UPDATE: mark members as inactive if subscription_end < today
$update_sql = "
    UPDATE members m
    LEFT JOIN subscriptions s ON m.id = s.member_id
    SET m.active = 0
    WHERE (m.subscription_end < '$today' OR m.subscription_end IS NULL OR s.active = 0)
      AND m.archived = 0
";
mysql_query($update_sql) or die('Erreur SQL update: ' . mysql_error());

// Fetch non-active members
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT m.id, m.name, m.age, m.phone, m.subscription_end 
        FROM members m 
        LEFT JOIN subscriptions s ON m.id = s.member_id 
        WHERE (m.subscription_end < '$today' OR m.subscription_end IS NULL OR s.active = 0) 
          AND m.archived = 0";

// Add search filter
if ($search !== '') {
    $search_safe = mysql_real_escape_string("%$search%");
    $sql .= " AND (m.name LIKE '$search_safe' OR m.phone LIKE '$search_safe' OR m.age LIKE '$search_safe' OR m.category LIKE '$search_safe')";
}

$sql .= " ORDER BY m.subscription_end";

$res = mysql_query($sql);
if (!$res) die('Erreur SQL: ' . mysql_error());
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Non-Actifs</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container">
    <h2>Membres non-actifs</h2>

    <form method="get" class="controls">
        <input type="text" name="search" class="search-box" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Rechercher</button>
        <?php if ($search): ?>
            <a href="non_active.php"><button type="button" class="secondary">Effacer</button></a>
        <?php endif; ?>
    </form>

    <table class="table">
        <thead>
            <tr><th>Nom</th><th>Âge</th><th>Tél</th><th>Fin</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while ($r = mysql_fetch_assoc($res)): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['name']); ?></td>
                <td><?php echo $r['age']; ?></td>
                <td><?php echo $r['phone']; ?></td>
                <td><?php echo !empty($r['subscription_end']) ? $r['subscription_end'] : 'Jamais'; ?></td>
                <td><a href="member_profile.php?id=<?php echo $r['id']; ?>"><button>Voir</button></a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
