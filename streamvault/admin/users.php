<?php
require_once '../includes/db.php'; require_once '../includes/auth.php'; requireAdmin();
$pageTitle='Manage Users'; $db=getDB();
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['user_id'],$_POST['role'])) {
    $uid=(int)$_POST['user_id']; $role=in_array($_POST['role'],['user','admin'])?$_POST['role']:'user';
    if ($uid!==(int)$_SESSION['user_id']) $db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$uid]);
}
$users=$db->query("SELECT u.*,(SELECT COUNT(*) FROM watchlist WHERE user_id=u.id) AS wl_count FROM users u ORDER BY u.created_at DESC")->fetchAll();
include '../includes/header.php';
?>
<div class="d-flex" style="min-height:calc(100vh - 68px);padding-top:68px;">
    <?php include 'sidebar.php'; ?>
    <div class="sv-admin-content">
        <h1 class="mb-4"><i class="fa-solid fa-users text-danger me-2"></i>Manage Users</h1>
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Watchlist</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><strong><?= sanitize($u['username']) ?></strong></td>
                <td><?= sanitize($u['email']) ?></td>
                <td><span class="badge <?= $u['role']==='admin'?'bg-danger':'bg-secondary' ?>"><?= $u['role'] ?></span></td>
                <td><?= (int)$u['wl_count'] ?></td>
                <td><?= date('Y-m-d',strtotime($u['created_at'])) ?></td>
                <td>
                    <?php if ((int)$u['id']!==(int)$_SESSION['user_id']): ?>
                    <form method="POST" class="d-flex gap-1 align-items-center">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <select name="role" class="form-select form-select-sm" style="width:auto;">
                            <option value="user"  <?= $u['role']==='user'?'selected':'' ?>>User</option>
                            <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-save"></i></button>
                    </form>
                    <?php else: ?><span class="text-secondary small"><i class="fa-solid fa-shield-halved me-1"></i>You</span><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
