<?php
include '../includes/auth.php';
include '../config/db.php';
include '../lib/db_tools.php';

if ($_SESSION['role'] !== 'user') {
    die('Access denied.');
}

$is_refresh = isset($_GET['refresh']);

$storm_row = db_fetch_one($conn, "SELECT * FROM solar_storms ORDER BY created_at DESC LIMIT 1");
$rad_row = db_fetch_one($conn, "SELECT * FROM radiation_logs ORDER BY created_at DESC LIMIT 1");
$pwr_row = db_fetch_one($conn, "SELECT * FROM power_logs ORDER BY created_at DESC LIMIT 1");

$avg_rad = null;
$avg_battery = null;
$events_24h = 0;

$avg_rad_val = db_fetch_value(
    $conn,
    "SELECT AVG(radiation_level) AS avg_radiation FROM radiation_logs WHERE created_at >= NOW() - INTERVAL 1 DAY",
    'avg_radiation'
);
if ($avg_rad_val !== null) {
    $avg_rad = round((float) $avg_rad_val, 2);
}

$avg_battery_val = db_fetch_value(
    $conn,
    "SELECT AVG(battery_level) AS avg_battery FROM power_logs WHERE created_at >= NOW() - INTERVAL 1 DAY",
    'avg_battery'
);
if ($avg_battery_val !== null) {
    $avg_battery = round((float) $avg_battery_val, 2);
}

$events_24h_val = db_fetch_value(
    $conn,
    "SELECT COUNT(*) AS total_events FROM events WHERE created_at >= NOW() - INTERVAL 1 DAY",
    'total_events'
);
if ($events_24h_val !== null) {
    $events_24h = (int) $events_24h_val;
}

$event_log = db_fetch_all(
    $conn,
    "SELECT event_type, notes, created_at FROM events ORDER BY created_at DESC LIMIT 8"
);

function status_cls($value)
{
    if ($value === 'danger' || $value === 'critical') {
        return 'status_danger';
    }

    if ($value === 'warning') {
        return 'status_warn';
    }

    return 'status_safe';
}
?>
<?php if (!$is_refresh): ?>

<?php include '../includes/header.php'; ?>
    <link rel="stylesheet" href="../assets/css/user.css">
    <h1>User Dashboard</h1>
    <section class="card status_strip">
        <div class="strip_row">
            <span>Mission: <strong class="status_safe">Active</strong></span>
            <span>Network: <strong class="status_safe">Synced</strong></span>
            <span>Role: <strong>User</strong></span>
            <span id="refresh_note">Refresh: waiting</span>
        </div>
    </section>
    <div id="dashboard_content">
<?php endif; ?>

<!-- telemetry modules -->
<section class="dash_panel panel_grid">
    <article class="card mod_storm">
        <h3 class="card_head">Storm Monitor</h3>
        <div class="card_body">
            <?php if ($storm_row): ?>
                <ul>
                    <li>Intensity: <span id="user_storm_intensity"><?php echo $storm_row['intensity']; ?></span></li>
                    <li>Description: <span id="user_storm_description"><?php echo htmlspecialchars($storm_row['description']); ?></span></li>
                    <li>Last update: <span id="user_storm_time"><?php echo $storm_row['created_at']; ?></span></li>
                </ul>
            <?php else: ?>
                <p>No storm data found.</p>
            <?php endif; ?>
            <div class="chart_wrap">
                <canvas id="chart_user_storm"></canvas>
            </div>
        </div>
    </article>

    <article class="card mod_rad">
        <h3 class="card_head">Radiation Monitor</h3>
        <div class="card_body">
            <?php if ($rad_row): ?>
                <ul>
                    <li>Radiation level: <span id="user_rad_level"><?php echo $rad_row['radiation_level']; ?></span></li>
                    <li>Status: <span id="user_rad_status"><?php echo htmlspecialchars($rad_row['status']); ?></span></li>
                    <li>Last update: <span id="user_rad_time"><?php echo $rad_row['created_at']; ?></span></li>
                </ul>
                <p id="user_rad_note" class="<?php echo status_cls($rad_row['status']); ?>">
                    <?php
                    if ($rad_row['status'] === 'danger') {
                        echo 'Radiation levels are dangerous.';
                    } elseif ($rad_row['status'] === 'warning') {
                        echo 'Radiation levels are elevated.';
                    } else {
                        echo 'Radiation levels are within safe operational limits.';
                    }
                    ?>
                </p>
            <?php else: ?>
                <p>No radiation data yet.</p>
            <?php endif; ?>
            <div class="chart_wrap">
                <canvas id="chart_user_radiation"></canvas>
            </div>
        </div>
    </article>

    <article class="card mod_pwr">
        <h3 class="card_head">Power Monitor</h3>
        <div class="card_body">
            <div class="chart_wrap">
                <canvas id="chart_user_power"></canvas>
            </div>

            <?php if ($pwr_row): ?>
                <ul>
                    <li>Solar output: <span id="user_power_solar"><?php echo $pwr_row['solar_output']; ?></span></li>
                    <li>Battery level: <span id="user_power_battery"><?php echo $pwr_row['battery_level']; ?></span></li>
                    <li>Power mode: <span id="user_power_mode"><?php echo htmlspecialchars($pwr_row['mode']); ?></span></li>
                    <li>Last update: <span id="user_power_time"><?php echo $pwr_row['created_at']; ?></span></li>
                </ul>
                <p id="user_power_note" class="<?php echo status_cls($pwr_row['mode']); ?>">
                    <?php echo $pwr_row['mode'] === 'normal' ? 'Power systems are operating normally.' : 'Power systems are in critical mode.'; ?>
                </p>
            <?php else: ?>
                <p>No power data yet.</p>
            <?php endif; ?>
        </div>
    </article>

    <article class="card mod_stats">
        <h3 class="card_head">System Health and Analytics</h3>
        <div class="card_body stats_grid">
            <div class="stats_item health_item">
                <p class="stats_label">System Health</p>
                <div class="chart_wrap chart_doughnut">
                    <canvas id="chart_user_health"></canvas>
                </div>
            </div>
            <div class="stats_item">
                <p class="stats_label">Average Radiation</p>
                <p id="user_avg_rad" class="stats_value"><?php echo $avg_rad !== null ? number_format($avg_rad, 2) : 'N/A'; ?></p>
            </div>
            <div class="stats_item">
                <p class="stats_label">Average Battery</p>
                <p id="user_avg_battery" class="stats_value"><?php echo $avg_battery !== null ? number_format($avg_battery, 2) . '%' : 'N/A'; ?></p>
            </div>
            <div class="stats_item">
                <p class="stats_label">Events Logged</p>
                <p id="user_events_24h" class="stats_value"><?php echo $events_24h; ?></p>
            </div>
        </div>
    </article>

    <article class="card mod_events">
        <h3 class="card_head">Event Log</h3>
        <div class="card_body">
            <?php if (count($event_log) > 0): ?>
                <table class="events_table">
                    <tr>
                        <th>Event type</th>
                        <th>Notes</th>
                        <th>Time</th>
                    </tr>
                    <?php foreach ($event_log as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                            <td><?php echo htmlspecialchars($event['notes']); ?></td>
                            <td><?php echo $event['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No events logged yet.</p>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php if (!$is_refresh): ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/user_charts.js"></script>
    <script src="../assets/js/auto_refresh.js"></script>
    <script src="../assets/js/user.js"></script>
<?php include '../includes/footer.php'; ?>
<?php endif; ?>