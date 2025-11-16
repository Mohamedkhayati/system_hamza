<?php
require_once 'db.php';
date_default_timezone_set('Africa/Tunis');

$message = '';
$search = '';
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
}

// Delete member
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $delete_sql = sprintf("DELETE FROM members WHERE id = %d", $id);
    $delete_res = mysql_query($delete_sql);
    if ($delete_res) {
        $message = "Membre supprimé avec succès.";
    } else {
        $message = "Erreur lors de la suppression : " . mysql_error();
    }
}

$today = date('Y-m-d');

// Prepare base SQL
$sql = "
    SELECT m.id, m.name, m.age, m.phone, m.category, m.professional,
           m.subscription_start, m.subscription_end, m.archived, m.active
    FROM members m
    WHERE 1=1
";

if ($search !== '') {
    $like = mysql_real_escape_string('%' . $search . '%');
    $sql .= " AND (m.name LIKE '$like' OR m.phone LIKE '$like' OR m.age LIKE '$like' OR m.category LIKE '$like')";
}

$sql .= " ORDER BY m.created_at DESC";
$res = mysql_query($sql);
if (!$res) {
    die('Erreur SQL : ' . mysql_error());
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Membres</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tr-archived { opacity: 0.6; }
        .status-active { color: green; font-weight:bold; }
        .status-inactive { color: #999; }
        form.inline { display:inline; margin:0; padding:0; }
        button.paye { background:#2d9cdb; color:#fff; border:none; padding:6px 8px; cursor:pointer; }
        button.paye[style*="display:none"] { visibility:hidden; }
    </style>
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<div class="container">
    <h2>Liste des membres</h2>

    <?php if ($message != ''): ?>
        <div class="success-msg"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="get" class="controls">
        <input type="text" name="search" class="search-box"
               placeholder="Rechercher par nom, téléphone, âge, catégorie..."
               value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Rechercher</button>
        <?php if ($search != ''): ?>
            <a href="Afficher.php"><button type="button" class="secondary">Effacer</button></a>
        <?php endif; ?>
    </form>

    <div class="controls">
        <a href="Ajouter.php"><button>Ajouter</button></a>
        <button id="toggleArchived" class="secondary">Archivés</button>
    </div>

    <table class="table" id="membersTable">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Âge</th>
                <th>Tél</th>
                <th>Cat</th>
                <th>Début</th>
                <th>Fin</th>
                <th>Statut</th>
                <th>Pro</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($r = mysql_fetch_assoc($res)):
            $status = ($r['active'] == 1) ? 'Actif' : 'Inactif';
            $statusClass = ($status == 'Actif') ? 'status-active' : 'status-inactive';
        ?>
            <tr data-id="<?php echo $r['id']; ?>" class="<?php echo ($r['archived'] ? 'tr-archived' : ''); ?>">
                <td><?php echo htmlspecialchars($r['name']); ?></td>
                <td><?php echo ($r['age'] ? $r['age'] : '-'); ?></td>
                <td><?php echo ($r['phone'] ? $r['phone'] : '-'); ?></td>
                <td><?php echo htmlspecialchars($r['category']); ?></td>
                <td><?php echo ($r['subscription_start'] ? $r['subscription_start'] : '-'); ?></td>
                <td><?php echo ($r['subscription_end'] ? $r['subscription_end'] : '-'); ?></td>
                <td class="<?php echo $statusClass; ?>"><?php echo $status; ?></td>
                <td><?php echo ($r['professional'] ? 'Oui' : 'Non'); ?></td>
                <td>
                    <a href="member_profile.php?id=<?php echo $r['id']; ?>"><button class="secondary">Voir</button></a>
                    <a href="update.php?id=<?php echo $r['id']; ?>"><button class="secondary">Modif</button></a>

                    <!-- Delete -->
                    <a href="Afficher.php?delete=<?php echo $r['id']; ?>&search=<?php echo urlencode($search); ?>" onclick="return confirm('Supprimer ?')">
                        <button class="danger">Suppr</button>
                    </a>

                    <!-- PAYE form: visible only if member is INACTIVE -->
                    <form class="inline" method="post" action="payer.php" onsubmit="return confirm('Confirmer paiement et ajouter 1 mois ?');">
                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                        <!-- hide visually if already active -->
                        <?php $style = ($r['active'] == 1) ? 'style="display:none"' : ''; ?>
                        <button class="paye" type="submit" <?php echo $style; ?>>Paye</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
var rows = document.querySelectorAll('#membersTable tbody tr');
for (var i = 0; i < rows.length; i++) {
    rows[i].ondblclick = function() {
        location.href = 'member_profile.php?id=' + this.getAttribute('data-id');
    };
}
var show = false;
document.getElementById('toggleArchived').onclick = function() {
    show = !show;
    var archived = document.querySelectorAll('.tr-archived');
    for (var i = 0; i < archived.length; i++) {
        archived[i].style.display = show ? '' : 'none';
    }
    this.textContent = show ? 'Masquer' : 'Archivés';
};
</script>
</body>
</html>
