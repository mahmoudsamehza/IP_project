<?php $adminPage=basename($_SERVER['PHP_SELF'],'.php'); ?>
<aside class="sv-admin-sidebar">
    <h6 class="mt-2 mb-2">Content</h6>
    <a href="index.php"     class="sv-admin-link <?= $adminPage==='index'?'active':'' ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
    <a href="movies.php"    class="sv-admin-link <?= $adminPage==='movies'?'active':'' ?>"><i class="fa-solid fa-film"></i> All Titles</a>
    <a href="add_movie.php" class="sv-admin-link <?= $adminPage==='add_movie'?'active':'' ?>"><i class="fa-solid fa-plus"></i> Add Title</a>
    <h6 class="mt-4 mb-2">Users</h6>
    <a href="users.php"     class="sv-admin-link <?= $adminPage==='users'?'active':'' ?>"><i class="fa-solid fa-users"></i> Manage Users</a>
    <h6 class="mt-4 mb-2">Site</h6>
    <a href="../index.php" target="_blank" class="sv-admin-link"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Site</a>
    <a href="../logout.php" class="sv-admin-link"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
</aside>
