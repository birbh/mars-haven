const user_chart_store = {
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

function user_chart_options() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 300 },
        plugins: {
            legend: {
                labels: { color: '#9eabb9' },
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

function user_render_chart(chart_key, canvas_id, type, data, options) {
    const canvas = document.getElementById(canvas_id);
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    if (user_chart_store[chart_key]) {
        const live_chart = user_chart_store[chart_key];
        live_chart.data.labels = data.labels;
        live_chart.data.datasets = data.datasets;
        live_chart.options = options;
        live_chart.update('none');
        return;
    }

    user_chart_store[chart_key] = new Chart(canvas.getContext('2d'), {
        type: type,
        data: data,
        options: options,
    });
}

function user_load_storm_chart() {
    return fetch('../api/storm_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (payload.latest) {
                set_text('user_storm_intensity', String(payload.latest.intensity ?? 'N/A'));
                set_text('user_storm_description', payload.latest.description || 'N/A');
                set_text('user_storm_time', payload.latest.created_at || 'N/A');
            }

            user_render_chart(
                'storm',
                'chart_user_storm',
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
                user_chart_options()
            );
        });
}

function user_load_radiation_chart() {
    return fetch('../api/radiation_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (payload.latest) {
                const status = String(payload.latest.status || 'safe');
                set_text('user_rad_level', String(payload.latest.radiation_level ?? 'N/A'));
                set_text('user_rad_status', status);
                set_text('user_rad_time', payload.latest.created_at || 'N/A');

                set_status_note(
                    'user_rad_note',
                    status,
                    'Radiation levels are within safe operational limits.',
                    'Radiation levels are elevated.',
                    'Radiation levels are dangerous.'
                );
            }

            user_render_chart(
                'radiation',
                'chart_user_radiation',
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
                user_chart_options()
            );
        });
}

function user_load_power_chart() {
    return fetch('../api/power_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (payload.latest) {
                const mode = String(payload.latest.mode || 'normal');
                set_text('user_power_solar', String(payload.latest.solar_output ?? 'N/A'));
                set_text('user_power_battery', String(payload.latest.battery_level ?? 'N/A'));
                set_text('user_power_mode', mode);
                set_text('user_power_time', payload.latest.created_at || 'N/A');

                set_status_note(
                    'user_power_note',
                    mode,
                    'Power systems are operating normally.',
                    'Power systems are in warning state.',
                    'Power systems are in critical mode.'
                );
            }

            user_render_chart(
                'power',
                'chart_user_power',
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
                user_chart_options()
            );
        });
}

function user_load_health_chart() {
    return fetch('../api/health_data.php')
        .then((res) => res.json())
        .then((payload) => {
            const health = Math.max(0, Math.min(100, Number(payload.health || 0)));

            set_text('user_avg_rad', payload.avg_radiation !== null ? Number(payload.avg_radiation).toFixed(2) : 'N/A');
            set_text('user_avg_battery', payload.avg_battery !== null ? Number(payload.avg_battery).toFixed(2) + '%' : 'N/A');
            set_text('user_events_24h', String(payload.events_24h ?? 0));

            user_render_chart(
                'health',
                'chart_user_health',
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
                    animation: { duration: 300 },
                    plugins: {
                        legend: {
                            labels: { color: '#9eabb9' },
                        },
                    },
                }
            );
        });
}

function load_user_charts() {
    Promise.all([
        user_load_storm_chart(),
        user_load_radiation_chart(),
        user_load_power_chart(),
        user_load_health_chart(),
    ]).catch((err) => {
        console.log('User chart load failed:', err);
    });
}
