<?php
include '../includes/auth.php';
include '../config/db.php';
include '../lib/db_tools.php';

if ($_SESSION['role'] !== 'astronaut') {
    die('Access denied.');
}

$is_refresh = isset($_GET['refresh']);

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

function health_color($health)
{
    if ($health >= 80) {
        return 'green';
    }

    if ($health >= 50) {
        return 'orange';
    }

    return 'red';
}

$latest_storm = db_fetch_one($conn, "SELECT * FROM solar_storms ORDER BY created_at DESC LIMIT 1");
$latest_radiation = db_fetch_one($conn, "SELECT * FROM radiation_logs ORDER BY created_at DESC LIMIT 1");

$power_latest_rows = db_fetch_all(
    $conn,
    "SELECT p.*, s.intensity FROM power_logs p LEFT JOIN solar_storms s ON p.storm_id = s.id ORDER BY p.created_at DESC LIMIT 2"
);
$latest_power = $power_latest_rows[0] ?? null;
$prev_power = $power_latest_rows[1] ?? null;

$power_rows = db_fetch_all(
    $conn,
    "SELECT p.*, s.intensity FROM power_logs p LEFT JOIN solar_storms s ON p.storm_id = s.id ORDER BY p.created_at DESC LIMIT 5"
);

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

$health = 100;
if ($latest_radiation) {
    if ($latest_radiation['status'] === 'danger') {
        $health -= 30;
    } elseif ($latest_radiation['status'] === 'warning') {
        $health -= 15;
    }
}

if ($latest_power) {
    if ($latest_power['mode'] === 'critical') {
        $health -= 25;
    }

    if ((float) $latest_power['battery_level'] < 40) {
        $health -= 15;
    }

    if ((float) $latest_power['battery_level'] < 20) {
        $health -= 10;
    }

    if (isset($latest_power['intensity']) && (int) $latest_power['intensity'] > 7) {
        $health -= 15;
    }
}

if ($health < 0) {
    $health = 0;
}

if ($health < 40) {
    db_insert_event_cooldown(
        $conn,
        'System-wide Critical Condition',
        'Combined system health dropped below 40%. Immediate intervention required.',
        5
    );
}

$storm_trend = 'No significant storm escalation detected.';
$storm_trend_class = 'status_safe';
$storm_trend_rows = db_fetch_all($conn, "SELECT intensity FROM solar_storms ORDER BY created_at DESC LIMIT 3");
if (count($storm_trend_rows) === 3) {
    $i1 = (int) $storm_trend_rows[0]['intensity'];
    $i2 = (int) $storm_trend_rows[1]['intensity'];
    $i3 = (int) $storm_trend_rows[2]['intensity'];

    if ($i1 >= $i2 && $i2 >= $i3) {
        if ($i1 >= 8) {
            $storm_trend = 'Storm intensity is escalating toward extreme levels.';
            $storm_trend_class = 'status_danger';
        } else {
            $storm_trend = 'Storm intensity is rising. Monitor closely.';
            $storm_trend_class = 'status_warn';
        }

        db_insert_event_cooldown(
            $conn,
            'Storm Escalation Warning',
            'Storm intensity rising toward extreme levels.',
            10
        );
    }
}

$battery_trend_text = 'Battery trend unavailable.';
$battery_trend_class = 'status_warn';
if ($latest_power && $prev_power) {
    $diff = (float) $latest_power['battery_level'] - (float) $prev_power['battery_level'];
    if ($diff <= -15) {
        $battery_trend_text = 'Battery level declining rapidly.';
        $battery_trend_class = 'status_danger';
    } elseif ($diff < 0) {
        $battery_trend_text = 'Battery level declining.';
        $battery_trend_class = 'status_warn';
    } elseif ($diff > 5) {
        $battery_trend_text = 'Battery level improving.';
        $battery_trend_class = 'status_safe';
    } else {
        $battery_trend_text = 'Battery level stable.';
        $battery_trend_class = 'status_safe';
    }
}
?>

<?php if (!$is_refresh): ?>
<?php include '../includes/header.php'; ?>
<link rel="stylesheet" href="../assets/css/astro.css">
<h1>Astronaut Dashboard</h1>
<section class="card status_strip">
    <div class="strip_row">
        <span>Mission: <strong class="status_safe">Active</strong></span>
        <span>Network: <strong class="status_safe">Synced</strong></span>
        <span>Role: <strong>Astronaut</strong></span>
        <span id="refresh_note_astro">Refresh: waiting</span>
    </div>
</section>
<div id="dashboard_content">
<?php endif; ?>

<!-- mission telemetry modules -->
<section class="dash_panel panel_grid">
    <article class="card mod_rad">
        <h2 class="card_head">Radiation Monitor</h2>
        <div class="card_body">
            <?php if ($latest_radiation): ?>
                <p><strong>Radiation level:</strong> <span id="astro_rad_level"><?php echo $latest_radiation['radiation_level']; ?></span></p>
                <p><strong>Status:</strong> <span id="astro_rad_status"><?php echo htmlspecialchars($latest_radiation['status']); ?></span></p>
                <p><strong>Last update:</strong> <span id="astro_rad_time"><?php echo $latest_radiation['created_at']; ?></span></p>
                <p id="astro_rad_note" class="<?php echo status_cls($latest_radiation['status']); ?>">
                    <?php
                    if ($latest_radiation['status'] === 'danger') {
                        echo 'Radiation levels are dangerous. Proceed to shelter immediately.';
                    } elseif ($latest_radiation['status'] === 'warning') {
                        echo 'Radiation elevated. Limit external activity.';
                    } else {
                        echo 'Radiation within safe limits.';
                    }
                    ?>
                </p>
            <?php else: ?>
                <p>No radiation data yet.</p>
            <?php endif; ?>
            <div class="chart_wrap">
                <canvas id="chart_radiation"></canvas>
            </div>
        </div>
    </article>

    <article class="card mod_health">
        <h2 class="card_head">System Health</h2>
        <div class="card_body">
            <p>System health: <strong id="astro_health_value"><?php echo $health; ?></strong>%</p>
            <div class="healbar health_bar">
                <div id="astro_health_fill" class="healfill" style="width:<?php echo $health; ?>%; background-color:<?php echo health_color($health); ?>;"></div>
            </div>
            <?php if ($health >= 80): ?>
                <p id="astro_health_note" class="status_safe">Habitat system operating in optimal range.</p>
            <?php elseif ($health >= 50): ?>
                <p id="astro_health_note" class="status_warn">System under moderate stress. Monitor closely.</p>
            <?php else: ?>
                <p id="astro_health_note" class="status_danger pulse_danger">Habitat system health is critical. Immediate action required.</p>
            <?php endif; ?>
            <div class="chart_wrap chart_doughnut">
                <canvas id="chart_health"></canvas>
            </div>
        </div>
    </article>

    <article class="card mod_pwr">
        <h2 class="card_head">Power Monitor</h2>
        <div class="card_body">
            <div class="chart_wrap">
                <canvas id="chart_power"></canvas>
            </div>

            <?php if (count($power_rows) > 0): ?>
                <table class="power_table">
                    <tr>
                        <th>Solar output</th>
                        <th>Battery</th>
                        <th>Power mode</th>
                        <th>Storm intensity</th>
                        <th>Time</th>
                    </tr>
                    <?php foreach ($power_rows as $row): ?>
                        <tr>
                            <td><?php echo $row['solar_output']; ?></td>
                            <td><?php echo $row['battery_level']; ?>%</td>
                            <td class="<?php echo status_cls($row['mode']); ?>"><?php echo htmlspecialchars($row['mode']); ?></td>
                            <td><?php echo $row['intensity']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>No power data yet.</p>
            <?php endif; ?>

            <?php if ($latest_power): ?>
                <?php if ((float) $latest_power['battery_level'] < 30): ?>
                    <p class="status_warn">Battery reserves below 30%.</p>
                <?php endif; ?>
                <?php if ((float) $latest_power['battery_level'] < 15): ?>
                    <p class="status_danger">Emergency battery depletion risk.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="card mod_battery_trend">
        <h2 class="card_head">Battery Trend</h2>
        <div class="card_body">
            <p class="<?php echo $battery_trend_class; ?>"><?php echo $battery_trend_text; ?></p>
        </div>
    </article>

    <article class="card mod_storm_forecast">
        <h2 class="card_head">Storm Monitor</h2>
        <div class="card_body">
            <p class="<?php echo $storm_trend_class; ?>"><?php echo htmlspecialchars($storm_trend); ?></p>
            <?php if ($latest_storm): ?>
                <p><strong>Storm intensity:</strong> <span id="astro_storm_intensity"><?php echo $latest_storm['intensity']; ?></span></p>
                <p><strong>Last update:</strong> <span id="astro_storm_time"><?php echo $latest_storm['created_at']; ?></span></p>
            <?php endif; ?>
            <div class="chart_wrap">
                <canvas id="chart_storm"></canvas>
            </div>
        </div>
    </article>

    <article class="card mod_stats">
        <h2 class="card_head">System Analytics (24h)</h2>
        <div class="card_body stats_grid">
            <div class="stats_item">
                <p class="stats_label">Average Radiation</p>
                <p id="astro_avg_rad" class="stats_value"><?php echo $avg_rad !== null ? number_format($avg_rad, 2) : 'N/A'; ?></p>
            </div>
            <div class="stats_item">
                <p class="stats_label">Average Battery</p>
                <p id="astro_avg_battery" class="stats_value"><?php echo $avg_battery !== null ? number_format($avg_battery, 2) . '%' : 'N/A'; ?></p>
            </div>
            <div class="stats_item">
                <p class="stats_label">Events Logged</p>
                <p id="astro_events_24h" class="stats_value"><?php echo $events_24h; ?></p>
            </div>
        </div>
    </article>

    <article class="card mod_events">
        <h2 class="card_head">Event Log</h2>
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
<script src="../assets/js/astro_charts.js"></script>
<script src="../assets/js/auto_refresh.js"></script>
<script src="../assets/js/astro.js"></script>
<?php include '../includes/footer.php'; ?>
<?php endif; ?>