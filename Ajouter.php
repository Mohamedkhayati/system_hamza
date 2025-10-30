<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // sanitize/validate minimally; expand in production
    $first = $_POST['first_name'] ?? '';
    $last = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? null;
    $email = $_POST['email'] ?? null;
    $category = in_array($_POST['category'] ?? '', ['karate','fitness']) ? $_POST['category'] : 'fitness';

    $stmt = $pdo->prepare("INSERT INTO members (first_name, last_name, phone, email, category) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$first, $last, $phone, $email, $category]);

    header("Location: Afficher.php?added=1");
    exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Ajouter Membre</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'dashboard_nav.php'; ?>
<main class="container">
  <h2>Ajouter un membre</h2>
  <form method="post" action="Ajouter.php" class="form-card">
    <label>Prénom <input name="first_name" required></label>
    <label>Nom <input name="last_name" required></label>
    <label>Téléphone <input name="phone"></label>
    <label>Email <input type="email" name="email"></label>
    <label>Catégorie
      <select name="category">
        <option value="fitness">Fitness</option>
        <option value="karate">Karate</option>
      </select>
    </label>
    <button type="submit" class="btn">Ajouter</button>
  </form>
</main>
</body>
</html>
