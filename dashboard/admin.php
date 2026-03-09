<?php
include '../includes/auth.php';
include '../config/db.php';
include '../lib/db_tools.php';

if ($_SESSION['role'] !== 'admin') {
    die('Access denied.');
}

$is_refresh = isset($_GET['refresh']);

function admin_url($params = [])
{
    $query = array_merge($_GET, $params);
    unset($query['refresh']);

    foreach ($query as $key => $value) {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            unset($query[$key]);
        }
    }

    return 'admin.php' . (count($query) ? ('?' . http_build_query($query)) : '');
}

$msg_ok = [];
$msg_err = [];
$edit_row = null;

$post_action = $_POST['action'] ?? '';

if ($post_action === 'create' || $post_action === 'update') {
    $storm_lvl = isset($_POST['storm_lvl']) ? (int) $_POST['storm_lvl'] : 0;
    $storm_desc = isset($_POST['storm_desc']) ? trim($_POST['storm_desc']) : '';

    if ($storm_lvl < 1 || $storm_lvl > 10) {
        $msg_err[] = 'Storm intensity must be between 1 and 10.';
    } else {
        if ($post_action === 'create') {
            $storm_stmt = db_run_stmt(
                $conn,
                'INSERT INTO solar_storms (intensity, description) VALUES (?, ?)',
                'is',
                [$storm_lvl, $storm_desc]
            );

            if (!$storm_stmt) {
                $msg_err[] = 'Failed to create storm record.';
            } else {
                $storm_stmt->close();
                $msg_ok[] = 'Storm record created.';

                $storm_id = (int) $conn->insert_id;

                // Keep linked telemetry generation deterministic from storm intensity.
                $rad_lvl = $storm_lvl * 12.5;

                if ($rad_lvl < 50) {
                    $rad_status = 'safe';
                } elseif ($rad_lvl <= 90) {
                    $rad_status = 'warning';
                } else {
                    $rad_status = 'danger';
                    db_insert_event_cooldown_storm(
                        $conn,
                        $storm_id,
                        'Emergency Shelter Activated',
                        'Radiation exceeded safe threshold.',
                        5
                    );
                }

                $rad_stmt = db_run_stmt(
                    $conn,
                    'INSERT INTO radiation_logs (storm_id, radiation_level, status) VALUES (?, ?, ?)',
                    'ids',
                    [$storm_id, $rad_lvl, $rad_status]
                );

                if ($rad_stmt) {
                    $rad_stmt->close();
                }

                $solar_out = 100 - $storm_lvl * 8;
                $battery_lvl = 100 - $storm_lvl * 10;
                $pwr_mode = $solar_out < 40 ? 'critical' : 'normal';

                $pwr_stmt = db_run_stmt(
                    $conn,
                    'INSERT INTO power_logs (storm_id, solar_output, battery_level, mode) VALUES (?, ?, ?, ?)',
                    'idds',
                    [$storm_id, $solar_out, $battery_lvl, $pwr_mode]
                );

                if ($pwr_stmt) {
                    $pwr_stmt->close();
                }
            }
        }

        if ($post_action === 'update') {
            $storm_id = isset($_POST['storm_id']) ? (int) $_POST['storm_id'] : 0;
            if ($storm_id <= 0) {
                $msg_err[] = 'Invalid storm id for update.';
            } else {
                $update_stmt = db_run_stmt(
                    $conn,
                    'UPDATE solar_storms SET intensity = ?, description = ? WHERE id = ? LIMIT 1',
                    'isi',
                    [$storm_lvl, $storm_desc, $storm_id]
                );

                if (!$update_stmt) {
                    $msg_err[] = 'Failed to update storm record.';
                } else {
                    $affected = $update_stmt->affected_rows;
                    $update_stmt->close();
                    if ($affected > 0) {
                        $msg_ok[] = 'Storm record updated.';
                    } else {
                        $msg_err[] = 'No storm record was updated.';
                    }
                }
            }
        }
    }
}

if ($post_action === 'delete') {
    $storm_id = isset($_POST['storm_id']) ? (int) $_POST['storm_id'] : 0;
    if ($storm_id <= 0) {
        $msg_err[] = 'Invalid storm id for delete.';
    } else {
        $delete_stmt = db_run_stmt(
            $conn,
            'DELETE FROM solar_storms WHERE id = ? LIMIT 1',
            'i',
            [$storm_id]
        );

        if (!$delete_stmt) {
            $msg_err[] = 'Failed to delete storm record.';
        } else {
            $affected = $delete_stmt->affected_rows;
            $delete_stmt->close();
            if ($affected > 0) {
                $msg_ok[] = 'Storm record deleted.';
            } else {
                $msg_err[] = 'No storm record was deleted.';
            }
        }
    }
}

$edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
if ($edit_id > 0) {
    // Preload one record for in-place update mode.
    $edit_row = db_fetch_one(
        $conn,
        'SELECT id, intensity, description FROM solar_storms WHERE id = ? LIMIT 1',
        'i',
        [$edit_id]
    );

    if (!$edit_row) {
        $msg_err[] = 'Storm record for edit not found.';
    }
}

$filter_lvl = isset($_GET['filter_lvl']) ? (int) $_GET['filter_lvl'] : 0;
if ($filter_lvl < 1 || $filter_lvl > 10) {
    $filter_lvl = 0;
}

$search_text = isset($_GET['search']) ? trim($_GET['search']) : '';
if (strlen($search_text) > 80) {
    $search_text = substr($search_text, 0, 80);
}

$where_parts = [];
$query_types = '';
$query_params = [];

if ($filter_lvl > 0) {
    $where_parts[] = 'intensity = ?';
    $query_types .= 'i';
    $query_params[] = $filter_lvl;
}

if ($search_text !== '') {
    $where_parts[] = 'description LIKE ?';
    $query_types .= 's';
    $query_params[] = '%' . $search_text . '%';
}

$where_sql = '';
if (count($where_parts) > 0) {
    $where_sql = ' WHERE ' . implode(' AND ', $where_parts);
}

$per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

// Resolve total rows for reliable pager bounds.
$total_rows_val = db_fetch_value(
    $conn,
    'SELECT COUNT(*) AS total_rows FROM solar_storms' . $where_sql,
    'total_rows',
    $query_types,
    $query_params
);
$total_rows = $total_rows_val !== null ? (int) $total_rows_val : 0;

$total_pages = (int) ceil($total_rows / $per_page);
if ($total_pages < 1) {
    $total_pages = 1;
}

if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $per_page;

$row_sql = 'SELECT * FROM solar_storms' . $where_sql . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
$row_types = $query_types . 'ii';
$row_params = array_merge($query_params, [$per_page, $offset]);
$storm_rows = db_fetch_all($conn, $row_sql, $row_types, $row_params);
?>

<?php if (!$is_refresh): ?>
<?php include '../includes/header.php';?>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <h1>Admin Dashboard</h1>
    <section class="card status_strip">
        <div class="strip_row">
            <span>Mission: <strong class="status_safe">Active</strong></span>
            <span>Network: <strong class="status_safe">Synced</strong></span>
            <span>Role: <strong>Admin</strong></span>
            <span id="refresh_note_admin">Refresh: waiting</span>
        </div>
    </section>
    <div id="dashboard_content">
<?php endif; ?>

    <!-- control modules -->
    <section class="dash_panel panel_grid">
    <article class="card mod_admin_form">
    <h2 class="card_head">Storm Management</h2>
    <div class="card_body">
    <form method="POST" class="admin_form">
        <input type="hidden" name="action" value="<?php echo $edit_row ? 'update' : 'create'; ?>">
        <?php if ($edit_row): ?>
            <input type="hidden" name="storm_id" value="<?php echo (int) $edit_row['id']; ?>">
        <?php endif; ?>

        <label for="storm_lvl">Storm intensity (1-10)</label>
        <input id="storm_lvl" type="number" name="storm_lvl" min="1" max="10" required value="<?php echo $edit_row ? (int) $edit_row['intensity'] : ''; ?>">

        <label for="storm_desc">Storm description</label>
        <textarea id="storm_desc" name="storm_desc" placeholder="Describe storm condition"><?php echo $edit_row ? htmlspecialchars($edit_row['description']) : ''; ?></textarea>

        <div class="btn_row">
            <input type="submit" value="<?php echo $edit_row ? 'Update Storm' : 'Create Storm'; ?>">
            <?php if ($edit_row): ?>
                <a class="btn_link" href="admin.php">Cancel Edit</a>
            <?php endif; ?>
        </div>
    </form>
    </div>
    </article>

    <article class="card mod_admin_logs">
    <h2 class="card_head">Storm Log</h2>
    <div class="card_body">

    <form method="GET" class="filter_form">
        <label for="filter_lvl">Storm intensity</label>
        <select id="filter_lvl" name="filter_lvl">
            <option value="">All</option>
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo $filter_lvl === $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
            <?php endfor; ?>
        </select>

        <label for="search">Description search</label>
        <input id="search" type="text" name="search" value="<?php echo htmlspecialchars($search_text); ?>" placeholder="Description contains">

        <button type="submit">Apply</button>
        <a class="btn_link" href="admin.php">Clear</a>
    </form>

<?php
    foreach ($msg_ok as $msg) {
        echo "<p class='success'>" . htmlspecialchars($msg) . "</p>";
    }
    foreach ($msg_err as $msg) {
        echo "<p class='error'>" . htmlspecialchars($msg) . "</p>";
    }

    if (count($storm_rows) > 0) {
        echo"<table>";
        echo "<tr><th>ID</th>
        <th>Intensity</th>
        <th>Description</th>
        <th>Timestamp</th>
        <th>Actions</th></tr>";
        foreach ($storm_rows as $row) {
            $row_id = (int) $row['id'];
            $edit_link = admin_url(['edit_id' => $row_id, 'page' => $page]);

            echo "<tr>
            <td>".$row_id."</td>
            <td>".(int) $row['intensity']."</td>
            <td>".htmlspecialchars($row['description'])."</td>
            <td>".htmlspecialchars($row['created_at'])."</td>
            <td>
                <div class='table_action'>
                    <a class='btn_link small' href='".htmlspecialchars($edit_link)."'>Edit</a>
                    <form method='POST' class='inline_form'>
                        <input type='hidden' name='action' value='delete'>
                        <input type='hidden' name='storm_id' value='".$row_id."'>
                        <button type='submit' class='btn_link small danger'>Delete</button>
                    </form>
                </div>
            </td>
            </tr>";
        }
        echo"</table>";

        echo "<div class='pager'>";
        echo "<span>Page " . $page . " of " . $total_pages . "</span>";

        if ($page > 1) {
            $prev_url = admin_url(['page' => $page - 1]);
            echo "<a class='btn_link small' href='" . htmlspecialchars($prev_url) . "'>Previous</a>";
        }

        if ($page < $total_pages) {
            $next_url = admin_url(['page' => $page + 1]);
            echo "<a class='btn_link small' href='" . htmlspecialchars($next_url) . "'>Next</a>";
        }

        echo "</div>";
    }
    else
        echo"<p>No storm data found.<br>Log some data to see it.</p>";
?>
</div>
</article>
</section>

<?php if (!$is_refresh): ?>
    </div>
    <script src="../assets/js/auto_refresh.js"></script>
    <script src="../assets/js/admin.js"></script>
<?php include '../includes/footer.php';?>
<?php endif; ?>
