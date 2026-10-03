/* ============================================================
   Gracewell QuietShield â€” Dashboard UI Logic
   Loaded with `defer`, runs after HTML is parsed.
   ============================================================ */

(() => {
    'use strict';

    /* ----------------------------------------------------------
       Small helpers
       ---------------------------------------------------------- */
    const $  = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];

    const ico = id => `<svg class="i"><use href="#i-${id}"/></svg>`;

    const esc = s => String(s).replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    /* ----------------------------------------------------------
       Toasts
       ---------------------------------------------------------- */
    function toast(msg, type = 'ok') {
        const t = document.createElement('div');
        t.className = 'toast ' + type;
        t.innerHTML = ico(type === 'ok' ? 'check' : 'alert') +
                      '<span>' + esc(msg) + '</span>';
        $('#toasts').appendChild(t);
        setTimeout(() => {
            t.style.transition = 'opacity .3s';
            t.style.opacity = '0';
            setTimeout(() => t.remove(), 300);
        }, 3000);
    }

    document.addEventListener('click', e => {
        const el = e.target.closest('[data-toast]');
        if (el) {
            e.preventDefault();
            toast(el.dataset.toast, 'info');
            closeMenus();
        }
    });

    /* ----------------------------------------------------------
       Menus + burger
       ---------------------------------------------------------- */
    function closeMenus() {
        $$('.menu.open').forEach(m => m.classList.remove('open'));
        $$('[data-menu]').forEach(b => b.setAttribute('aria-expanded', 'false'));
    }

    $$('[data-menu]').forEach(btn => btn.addEventListener('click', e => {
        e.stopPropagation();
        const m = $('#menu-' + btn.dataset.menu);
        const open = !m.classList.contains('open');
        closeMenus();
        setNav(false);
        if (open) {
            m.classList.add('open');
            btn.setAttribute('aria-expanded', 'true');
        }
    }));

    const nav = $('#mainNav');
    const burger = $('#burger');

    function setNav(o) {
        nav.classList.toggle('open', o);
        burger.setAttribute('aria-expanded', o);
    }

    burger.addEventListener('click', e => {
        e.stopPropagation();
        closeMenus();
        setNav(!nav.classList.contains('open'));
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.menu')) closeMenus();
        if (!e.target.closest('#mainNav,#burger')) setNav(false);
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeMenus();
            setNav(false);
        }
    });

    matchMedia('(min-width:1500px)').addEventListener('change', () => setNav(false));

    $('#markRead').addEventListener('click', () => {
        $('#notifDot').classList.add('off');
        $$('.menu-item.unread').forEach(i => i.classList.remove('unread'));
        toast('All notifications marked as read');
    });

    /* ----------------------------------------------------------
       Theme toggle
       ---------------------------------------------------------- */
    const root = document.documentElement;

    const syncTheme = () => {
        $('#themeLbl').textContent =
            root.dataset.theme === 'dark' ? 'Light mode' : 'Dark mode';
    };

    $('#themeToggle').addEventListener('click', () => {
        root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem('gwqsh-theme', root.dataset.theme); } catch (e) {}
        syncTheme();
        closeMenus();
    });

    syncTheme();

    /* ----------------------------------------------------------
       Modals
       ---------------------------------------------------------- */
    function openModal(html, cls = '') {
        const last = document.activeElement;
        const bg = document.createElement('div');
        bg.className = 'modal-bg';
        bg.innerHTML = `<div class="modal ${cls}" role="dialog" aria-modal="true">${html}</div>`;
        document.getElementById('gracewell-quietshield-app').appendChild(bg);

        const close = () => {
            bg.remove();
            if (last && last.focus) last.focus();
        };

        bg.addEventListener('click', e => {
            if (e.target === bg || e.target.closest('[data-close]')) close();
        });
        bg.addEventListener('keydown', e => {
            if (e.key === 'Escape') close();
        });

        setTimeout(() => {
            const focusTarget = bg.querySelector('input,button:not(.close-btn)') || bg;
            focusTarget.focus();
        }, 10);

        return { el: bg, close };
    }

    /* ----------------------------------------------------------
       Pro modal / hero card
       ---------------------------------------------------------- */
    const PRO = [
        'Plugin & theme integrity scan (malware signatures)',
        'Scheduled scans',
        'Passkeys & forced 2FA by role',
        'Country blocking & shared IP reputation list',
        'Unlimited activity log history & CSV export',
        'WAF and one-click malware cleanup'
    ];

    const bullets = PRO.map(p =>
        `<li><span class="ck">${ico('check')}</span><span>${esc(p)}</span></li>`
    ).join('');

    $('#proBullets').innerHTML = bullets;

    document.addEventListener('click', e => {
        if (!e.target.closest('[data-pro]')) return;
        closeMenus();
        setNav(false);

        const m = openModal(
            `<div class="modal-h">
                <span class="tile t-amber"><svg class="i fill"><use href="#i-crown"/></svg></span>
                <h3>QuietShield Pro</h3>
                <button class="close-btn" data-close aria-label="Close">&times;</button>
            </div>
            <div class="modal-b">
                <p class="muted" style="margin-bottom:12px">Pro features are not available in version 1.0.0. Planned features include:</p>
                <ul class="pro-bullets">${bullets}</ul>
            </div>
            <div class="modal-f">
                <button class="btn btn-neutral" data-close>Maybe later</button>
                <button class="btn btn-primary" id="seePlans">Close</button>
            </div>`
        );

        $('#seePlans', m.el).onclick = () => {
            m.close();
        };
    });

    const heroPro = $('#heroProCard');
    try {
        if (localStorage.getItem('gwqsh-hide-pro') === '1') heroPro.remove();
    } catch (e) {}

    $('#closeHeroPro')?.addEventListener('click', () => {
        heroPro.remove();
        try { localStorage.setItem('gwqsh-hide-pro', '1'); } catch (e) {}
    });

    /* ----------------------------------------------------------
       Search palette (Ctrl/Cmd + K)
       ---------------------------------------------------------- */
    const PAGES = [
        ['Dashboard',        window.GWQSH_PAGES['dashboard'],       'grid'],
        ['Login Protection', window.GWQSH_PAGES['login-protection'],'lock'],
        ['Two-Factor',       window.GWQSH_PAGES['two-factor'],      'shield-check'],
        ['File Integrity',   window.GWQSH_PAGES['file-integrity'],  'file'],
        ['Hardening',        window.GWQSH_PAGES['hardening'],       'gear'],
        ['Activity Log',     window.GWQSH_PAGES['activity-log'],    'list'],
        ['Settings',         window.GWQSH_PAGES['settings'],        'gear']
    ];

    function openSearch() {
        closeMenus();
        setNav(false);

        const m = openModal(
            `<div class="search">
                <svg class="i"><use href="#i-search"/></svg>
                <input type="text" placeholder="Search pages…" aria-label="Search pages">
            </div>
            <ul></ul>`,
            'palette'
        );

        const inp = $('input', m.el);
        const ul = $('ul', m.el);
        let idx = 0;
        let items = [];

        const draw = () => {
            const q = inp.value.toLowerCase().trim();
            items = PAGES.filter(p => p[0].toLowerCase().includes(q));
            idx = Math.min(idx, Math.max(items.length - 1, 0));
            ul.innerHTML = items.length
                ? items.map((p, i) =>
                    `<li><a href="${p[1]}" class="${i === idx ? 'active' : ''}">
                        ${ico(p[2])}<span>${p[0]}</span><small>${p[1]}</small>
                    </a></li>`).join('')
                : '<li class="empty">No results</li>';
        };

        inp.addEventListener('input', () => { idx = 0; draw(); });
        inp.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown') {
                idx = Math.min(idx + 1, items.length - 1);
                draw();
                e.preventDefault();
            }
            if (e.key === 'ArrowUp') {
                idx = Math.max(idx - 1, 0);
                draw();
                e.preventDefault();
            }
            if (e.key === 'Enter' && items[idx]) location.href = items[idx][1];
        });

        draw();
    }

    $$('[data-open-search]').forEach(b => b.addEventListener('click', openSearch));

    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (!$('.palette')) openSearch();
        }
    });

    /* ----------------------------------------------------------
       Count-up animation for stat numbers
       ---------------------------------------------------------- */
    function countUp(el, to) {
        const s = performance.now();
        const d = 900;
        (function f(n) {
            const p = Math.min((n - s) / d, 1);
            el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(f);
        })(s);
    }

    /* ----------------------------------------------------------
       Real data initialization from backend
       ---------------------------------------------------------- */
    const D = window.GWQSH_DATA || {};
    const statPills = $$('.top-stats .stat-head .pill');
    if (statPills[0]) { statPills[0].textContent = D.score_components?.login ? 'Active' : 'Paused'; statPills[0].className = 'pill ' + (D.score_components?.login ? 'p-green' : 'p-gray'); }
    if (statPills[1]) { statPills[1].textContent = D.users_2fa ? 'Enabled' : 'Awaiting setup'; statPills[1].className = 'pill ' + (D.users_2fa ? 'p-green' : 'p-amber'); }
    if (statPills[2]) { const issues = Number(D.scan_summary?.modified || 0) + Number(D.scan_summary?.missing || 0) + Number(D.scan_summary?.suspicious || 0); statPills[2].textContent = !D.scan_summary?.total ? 'Not scanned' : issues ? 'Needs review' : 'Clean'; statPills[2].className = 'pill ' + (issues ? 'p-amber' : 'p-green'); }
    const H_MAP = {
        'Disable XML-RPC': 'xmlrpc',
        'Disable file editor': 'editor',
        'Hide WP version': 'version',
        'Block user enumeration': 'enum',
        'Security headers': 'headers',
        'Block PHP in uploads': 'uploads'
    };

    // Update stat card DOM targets with real initial data
    if (D.failed_24h !== undefined) {
        const el = document.querySelector('.card.stat:nth-child(1) [data-count]');
        if (el) el.dataset.count = D.failed_24h;
    }
    if (D.users_2fa !== undefined && D.users_total !== undefined) {
        const el = document.querySelector('.card.stat:nth-child(2) [data-count]');
        if (el) {
            el.dataset.count = D.users_2fa;
            el.parentElement.innerHTML = `<span data-count="${D.users_2fa}">${D.users_2fa}</span> / ${D.users_total}`;
        }
    }
    if (D.scan_summary) {
        const el = document.querySelector('.card.stat:nth-child(3) [data-count]');
        if (el) el.dataset.count = D.scan_summary.modified || 0;
        const ls = $('#lastScan');
        if (ls && D.scan_summary.last_date) ls.textContent = D.scan_summary.last_date;
    }
    if (D.total_events !== undefined) {
        const el = $('#statEvents');
        if (el) {
            el.dataset.count = D.total_events;
            el.textContent = D.total_events;
        }
    }

    /* ----------------------------------------------------------
       Count-up animation for stat numbers
       ---------------------------------------------------------- */
    function countUp(el, to) {
        const s = performance.now();
        const d = 900;
        (function f(n) {
            const p = Math.min((n - s) / d, 1);
            el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(f);
        })(s);
    }

    $$('[data-count]').forEach(el => countUp(el, +el.dataset.count));

    /* ----------------------------------------------------------
       Security score gauge
       ---------------------------------------------------------- */
    const CIRC = 2 * Math.PI * 62;

    function updateScore(score) {
        if (score === undefined) {
            const on = switches.filter(s => s.getAttribute('aria-checked') === 'true').length;
            const hardPct = switches.filter(s => s.getAttribute('aria-checked') === 'true').reduce((sum,s) => sum + ({xmlrpc:15,editor:15,version:10,enum:15,headers:33,uploads:12}[H_MAP[s.dataset.h]] || 0), 0);
            const c = D.score_components || {};
            score = Math.round((c.login || 0) * .2 + (c.tfa || 0) * .25 + (c.file || 0) * .15 + hardPct * .25 + (c.activity || 0) * .15);
        }

        $('#scoreBar').style.strokeDashoffset = CIRC * (1 - score / 100);
        $('#scoreVal').textContent = score;

        const s = $('#scoreStatus');
        s.textContent = score >= 85 ? 'Excellent' : score >= 70 ? 'Good' : 'Needs work';
        s.style.color = score >= 70 ? 'var(--green)' : 'var(--amber)';

        const onCount = switches.filter(sw => sw.getAttribute('aria-checked') === 'true').length;
        const hardPct = switches.filter(s => s.getAttribute('aria-checked') === 'true').reduce((sum,s) => sum + ({xmlrpc:15,editor:15,version:10,enum:15,headers:33,uploads:12}[H_MAP[s.dataset.h]] || 0), 0);
        $('#scoreHard').textContent = hardPct;
        $$('.score-item b').forEach((el, i) => { el.textContent = [D.score_components?.login || 0, D.score_components?.tfa || 0, D.score_components?.file || 0, hardPct, D.score_components?.activity || 0][i]; });
        $('#scoreHeadline').textContent =
            score >= 70 ? 'Your site is well protected' : 'Your site needs attention';
        $('#scoreHint').textContent =
            hardPct === 100
                ? 'Great job! All hardening options are enabled.'
                : 'Keep going! Enable more hardening options to further improve your security.';

        const h = $('#heroTitle');
        h.classList.toggle('warn', score < 70);
        h.querySelector('.hl').textContent = score < 70 ? 'at risk' : 'protected';
    }

    /* ----------------------------------------------------------
       Hardening checklist
       ---------------------------------------------------------- */
    const switches = $$('#hList .switch');

    // Sync switches with real data from backend
    if (D.hardening) {
        switches.forEach(s => {
            const key = H_MAP[s.dataset.h];
            if (key && D.hardening[key] !== undefined) {
                s.setAttribute('aria-checked', D.hardening[key] ? 'true' : 'false');
            }
        });
    }

    function syncHardening() {
        const on = switches.filter(s => s.getAttribute('aria-checked') === 'true').length;
        const cls = 'pill ' + (on >= 5 ? 'p-green' : 'p-amber');

        $('#hBadge').textContent = `${on} / 6 Enabled`;
        $('#hBadge').className = cls;

        $('#statHardPill').textContent = `${on} / 6`;
        $('#statHardPill').className = cls;

        $('#statHardNum').textContent = on;
        updateScore();
    }

    switches.forEach(s => s.addEventListener('click', async () => {
        const v = s.getAttribute('aria-checked') !== 'true';
        s.setAttribute('aria-checked', v);
        syncHardening();


        const feat = H_MAP[s.dataset.h];
        if (feat && window.GWQSH && GWQSH.ajax) {
            const savedResponse = await GWQSH.ajax('toggle_hardening', { feature: feat, on: v ? 1 : 0 });
            if (!savedResponse.success) { s.setAttribute('aria-checked', !v); syncHardening(); toast(savedResponse.data?.message || 'Could not save protection', 'err'); return false; }
            switches.forEach(button => button.setAttribute('aria-checked', !!savedResponse.data.hardening[H_MAP[button.dataset.h]])); syncHardening();
        }
        toast(`${s.dataset.h} ${v ? 'enabled' : 'disabled'}`, v ? 'ok' : 'warn');

        addActivity(
            window.GWQSH_CONFIG?.user?.login || 'admin',
            v ? 'Settings updated' : 'Setting disabled',
            `${s.dataset.h} ${v ? 'on' : 'off'}`,
            D.client_ip || '',
            v ? 'blue' : 'amber'
        );
    }));

    requestAnimationFrame(() => requestAnimationFrame(syncHardening));

    /* ----------------------------------------------------------
       Recent activity
       ---------------------------------------------------------- */
    let ACT = [];
    if (D.recent_activity && D.recent_activity.length) {
        ACT = D.recent_activity.map(row => {
            const t = new Date(row.event_time);
            const tStr = t.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' +
                         t.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            const col = row.event_type === 'Security' ? 'red' : (row.event_type === 'User' ? 'green' : 'blue');
            return [tStr, row.user_login, row.event_name, row.details, row.ip_address, col];
        });
    } else {
        ACT = [];
    }

    const COL = { green: '#22C55E', red: '#EF4444', blue: '#2F5BEA', amber: '#F59E0B' };

    const rowHTML = ([t, u, ev, d, ip, c]) => `<tr>
        <td class="c-time muted">${esc(t)}</td>
        <td class="c-user">${u === 'â€”' ? '<span class="muted">â€”</span>' : '<b>' + esc(u) + '</b>'}</td>
        <td class="c-ev"><span class="ev ev-${c}"><i class="dot" style="background:${COL[c] || COL.blue}"></i>${esc(ev)}</span></td>
        <td class="c-det">${esc(d)}</td>
        <td class="c-ip ${ip === 'â€”' ? 'muted' : 'mono'}">${esc(ip)}</td>
    </tr>`;

    const drawActivity = () => {
        $('#recentBody').innerHTML = ACT.slice(0, 6).map(rowHTML).join('');
    };

    function nowLabel(full) {
        const d = new Date();
        const m = d.toLocaleString('en-US', { month: 'short' });
        const t = d.toLocaleString('en-US', { hour: '2-digit', minute: '2-digit' });
        return full
            ? `${m} ${d.getDate()}, ${d.getFullYear()}, ${t}`
            : `${m} ${d.getDate()}, ${t}`;
    }

    function addActivity(u, ev, d, ip, c) {
        ACT.unshift([nowLabel(), u, ev, d, ip, c]);
        drawActivity();
        const e = $('#statEvents');
        if (e) e.textContent = (+e.textContent || 0) + 1;
    }

    drawActivity();

    /* ----------------------------------------------------------
       Locked IPs
       ---------------------------------------------------------- */
    let LOCKED = [];
    if (D.locked_ips && Array.isArray(D.locked_ips)) {
        LOCKED = D.locked_ips.map(x => [x.ip, x.attempts, x.until]);
    }

    function drawLocked() {
        const n = LOCKED.length;

        $('#lockedBody').innerHTML = n
            ? LOCKED.map(([ip, a, u]) => `<tr>
                <td class="c-ip mono">${esc(ip)}</td>
                <td class="c-att">${a}</td>
                <td class="c-until">${esc(u)}</td>
                <td class="c-act" style="text-align:right">
                    <button class="unlock-btn" type="button" data-unlock="${esc(ip)}">Unlock</button>
                </td>
            </tr>`).join('')
            : '<tr class="empty-row"><td colspan="4" class="empty">No IPs are currently locked.</td></tr>';

        $('#lockedPill').textContent = `${n} IP${n === 1 ? '' : 's'}`;
        $('#lockedPill').className = 'pill ' + (n ? 'p-red' : 'p-green');
        $('#statLocked').textContent = n;
        if ($('#notifLockTxt')) $('#notifLockTxt').textContent =
            n ? `${n} IP${n === 1 ? '' : 's'} locked out` : 'No IPs locked out';
    }

    $('#lockedBody').addEventListener('click', async e => {
        const b = e.target.closest('[data-unlock]');
        if (!b) return;

        const ip = b.dataset.unlock;


        if (window.GWQSH && GWQSH.ajax) {
            const savedResponse = await GWQSH.ajax('unlock_ip', { ip });
            if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
        }

        setTimeout(() => {
            LOCKED.splice(LOCKED.findIndex(r => r[0] === ip), 1);
            drawLocked();
            toast(`${ip} unlocked`);
            addActivity(window.GWQSH_CONFIG?.user?.login || 'admin', 'IP unlocked', 'Manual unlock', ip, 'blue');
        }, 220);
    });

    drawLocked();

    /* ----------------------------------------------------------
       IP allowlist
       ---------------------------------------------------------- */
    let ALLOW = Array.isArray(D.allow_ips) ? D.allow_ips.slice() : [];

    function drawAllow() {
        $('#allowChips').innerHTML =
            ALLOW.map(ip => `<span class="chip">${esc(ip)}</span>`).join('') ||
            '<span class="muted">No allowlisted IPs</span>';

        $('#allowPill').textContent = `${ALLOW.length} IP${ALLOW.length === 1 ? '' : 's'}`;
        $('#statAllow').textContent = ALLOW.length;
    }

    const validIP = v =>
        /^(25[0-5]|2[0-4]\d|1?\d?\d)(\.(25[0-5]|2[0-4]\d|1?\d?\d)){3}(\/([0-9]|[12]\d|3[0-2]))?$/.test(v) ||
        (/^[0-9a-f:]+$/i.test(v) && v.includes(':'));

    $('#manageAllow').addEventListener('click', () => {
        const m = openModal(
            `<div class="modal-h">
                <span class="tile t-green">${ico('shield-check')}</span>
                <h3>Manage IP Allowlist</h3>
                <button class="close-btn" data-close aria-label="Close">&times;</button>
            </div>
            <div class="modal-b">
                <p class="muted" style="margin-bottom:12px">Allowlisted IPs are never locked out by login protection.</p>
                <form id="allowForm" style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
                    <input class="field" style="flex:1 1 180px;height:38px"
                           placeholder="e.g. 203.0.113.10 or 10.0.0.0/24"
                           aria-label="IP address">
                    <button class="btn btn-primary" type="submit">Add</button>
                </form>
                <div class="chips" id="allowEdit"></div>
            </div>
            <div class="modal-f">
                <button class="btn btn-neutral" data-close>Done</button>
            </div>`
        );

        const draw = () => {
            $('#allowEdit', m.el).innerHTML = ALLOW.map(ip =>
                `<span class="chip">${esc(ip)}<button type="button" data-rm="${esc(ip)}"
                    aria-label="Remove ${esc(ip)}">&times;</button></span>`
            ).join('');
            drawAllow();
        };

        $('#allowEdit', m.el).addEventListener('click', async e => {
            const b = e.target.closest('[data-rm]');
            if (!b) return;
            const ip = b.dataset.rm;
            ALLOW = ALLOW.filter(x => x !== ip);
            draw();
            toast(`${ip} removed`, 'warn');
            if (window.GWQSH && GWQSH.ajax) {
                const savedResponse = await GWQSH.ajax('remove_allow_ip', { ip });
                if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
            }
        });

        $('#allowForm', m.el).addEventListener('submit', async e => {
            e.preventDefault();
            const inp = $('input', m.el);
            const v = inp.value.trim();

            if (!validIP(v)) return toast('Please enter a valid IP address', 'err');
            if (ALLOW.includes(v)) return toast('That IP is already allowlisted', 'warn');

            ALLOW.push(v);
            inp.value = '';
            draw();
            toast(`${v} added to allowlist`);

            if (window.GWQSH && GWQSH.ajax) {
                const savedResponse = await GWQSH.ajax('add_allow_ip', { ip: v });
                if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
            }
        });

        draw();
    });

    drawAllow();

    /* ----------------------------------------------------------
       Run scan
       ---------------------------------------------------------- */
    $('#runScanBtn').addEventListener('click', async () => {
        const btn = $('#runScanBtn');
        btn.disabled = true;
        btn.innerHTML = `<svg class="i spin"><use href="#i-refresh"/></svg><span>Scanning…</span>`;

        const m = openModal(
            `<div class="modal-h">
                <span class="tile t-blue">${ico('file')}</span>
                <h3>Scanning core files</h3>
            </div>
            <div class="modal-b">
                <p class="muted" id="scanTxt">Comparing checksums with WordPress.org…</p>
                <div class="progress"><i id="scanBar"></i></div>
                <small class="muted" id="scanPct">0%</small>
            </div>
            <div class="modal-f"></div>`
        );

        $('#scanBar', m.el).style.width = '100%';
        $('#scanBar', m.el).style.opacity = '.35';
        let elapsed = 0;
        $('#scanPct', m.el).textContent = 'In progress';
        const iv = setInterval(() => { $('#scanTxt', m.el).textContent = 'Scanning files… ' + (++elapsed) + ' seconds elapsed'; }, 1000);

        // Run real scan via backend AJAX
        let scanRes = null;
        if (window.GWQSH && GWQSH.ajax) {
            scanRes = await GWQSH.ajax('run_scan');
        }

        clearInterval(iv);
        if (!scanRes?.success) { m.close(); btn.disabled = false; btn.innerHTML = '<span>Run New Scan</span>' + ico('arrow'); toast(scanRes?.data?.message || 'Scan failed', 'warn'); return; }
        D.scan_summary = scanRes.data.summary;
        D.score_components.file = scanRes.data.summary.total ? Math.max(0, Math.round(scanRes.data.summary.clean / scanRes.data.summary.total * 100)) : 0;
        updateScore();

        $('#scanBar', m.el).style.width = '100%';
        $('#scanPct', m.el).textContent = '100%';

        setTimeout(() => {
            m.close();
            btn.disabled = false;
            btn.innerHTML = `<span>Run New Scan</span>${ico('arrow')}`;
            const timeStr = nowLabel(true);
            $('#lastScan').textContent = timeStr;

            const mod = scanRes?.data?.result?.modified ?? 0;
            const sus = scanRes?.data?.result?.suspicious ?? 0;
            const issues = mod + sus + Number(scanRes.data.result.missing || 0);
            const msg = issues ? `Scan complete · ${issues} issue(s) found` : 'Scan complete · 0 modified files';
            toast(msg, issues ? 'warn' : 'ok');
            addActivity('system', 'File scan completed', `${issues} file issues`, 'â€”', issues ? 'amber' : 'green');
        }, 350);
    });

    /* ----------------------------------------------------------
       Chart (SVG absolutely positioned so it never forces card wider)
       ---------------------------------------------------------- */
    let cachedChart = null;

    async function fetchChartData(days) {
        if (window.GWQSH && GWQSH.ajax) {
            const res = await GWQSH.ajax('get_dashboard_data', { days });
            if (res.success && res.data && res.data.chart_data) {
                return res.data.chart_data;
            }
        }
        // Fallback default curve
        const labels = [], failed = [], files = [];
        for (let i = days - 1; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            labels.push(d.toLocaleString('en-US', { month: 'short', day: 'numeric' }));
            failed.push(0);
            files.push(0);
        }
        return { labels, failed, files };
    }

    async function renderChart() {
        const el = $('#activityChart');
        const tip = $('#chartTip');
        const days = +$('#chartRange').value;
        const d = await fetchChartData(days);

        const W = el.clientWidth;
        const H = el.clientHeight;
        if (!W || !H) return;

        const P = { l: 26, r: 6, t: 8, b: 22 };
        const n = d.failed.length;
        const max = Math.max(10, Math.ceil(Math.max(...d.failed, ...d.files, 5) / 10) * 10);

        const x = i => P.l + i * (W - P.l - P.r) / Math.max(1, n - 1);
        const y = v => P.t + (1 - v / max) * (H - P.t - P.b);

        const line = a => a.map((v, i) =>
            (i ? 'L' : 'M') + x(i).toFixed(1) + ',' + y(v).toFixed(1)
        ).join('');

        let g = '';
        for (let v = 0; v <= max; v += max / 5) {
            g += `<line class="gl" x1="${P.l}" x2="${W - P.r}" y1="${y(v)}" y2="${y(v)}"/>`;
            g += `<text class="ax" x="${P.l - 6}" y="${y(v) + 4}" text-anchor="end">${Math.round(v)}</text>`;
        }

        const every = Math.ceil(n / Math.max(2, Math.floor((W - P.l - P.r) / 46)));
        let xl = '';
        d.labels.forEach((l, i) => {
            if ((n - 1 - i) % every === 0) {
                xl += `<text class="ax" x="${x(i)}" y="${H - 5}" text-anchor="middle">${l}</text>`;
            }
        });

        const dots = n <= 14
            ? d.failed.map((v, i) =>
                `<circle cx="${x(i)}" cy="${y(v)}" r="3.2" fill="var(--surface)" stroke="#2F5BEA" stroke-width="2"/>`
              ).join('') +
              d.files.map((v, i) =>
                `<circle cx="${x(i)}" cy="${y(v)}" r="2.8" fill="var(--surface)" stroke="#2DD4BF" stroke-width="2"/>`
              ).join('')
            : '';

        $$('svg', el).forEach(s => s.remove());

        el.insertAdjacentHTML('afterbegin',
            `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" role="img"
                 aria-label="Failed login attempts and file changes over the last ${days} days">
                <defs>
                    <linearGradient id="aF" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#2F5BEA" stop-opacity=".22"/>
                        <stop offset="1" stop-color="#2F5BEA" stop-opacity="0"/>
                    </linearGradient>
                    <linearGradient id="aC" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#2DD4BF" stop-opacity=".18"/>
                        <stop offset="1" stop-color="#2DD4BF" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                ${g}${xl}
                <path d="${line(d.failed)}L${x(n - 1)},${y(0)}L${x(0)},${y(0)}Z" fill="url(#aF)"/>
                <path d="${line(d.files)}L${x(n - 1)},${y(0)}L${x(0)},${y(0)}Z" fill="url(#aC)"/>
                <path d="${line(d.failed)}" fill="none" stroke="#2F5BEA" stroke-width="2.2" stroke-linejoin="round"/>
                <path d="${line(d.files)}"  fill="none" stroke="#2DD4BF" stroke-width="2"   stroke-linejoin="round"/>
                ${dots}
                <line id="hoverLine" x1="0" x2="0" y1="${P.t}" y2="${H - P.b}"
                      stroke="var(--faint)" stroke-dasharray="3 3" opacity="0"/>
                <rect x="${P.l}" y="0" width="${W - P.l - P.r}" height="${H}"
                      fill="transparent" id="hoverZone"/>
            </svg>`
        );

        const zone = $('#hoverZone', el);
        const hl = $('#hoverLine', el);

        const show = cx => {
            const r = el.getBoundingClientRect();
            const i = Math.max(0, Math.min(n - 1,
                Math.round((cx - r.left - P.l) / ((W - P.l - P.r) / (n - 1)))));

            hl.setAttribute('x1', x(i));
            hl.setAttribute('x2', x(i));
            hl.setAttribute('opacity', 1);

            tip.innerHTML =
                `<p class="t">${d.labels[i]}</p>
                 <div><i class="dot" style="background:#2F5BEA"></i>Failed logins<b>${d.failed[i]}</b></div>
                 <div><i class="dot" style="background:#2DD4BF"></i>File changes<b>${d.files[i]}</b></div>`;

            tip.style.left = Math.max(75, Math.min(W - 75, x(i))) + 'px';
            tip.style.top = Math.max(62, y(Math.max(d.failed[i], d.files[i]))) + 'px';
            tip.classList.add('show');
        };

        zone.addEventListener('mousemove', e => show(e.clientX));
        zone.addEventListener('touchstart', e => show(e.touches[0].clientX), { passive: true });
        zone.addEventListener('mouseleave', () => {
            tip.classList.remove('show');
            hl.setAttribute('opacity', 0);
        });
    }

    $('#chartRange').addEventListener('change', renderChart);

    let rt;
    new ResizeObserver(() => {
        clearTimeout(rt);
        rt = setTimeout(renderChart, 60);
    }).observe($('#activityChart'));

    renderChart();

    /* ----------------------------------------------------------
       Topbar "scrolled" state â€” toggles solid background
       ---------------------------------------------------------- */
    const tb = document.querySelector('.topbar');
    const hero = document.querySelector('.hero');

    const onScroll = () => tb.classList.toggle(
        'scrolled',
        scrollY > (hero ? hero.offsetHeight - 70 : 10)
    );

    onScroll();
    addEventListener('scroll', onScroll, { passive: true });
    addEventListener('resize', onScroll);
})();
