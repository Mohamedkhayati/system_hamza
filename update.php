<?php
require 'db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: Afficher.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = $_POST['first_name'] ?? '';
    $last = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? null;
    $email = $_POST['email'] ?? null;
    $category = in_array($_POST['category'] ?? '', ['karate','fitness']) ? $_POST['category'] : 'fitness';

    $stmt = $pdo->prepare("UPDATE members SET first_name = ?, last_name = ?, phone = ?, email = ?, category = ? WHERE id = ?");
    $stmt->execute([$first, $last, $phone, $email, $category, $id]);

    header("Location: Afficher.php?updated=1");
    exit;
}

// fetch
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$m = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$m) { header('Location: Afficher.php'); exit; }
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Modifier</title><link rel="stylesheet" href="styles.css"></head>
<body>
<?php include 'dashboard_nav.php'; ?>
<main class="container">
  <h2>Modifier membre</h2>
  <form method="post" class="form-card">
    <label>Prénom <input name="first_name" value="<?= htmlspecialchars($m['first_name']) ?>" required></label>
    <label>Nom <input name="last_name" value="<?= htmlspecialchars($m['last_name']) ?>" required></label>
    <label>Téléphone <input name="phone" value="<?= htmlspecialchars($m['phone']) ?>"></label>
    <label>Email <input type="email" name="email" value="<?= htmlspecialchars($m['email']) ?>"></label>
    <label>Catégorie
      <select name="category">
        <option value="fitness" <?= $m['category']==='fitness' ? 'selected' : '' ?>>Fitness</option>
        <option value="karate" <?= $m['category']==='karate' ? 'selected' : '' ?>>Karate</option>
      </select>
    </label>
    <button type="submit" class="btn">Enregistrer</button>
  </form>
</main>
</body>
</html>
