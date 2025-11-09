<?php
$pages = array(
    'dashboard.php' => 'Dashboard',
    'Afficher.php' => 'Membres',
    'Ajouter.php' => 'Ajouter',
    'non_active.php' => 'Non-Actifs',
    'professional_list.php' => 'Pros',
    'archive.php' => 'Archive'
);
$current = basename($_SERVER['PHP_SELF']);
?>
<nav>
    <ul>
        <?php foreach($pages as $file => $label): ?>
            <li>
                <a href="<?php echo $file; ?>" <?php echo ($current === $file) ? 'aria-current="page"' : ''; ?>>
                    <?php echo $label; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
