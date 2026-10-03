/* ============================================================
   Gracewell QuietShield — Settings page logic
   Depends on: qs-core.js (loaded before this, both deferred)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    const { $, $$, toast, modal, confirm, esc, fmt } = GWQSH;
    const D = window.GWQSH_DATA || {};

    /* ---------- persisted settings (localStorage + backend) ---------- */
    const KEY = 'gwqsh-settings';

    const DEF = {
        status: true, autoupdate: true, retention: '30', language: 'en_US', timezone: 'Asia/Kolkata',
        telemetry: false, notice: true, proxy: false, https: true,
        theme: 'light', compact: false, anim: true, tips: true,
        debug: false, proxyheader: 'xff', uninstall: false
    };

    let S = Object.assign({}, DEF, D.settings || {});

    // cross-page prefs
    S.theme = GWQSH.store.get('gwqsh-theme', S.theme);
    S.compact = GWQSH.store.get('gwqsh-density', 'comfortable') === 'compact';
    S.anim = GWQSH.store.get('gwqsh-anim', '1') === '1';
    S.tips = GWQSH.store.get('gwqsh-tips', '1') === '1';

    const LABEL = {
        status: 'Plugin status', autoupdate: 'Automatic updates', retention: 'Data retention',
        language: 'Plugin language', timezone: 'Timezone', telemetry: 'Telemetry',
        notice: 'Admin notice for critical issues', proxy: 'IP detection via proxy',
        https: 'HTTPS for plugin pages',
        theme: 'Color scheme', compact: 'Compact layout', anim: 'Animated charts',
        tips: 'Security tips', debug: 'Debug logging', proxyheader: 'Proxy header',
        uninstall: 'Delete data on uninstall'
    };

    function paint() {
        $$('[data-key]').forEach(el => {
            const k = el.dataset.key;
            if (el.classList.contains('switch')) GWQSH.setSwitch(el, !!S[k]);
            else el.value = S[k];
        });
        $('#proxyheader').disabled = !S.proxy;
    }

    async function persist() {
        const res = await GWQSH.ajax('save_settings', { settings: S });
        if (!res.success) { toast(res.data?.message || 'Could not save settings', 'err'); return false; }
        S = res.data.settings;
        GWQSH.store.set(KEY, JSON.stringify(S));
        GWQSH.store.set('gwqsh-theme', S.theme);
        GWQSH.store.set('gwqsh-density', S.compact ? 'compact' : 'comfortable');
        GWQSH.store.set('gwqsh-anim', S.anim ? '1' : '0');
        GWQSH.store.set('gwqsh-tips', S.tips ? '1' : '0');
        const before = document.documentElement.dataset.theme;
        GWQSH.applyPrefs();
        if (before !== document.documentElement.dataset.theme && GWQSH.rebuildHero) GWQSH.rebuildHero();

        // Save to WordPress database
        return true;
    }

    async function save(k, v) {
        const prev = S[k];
        S[k] = v;
        if (!await persist()) { S[k] = prev; paint(); return false; }
        paint();
        const txt = typeof v === 'boolean' ? LABEL[k] + (v ? ' enabled' : ' disabled') : LABEL[k] + ' saved';
        toast(txt, 'ok', {
            label: 'Undo',
            fn: async () => {
                S[k] = prev;
                await persist();
                paint();
            }
        });
    }

    paint();

    document.addEventListener('gwqsh:toggle', e => {
        const k = e.target.dataset.key;
        if (!k) return;

        if (k === 'status' && !e.detail.on) {
            GWQSH.setSwitch(e.target, true);
            confirm('Turn off QuietShield?',
                'Login protection, 2FA enforcement will stop until you turn it back on.',
                'Turn off', () => save('status', false), true);
            return;
        }

        if (k === 'uninstall' && e.detail.on) {
            GWQSH.setSwitch(e.target, false);
            confirm('Delete data on uninstall?',
                'If you delete the plugin, every setting, log and scan result is removed and can\u2019t be recovered.',
                'Turn on', () => save('uninstall', true), true);
            return;
        }

        save(k, e.detail.on);
    });

    $$('select[data-key]').forEach(el => el.addEventListener('change', () => save(el.dataset.key, el.value)));

    /* ---------- tabs ---------- */
    const OVERVIEW = ['general', 'security'];

    function showTab(t, focus) {
        $$('.tabs-scroll .seg-btn').forEach(b => b.setAttribute('aria-selected', b.dataset.tab === t));
        const shown = t === 'general' ? OVERVIEW : [t];
        $$('.s-card').forEach(c => {
            if (c.id === 'appearance' || c.id === 'advanced') { c.classList.add('hide'); return; }
            c.classList.toggle('hide', !shown.includes(c.dataset.tab));
        });
        $('#panels').classList.toggle('single', shown.length === 1);
        $('#panels').setAttribute('aria-labelledby', 't-' + t);
        if (GWQSH.animOn()) GWQSH_Motion.from($$('.s-card:not(.hide)'), { y: 10, opacity: 0, duration: .35, stagger: .05, ease: 'power2.out', clearProps: 'all' });
        if (focus) history.replaceState(null, '', '#' + t);
    }

    $('.tabs-scroll').addEventListener('click', e => {
        const b = e.target.closest('.seg-btn');
        if (b) showTab(b.dataset.tab, true);
    });

    $('.tabs-scroll').addEventListener('keydown', e => {
        if (!['ArrowRight', 'ArrowLeft'].includes(e.key)) return;
        const bs = $$('.tabs-scroll .seg-btn');
        const i = bs.findIndex(b => b.getAttribute('aria-selected') === 'true');
        const n = bs[(i + (e.key === 'ArrowRight' ? 1 : -1) + bs.length) % bs.length];
        n.focus();
        showTab(n.dataset.tab, true);
    });

    const h = location.hash.slice(1);
    showTab(['security', 'tools'].includes(h) ? h : 'general');
    if (h === 'importexport') setTimeout(() => $('#importexport').scrollIntoView({ behavior: 'smooth' }), 300);

    /* ---------- import / export / reset ---------- */
    $('#exportS')?.addEventListener('click', () => {
        GWQSH.download(
            'quietshield-settings.json',
            JSON.stringify({ plugin: 'gracewell-quietshield', version: window.GWQSH_CONFIG?.version || '', exported: new Date().toISOString(), settings: S }, null, 2),
            'application/json'
        );
        toast('Settings exported');
    });

    $('#importS')?.addEventListener('click', () => $('#importFile')?.click());

    $('#importFile')?.addEventListener('change', e => {
        const f = e.target.files[0];
        if (!f) return;
        const rd = new FileReader();
        rd.onload = async () => {
            try {
                const j = JSON.parse(rd.result);
                const inc = j.settings || j;
                const known = Object.keys(DEF).filter(k => k in inc);
                if (!known.length) throw 0;
                known.forEach(k => S[k] = typeof DEF[k] === 'boolean' ? !!inc[k] : String(inc[k]));
                if (!await persist()) { S = Object.assign({}, DEF, D.settings || {}); paint(); return; }
                paint();
                toast(known.length + ' settings imported from ' + f.name);
            } catch (err) {
                toast('That file isn\u2019t a QuietShield settings export. Choose the .json file from \u201cExport Settings\u201d.', 'err');
            }
            e.target.value = '';
        };
        rd.readAsText(f);
    });

    /* ---------- tools ---------- */
    $('#rebuild').addEventListener('click', () =>
        confirm('Rebuild the file baseline?',
            'QuietShield will verify core files against the official WordPress.org checksums and scan for suspicious files.',
            'Run integrity scan',
            async () => {
                if (window.GWQSH && GWQSH.ajax) {
                    const res = await GWQSH.ajax('rebuild_baseline');
                    if (!res.success) { toast(res.data?.message || 'Scan failed', 'err'); return false; }
                }
                toast('File integrity scan completed');
            }));

    $('#clearLogs').addEventListener('click', () =>
        confirm('Clear all activity logs?',
            'All stored activity events will be permanently deleted. Export them first if you need a copy.',
            'Clear logs',
            async () => {
                if (window.GWQSH && GWQSH.ajax) {
                    const savedResponse = await GWQSH.ajax('clear_activity_logs');
                    if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
                }
                toast('Activity logs cleared', 'warn');
            },
            true));
});
