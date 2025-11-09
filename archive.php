<?php
require_once 'db.php'; // This should contain your mysql_connect() code

// Unarchive member
if (isset($_GET['unarchive'])) {
    $id = (int)$_GET['unarchive'];
    $query = sprintf("UPDATE members SET archived = 0 WHERE id = %d", $id);
    $result = mysql_query($query);
    if (!$result) {
        die('Error updating record: ' . mysql_error());
    }
    header('Location: archive.php');
    exit;
}

// Fetch archived members
$res = mysql_query("SELECT id, name, created_at FROM members WHERE archived = 1 ORDER BY created_at DESC");
if (!$res) {
    die('Error fetching data: ' . mysql_error());
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Archive</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container">
    <h2>Membres archivés</h2>
    <ul>
    <?php while ($r = mysql_fetch_assoc($res)): ?>
        <li>
            <?php echo htmlspecialchars($r['name']); ?> — <?php echo $r['created_at']; ?>
            <a href="member_profile.php?id=<?php echo $r['id']; ?>">Voir</a> |
            <a href="archive.php?unarchive=<?php echo $r['id']; ?>">Désarchiver</a>
        </li>
    <?php endwhile; ?>
    </ul>
    <a href="Afficher.php"><button class="secondary">Retour</button></a>
</div>
</body>
</html>
