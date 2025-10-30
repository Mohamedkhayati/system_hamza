<?php
require 'db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo "<p>Membre introuvable</p>"; exit; }

// get member
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) { echo "<p>Membre introuvable</p>"; exit; }

// current subscription (if any)
$subStmt = $pdo->prepare("SELECT * FROM subscriptions WHERE member_id = ? AND active = 1 ORDER BY start_date DESC LIMIT 1");
$subStmt->execute([$id]);
$currentSub = $subStmt->fetch(PDO::FETCH_ASSOC);

// archive
$archStmt = $pdo->prepare("SELECT * FROM subscription_archive WHERE member_id = ? ORDER BY archived_at DESC");
$archStmt->execute([$id]);
$archives = $archStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="profile">
  <div class="profile-header">
    <img class="avatar" src="<?= htmlspecialchars($m['photo'] ?: 'default-avatar.png') ?>" alt="photo">
    <div class="profile-info">
      <h3><?= htmlspecialchars($m['first_name'].' '.$m['last_name']) ?></h3>
      <p><strong>Catégorie:</strong> <?= htmlspecialchars($m['category']) ?></p>
      <p><strong>Téléphone:</strong> <?= htmlspecialchars($m['phone']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($m['email']) ?></p>
    </div>
  </div>

  <section>
    <h4>Abonnement courant</h4>
    <?php if ($currentSub): ?>
      <p><strong>Plan:</strong> <?= htmlspecialchars($currentSub['plan_name']) ?></p>
      <p><strong>De:</strong> <?= htmlspecialchars($currentSub['start_date']) ?> <strong>à</strong> <?= htmlspecialchars($currentSub['end_date']) ?></p>
      <p><strong>Prix:</strong> <?= htmlspecialchars($currentSub['price']) ?> DT</p>
    <?php else: ?>
      <p>Aucun abonnement actif</p>
    <?php endif; ?>
  </section>

  <section>
    <h4>Historique des abonnements</h4>
    <?php if (count($archives) === 0): ?>
      <p>Pas d'historique.</p>
    <?php else: ?>
      <table class="archive-table">
        <thead><tr><th>Plan</th><th>Début</th><th>Fin</th><th>Prix</th><th>Archivé le</th><th>Note</th></tr></thead>
        <tbody>
          <?php foreach($archives as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['plan_name']) ?></td>
              <td><?= htmlspecialchars($a['start_date']) ?></td>
              <td><?= htmlspecialchars($a['end_date']) ?></td>
              <td><?= htmlspecialchars($a['price']) ?></td>
              <td><?= htmlspecialchars($a['archived_at']) ?></td>
              <td><?= htmlspecialchars($a['note']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <div class="profile-actions">
    <a class="btn" href="update.php?id=<?= $m['id'] ?>">Modifier</a>
    <a class="btn" href="update_subscriptions.php?member_id=<?= $m['id'] ?>">Gérer abonnement</a>
  </div>
</div>
