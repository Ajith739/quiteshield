/* ============================================================
   Gracewell QuietShield — Activity Log page logic
   Depends on: qs-core.js (loaded before this, both deferred)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    const { $, $$, toast, modal, confirm, esc, fmt } = GWQSH;
    const D = window.GWQSH_DATA || {};

    const TYPE = {
        Security: ['#EF4444', 'p-red'],
        User: ['#3B82F6', 'p-blue'],
        Plugin: ['#8B5CF6', 'p-purple'],
        System: ['#F59E0B', 'p-amber']
    };

    const IC = {
        shield: '<svg class="i" viewBox="0 0 24 24"><path fill="currentColor" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        shieldx: '<svg class="i" viewBox="0 0 24 24"><path fill="currentColor" d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m14.5 9.5-5 5m0-5 5 5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>',
        login: '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/></svg>',
        scan: '<svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" fill="currentColor"/><circle cx="12" cy="12" r="3.5" fill="#fff"/></svg>',
        plug: '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M12 22v-5M9 8V2M15 8V2"/><path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z" fill="currentColor"/></svg>',
        gear: '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" fill="currentColor"/><circle cx="12" cy="12" r="3" fill="#fff" stroke="none"/></svg>',
        user: '<svg class="i" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="currentColor"/><circle cx="12" cy="10" r="3.2" fill="#fff"/><path d="M6.5 18.5a6 6 0 0 1 11 0" fill="#fff"/></svg>'
    };

    const EV = {
        'Login successful': ['User', 'login', '#16A34A'],
        'Login attempt blocked': ['Security', 'shieldx', '#EF4444'],
        'File scan completed': ['System', 'scan', '#F59E0B'],
        'Plugin updated': ['Plugin', 'plug', '#8B5CF6'],
        'Plugin installed': ['Plugin', 'plug', '#8B5CF6'],
        '2FA enabled': ['Security', 'shield', '#16A34A'],
        '2FA disabled': ['Security', 'shieldx', '#EF4444'],
        'Theme updated': ['Plugin', 'plug', '#8B5CF6'],
        'Settings changed': ['System', 'gear', '#F59E0B'],
        'User role changed': ['User', 'user', '#3B82F6'],
        'Suspicious file detected': ['Security', 'shieldx', '#EF4444'],
        'Core file modified': ['Security', 'scan', '#F59E0B'],
        'Profile updated': ['User', 'user', '#3B82F6'],
        'Scheduled task ran': ['System', 'gear', '#F59E0B'],
        'IP locked out': ['Security', 'shieldx', '#EF4444'],
        'IP unlocked': ['Security', 'shield', '#3B82F6'],
        'User logged out': ['User', 'login', '#64748B']
    };

    $('#evSel').innerHTML += Object.keys(EV).sort().map(n => '<option>' + esc(n) + '</option>').join('');

    let currentItems = Array.isArray(D.items) ? D.items : [];
    let currentTotal = D.total || currentItems.length;
    let currentBreakdown = D.breakdown || { Security: 0, User: 0, Plugin: 0, System: 0 };

    let tab = '', page = 1, per = 10, asc = false, range = 7;

    const P = new URLSearchParams(location.search);
    const typeMap = { security: 'Security', user: 'User', users: 'User', plugin: 'Plugin', system: 'System' };
    if (P.get('type') && typeMap[P.get('type')]) tab = typeMap[P.get('type')];
    if (P.get('user')) $('#userSel').value = P.get('user');

    const fmtT = s => {
        if (!s) return '—';
        const d = new Date(String(s).replace(' ', 'T'));
        if (!Number.isFinite(d.getTime())) return 'Unknown time';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    };

    function pagerHtml(p, n) {
        const b = (v, l, dis, cur) =>
            '<button type="button" data-p="' + v + '"' +
            (dis ? ' disabled' : '') +
            (cur ? ' aria-current="page"' : '') +
            (typeof l === 'string' && l.startsWith('<') ? ' aria-label="' + (v < p ? 'Previous' : 'Next') + ' page"' : '') +
            '>' + l + '</button>';

        const L = '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>';
        const R = '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m9 18 6-6-6-6"/></svg>';

        let s = b(p - 1, L, p === 1);
        const set = new Set([1, n, p - 1, p, p + 1]);
        if (p <= 3) [2, 3, 4, 5].forEach(x => set.add(x));
        if (p >= n - 2) [n - 1, n - 2, n - 3].forEach(x => set.add(x));
        const list = [...set].filter(x => x >= 1 && x <= n).sort((a, b) => a - b);
        let prev = 0;
        list.forEach(x => {
            if (x - prev > 1) s += '<span class="gap">…</span>';
            s += b(x, x, false, x === p);
            prev = x;
        });
        return s + b(p + 1, R, p === n);
    }

    async function fetchLogs() {
        if (window.GWQSH && GWQSH.ajax) {
            const res = await GWQSH.ajax('get_activity_logs', {
                days: range,
                type: tab,
                event: $('#evSel').value,
                user: $('#userSel').value,
                search: $('#q').value.trim(),
                order: asc ? 'ASC' : 'DESC',
                page_num: page,
                limit: per
            });
            if (res.success && res.data) {
                currentItems = res.data.items || [];
                currentTotal = res.data.total || 0;
                if (res.data.breakdown) currentBreakdown = res.data.breakdown;
            } else { toast(res.data?.message || 'Could not load activity logs', 'err'); return; }
        }
        drawUI();
    }

    function drawUI() {
        const n = Math.max(1, Math.ceil(currentTotal / per));
        page = Math.min(page, n);

        $('#body').innerHTML = currentItems.length
            ? currentItems.map(e => {
                const v = EV[e.event_name] || ['System', 'gear', '#F59E0B'];
                const tInfo = TYPE[e.event_type] || ['#64748B', 'p-gray'];
                return '<tr data-id="' + e.id + '">' +
                    '<td class="log-time" data-label="Time">' + esc(fmtT(e.event_time)).replace(/, (?=[0-9]{1,2}:)/, '<span>') + '</span>' + '</td>' +
                    '<td data-label="Event"><span class="ev" style="color:' + v[2] + '">' + (IC[v[1]] || IC.gear) +
                    '<span style="color:var(--ink)">' + esc(e.event_name) + '</span></span></td>' +
                    '<td class="det" data-label="Details"><span class="log-details">' + esc(e.details || 'No additional details.') + '</span>' + (String(e.details || '').length > 100 ? '<button class="detail-toggle" type="button" data-more>View details</button>' : '') + '</td>' +
                    '<td data-label="User">' + esc(e.user_login) + '</td>' +
                    '<td data-label="IP address">' + esc(e.ip_address) + '</td>' +
                    '<td data-label="Type"><span class="type ' + tInfo[1] + '">' + esc(e.event_type) + '</span></td>' +
                    '<td data-label="Actions" style="text-align:center"><button class="kebab" data-more type="button" aria-label="Actions for ' +
                    esc(e.event_name) + ' at ' + fmtT(e.event_time) + '"><svg class="i" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg></button></td>' +
                    '</tr>';
            }).join('')
            : '<tr class="empty-row"><td colspan="7">No activity matches these filters. Widen the date range or clear the search.</td></tr>';

        $('#info').textContent = currentTotal
            ? 'Showing ' + ((page - 1) * per + 1) + ' to ' + Math.min(page * per, currentTotal) + ' of ' + fmt(currentTotal) + ' items'
            : '';
        $('#pager').innerHTML = n > 1 ? pagerHtml(page, n) : '';

        // Tab counts
        $$('[data-c]').forEach(b => {
            const t = b.dataset.c;
            const cVal = t ? (currentBreakdown[t] || 0) : (currentBreakdown.Security + currentBreakdown.User + currentBreakdown.Plugin + currentBreakdown.System);
            b.textContent = fmt(cVal);
        });
        $$('#tabs .seg-btn').forEach(x => x.setAttribute('aria-selected', x.dataset.t === tab));
        drawStats();
    }

    $('#pager').addEventListener('click', e => {
        const b = e.target.closest('[data-p]');
        if (!b) return;
        page = +b.dataset.p;
        fetchLogs();
        $('#log').scrollIntoView({ behavior: GWQSH.reduced ? 'auto' : 'smooth', block: 'start' });
    });

    $('#tabs').addEventListener('click', e => {
        const b = e.target.closest('.seg-btn');
        if (!b) return;
        tab = b.dataset.t;
        page = 1;
        fetchLogs();
    });

    let deb;
    ['#q', '#evSel', '#userSel'].forEach(s => $(s).addEventListener('input', () => {
        clearTimeout(deb);
        deb = setTimeout(() => { page = 1; fetchLogs(); }, 250);
    }));

    $('#sortT').addEventListener('click', e => {
        asc = !asc;
        e.currentTarget.querySelector('.i').style.transform = asc ? 'rotate(180deg)' : '';
        fetchLogs();
    });

    $('#menu-range').addEventListener('click', e => {
        const b = e.target.closest('[data-range]');
        if (!b) return;
        range = +b.dataset.range;
        const now = new Date();
        const s = new Date(now - range * 864e5);
        $('#rangeLbl').textContent = s.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' - ' + now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        $('#menu-range').classList.remove('open');
        page = 1;
        fetchLogs();
    });

    $('#body').addEventListener('click', e => {
        const b = e.target.closest('[data-more]');
        if (!b) return;
        const ev = currentItems.find(x => String(x.id) === b.closest('tr').dataset.id);
        if (!ev) return;

        const acts = [{ label: 'Close' }];
        if (ev.ip_address && ev.ip_address !== '—') acts.unshift({ label: 'Copy IP', fn: () => GWQSH.copy(ev.ip_address, 'IP address copied') });

        modal({
            title: esc(ev.event_name),
            body: '<dl class="kv2">' +
                '<dt>Time</dt><dd>' + esc(fmtT(ev.event_time)) + '</dd>' +
                '<dt>Details</dt><dd>' + esc(ev.details) + '</dd>' +
                '<dt>User</dt><dd>' + esc(ev.user_login) + '</dd>' +
                '<dt>IP address</dt><dd>' + esc(ev.ip_address) + '</dd>' +
                '<dt>Type</dt><dd>' + esc(ev.event_type) + '</dd>' +
                '</dl>',
            actions: acts
        });
    });

    $('#export').addEventListener('click', () => {
        const q = v => '"' + String(v).replace(/"/g, '""') + '"';
        GWQSH.download(
            'quietshield-activity-log.csv',
            ['Time,Event,Details,User,IP Address,Type']
                .concat(currentItems.map(e => [e.event_time, e.event_name, e.details, e.user_login, e.ip_address, e.event_type].map(q).join(',')))
                .join('\n'),
            'text/csv'
        );
        toast(fmt(currentItems.length) + ' events exported to CSV');
    });

    /* stats donut */
    const LBL = { Security: 'Security', User: 'User', Plugin: 'Plugin/Theme', System: 'System' };

    function drawStats() {
        GWQSH.labelTable($('#body').closest('table'));
        const tot = Math.max(1, (currentBreakdown.Security || 0) + (currentBreakdown.User || 0) + (currentBreakdown.Plugin || 0) + (currentBreakdown.System || 0));
        const segs = ['Security', 'User', 'Plugin', 'System'].map(t => ({
            label: LBL[t],
            color: TYPE[t][0],
            value: currentBreakdown[t] || 0
        }));

        GWQSH.donut($('#donut'),
            segs.map(s => Object.assign({}, s, { value: Math.max(s.value, .0001) })),
            { size: 150, width: 30, gap: 2.5, aria: 'Events by type' });

        GWQSH.setCount($('#dTotal'), tot === 1 && !currentItems.length ? 0 : tot);

        $('#dLegend').innerHTML = segs.map(s =>
            '<li><i class="dot" style="color:' + s.color + '"></i><span>' + s.label + '</span>' +
            '<b>' + s.value + ' (' + (s.value / tot * 100).toFixed(1) + '%)</b></li>'
        ).join('');
    }

    $('#statRange').addEventListener('change', e => {
        range = +e.target.value;
        fetchLogs();
    });

    $('#viewSec').addEventListener('click', () => {
        tab = 'Security';
        page = 1;
        fetchLogs();
        $('#log').scrollIntoView({ behavior: GWQSH.reduced ? 'auto' : 'smooth' });
    });

    drawUI();
});
