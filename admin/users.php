<?php
require '../config_db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 0. Ensure 'status' column exists
$check_status = $conn->query("SHOW COLUMNS FROM mlm_users LIKE 'status'");
if ($check_status->num_rows == 0) {
    $conn->query("ALTER TABLE mlm_users ADD COLUMN status ENUM('active', 'banned') DEFAULT 'active'");
}

// Handle Actions (Ban/Unban)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'ban') {
        $conn->query("UPDATE mlm_users SET status='banned' WHERE id=$id");
    } elseif ($action === 'unban') {
        $conn->query("UPDATE mlm_users SET status='active' WHERE id=$id");
    }
    // Redirect to remove query params
    header("Location: users.php");
    exit;
}

// Pagination & Search
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = "WHERE 1=1";
if ($search) {
    $where_clause .= " AND (username LIKE '%$search%' OR email LIKE '%$search%')";
}

$total_users = $conn->query("SELECT COUNT(*) FROM mlm_users $where_clause")->fetch_row()[0];
$total_pages = ceil($total_users / $limit);

$users = $conn->query("SELECT * FROM mlm_users $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 1.5rem; }
    </style>
</head>
<body class="flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 ml-[280px] p-8">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">User Management</h1>
            <form class="flex gap-2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search users..." 
                       class="px-4 py-2 bg-slate-800 border border-slate-700 rounded text-sm focus:outline-none focus:border-indigo-500">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded hover:bg-indigo-500">Search</button>
            </form>
        </header>

        <div class="glass-card overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="text-xs uppercase bg-slate-800 text-slate-400">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Contact</th>
                        <th class="px-6 py-3">Sponsor</th>
                        <th class="px-6 py-3">Investment</th>
                        <th class="px-6 py-3">Joined</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($u = $users->fetch_assoc()): 
                        // Fetch sponsor username
                        $sponsor_name = '-';
                        if ($u['sponsor_id']) {
                            $s = $conn->query("SELECT username FROM mlm_users WHERE id=" . $u['sponsor_id']);
                            if ($s && $s->num_rows > 0) $sponsor_name = $s->fetch_assoc()['username'];
                        }
                    ?>
                    <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                        <td class="px-6 py-4">#<?= $u['id'] ?></td>
                        <td class="px-6 py-4 font-bold text-white"><?= htmlspecialchars($u['username']) ?> <span class="text-xs font-normal text-slate-500">(<?= $u['role'] ?>)</span></td>
                        <td class="px-6 py-4"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="px-6 py-4 text-indigo-400"><?= $sponsor_name ?></td>
                        <td class="px-6 py-4 text-emerald-400">$<?= number_format($u['investment'], 2) ?></td>
                        <td class="px-6 py-4 text-slate-400"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                        <td class="px-6 py-4">
                            <?php if ($u['status'] == 'banned'): ?>
                                <a href="?action=unban&id=<?= $u['id'] ?>" class="text-emerald-400 hover:text-white mr-2" onclick="return confirm('Unban this user?');">Unban</a>
                            <?php else: ?>
                                <a href="?action=ban&id=<?= $u['id'] ?>" class="text-red-400 hover:text-red-300 mr-2" onclick="return confirm('Ban this user?');">Ban</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="mt-4 flex justify-between items-center text-sm text-slate-400">
                <div>Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_users) ?> of <?= $total_users ?> users</div>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 bg-slate-800 rounded hover:bg-slate-700">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 bg-slate-800 rounded hover:bg-slate-700">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
