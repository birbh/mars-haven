const user_chart_store = {
    activity: null,
};

const user_palette = {
    primary_blue: '#4da3ff',
    warning_orange: '#f59e0b',
    danger_red: '#ef4444',
    safe_green: '#22c55e',
    grid: 'rgba(42, 52, 66, 0.45)',
    text: '#9eabb9',
};

function user_set_text(id, value) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = value;
    }
}

function user_set_badge(id, status) {
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

function user_line_options() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 220 },
        plugins: {
            legend: {
                labels: { color: user_palette.text },
            },
        },
        scales: {
            x: {
                ticks: { color: user_palette.text },
                grid: { color: user_palette.grid, lineWidth: 1 },
            },
            y: {
                beginAtZero: true,
                ticks: { color: user_palette.text },
                grid: { color: user_palette.grid, lineWidth: 1 },
            },
        },
    };
}

function user_render_or_replace(chart_key, canvas_id, data, options) {
    const canvas = document.getElementById(canvas_id);
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    if (user_chart_store[chart_key]) {
        const live_chart = user_chart_store[chart_key];
        if (live_chart.canvas !== canvas) {
            live_chart.destroy();
            user_chart_store[chart_key] = null;
        } else {
            live_chart.data.labels = data.labels;
            live_chart.data.datasets = data.datasets;
            live_chart.options = options;
            live_chart.update('none');
            return;
        }
    }

    user_chart_store[chart_key] = new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: data,
        options: options,
    });
}

function user_load_health_summary() {
    return fetch('../api/health_data.php')
        .then((res) => res.json())
        .then((payload) => {
            const health = Math.max(0, Math.min(100, Number(payload.health || 0)));
            user_set_text('user_health_value', String(health) + '%');
            user_set_text('user_health_time', new Date().toLocaleTimeString());

            if (health >= 80) {
                user_set_badge('user_health_status', 'safe');
            } else if (health >= 50) {
                user_set_badge('user_health_status', 'warn');
            } else {
                user_set_badge('user_health_status', 'critical');
            }
        });
}

function user_load_storm_summary_and_chart() {
    return fetch('../api/storm_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (payload.latest) {
                const intensity = Number(payload.latest.intensity || 0);
                user_set_text('user_storm_time', payload.latest.created_at || 'N/A');

                if (intensity >= 8) {
                    user_set_text('user_storm_level', 'High');
                    user_set_badge('user_storm_status', 'critical');
                } else if (intensity >= 5) {
                    user_set_text('user_storm_level', 'Moderate');
                    user_set_badge('user_storm_status', 'warn');
                } else {
                    user_set_text('user_storm_level', 'Low');
                    user_set_badge('user_storm_status', 'safe');
                }
            }

            user_render_or_replace(
                'activity',
                'user_chart_activity',
                {
                    labels: payload.labels || [],
                    datasets: [
                        {
                            label: 'Activity index',
                            data: payload.values || [],
                            borderColor: user_palette.primary_blue,
                            backgroundColor: 'rgba(77, 163, 255, 0.08)',
                            fill: true,
                            tension: 0.2,
                            borderWidth: 2,
                        },
                    ],
                },
                user_line_options()
            );
        });
}

function user_load_radiation_summary() {
    return fetch('../api/radiation_data.php')
        .then((res) => res.json())
        .then((payload) => {
            if (!payload.latest) {
                return;
            }

            const level = Number(payload.latest.radiation_level || 0);
            const status = String(payload.latest.status || 'safe');
            user_set_text('user_rad_level', level.toFixed(1));
            user_set_text('user_rad_time', payload.latest.created_at || 'N/A');

            if (status === 'danger' || status === 'critical') {
                user_set_badge('user_rad_status', 'critical');
            } else if (status === 'warning') {
                user_set_badge('user_rad_status', 'warn');
            } else {
                user_set_badge('user_rad_status', 'safe');
            }
        });
}

function load_user_charts() {
    Promise.all([
        user_load_health_summary(),
        user_load_storm_summary_and_chart(),
        user_load_radiation_summary(),
    ]).catch((err) => {
        console.log('User summary load failed:', err);
    });
}
