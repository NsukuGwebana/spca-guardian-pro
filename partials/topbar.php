<?php
// Shared top bar for staff pages: brand, link back to the user's own dashboard, sign out.
$__who = trim(($_SESSION['firstname'] ?? '') . ' (' . ($_SESSION['userrole'] ?? '') . ')');
?>
<header class="spca-topbar">
  <a class="spca-brand" href="dashboard.php"><span>SPCA</span>Guardian Pro</a>
  <nav>
    <?php if (!empty($_SESSION['username'])): ?><a href="dashboard.php" title="Signed in as <?= e($__who) ?>">&larr; My dashboard</a><?php endif; ?>
    <a href="logoutForm.html">Log out</a>
  </nav>
</header>
