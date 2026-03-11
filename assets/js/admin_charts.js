const admin_chart_store = {
    storm: null,
    radiation: null,
    power: null,
    health: null,
    power_history: null,
};

const admin_palette = {
    primary_blue: '#4da3ff',
    warning_orange: '#f59e0b',
    danger_red: '#ef4444',
    safe_green: '#22c55e',
    border: '#2a3442',
    grid: 'rgba(42, 52, 66, 0.45)',
    text: '#9eabb9',
};

function admin_set_text(id, value) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = value;
    }
}

function admin_set_badge(id, status) {
    const el = document.getElementById(id);
    if (!el) {
        return;
    }

    el.classList.remove('status_safe', 'status_warn', 'status_critical');

    if (status === 'critical') {
        el.classList.add('status_critical');
        el.textContent = 'Critical';
        return;
    }

    if (status === 'warn') {
        el.classList.add('status_warn');
        el.textContent = 'Warn';
        return;
    }

    el.classList.add('status_safe');
    el.textContent = 'Safe';
}

function admin_line_bar_options() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 240 },
        plugins: {
            legend: {
                labels: { color: admin_palette.text },
            },
        },
        scales: {
            x: {
                ticks: { color: admin_palette.text },
                grid: { color: admin_palette.grid, lineWidth: 1 },
            },
            y: {
                beginAtZero: true,
                ticks: { color: admin_palette.text },
                grid: { color: admin_palette.grid, lineWidth: 1 },
            },
        },
    };
}

function admin_doughnut_options() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 240 },
        plugins: {
            legend: {
                labels: { color: admin_palette.text },
            },
        },
    };
}

function admin_render_or_replace(chart_key, canvas_id, type, data, options) {
    const canvas = document.getElementById(canvas_id);
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    if (admin_chart_store[chart_key]) {
        const live_chart = admin_chart_store[chart_key];
        if (live_chart.canvas !== canvas) {
            live_chart.destroy();
            admin_chart_store[chart_key] = null;
        } else {
            live_chart.data.labels = data.labels;
            live_chart.data.datasets = data.datasets;
            live_chart.options = options;
            live_chart.update('none');
            return;
        }
    }

    admin_chart_store[chart_key] = new Chart(canvas.getContext('2d'), {
        type: type,
        data: data,
        options: options,
    });
}

function admin_load_storm_chart() {
    return fetch('../api/storm_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (payload.latest) {
                const intensity = Number(payload.latest.intensity || 0);
                admin_set_text('admin_storm_intensity', String(intensity));
                admin_set_text('admin_storm_time', payload.latest.created_at || 'N/A');

                if (intensity >= 8) {
                    admin_set_badge('admin_storm_status', 'critical');
                } else if (intensity >= 5) {
                    admin_set_badge('admin_storm_status', 'warn');
                } else {
                    admin_set_badge('admin_storm_status', 'safe');
                }
            }

            admin_render_or_replace(
                'storm',
                'admin_chart_storm',
                'line',
                {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Storm intensity',
                            data: payload.values || [],
                            borderColor: admin_palette.warning_orange,
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            tension: 0.2,
                            fill: true,
                            borderWidth: 2,
                        },
                    ],
                },
                admin_line_bar_options()
            );
        });
}

function admin_load_radiation_chart() {
    return fetch('../api/radiation_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (payload.latest) {
                const level = Number(payload.latest.radiation_level || 0);
                const status = String(payload.latest.status || 'safe');

                admin_set_text('admin_rad_level', level.toFixed(2));
                admin_set_text('admin_rad_time', payload.latest.created_at || 'N/A');

                if (status === 'danger' || status === 'critical') {
                    admin_set_badge('admin_rad_status', 'critical');
                } else if (status === 'warning') {
                    admin_set_badge('admin_rad_status', 'warn');
                } else {
                    admin_set_badge('admin_rad_status', 'safe');
                }
            }

            admin_render_or_replace(
                'radiation',
                'admin_chart_radiation',
                'line',
                {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Radiation level',
                            data: payload.values || [],
                            borderColor: admin_palette.danger_red,
                            backgroundColor: 'rgba(239, 68, 68, 0.08)',
                            tension: 0.2,
                            fill: true,
                            borderWidth: 2,
                        },
                    ],
                },
                admin_line_bar_options()
            );
        });
}

function admin_load_power_charts() {
    return fetch('../api/power_data.php')
        .then((res) => res.json())
        .then((payload) => {
            const latest = payload.latest || null;
            const backup_value = latest && latest.mode === 'critical' ? 100 : 0;

            if (latest) {
                admin_set_text('admin_power_solar', String(latest.solar_output ?? 'N/A'));
                admin_set_text('admin_power_battery', String(latest.battery_level ?? 'N/A') + '%');
                admin_set_text('admin_power_time', latest.created_at || 'N/A');
                admin_set_badge('admin_power_mode', latest.mode === 'critical' ? 'critical' : 'safe');
            }

            admin_render_or_replace(
                'power',
                'admin_chart_power',
                'bar',
                {
                    labels: ['Solar Output', 'Battery Level', 'Backup Status'],
                    datasets: [
                        {
                            label: 'Current values',
                            data: latest
                                ? [
                                      Number(latest.solar_output || 0),
                                      Number(latest.battery_level || 0),
                                      backup_value,
                                  ]
                                : [0, 0, 0],
                            backgroundColor: [
                                admin_palette.primary_blue,
                                admin_palette.safe_green,
                                admin_palette.warning_orange,
                            ],
                            borderColor: admin_palette.border,
                            borderWidth: 1,
                        },
                    ],
                },
                admin_line_bar_options()
            );

            admin_render_or_replace(
                'power_history',
                'admin_chart_power_history',
                'line',
                {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Solar output',
                            data: payload.solar_output || [],
                            borderColor: admin_palette.primary_blue,
                            backgroundColor: 'rgba(77, 163, 255, 0.08)',
                            fill: false,
                            tension: 0.2,
                            borderWidth: 2,
                        },
                        {
                            label: 'Battery level',
                            data: payload.battery_level || [],
                            borderColor: admin_palette.safe_green,
                            backgroundColor: 'rgba(34, 197, 94, 0.08)',
                            fill: false,
                            tension: 0.2,
                            borderWidth: 2,
                        },
                    ],
                },
                admin_line_bar_options()
            );
        });
}

function admin_load_health_chart() {
    return fetch('../api/health_data.php')
        .then((res) => res.json())
        .then((payload) => {
            const health = Math.max(0, Math.min(100, Number(payload.health || 0)));
            admin_set_text('admin_health_value', String(health) + '%');
            admin_set_text('admin_health_time', new Date().toLocaleTimeString());

            if (health >= 80) {
                admin_set_badge('admin_health_status', 'safe');
            } else if (health >= 50) {
                admin_set_badge('admin_health_status', 'warn');
            } else {
                admin_set_badge('admin_health_status', 'critical');
            }

            admin_render_or_replace(
                'health',
                'admin_chart_health',
                'doughnut',
                {
                    labels: ['Healthy', 'Risk'],
                    datasets: [
                        {
                            data: [health, 100 - health],
                            backgroundColor: [admin_palette.safe_green, admin_palette.danger_red],
                            borderColor: admin_palette.border,
                            borderWidth: 1,
                        },
                    ],
                },
                admin_doughnut_options()
            );
        });
}

function load_admin_charts() {
    Promise.all([
        admin_load_storm_chart(),
        admin_load_radiation_chart(),
        admin_load_power_charts(),
        admin_load_health_chart(),
    ]).catch((err) => {
        console.log('Admin chart load failed:', err);
    });
}
