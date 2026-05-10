<?php
include 'db_connect.php';

$results = [];
$keyword = '';

if (isset($_POST['search'])) {
    $keyword = trim($_POST['keyword'] ?? '');

    $sql = "SELECT * FROM lost_items
            WHERE item_name    LIKE :keyword
               OR reported_by  LIKE :keyword
               OR category     LIKE :keyword
               OR location_found LIKE :keyword
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([':keyword' => '%' . $keyword . '%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // Show all records on first load, newest first
    $stmt = $conn->prepare("SELECT * FROM lost_items ORDER BY created_at DESC");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Lost Items — Lost &amp; Found</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/style.search.css">
</head>
<body>

<!-- Page header -->
<header class="page-header">
    <h1>Lost &amp; Found</h1>
</header>

<!-- Navigation -->
<nav class="nav">
    <a href="index.php">Report Item</a>
    <a href="search.php" class="active">Search</a>
</nav>

<!-- Success banner (from redirect after insert) -->
<?php if (isset($_GET['inserted']) && $_GET['inserted'] === '1'): ?>
<div class="alert alert-ok" style="width:100%;max-width:100%;position:relative;z-index:1;animation:fadeUp 0.4s ease both;margin-bottom:20px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <div>
        <strong>Item registered successfully!</strong>
        <p>The record has been saved and is now visible in the table below.</p>
    </div>
</div>
<?php endif; ?>

<?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
<div class="alert alert-ok" style="width:100%;max-width:100%;position:relative;z-index:1;animation:fadeUp 0.4s ease both;margin-bottom:20px;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <div>
        <strong>Item updated successfully!</strong>
        <p>Your changes have been saved.</p>
    </div>
</div>
<?php endif; ?>

<!-- Main card -->
<div class="search-card">

    <!-- Search bar -->
    <form method="POST" id="search-form">
        <div class="search-row">
            <div class="input-wrap">
                <span class="ico">
                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input
                    type="text"
                    name="keyword"
                    id="keyword"
                    placeholder="Search by item name, category, location or reporter…"
                    value="<?= htmlspecialchars($keyword) ?>"
                    autocomplete="off">
            </div>
            <button type="submit" name="search" class="search-btn">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Search
            </button>
        </div>
    </form>

    <!-- Result meta -->
    <div class="result-meta">
        <span>
            <?php if (isset($_POST['search']) && $keyword !== ''): ?>
                Showing <strong><?= count($results) ?></strong> result<?= count($results) !== 1 ? 's' : '' ?> for "<strong><?= htmlspecialchars($keyword) ?></strong>"
            <?php else: ?>
                <strong><?= count($results) ?></strong> total item<?= count($results) !== 1 ? 's' : '' ?> on record
            <?php endif; ?>
        </span>
        <?php if (isset($_POST['search']) && $keyword !== ''): ?>
            <a href="search.php" style="color:var(--text-2);font-size:12px;text-decoration:none;">✕ Clear search</a>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Location Found</th>
                    <th>Date Found</th>
                    <th>Reported By</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($results) > 0): ?>
                <?php foreach ($results as $row): ?>
                <?php
                    // Resolve status badge class
                    $s = $row['status'];
                    $badgeClass = match($s) {
                        'Unclaimed'   => 'badge-unclaimed',
                        'Claimed'     => 'badge-claimed',
                        default       => 'badge-review',
                    };
                    $dotColor = match($s) {
                        'Unclaimed'   => '#fbbf24',
                        'Claimed'     => '#22c984',
                        default       => '#4a8cff',
                    };
                ?>
                <tr>
                    <td class="muted"><?= htmlspecialchars($row['id']) ?></td>
                    <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                    <td class="muted"><?= htmlspecialchars($row['category']) ?></td>
                    <td class="muted"><?= htmlspecialchars($row['location_found']) ?></td>
                    <td class="muted"><?= htmlspecialchars($row['date_found']) ?></td>
                    <td><?= htmlspecialchars($row['reported_by']) ?></td>
                    <td class="muted"><?= htmlspecialchars($row['contact_number'] ?? '—') ?></td>
                    <td>
                        <span class="badge-status <?= $badgeClass ?>">
                            <span style="width:6px;height:6px;border-radius:50%;background:<?= $dotColor ?>;display:inline-block;flex-shrink:0;"></span>
                            <?= htmlspecialchars($s) ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit.php?id=<?= htmlspecialchars($row['id']) ?>" class="edit-link">
                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Edit
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <p>No items found<?= ($keyword !== '') ? ' matching "' . htmlspecialchars($keyword) . '"' : '' ?>.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>