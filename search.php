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
<style>
/* ── Full-width body override for search page ── */
body {
    align-items: stretch;
    padding-left: 32px;
    padding-right: 32px;
}
.nav { align-self: flex-start; }
.page-header { align-self: center; }
.search-card {
    width: 100%;
    max-width: 100%;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 36px 40px 44px;
    position: relative; z-index: 1;
    animation: fadeUp 0.55s 0.1s ease both;
}
.search-card::before {
    content: '';
    position: absolute;
    inset: -1px;
    border-radius: calc(var(--radius) + 1px);
    background: linear-gradient(135deg, rgba(74,140,255,0.18), rgba(94,106,210,0.08) 50%, transparent 75%);
    pointer-events: none;
}

/* search bar row */
.search-row {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 32px;
}
.search-row .input-wrap { flex: 1; }
.search-row input {
    padding-left: 42px;
    font-size: 14px;
}
.search-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #4a8cff 0%, #3060d0 100%);
    color: #fff;
    border: none;
    border-radius: var(--radius-s);
    padding: 11px 24px;
    font-family: var(--font-b);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 4px 20px rgba(74,140,255,0.38), inset 0 1px 0 rgba(255,255,255,0.15);
    white-space: nowrap;
}
.search-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 32px rgba(74,140,255,0.52), inset 0 1px 0 rgba(255,255,255,0.15);
}
.search-btn svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

/* table */
.tbl-wrap { overflow-x: auto; }
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
}
thead th {
    background: rgba(74,140,255,0.07);
    color: var(--text-2);
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
    text-align: left;
}
tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.15s ease;
}
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: rgba(74,140,255,0.04); }
tbody td {
    padding: 13px 16px;
    color: var(--text-1);
    vertical-align: middle;
}
tbody td.muted { color: var(--text-2); font-size: 13px; }

/* status badges */
.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 20px;
    border-radius: 99px;
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: 0.04em;
}
.badge-unclaimed { background: rgba(251,191,36,0.14);  color: #fbbf24; border: 1px solid rgba(251,191,36,0.28); }
.badge-claimed   { background: rgba(34,201,132,0.14);  color: #22c984; border: 1px solid rgba(34,201,132,0.28); }
.badge-review    { background: rgba(74,140,255,0.14);  color: #4a8cff; border: 1px solid rgba(74,140,255,0.28); }

/* action link */
.edit-link {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--accent);
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 500;
    padding: 4px 12px;
    border: 1px solid rgba(74,140,255,0.28);
    border-radius: 6px;
    transition: all 0.18s ease;
}
.edit-link:hover {
    background: rgba(74,140,255,0.10);
    border-color: rgba(74,140,255,0.50);
}
.edit-link svg { width: 13px; height: 13px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

/* empty state */
.empty-state {
    text-align: center;
    padding: 56px 20px;
    color: var(--text-3);
}
.empty-state svg { width: 48px; height: 48px; margin-bottom: 16px; stroke: var(--text-3); fill: none; stroke-width: 1.2; stroke-linecap: round; stroke-linejoin: round; }
.empty-state p { font-size: 14px; }

/* result count */
.result-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    font-size: 12.5px;
    color: var(--text-2);
}
.result-meta strong { color: var(--text-1); }

/* ── Responsive ───────────────────────────────────────────────────────────── */

/* Tablet – 768px */
@media (max-width: 768px) {
    body {
        padding-left: 16px;
        padding-right: 16px;
        padding-top: 32px;
    }
    .search-card {
        padding: 24px 20px 32px;
    }
    thead th, tbody td {
        padding: 10px 12px;
    }
    .badge-status {
        padding: 4px 10px;
        font-size: 11px;
    }
}

/* Mobile – 560px */
@media (max-width: 560px) {
    body {
        padding-left: 12px;
        padding-right: 12px;
        padding-top: 24px;
    }
    .search-card {
        padding: 20px 14px 28px;
        border-radius: 10px;
    }

    /* Stack search bar: input on top, button below */
    .search-row {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        margin-bottom: 20px;
    }
    .search-btn {
        justify-content: center;
        width: 100%;
        padding: 12px;
    }

    /* Horizontally scrollable table with a subtle scroll hint */
    .tbl-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 8px;
        /* faint right-edge fade to hint at scroll */
        mask-image: linear-gradient(to right, black 85%, transparent 100%);
        -webkit-mask-image: linear-gradient(to right, black 85%, transparent 100%);
    }
    /* Remove mask once scrolled near the end via JS — keep it simple, just always show */
    table {
        min-width: 640px;  /* prevent content crushing */
        font-size: 12.5px;
    }
    thead th {
        font-size: 10px;
        padding: 10px 10px;
        letter-spacing: 0.05em;
    }
    tbody td {
        padding: 11px 10px;
        font-size: 12.5px;
    }

    /* Result meta: stack on two lines */
    .result-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
        margin-bottom: 10px;
    }

    /* Nav pills wrap neatly */
    .nav {
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 24px;
    }

    /* Header */
    .page-header { margin-bottom: 24px; }
}
</style>
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