<?php
require 'db.php';

$filter = $_GET['category'] ?? 'all';

// fetch members
if ($filter !== 'all' && in_array($filter, ['karate','fitness'])) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE category = ? ORDER BY last_name, first_name");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query("SELECT * FROM members ORDER BY last_name, first_name");
}
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Liste des membres</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<main class="container">
  <h2>Membres</h2>

  <div class="controls">
    <a href="Ajouter.php" class="btn">+ Nouveau membre</a>

    <label>Filtre catégorie:
    <select id="categoryFilter">
      <option value="all" <?= $filter==='all' ? 'selected' : '' ?>>Tous</option>
      <option value="fitness" <?= $filter==='fitness' ? 'selected' : '' ?>>Fitness</option>
      <option value="karate" <?= $filter==='karate' ? 'selected' : '' ?>>Karate</option>
    </select>
    </label>
  </div>

  <table class="members-table">
    <thead>
      <tr><th>Nom</th><th>Téléphone</th><th>Email</th><th>Catégorie</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach($members as $m): ?>
      <tr class="member-row" data-id="<?= htmlspecialchars($m['id']) ?>">
        <td><?= htmlspecialchars($m['last_name'] . ' ' . $m['first_name']) ?></td>
        <td><?= htmlspecialchars($m['phone']) ?></td>
        <td><?= htmlspecialchars($m['email']) ?></td>
        <td><?= htmlspecialchars($m['category']) ?></td>
        <td>
          <a href="update.php?id=<?= $m['id'] ?>" class="btn small">Edit</a>
          <a href="delete.php?id=<?= $m['id'] ?>" class="btn small danger" onclick="return confirm('Supprimer ?')">Delete</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<!-- Profile modal -->
<div id="profileModal" class="modal" aria-hidden="true">
  <div class="modal-content" role="dialog" aria-modal="true">
    <button id="closeModal" class="modal-close">×</button>
    <div id="profileBody"></div>
  </div>
</div>

<script>
document.getElementById('categoryFilter').addEventListener('change', function() {
  const v = this.value;
  const url = new URL(window.location.href);
  url.searchParams.set('category', v);
  window.location.href = url.toString();
});

// double-click to show profile
document.querySelectorAll('.member-row').forEach(row => {
  row.addEventListener('dblclick', function() {
    const id = this.dataset.id;
    fetch('member_profile.php?id=' + encodeURIComponent(id))
      .then(r => r.text())
      .then(html => {
        document.getElementById('profileBody').innerHTML = html;
        document.getElementById('profileModal').style.display = 'block';
        document.getElementById('profileModal').setAttribute('aria-hidden','false');
      });
  });
});

document.getElementById('closeModal').addEventListener('click', function(){
  document.getElementById('profileModal').style.display = 'none';
  document.getElementById('profileModal').setAttribute('aria-hidden','true');
});
</script>
</body>
</html>
