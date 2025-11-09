<?php
require_once 'db.php';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT id, name, category, photo FROM members WHERE professional = 1";

// Add search filter
if ($search !== '') {
    $search_safe = mysql_real_escape_string("%$search%");
    $sql .= " AND (name LIKE '$search_safe' OR phone LIKE '$search_safe' OR category LIKE '$search_safe')";
}

$sql .= " ORDER BY created_at DESC";

$res = mysql_query($sql);
if (!$res) die('Erreur SQL: ' . mysql_error());
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Professionnels</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container">
    <h2>Professionnels</h2>

    <form method="get" class="controls">
        <input type="text" name="search" class="search-box" placeholder="Rechercher pro..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Rechercher</button>
        <?php if ($search): ?>
            <a href="professional_list.php"><button type="button" class="secondary">Effacer</button></a>
        <?php endif; ?>
    </form>

    <ul>
    <?php while ($r = mysql_fetch_assoc($res)): ?>
        <li style="margin:8px 0;">
            <a href="member_profile.php?id=<?php echo $r['id']; ?>">
                <?php if (!empty($r['photo'])): ?>
                    <img src="<?php echo htmlspecialchars($r['photo']); ?>" style="height:48px;width:48px;object-fit:cover;border-radius:6px;margin-right:8px;vertical-align:middle;">
                <?php endif; ?>
                <?php echo htmlspecialchars($r['name']); ?>
            </a> — <span class="small"><?php echo htmlspecialchars($r['category']); ?></span>
        </li>
    <?php endwhile; ?>
    </ul>
    <a href="Afficher.php"><button class="secondary">Retour</button></a>
</div>
</body>
</html>
