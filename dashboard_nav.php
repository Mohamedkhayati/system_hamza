<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="dashboard.php">MyGym</a>
    <nav class="top-nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="Afficher.php">Membres</a>
      <a href="Ajouter.php">Ajouter</a>
      <a href="export.php">Exporter</a>
      <a href="Non_active.php">Inactifs</a>
    </nav>
    <div class="top-actions">
      <input id="searchInput" placeholder="Rechercher membre...">
    </div>
  </div>
</header>
<script>
document.getElementById('searchInput')?.addEventListener('input', function(e) {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('.member-row').forEach(r => {
    const txt = r.textContent.toLowerCase();
    r.style.display = txt.includes(q) ? '' : 'none';
  });
});
</script>
