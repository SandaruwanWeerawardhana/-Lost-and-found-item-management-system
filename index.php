<?php

$errors  = [];
$success = false;
$old     = [];  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitise every input
    $fields = ['item_number','item_name','category','location','date_found','reported_by','contact','status'];
    foreach ($fields as $f) {
        $old[$f] = trim($_POST[$f] ?? '');
    }

    // ── Required-field validation ──────────────────────────────────────────────
    if ($old['item_number'] === '')  $errors['item_number']  = 'Item number is required.';
    if ($old['item_name'] === '')    $errors['item_name']    = 'Item name is required.';
    if ($old['category'] === '')     $errors['category']     = 'Please select a category.';
    if ($old['location'] === '')     $errors['location']     = 'Location found is required.';
    if ($old['date_found'] === '')   $errors['date_found']   = 'Date found is required.';
    if ($old['reported_by'] === '')  $errors['reported_by']  = 'Reported by is required.';
    if ($old['status'] === '')       $errors['status']       = 'Please select a status.';

    // Format validation 
    if (empty($errors['item_number']) && !preg_match('/^[A-Za-z0-9\-]+$/', $old['item_number'])) {
        $errors['item_number'] = 'Item number may only contain letters, numbers, and hyphens.';
    }
    if (empty($errors['contact']) && $old['contact'] !== '' && !preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $old['contact'])) {
        $errors['contact'] = 'Enter a valid contact number.';
    }
    if (empty($errors['date_found']) && $old['date_found'] !== '') {
        $dt = DateTime::createFromFormat('Y-m-d', $old['date_found']);
        if (!$dt || $dt > new DateTime()) {
            $errors['date_found'] = 'Date found cannot be in the future.';
        }
    }

    // Insert if no errors 
    if (empty($errors)) {
        require 'includes/db_connect.php';

        $stmt = $pdo->prepare("
            INSERT INTO lost_items
                (item_number, item_name, category, location, date_found, reported_by, contact, status)
            VALUES
                (:item_number, :item_name, :category, :location, :date_found, :reported_by, :contact, :status)
        ");

        try {
            $stmt->execute([
                ':item_number' => $old['item_number'],
                ':item_name'   => $old['item_name'],
                ':category'    => $old['category'],
                ':location'    => $old['location'],
                ':date_found'  => $old['date_found'],
                ':reported_by' => $old['reported_by'],
                ':contact'     => $old['contact'],
                ':status'      => $old['status'],
            ]);
            $success = true;
            $old     = [];   // clear form on success
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors['item_number'] = 'This item number already exists in the database.';
            } else {
                $errors['_db'] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Helper: repopulate old values safely

function old(string $key, string $default = ''): string {
    global $old;
    return htmlspecialchars($old[$key] ?? $default, ENT_QUOTES);
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
<title>Report Lost Item — Lost &amp; Found</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Page header -->
<header class="page-header">
    <h1>Report a Lost Item</h1>
</header>


<!-- Navigation -->
<nav class="nav">
    <a href="add_item.php" class="active">Report Item</a>
    <a href="search.php">Search</a>
</nav>

<!-- Form progress bar (JS-driven) -->
<div class="progress-strip" id="progress-strip">
    <div class="progress-fill" id="progress-fill"></div>
</div>

<!-- Main card -->
<div class="card">

    <?php if ($success): ?>
    <div class="alert alert-ok">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div>
            <strong>Item registered successfully!</strong>
            <p>The record has been saved. You can search for it or report another item.</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>Please fix the <?= count($errors) ?> error<?= count($errors) > 1 ? 's' : '' ?> below before submitting.</div>
    </div>
    <?php endif; ?>

    <?php if (isset($errors['_db'])): ?>
    <div class="alert alert-err">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div><?= htmlspecialchars($errors['_db']) ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" action="add_item.php" id="lost-form" novalidate>

        <!-- Item Identity -->
        <div class="grid-2">
            <div class="field<?= hasErr('item_number') ?>">
                <label for="item_number">Item number <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h6"/></svg></span>
                    <input type="text" id="item_number" name="item_number" placeholder="e.g. LF-2024-001" value="<?= old('item_number') ?>" maxlength="20" autocomplete="off">
                </div>
                <?= err('item_number') ?>
            </div>

            <div class="field<?= hasErr('item_name') ?>">
                <label for="item_name">Item name <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 3H8l-2 4h12l-2-4z"/></svg></span>
                    <input type="text" id="item_name" name="item_name" placeholder="e.g. Black leather wallet" value="<?= old('item_name') ?>" maxlength="100">
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
                            $sel = old('category') === $c ? ' selected' : '';
                            echo "<option value=\"{$c}\"{$sel}>{$c}</option>\n";
                        }
                        ?>
                    </select>
                </div>
                <?= err('category') ?>
            </div>
        </div>

        <!-- ── Found Details -->

        <div class="grid-2">
            <div class="field col-span-2<?= hasErr('location') ?>">
                <label for="location">Location found <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                    <input type="text" id="location" name="location" placeholder="e.g. Main library, 2nd floor reading room" value="<?= old('location') ?>" maxlength="150">
                </div>
                <?= err('location') ?>
            </div>

            <div class="field<?= hasErr('date_found') ?>">
                <label for="date_found">Date found <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></span>
                    <input type="date" id="date_found" name="date_found" value="<?= old('date_found') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <?= err('date_found') ?>
            </div>
        </div>

        <!-- ── Reporter Info  -->
   
        <div class="grid-2">
            <div class="field<?= hasErr('reported_by') ?>">
                <label for="reported_by">Full name <span class="required">*</span></label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
                    <input type="text" id="reported_by" name="reported_by" placeholder="e.g. Kasun Perera" value="<?= old('reported_by') ?>" maxlength="100">
                </div>
                <?= err('reported_by') ?>
            </div>

            <div class="field<?= hasErr('contact') ?>">
                <label for="contact">Contact number</label>
                <div class="input-wrap">
                    <span class="ico"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.42 2 2 0 0 1 3.6 1.24h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.82a16 16 0 0 0 6.16 6.16l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7a2 2 0 0 1 1.72 2.04z"/></svg></span>
                    <input type="text" id="contact" name="contact" placeholder="e.g. 077 123 4567" value="<?= old('contact') ?>" maxlength="20">
                </div>
                <?= err('contact') ?>
            </div>
        </div>

        <!-- ── Status ─────────────────────────────────────────────────────── -->
        <div class="section-label">
            <div class="section-icon">
                <svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="7"/><path d="M8 5v3l2 2" stroke="#6c9fff" stroke-width="1.2" stroke-linecap="round" fill="none"/></svg>
            </div>
            Status <span class="required" style="color:var(--accent);margin-left:4px;">*</span>
        </div>

        <div class="field<?= hasErr('status') ?>">
            <div class="radio-group">
                <label>
                    <input type="radio" name="status" value="Unclaimed" <?= old('status','Unclaimed') === 'Unclaimed' ? 'checked' : '' ?>>
                    <span class="dot dot-yellow"></span> Unclaimed
                </label>
                <label>
                    <input type="radio" name="status" value="Claimed" <?= old('status') === 'Claimed' ? 'checked' : '' ?>>
                    <span class="dot dot-green"></span> Claimed
                </label>
                <label>
                    <input type="radio" name="status" value="Under Review" <?= old('status') === 'Under Review' ? 'checked' : '' ?>>
                    <span class="dot dot-blue"></span> Under Review
                </label>
            </div>
            <?= err('status') ?>
        </div>

        <!-- ── Submit ─────────────────────────────────────────────────────── -->
        <div class="btn-wrap">
            <p class="hint"><span>*</span> Required fields</p>
            <button type="submit">
                <svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Register Item
            </button>
        </div>

    </form>
</div>

<script>
// ── Live progress bar based on filled fields ──────────────────────────────────
(function () {
    const form    = document.getElementById('lost-form');
    const fill    = document.getElementById('progress-fill');
    const fields  = form.querySelectorAll('input:not([type="radio"]), select');
    const radios  = form.querySelectorAll('input[type="radio"]');

    function calcProgress() {
        let filled = 0, total = fields.length + 1; // +1 for radio group
        fields.forEach(f => { if (f.value.trim() !== '') filled++; });
        const anyRadio = [...radios].some(r => r.checked);
        if (anyRadio) filled++;
        fill.style.width = Math.round((filled / total) * 100) + '%';
    }

    form.addEventListener('input', calcProgress);
    form.addEventListener('change', calcProgress);
    calcProgress();   // init on page load (repopulate case)
})();
</script>
</body>
</html>