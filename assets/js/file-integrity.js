/* ============================================================
   Gracewell QuietShield — File Integrity page logic
   Depends on: qs-core.js (loaded before this, both deferred)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    const { $, $$, toast, modal, confirm, esc, fmt } = GWQSH;
    const D = window.GWQSH_DATA || {};

    /* ---------- data ---------- */
    let files = Array.isArray(D.issues) ? D.issues.map((r, i) => ({
        id: Number(r.id !== undefined ? r.id : i),
        path: r.path,
        type: r.type,
        st: r.st,
        det: r.det,
        date: r.date
    })) : [];

    $('#kvFiles').textContent = fmt(Number(D.summary?.total || 0));
    $('#kvDur').textContent = Number(D.summary?.duration || 0) + ' seconds';
    $('#lastDate').textContent = D.summary?.last_date || 'Never';
    let tab = '';
    let page = 1;
    let per = 7;
    let asc = false;
    let sel = new Set();
    let TOTAL = D.summary?.total ?? 0;

    const fmtDate = s => {
        if (!s) return '—';
        const d = new Date(s);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    };

    const pill = { Modified: 'p-amber', Missing: 'p-red', Suspicious: 'p-red' };
    const action = { Modified: 'View Diff', Missing: 'Restore', Suspicious: 'Quarantine' };

    function counts() {
        return { Modified: Number(D.summary?.modified || 0), Missing: Number(D.summary?.missing || 0), Suspicious: Number(D.summary?.suspicious || 0) };
    }

    function refreshSummary() {
        const scopes = D.summary?.scope_counts || {};
        $$('.stats .stat-head .pill').forEach((el, i) => {
            const b = D.summary?.breakdown || {};
            const n = [{ a: 'core-mod', b: 'core-miss' }, { a: 'pl-mod', b: 'pl-sus' }, { a: 'th-mod', b: 'th-sus' }, { a: 'up-php' }][i];
            const alerts = Number(b[n.a] || 0) + Number(b[n.b] || 0);
            el.textContent = !TOTAL ? 'Not scanned' : alerts ? alerts + ' alerts' : 'Checked';
            el.className = 'pill ' + (alerts ? 'p-amber' : 'p-green');
        });
        $$('.stats .stat [data-count]').forEach((el, i) => GWQSH.setCount(el, [Number(D.summary?.breakdown?.['core-ok'] || 0) + Number(D.summary?.breakdown?.['core-mod'] || 0) + Number(D.summary?.breakdown?.['core-miss'] || 0), Number(scopes.Plugin || 0), Number(scopes.Theme || 0), Number(scopes.Uploads || 0)][i]));
        const c = counts(), issues = c.Modified + c.Missing + c.Suspicious, clean = Math.max(0, TOTAL - issues);

        $$('[data-n]').forEach(el => el.textContent = el.dataset.n === 'all' ? issues : (c[el.dataset.n] || 0));

        GWQSH.setCount($('#nClean'), clean);
        $('#nMod').textContent = c.Modified;
        $('#nMiss').textContent = c.Missing;
        $('#nSus').textContent = c.Suspicious;

        const pct = TOTAL ? +(clean / TOTAL * 100).toFixed(1) : 0;
        GWQSH.ring($('#cleanRing'), pct);
        GWQSH.setCount($('#cleanPct'), pct, 1);

        $('#sumP').textContent = !TOTAL ? 'No file scan has run yet.' : issues
            ? 'We checked ' + fmt(TOTAL) + ' files and found ' + issues + ' item' + (issues === 1 ? '' : 's') + ' that need attention.'
            : 'We scanned ' + fmt(TOTAL) + ' files and found nothing that needs attention.';
        $('#sumHead').textContent = !TOTAL ? 'Run your first file scan' : c.Suspicious ? 'Your site needs a look' : 'Your site looks good!';

        const by = (t, s) => Number(D.summary?.breakdown?.[({ Core: { Modified: 'core-mod', Missing: 'core-miss' }, Plugin: { Modified: 'pl-mod', Suspicious: 'pl-sus' }, Theme: { Modified: 'th-mod', Suspicious: 'th-sus' }, Uploads: { Suspicious: 'up-php' } })[t]?.[s]] || 0);
        const set = (k, v) => { const el = $('[data-k="' + k + '"]'); el && (el.textContent = fmt(v)); };
        set('core-mod', by('Core', 'Modified'));
        set('core-miss', by('Core', 'Missing'));
        set('core-ok', D.summary?.breakdown?.['core-ok'] ?? 0);
        set('pl-mod', by('Plugin', 'Modified'));
        set('pl-sus', by('Plugin', 'Suspicious'));
        set('pl-ok', D.summary?.breakdown?.['pl-ok'] ?? 0);
        set('th-mod', by('Theme', 'Modified'));
        set('th-sus', by('Theme', 'Suspicious'));
        set('th-ok', D.summary?.breakdown?.['th-ok'] ?? 0);
        set('up-php', by('Uploads', 'Suspicious'));
        set('up-ok', D.summary?.breakdown?.['up-ok'] ?? 0);
    }

    function filtered() {
        const q = $('#fSearch').value.trim().toLowerCase();
        const loc = $('#fLoc').value;
        const st = $('#fStat').value || tab;
        return files
            .filter(f => (!q || f.path.toLowerCase().includes(q)) && (!loc || f.type === loc) && (!st || f.st === st))
            .sort((a, b) => {
                const x = a.date || '0', y = b.date || '0';
                return asc ? x.localeCompare(y) : y.localeCompare(x);
            });
    }

    function draw() {
        const rows = filtered();
        const pages = Math.max(1, Math.ceil(rows.length / per));
        page = Math.min(page, pages);
        const slice = rows.slice((page - 1) * per, page * per);

        function formatPath(p) {
            const parts = p.split('/');
            const file = parts.pop();
            const dir = parts.join('/') + (parts.length ? '/' : '');
            return '<span class="path-wrap" title="' + esc(p) + '"><span class="path-dir">' + esc(dir) + '</span><strong class="path-file">' + esc(file) + '</strong></span>';
        }

        $('#fBody').innerHTML = slice.length
            ? slice.map(f => '<tr data-id="' + f.id + '">' +
                '<td><input type="checkbox" class="checkbox" aria-label="Select ' + esc(f.path) + '"' + (sel.has(f.id) ? ' checked' : '') + '></td>' +
                '<td class="path">' + formatPath(f.path) + '</td>' +
                '<td>' + esc(f.type) + '</td>' +
                '<td><span class="pill p-status ' + (pill[f.st] || 'p-amber') + '">' + esc(f.st) + '</span></td>' +
                '<td class="details">' + esc(f.det) + '</td>' +
                '<td>' + fmtDate(f.date) + '</td>' +
                '<td style="text-align:center"><button class="btn btn-outline btn-sm" data-do="' + esc(f.st) + '" type="button" style="min-width:92px">' + (action[f.st] || 'Review') + '</button></td>' +
                '<td><button class="kebab" data-more aria-label="More actions for ' + esc(f.path) + '" type="button"><svg class="i" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg></button></td></tr>').join('')
            : '<tr class="empty-row"><td colspan="8">' + (files.length ? 'No files match these filters.' : 'All clear. Nothing needs attention right now.') + '</td></tr>';

        GWQSH.labelTable($('#fBody').closest('table'));
        $('#fInfo').textContent = rows.length
            ? 'Showing ' + ((page - 1) * per + 1) + ' to ' + Math.min(page * per, rows.length) + ' of ' + rows.length + ' files'
            : '';
        const c = counts();
        if (c.Modified + c.Missing + c.Suspicious > files.length) $('#fInfo').textContent += ' (latest ' + files.length + ' findings loaded; summary includes all findings)';

        let p = '<button type="button" data-p="' + (page - 1) + '" aria-label="Previous page"' + (page === 1 ? ' disabled' : '') + '><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg></button>';
        for (let i = Math.max(1, page - 2); i <= Math.min(pages, page + 2); i++) {
            p += '<button type="button" data-p="' + i + '"' + (i === page ? ' aria-current="page"' : '') + '>' + i + '</button>';
        }
        p += '<button type="button" data-p="' + (page + 1) + '" aria-label="Next page"' + (page === pages ? ' disabled' : '') + '><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="m9 18 6-6-6-6"/></svg></button>';
        $('#fPager').innerHTML = pages > 1 ? p : '';

        $('#checkAll').checked = slice.length > 0 && slice.every(f => sel.has(f.id));
        $('#bulk').classList.toggle('hide', !sel.size);
        $('#bulkN').textContent = sel.size + ' selected';
    }

    async function removeFiles(ids, msg, type = 'ok', operation = 'mark_reviewed') {
        const gone = files.filter(f => ids.includes(f.id));
        for (const item of gone) {
            if (window.GWQSH && GWQSH.ajax) {
                const res = await GWQSH.ajax(operation, { path: item.path });
                if (!res.success) { draw(); refreshSummary(); toast(res.data?.message || 'File action failed', 'err'); return false; }
                if (res.data?.summary) D.summary = res.data.summary;
                files = files.filter(f => f.id !== item.id);
                if (Array.isArray(res.data?.issues)) files = res.data.issues.map(r => ({ ...r, id: Number(r.id) }));
                sel.delete(item.id);
            }
        }
        files = files.filter(f => !ids.includes(f.id));
        ids.forEach(i => sel.delete(i));
        draw();
        refreshSummary();
        toast(msg, type);
    }

    $('#fBody').addEventListener('click', async e => {
        const tr = e.target.closest('tr[data-id]');
        if (!tr) return;
        const f = files.find(x => x.id === +tr.dataset.id);
        if (!f) return;

        if (e.target.matches('.checkbox')) {
            e.target.checked ? sel.add(f.id) : sel.delete(f.id);
            draw();
            return;
        }

        const b = e.target.closest('[data-do]');

        if (b && f.st === 'Modified') {
            b.disabled = true;
            let diffHtml = '<p class="muted">Loading diff from WordPress.org…</p>';
            if (window.GWQSH && GWQSH.ajax) {
                const res = await GWQSH.ajax('get_diff', { path: f.path });
                if (res.success && res.data && res.data.diff) {
                    diffHtml = res.data.diff;
                } else { diffHtml = '<p class="muted">' + esc(res.data?.message || 'No readable difference is available.') + '</p>'; }
            }
            b.disabled = false;

            modal({
                title: 'Changes in ' + esc(f.path.split('/').pop()),
                wide: true,
                body: '<p class="muted" style="font-size:13px;word-break:break-all;margin-bottom:12px">' + esc(f.path) + ' compared with the official WordPress.org file.</p>' +
                    diffHtml,
                actions: [
                    { label: 'Close' },
                    { label: 'Mark as reviewed', cls: 'btn-neutral', fn: () => removeFiles([f.id], f.path.split('/').pop() + ' marked as reviewed') },
                    {
                        label: 'Quarantine', cls: 'btn-danger', fn: async () => {
                            return removeFiles([f.id], f.path.split('/').pop() + ' quarantined', 'warn', 'quarantine_file');
                        }
                    },
                    {
                        label: f.type === 'Core' ? 'Restore original' : 'Reinstall ' + f.type.toLowerCase(), cls: 'btn-primary', fn: async () => {
                            return removeFiles([f.id], f.path.split('/').pop() + ' restored from official source', 'ok', 'restore_file');
                        }
                    }
                ]
            });
        } else if (b && f.st === 'Missing') {
            return removeFiles([f.id], f.path.split('/').pop() + ' restored', 'ok', 'restore_file');
        } else if (b && f.st === 'Suspicious') {
            confirm('Quarantine this file?',
                '<span class="code-inline" style="word-break:break-all">' + esc(f.path) + '</span> will be moved out of the web root and can\u2019t run.',
                'Quarantine',
                async () => {
                    return removeFiles([f.id], f.path.split('/').pop() + ' quarantined', 'warn', 'quarantine_file');
                },
                true);
        }
    });

    /* row "more" actions */
    $('#fBody').addEventListener('click', e => {
        const k = e.target.closest('[data-more]');
        if (!k) return;
        const f = files.find(x => x.id === +k.closest('tr').dataset.id);
        if (!f) return;

        const rowActions = [
            { label: 'Copy path', fn: () => GWQSH.copy(f.path, 'File path copied') }
        ];

        if (f.type === 'Core' && (f.st === 'Modified' || f.st === 'Missing')) {
            rowActions.push({
                label: 'Restore original',
                cls: 'btn-primary',
                fn: async () => {
                    return removeFiles([f.id], f.path.split('/').pop() + ' restored from official source', 'ok', 'restore_file');
                }
            });
        }

        if (f.st === 'Suspicious' || f.st === 'Modified') {
            rowActions.push({
                label: 'Quarantine file',
                cls: 'btn-danger',
                fn: async () => {
                    return removeFiles([f.id], f.path.split('/').pop() + ' quarantined', 'warn', 'quarantine_file');
                }
            });
        }

        rowActions.push({
            label: 'Mark as reviewed',
            cls: 'btn-neutral',
            fn: () => removeFiles([f.id], f.path.split('/').pop() + ' marked as reviewed')
        });

        modal({
            title: esc(f.path.split('/').pop()),
            body: '<p class="path" style="word-break:break-all;font-size:12.5px;padding:8px 12px;background:var(--bg-2);border:1px solid var(--line-2);border-radius:6px;margin:8px 0;line-height:1.4">' + esc(f.path) + '</p>' +
                '<p class="muted" style="margin-top:8px">' + f.type + ' · ' + f.st + ' · ' + fmtDate(f.date) + '</p>' +
                '<p style="font-size:12.5px;color:var(--ink-2);margin-top:6px">' + esc(f.det) + '</p>',
            actions: rowActions
        });
    });

    $('#checkAll').addEventListener('change', e => {
        const rows = filtered().slice((page - 1) * per, page * per);
        rows.forEach(f => e.target.checked ? sel.add(f.id) : sel.delete(f.id));
        draw();
    });

    $('#bulkIgnore').addEventListener('click', () => removeFiles([...sel], sel.size + ' files marked as reviewed'));
    $('#bulkClear').addEventListener('click', () => { sel.clear(); draw(); });

    $('#fPager').addEventListener('click', e => {
        const b = e.target.closest('[data-p]');
        if (!b) return;
        page = +b.dataset.p;
        draw();
    });

    ['#fSearch', '#fLoc', '#fStat'].forEach(s => $(s).addEventListener('input', () => { page = 1; draw(); }));

    $('#fTabs').addEventListener('click', e => {
        const b = e.target.closest('.seg-btn');
        if (!b) return;
        $$('#fTabs .seg-btn').forEach(x => x.setAttribute('aria-selected', x === b));
        tab = b.dataset.t;
        $('#fStat').value = '';
        page = 1;
        draw();
    });

    $('#sortDate').addEventListener('click', e => {
        asc = !asc;
        e.currentTarget.querySelector('.i').style.transform = asc ? 'rotate(180deg)' : '';
        draw();
    });

    /* ---------- tools ---------- */
    $('#toolReplace').addEventListener('click', () => {
        const ids = files.filter(f => f.type === 'Core' && f.st === 'Modified').map(f => f.id);
        if (!ids.length) { toast('No modified core files to replace', 'info'); return; }
        confirm('Replace ' + ids.length + ' modified core files?',
            'QuietShield downloads clean copies from WordPress.org and overwrites the modified files. A backup is kept in quarantine.',
            'Replace files',
            async () => {
                return removeFiles(ids, ids.length + ' core files replaced with clean copies', 'ok', 'restore_file');
            });
    });

    $('#toolQuar').addEventListener('click', () => {
        const ids = files.filter(f => f.st === 'Suspicious').map(f => f.id);
        if (!ids.length) { toast('No suspicious files to quarantine', 'info'); return; }
        confirm('Quarantine ' + ids.length + ' suspicious files?',
            'They\u2019ll be moved out of the web root so they can\u2019t run.',
            'Quarantine all',
            async () => {
                return removeFiles(ids, ids.length + ' files quarantined', 'warn', 'quarantine_file');
            },
            true);
    });

    $('#tipsMore').addEventListener('click', e => {
        const open = e.currentTarget.getAttribute('aria-expanded') !== 'true';
        e.currentTarget.setAttribute('aria-expanded', open);
        e.currentTarget.firstChild.textContent = open ? 'Show less ' : 'View All ';
        $$('#tipList .extra').forEach(x => x.classList.toggle('hide', !open));
        if (open && GWQSH.animOn()) GWQSH_Motion.from('#tipList .extra', { opacity: 0, y: -6, stagger: .05, duration: .3 });
    });

    /* ---------- chart ---------- */
    function data(days) {
        const L = [], T = [], C = [], M = [], S = [];
        const cutoff = Date.now() - days * 864e5;
        const history = (D.history || []).filter(row => new Date(row.scan_date.replace(' ', 'T')).getTime() >= cutoff).reverse();
        for (const row of history) {
            const d = new Date(row.scan_date.replace(' ', 'T'));
            L.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
            T.push(d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));
            C.push(Number(row.clean_files));
            M.push(Number(row.modified_files));
            S.push(Number(row.suspicious_files));
        }
        return { L, T, C, M, S };
    }

    let d = data(7);
    const ser = d => [
        { name: 'Clean', color: '#22C55E', data: d.C },
        { name: 'Modified', color: '#F59E0B', data: d.M, axis: 'right', area: false },
        { name: 'Suspicious', color: '#EF4444', data: d.S, axis: 'right', area: false }
    ];

    const chart = GWQSH.lineChart($('#scanChart'), {
        height: 176, labels: d.L,
        yMax: 6000, yStep: 2000, rMax: 60, rStep: 20,
        fmt: v => v ? v / 1000 + 'K' : 0,
        pin: 4, tipTitle: i => d.T[i],
        aria: 'Clean, modified and suspicious files per scan',
        series: ser(d)
    });

    $('#actRange').addEventListener('change', e => {
        d = data(+e.target.value);
        chart.update({ labels: d.L, pin: +e.target.value === 7 ? 4 : null, series: ser(d) });
    });

    /* ---------- manual scan ---------- */
    $('#runScan').addEventListener('click', async e => {
        const b = e.currentTarget, h = b.innerHTML;
        b.disabled = true;
        b.textContent = 'Scanning…';

        const pill = $('#scanPill');
        pill.className = 'pill p-blue';
        pill.textContent = 'Running';

        $('#scanProg').classList.remove('hide');
        const bar = $('#scanBar');
        bar.style.width = '0%';

        bar.style.width = '100%';
        bar.style.opacity = '.35';
        let elapsed = 0;
        $('#scanTxt').textContent = 'Scanning files…';
        const iv = setInterval(() => { $('#scanTxt').textContent = 'Scanning files… ' + (++elapsed) + ' seconds elapsed'; }, 1000);

        let scanRes = null;
        if (window.GWQSH && GWQSH.ajax) {
            scanRes = await GWQSH.ajax('run_scan');
        }

        clearInterval(iv);
        bar.style.width = '100%';

        if (!scanRes || !scanRes.success) {
            b.disabled = false;
            b.innerHTML = h;
            pill.className = 'pill p-red';
            pill.textContent = 'Scan failed';
            $('#scanProg').classList.add('hide');
            toast(scanRes?.data?.message || 'Scan could not complete.', 'warn');
            return;
        }

        setTimeout(() => {
            b.disabled = false;
            b.innerHTML = h;
            pill.className = 'pill p-green';
            pill.textContent = 'Completed';
            $('#scanProg').classList.add('hide');

            const now = new Date();
            $('#lastDate').textContent = now.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) +
                ' at ' + now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

            if (scanRes && scanRes.data) {
                D.summary = scanRes.data.summary;
                D.history = scanRes.data.history || D.history;
                if (scanRes.data.result) {
                    TOTAL = scanRes.data.result.total ?? TOTAL;
                    $('#kvDur').textContent = (scanRes.data.result.duration ?? 0) + ' seconds';
                }
                if (Array.isArray(scanRes.data.issues)) {
                    files = scanRes.data.issues.map((r, i) => ({
                        id: Number(r.id !== undefined ? r.id : i),
                        path: r.path,
                        type: r.type,
                        st: r.st,
                        det: r.det,
                        date: r.date
                    }));
                }
            }

            refreshSummary();
            d = data(+$('#actRange').value); chart.update({ labels: d.L, series: ser(d), pin: null });
            draw();
            toast('Scan complete. ' + files.length + ' item' + (files.length === 1 ? '' : 's') + ' need attention.', files.length ? 'warn' : 'ok');
        }, 400);
    });

    /* ---------- early guardian ---------- */
    const guardianVerdict = { trusted: ['p-green', 'trusted'], ok: ['p-green', 'clean'], review: ['p-amber', 'review'], neutralized: ['p-red', 'blocked'], block_failed: ['p-red', 'quarantine failed'], unreadable: ['p-amber', 'unreadable'], unknown: ['p-amber', 'pending'] };

    function guardianModal(g, msg, msgType) {
        g = g || {};
        const status = g.status || 'absent';
        const statusPill = { active: 'p-green', outdated: 'p-amber', modified: 'p-red', absent: 'p-amber' }[status] || 'p-amber';
        const statusText = { active: 'Active', outdated: 'Update available', modified: 'Modified - review required', absent: 'Not installed' }[status] || status;
        let body = '<p class="muted" style="margin:4px 0 10px">The Early Guardian is an optional must-use plugin. It checks must-use plugins before they load and quarantines only extremely-high-confidence malware; WordPress itself never stops working.</p>';
        body += '<p style="margin:6px 0">Status: <span class="pill ' + statusPill + '">' + esc(statusText) + '</span>' +
            (g.mu_baseline && g.mu_baseline.files ? ' <span class="pill p-blue">' + g.mu_baseline.files + ' baseline file' + (g.mu_baseline.files === 1 ? '' : 's') + '</span>' : '') + '</p>';
        if (msg) toast(msg, msgType || 'ok');

        if (Array.isArray(g.files) && g.files.length) {
            body += '<p class="muted" style="margin:12px 0 4px;font-size:12px">MUST-USE PLUGINS</p>';
            body += g.files.map(f => {
                const v = guardianVerdict[f.verdict] || guardianVerdict.unknown;
                return '<p class="path" style="word-break:break-all;font-size:12.5px;padding:6px 10px;background:var(--bg-2);border:1px solid var(--line-2);border-radius:6px;margin:4px 0">' +
                    esc(f.file) + ' <span class="pill ' + v[0] + '">' + v[1] + '</span></p>';
            }).join('');
        }
        const quarantined = (g.quarantined || []).filter(q => q.source === 'guardian');
        if (quarantined.length) {
            body += '<p class="muted" style="margin:12px 0 4px;font-size:12px">NEUTRALIZED BY GUARDIAN</p>';
            body += quarantined.slice(0, 5).map(q =>
                '<p class="path" style="word-break:break-all;font-size:12.5px;padding:6px 10px;background:var(--bg-2);border:1px solid var(--line-2);border-radius:6px;margin:4px 0">' +
                esc(q.original || '') + ' <button class="btn btn-neutral" data-gwqsh-restore="' + esc(q.file) + '" type="button">Restore</button></p>').join('');
        }

        const actions = [];
        if (status === 'active' || status === 'outdated') {
            actions.push({
                label: 'Remove Guardian', cls: 'btn-neutral',
                fn: async () => {
                    const res = await GWQSH.ajax('guardian_uninstall');
                    if (!res.success) { toast(res.data?.message || 'Removal failed', 'err'); return false; }
                    guardianModal(res.data.guardian, res.data?.message, 'ok');
                    return true;
                }
            });
        }
        if (status !== 'active') {
            actions.push({
                label: status === 'outdated' ? 'Update Guardian' : 'Install Guardian', cls: 'btn-danger',
                fn: async () => {
                    const res = await GWQSH.ajax('guardian_install');
                    if (!res.success) { toast(res.data?.message || 'Installation failed', 'err'); return false; }
                    guardianModal(res.data.guardian, res.data?.message, 'ok');
                    return true;
                }
            });
        }
        actions.push({
            label: 'Establish Baselines', cls: 'btn-neutral',
            fn: async () => {
                const res = await GWQSH.ajax('establish_baseline');
                if (!res.success) { toast(res.data?.message || 'Baseline failed', 'err'); return false; }
                guardianModal(res.data.guardian, res.data?.message, 'ok');
                return true;
            }
        });

        const box = modal({ title: 'Early Guardian', body, actions });
        $$('[data-gwqsh-restore]', box.el).forEach(btn => btn.addEventListener('click', async () => {
            const name = btn.dataset.gwqshRestore;
            btn.disabled = true;
            const res = await GWQSH.ajax('restore_quarantine', { name });
            btn.disabled = false;
            if (!res.success) { toast(res.data?.message || 'Restore failed', 'err'); return; }
            box.close();
            guardianModal(res.data.guardian, res.data?.message, 'ok');
        }));
    }

    $('#toolGuardian').addEventListener('click', async () => {
        let g = D.guardian;
        if (!g) {
            const res = await GWQSH.ajax('guardian_status');
            if (!res.success) { toast(res.data?.message || 'Guardian status unavailable', 'err'); return; }
            g = res.data.guardian;
        }
        guardianModal(g);
    });

    draw();
    refreshSummary();
});
