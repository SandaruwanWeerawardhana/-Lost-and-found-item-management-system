<?php
require_once 'db_connect.php';

// ── 1. Fetch the record by ID from the URL ────────────────────────────────────
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: search.php');
    exit;
}

$item = $pdo->prepare("SELECT * FROM lost_items WHERE id = :id LIMIT 1");
$item->execute([':id' => $id]);
$record = $item->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    header('Location: search.php');
    exit;
}

// ── 2. Handle POST (update) ───────────────────────────────────────────────────
$errors  = [];
$success = false;
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fields = ['item_name','category','location_found','date_found','reported_by','contact_number','status'];
    foreach ($fields as $f) {
        $old[$f] = trim($_POST[$f] ?? '');
    }

    // Required-field validation
    if ($old['item_name'] === '')       $errors['item_name']      = 'Item name is required.';
    if ($old['category'] === '')        $errors['category']       = 'Please select a category.';
    if ($old['location_found'] === '')  $errors['location_found'] = 'Location found is required.';
    if ($old['date_found'] === '')      $errors['date_found']     = 'Date found is required.';
    if ($old['reported_by'] === '')     $errors['reported_by']    = 'Reported by is required.';
    if ($old['status'] === '')          $errors['status']         = 'Please select a status.';

    // Format validation
    if (empty($errors['contact_number']) && $old['contact_number'] !== ''
        && !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $old['contact_number'])) {
        $errors['contact_number'] = 'Enter a valid contact number.';
    }
    if (empty($errors['date_found']) && $old['date_found'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $old['date_found']);
        if (!$dt || $dt > new DateTime()) {
            $errors['date_found'] = 'Date found cannot be in the future.';
        }
    }

    // UPDATE if no errors
    if (empty($errors)) {
        try {
            $upd = $pdo->prepare("
                UPDATE lost_items SET
                    item_name      = :item_name,
                    category       = :category,
                    location_found = :location_found,
                    date_found     = :date_found,
                    reported_by    = :reported_by,
                    contact_number = :contact_number,
                    status         = :status
                WHERE id = :id
            ");
            $upd->execute([
                ':item_name'      => $old['item_name'],
                ':category'       => $old['category'],
                ':location_found' => $old['location_found'],
                ':date_found'     => $old['date_found'],
                ':reported_by'    => $old['reported_by'],
                ':contact_number' => $old['contact_number'] !== '' ? $old['contact_number'] : null,
                ':status'         => $old['status'],
                ':id'             => $id,
            ]);

            // PRG: redirect to search with success flag
            $base = rtrim(dirname($_SERVER['PHP_SELF']), '/');
            header('Location: ' . $base . '/search.php?updated=1');
            exit;

        } catch (PDOException $e) {
            $errors['_db'] = 'Database error: ' . $e->getMessage();
        }
    }

    // Keep form values from POST on error
    $display = $old;
} else {
    // First load – use values from database
    $display = $record;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function val(string $key, array $display, string $default = ''): string {
    return htmlspecialchars($display[$key] ?? $default, ENT_QUOTES);
}
function err(string $key): string {
    global $errors;
    return isset($errors[$key])
        ? '<span class="err-msg"><svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><circle cx="8" cy="8" r="7"/><path d="M8 5v3.5M8 10.5v.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" fill="none"/></svg>' . htmlspecialchars($errors[$key]) . '</span>'
        : '';
}
function hasErr(string $key): string {
    global $errors;
    return isset($errors[$key]) ? ' has-error' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Item — Lost &amp; Found</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Page header -->
<header class="page-header">
    <h1>Edit Item</h1>
</header>

<!-- Navigation -->
<nav class="nav">
    <a href="index.php">Report Item</a>
    <a href="search.php" class="active">Search</a>
</nav>

<!-- Form progress bar -->
<div class="progress-strip" id="progress-strip">
    <div class="progress-fill" id="progress-fill"></div>
</div>

<!-- Main card -->
<div class="card">

    <?php if (!empty($errors)): ?>
    <div class="alert alert-err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>Please fix the <?= count($errors) ?> error<?= count($errors) > 1 ? 's' : '' ?> below before saving.</div>
    </div>
    <?php endif; ?>

    <?php if (isset($errors['_db'])): ?>
    <div class="alert alert-err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div><?= htmlspecialchars($errors['_db']) ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= (int)$id ?>" id="edit-form" novalidate>
        <input type="hidden" name="id" value="<?= (int)$id ?>">

        <!-- Item Identity -->
        <div class="grid-2">

            <div class="field<?= hasErr('item_name') ?>">
                <label for="item_name">Item name <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 3H8l-2 4h12l-2-4z"/></svg></span>
                    <input type="text" id="item_name" name="item_name"
                           placeholder="e.g. Black leather wallet"
                           value="<?= val('item_name', $display) ?>" maxlength="150">
                </div>
                <?= err('item_name') ?>
            </div>

            <div class="field<?= hasErr('category') ?>">
                <label for="category">Category <span class="required">*</span></label>
                <div class="input-wrap is-select">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 10h16M4 14h10"/></svg></span>
                    <select id="category" name="category">
                        <option value="">Select a category</option>
                        <?php
                        $cats = ['Electronics','Clothing','Accessories','Documents','Bags','Jewellery','Books / Stationery','Keys','Sports Equipment','Other'];
                        foreach ($cats as $c) {
                            $sel = ($display['category'] === $c) ? ' selected' : '';
                            echo "<option value=\"{$c}\"{$sel}>{$c}</option>\n";
                        }
                        ?>
                    </select>
                </div>
                <?= err('category') ?>
            </div>

        </div>

        <!-- Found Details -->
        <div class="grid-2">

            <div class="field col-span-2<?= hasErr('location_found') ?>">
                <label for="location_found">Location found <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                    <input type="text" id="location_found" name="location_found"
                           placeholder="e.g. Main library, 2nd floor"
                           value="<?= val('location_found', $display) ?>" maxlength="200">
                </div>
                <?= err('location_found') ?>
            </div>

            <div class="field<?= hasErr('date_found') ?>">
                <label for="date_found">Date found <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
                    <input type="date" id="date_found" name="date_found"
                           value="<?= val('date_found', $display) ?>"
                           max="<?= date('Y-m-d') ?>">
                </div>
                <?= err('date_found') ?>
            </div>

        </div>

        <!-- Reporter Info -->
        <div class="grid-2">

            <div class="field<?= hasErr('reported_by') ?>">
                <label for="reported_by">Full name <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <input type="text" id="reported_by" name="reported_by"
                           placeholder="e.g. Kasun Perera"
                           value="<?= val('reported_by', $display) ?>" maxlength="100">
                </div>
                <?= err('reported_by') ?>
            </div>

            <div class="field<?= hasErr('contact_number') ?>">
                <label for="contact_number">Contact number</label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.42 2 2 0 0 1 3.6 1.24h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.82a16 16 0 0 0 6.16 6.16l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7a2 2 0 0 1 1.72 2.04z"/></svg></span>
                    <input type="text" id="contact_number" name="contact_number"
                           placeholder="e.g. 077 123 4567"
                           value="<?= val('contact_number', $display) ?>" maxlength="16">
                </div>
                <?= err('contact_number') ?>
            </div>

        </div>

        <!-- Status -->
        <div class="section-label">
            <div class="section-icon">
                <svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="7"/><path d="M8 5v3l2 2" stroke="#6c9fff" stroke-width="1.2" stroke-linecap="round" fill="none"/></svg>
            </div>
            Status <span class="required" style="color:var(--accent);margin-left:4px;">*</span>
        </div>

        <div class="field<?= hasErr('status') ?>">
            <div class="radio-group">
                <?php foreach (['Unclaimed','Claimed','Under Review'] as $sv):
                    $checked = ($display['status'] === $sv) ? 'checked' : '';
                    $dotClass = match($sv) { 'Unclaimed' => 'dot-yellow', 'Claimed' => 'dot-green', default => 'dot-blue' };
                ?>
                <label>
                    <input type="radio" name="status" value="<?= $sv ?>" <?= $checked ?>>
                    <span class="dot <?= $dotClass ?>"></span> <?= $sv ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?= err('status') ?>
        </div>

        <!-- Actions -->
        <div class="btn-wrap">
            <a href="search.php" style="color:var(--text-2);font-size:13px;text-decoration:none;">
                ← Back to search
            </a>
            <button type="submit">
                <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Changes
            </button>
        </div>

    </form>
</div>

<script>
(function () {
    const form   = document.getElementById('edit-form');
    const fill   = document.getElementById('progress-fill');
    const fields = form.querySelectorAll('input:not([type="radio"]):not([type="hidden"]), select');
    const radios = form.querySelectorAll('input[type="radio"]');

    function calcProgress() {
        let filled = 0, total = fields.length + 1;
        fields.forEach(f => { if (f.value.trim() !== '') filled++; });
        if ([...radios].some(r => r.checked)) filled++;
        fill.style.width = Math.round((filled / total) * 100) + '%';
    }

    form.addEventListener('input', calcProgress);
    form.addEventListener('change', calcProgress);
    calcProgress();
})();
</script>
</body>
</html>