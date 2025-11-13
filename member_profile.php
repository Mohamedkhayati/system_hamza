<?php
require_once 'db.php';
if (!isset($_GET['id'])) die('ID manquant');

$id = intval($_GET['id']);
$id_safe = mysql_real_escape_string($id);

// Fetch member
$query = "SELECT * FROM members WHERE id = $id_safe";
$res = mysql_query($query);
if (!$res) die('Erreur SQL: ' . mysql_error());
$m = mysql_fetch_assoc($res);
if (!$m) die('Membre non trouvé');

// Auto-archive after 6 months since created_at
$created = strtotime($m['created_at']);
$now = time();
$months = (date('Y', $now) - date('Y', $created)) * 12 + (date('m', $now) - date('m', $created));
if ($months > 6 && !$m['archived']) {
    mysql_query("UPDATE members SET archived = 1 WHERE id = $id_safe");
    $m['archived'] = 1;
}

// Fetch subscription history
$history_res = mysql_query("SELECT start_date, end_date, archived_at FROM subscription_archive WHERE member_id = $id_safe ORDER BY archived_at DESC");
if (!$history_res) die('Erreur SQL: ' . mysql_error());
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Profil</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container">
    <a href="Afficher.php">Retour</a>
    <h1><?php echo htmlspecialchars($m['name']); ?></h1>

    <p class="small">Âge: <?php echo !empty($m['age']) ? $m['age'] : 'Non renseigné'; ?></p>
    <p class="small">Téléphone: <?php echo !empty($m['phone']) ? htmlspecialchars($m['phone']) : 'Non renseigné'; ?></p>
    <p class="small">Catégorie: <?php echo htmlspecialchars($m['category']); ?></p>
    <p class="small">Pro: <?php echo $m['professional'] ? 'Oui' : 'Non'; ?></p>
    <p class="small">Abonnement: <?php echo $m['subscription_start'] ?: 'Non défini'; ?> → <?php echo $m['subscription_end'] ?: 'Non défini'; ?></p>
    <p class="small">Actif: <?php echo $m['active'] ? 'Oui' : 'Non'; ?></p>
    <p class="small">Archivé: <?php echo $m['archived'] ? 'Oui' : 'Non'; ?></p>

    <?php if (!empty($m['photo'])): ?>
        <img src="<?php echo htmlspecialchars($m['photo']); ?>" class="preview" alt="Photo de <?php echo htmlspecialchars($m['name']); ?>">
    <?php endif; ?>

    <p style="margin-top:12px;">
        <a href="update.php?id=<?php echo $m['id']; ?>"><button>Modifier</button></a>
    </p>

    <h2 style="margin-top:24px;">Historique des abonnements</h2>
    <table class="table">
        <thead><tr><th>Début</th><th>Fin</th><th>Archivé le</th></tr></thead>
        <tbody>
        <?php if (mysql_num_rows($history_res) === 0): ?>
            <tr><td colspan="3" style="text-align:center;color:#777;">Aucun historique</td></tr>
        <?php else: while ($r = mysql_fetch_assoc($history_res)): ?>
            <tr>
                <td><?php echo $r['start_date']; ?></td>
                <td><?php echo $r['end_date']; ?></td>
                <td><?php echo $r['archived_at']; ?></td>
            </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
