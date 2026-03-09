const astro_chart_store = {
    storm: null,
    radiation: null,
    power: null,
    health: null,
};

function set_text(id, value) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = value;
    }
}

function set_status_note(id, status, safe_msg, warn_msg, danger_msg) {
    const el = document.getElementById(id);
    if (!el) {
        return;
    }

    if (status === 'danger' || status === 'critical') {
        el.className = 'status_danger';
        el.textContent = danger_msg;
        return;
    }

    if (status === 'warning') {
        el.className = 'status_warn';
        el.textContent = warn_msg;
        return;
    }

    el.className = 'status_safe';
    el.textContent = safe_msg;
}

function build_chart_config() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
            duration: 350,
        },
        plugins: {
            legend: {
                labels: {
                    color: '#9eabb9',
                },
            },
        },
        scales: {
            x: {
                ticks: { color: '#9eabb9' },
                grid: { color: '#2a3442' },
            },
            y: {
                ticks: { color: '#9eabb9' },
                grid: { color: '#2a3442' },
            },
        },
    };
}

function render_or_replace(chart_key, canvas_id, type, data, options) {
    const canvas = document.getElementById(canvas_id);
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    if (astro_chart_store[chart_key]) {
        const live_chart = astro_chart_store[chart_key];
        live_chart.data.labels = data.labels;
        live_chart.data.datasets = data.datasets;
        live_chart.options = options;
        live_chart.update('none');
        return;
    }

    const ctx = canvas.getContext('2d');
    astro_chart_store[chart_key] = new Chart(ctx, {
        type: type,
        data: data,
        options: options,
    });
}

function load_storm_chart() {
    return fetch('../api/storm_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (payload.latest) {
                set_text('astro_storm_intensity', String(payload.latest.intensity ?? 'N/A'));
                set_text('astro_storm_time', payload.latest.created_at || 'N/A');
            }

            render_or_replace(
                'storm',
                'chart_storm',
                'line',
                {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Storm intensity',
                            data: payload.values || [],
                            borderColor: '#f5a93b',
                            backgroundColor: 'rgba(245, 169, 59, 0.12)',
                            tension: 0.25,
                            fill: true,
                        },
                    ],
                },
                build_chart_config()
            );
        });
}

function load_radiation_chart() {
    return fetch('../api/radiation_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (payload.latest) {
                const status = String(payload.latest.status || 'safe');
                set_text('astro_rad_level', String(payload.latest.radiation_level ?? 'N/A'));
                set_text('astro_rad_status', status);
                set_text('astro_rad_time', payload.latest.created_at || 'N/A');

                set_status_note(
                    'astro_rad_note',
                    status,
                    'Radiation within safe limits.',
                    'Radiation elevated. Limit external activity.',
                    'Radiation levels are dangerous. Proceed to shelter immediately.'
                );
            }

            render_or_replace(
                'radiation',
                'chart_radiation',
                'line',
                {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Radiation level',
                            data: payload.values || [],
                            borderColor: '#ff5a66',
                            backgroundColor: 'rgba(255, 90, 102, 0.1)',
                            tension: 0.25,
                            fill: true,
                        },
                    ],
                },
                build_chart_config()
            );
        });
}

function load_power_chart() {
    return fetch('../api/power_data.php')
        .then((res) => res.json())
        .then((payload) => {
            render_or_replace(
                'power',
                'chart_power',
                'bar',
                {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Solar output',
                            data: payload.solar_output || [],
                            backgroundColor: '#4da3ff',
                        },
                        {
                            label: 'Battery level',
                            data: payload.battery_level || [],
                            backgroundColor: '#57d783',
                        },
                        {
                            label: 'Backup status',
                            data: payload.backup_status || [],
                            backgroundColor: '#f5a93b',
                        },
                    ],
                },
                build_chart_config()
            );
        });
}

function load_health_chart() {
    return fetch('../api/health_data.php')
        .then((res) => res.json())
        .then((payload) => {
            const health = Math.max(0, Math.min(100, Number(payload.health || 0)));

            set_text('astro_health_value', String(health));
            set_text('astro_avg_rad', payload.avg_radiation !== null ? Number(payload.avg_radiation).toFixed(2) : 'N/A');
            set_text('astro_avg_battery', payload.avg_battery !== null ? Number(payload.avg_battery).toFixed(2) + '%' : 'N/A');
            set_text('astro_events_24h', String(payload.events_24h ?? 0));

            const fill_el = document.getElementById('astro_health_fill');
            if (fill_el) {
                fill_el.style.width = health + '%';
                if (health >= 80) {
                    fill_el.style.backgroundColor = 'green';
                } else if (health >= 50) {
                    fill_el.style.backgroundColor = 'orange';
                } else {
                    fill_el.style.backgroundColor = 'red';
                }
            }

            const health_note = document.getElementById('astro_health_note');
            if (health_note) {
                if (health >= 80) {
                    health_note.className = 'status_safe';
                    health_note.textContent = 'Habitat system operating in optimal range.';
                } else if (health >= 50) {
                    health_note.className = 'status_warn';
                    health_note.textContent = 'System under moderate stress. Monitor closely.';
                } else {
                    health_note.className = 'status_danger pulse_danger';
                    health_note.textContent = 'Habitat system health is critical. Immediate action required.';
                }
            }

            render_or_replace(
                'health',
                'chart_health',
                'doughnut',
                {
                    labels: ['Healthy', 'Risk'],
                    datasets: [
                        {
                            data: [health, 100 - health],
                            backgroundColor: ['#57d783', '#2a3442'],
                            borderColor: ['#57d783', '#2a3442'],
                        },
                    ],
                },
                {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 350 },
                    plugins: {
                        legend: {
                            labels: {
                                color: '#9eabb9',
                            },
                        },
                    },
                }
            );
        });
}

function load_astro_charts() {
    Promise.all([
        load_storm_chart(),
        load_radiation_chart(),
        load_power_chart(),
        load_health_chart(),
    ]).catch((err) => {
        console.log('Chart load failed:', err);
    });
}
