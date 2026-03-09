function refresh_astro_charts() {
    if (typeof load_astro_charts === 'function') {
        load_astro_charts();
    }

    const note_el = document.getElementById('refresh_note_astro');
    if (note_el) {
        note_el.textContent = 'Refresh: ' + new Date().toLocaleTimeString();
        note_el.className = 'status_safe';
    }
}

refresh_astro_charts();
setInterval(refresh_astro_charts, 10000);
