/* ============================================================
   Gracewell QuietShield — Hardening page logic
   Depends on: qs-core.js (loaded before this, both deferred)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
    const { $, $$, toast, modal, confirm, esc, fmt } = GWQSH;
    const D = window.GWQSH_DATA || {};

    const W = { xmlrpc: 15, editor: 15, version: 10, enum: 15, headers: 33, uploads: 12 };
    const NAME = {
        xmlrpc: 'Disable XML-RPC',
        editor: 'Disable file editor',
        version: 'Hide WordPress version',
        enum: 'Block user enumeration',
        headers: 'Enable security headers',
        uploads: 'Block PHP in uploads'
    };
    const HINT = {
        xmlrpc: 'Stops brute-force attacks through xmlrpc.php.',
        editor: 'Blocks code editing from wp-admin.',
        version: 'Hides your WordPress version from scanners.',
        enum: 'Stops attackers listing your usernames.',
        headers: 'Add HTTP security headers for better protection.',
        uploads: 'Stops uploaded PHP files from running.'
    };
    const DEF = { xmlrpc: 1, editor: 1, version: 1, enum: 1, headers: 0, uploads: 1 };

    const sw = k => $('#checkBody tr[data-k="' + k + '"] .switch');
    const isOn = k => sw(k) && sw(k).getAttribute('aria-checked') === 'true';
    let showAllRec = false;

    function set(k, on) {
        const s = sw(k);
        if (!s) return;
        GWQSH.setSwitch(s, on);
        const p = $('#checkBody tr[data-k="' + k + '"] [data-pill]');
        if (p) {
            p.className = 'pill p-status ' + (on ? 'p-green' : 'p-amber');
            p.textContent = on ? 'Enabled' : 'Not Enabled';
        }
    }

    // Initialize from real backend data
    if (D.hardening) {
        Object.entries(D.hardening).forEach(([k, v]) => set(k, !!v));
    }

    function refresh() {
        const keys = Object.keys(W);
        const on = keys.filter(isOn);
        const hard = on.reduce((sum, k) => sum + W[k], 0);
        const components = D.score_components || {};
        const score = hard;
        $$('.break-list b').forEach((el, i) => { el.textContent = [components.login || 0, components.tfa || 0, components.file || 0, hard, components.activity || 0][i]; });
        const pend = keys.length - on.length;

        GWQSH.setCount($('#scoreN'), score);
        GWQSH.setCount($('#ringN'), score);
        GWQSH.setBar($('#scoreBar'), score);
        GWQSH.ring($('#scoreRing'), score);
        $('#bHard').textContent = hard;
        $('#enN').textContent = on.length;
        $('#pendN').textContent = pend;
        $('#pendPill').textContent = pend + ' pending';
        $$('.hd-stats .stat-head .pill')[0].textContent = on.length + ' / ' + keys.length;

        $('#enMsg').textContent = on.length === 6 ? 'All recommended settings are active.'
            : on.length >= 4 ? 'Most recommended settings are active.'
                : 'Several recommended settings are off.';

        $('#pendMsg').textContent = pend === 0 ? 'Nothing left to do.'
            : pend === 1 ? 'One important setting left.'
                : pend + ' settings still need attention.';

        $('#scoreMsg').textContent = score >= 75 ? 'Great! Your site is well hardened.'
            : score >= 60 ? 'Good! Enable more hardening options.'
                : 'Enable hardening options to raise your score.';

        const risk = hard >= 85 ? ['Low', 'Most weighted hardening protections pass.']
            : hard >= 60 ? ['Medium', 'Some hardening protections are missing.']
            : hard >= 35 ? ['High', 'Important hardening protections are missing.']
            : ['Critical', 'Most hardening protections are disabled.'];

        $('#riskN').textContent = risk[0];
        $('#riskPill').textContent = risk[0];
        $('#riskPill').className = 'pill ' + (risk[0] === 'Low' ? 'p-green' : risk[0] === 'Medium' ? 'p-amber' : 'p-red');
        $('#riskMsg').textContent = risk[1];
        $('#riskN').style.color = risk[0] === 'Low' ? '' : risk[0] === 'Medium' ? 'var(--amber-600)' : 'var(--red-600)';

        $('#recPill').textContent = pend + ' pending';
        $('#recPill').className = 'pill ' + (pend ? 'p-amber' : 'p-green');

        const order = keys.filter(k => !isOn(k)).concat(keys.filter(isOn));
        const list = showAllRec ? order : order.slice(0, 4);
        const x = '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';
        const c = '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

        $('#recList').innerHTML = list.map(k => {
            const d = isOn(k);
            return '<div class="lrow"><span class="rec-ic ' + (d ? 'done' : 'todo') + '">' + (d ? c : x) + '</span>' +
                '<div class="txt"><b>' + (d ? NAME[k].replace('Enable ', '') : NAME[k]) + '</b>' +
                '<small>' + (d ? 'Already enabled.' : HINT[k]) + '</small></div>' +
                (d ? '<span class="btn btn-sm btn-done" aria-label="Done">Done</span>'
                    : '<button class="btn btn-sm btn-outline" data-enable="' + k + '" type="button">Enable</button>') +
                '</div>';
        }).join('');

        $('#recAll').firstChild.textContent = showAllRec ? 'Show fewer ' : 'View All Recommendations ';
    }

    $('#checkBody').addEventListener('gwqsh:toggle', async e => {
        const tr = e.target.closest('tr');
        if (!tr) return;
        const k = tr.dataset.k;
        const button = e.target; button.disabled = true;
        const res = await GWQSH.ajax('toggle_hardening', { feature: k, on: e.detail.on ? 1 : 0 });
        button.disabled = false;
        if (!res.success) { set(k, !e.detail.on); refresh(); toast(res.data?.message || 'Could not save protection', 'err'); return; }
        Object.entries(res.data.hardening).forEach(([key, value]) => set(key, !!value)); refresh();

        toast((e.detail.on ? 'Enabled: ' : 'Disabled: ') + NAME[k].replace('Enable ', ''), e.detail.on ? 'ok' : 'warn');
    });

    $('#recList').addEventListener('click', async e => {
        const b = e.target.closest('[data-enable]');
        if (!b) return;
        const k = b.dataset.enable;
        b.disabled = true;
        const res = await GWQSH.ajax('toggle_hardening', { feature: k, on: 1 });
        b.disabled = false;
        if (!res.success) { toast(res.data?.message || 'Could not enable protection', 'err'); return; }
        Object.entries(res.data.hardening).forEach(([key, value]) => set(key, !!value)); refresh();

        toast('Enabled: ' + NAME[k].replace('Enable ', ''));
    });

    $('#recAll').addEventListener('click', () => { showAllRec = !showAllRec; refresh(); });

    $('#resetDef').addEventListener('click', () =>
        confirm('Reset hardening to defaults?',
            'Recommended options will be turned on and security headers left off, matching a fresh install.',
            'Reset to Defaults',
            async () => {
                const res = await GWQSH.ajax('reset_hardening');
                if (!res.success) { toast(res.data?.message || 'Could not reset hardening', 'err'); return false; }
                Object.entries(res.data.hardening).forEach(([k, v]) => set(k, !!v));
                refresh();
                toast('Hardening reset to defaults', 'info');
            }));

    $('#revertAll').addEventListener('click', () => {
        const prev = {};
        Object.keys(W).forEach(k => prev[k] = isOn(k));
        confirm('Revert all hardening changes?',
            'Every hardening option will be turned off and any rules QuietShield wrote to .htaccess will be removed.',
            'Revert All Changes',
            async () => {
                const res = await GWQSH.ajax('revert_hardening');
                if (!res.success) { toast(res.data?.message || 'Could not revert hardening', 'err'); return false; }
                Object.entries(res.data.hardening).forEach(([k, v]) => set(k, !!v));
                refresh();
                toast('All hardening changes reverted', 'warn');
            },
            true);
    });

    $('.info-btn').addEventListener('click', () =>
        modal({
            title: 'Security headers',
            body: '<p>Headers tell browsers how to treat your pages. QuietShield adds:</p>' +
                '<div class="code-block">X-Frame-Options: SAMEORIGIN\n' +
                'X-Content-Type-Options: nosniff\n' +
                'Referrer-Policy: strict-origin-when-cross-origin\n' +
                'Permissions-Policy: camera=(), microphone=()\n' +
                'Content-Security-Policy: upgrade-insecure-requests</div>' +
                '<p class="muted" style="margin-top:10px">Test your site after enabling. Some page builders need extra CSP rules.</p>'
        }));

    const HT = '<span class="cm"># BEGIN QuietShield</span>\n' +
        '&lt;Files xmlrpc.php&gt;\n  Require all denied\n&lt;/Files&gt;\n' +
        '&lt;Files wp-config.php&gt;\n  Require all denied\n&lt;/Files&gt;\n' +
        'Options -Indexes\n' +
        '<span class="cm"># Block PHP in uploads</span>\n' +
        '&lt;IfModule mod_rewrite.c&gt;\n  RewriteEngine On\n  RewriteRule ^wp-content/uploads/.*\\.php$ - [F,L]\n&lt;/IfModule&gt;\n' +
        '<span class="cm"># END QuietShield</span>';

    const NG = '<span class="cm"># Add inside your server { } block</span>\n' +
        'location = /xmlrpc.php { deny all; }\n' +
        'location = /wp-config.php { deny all; }\n' +
        'location ~* /wp-content/uploads/.*\\.php$ { deny all; }\n' +
        'autoindex off;\n' +
        'add_header X-Frame-Options "SAMEORIGIN" always;\n' +
        'add_header X-Content-Type-Options "nosniff" always;';

    const plain = h => { const d = document.createElement('div'); d.innerHTML = h; return d.textContent; };

    $('#viewRules').addEventListener('click', () =>
        modal({
            title: '.htaccess rules',
            wide: true,
            body: '<p>Add these to the top of the <span class="code-inline">.htaccess</span> file in your site root. Apache 2.4+.</p>' +
                '<div class="code-block">' + HT + '</div>',
            actions: [
                { label: 'Close' },
                { label: 'Copy rules', cls: 'btn-primary', fn: () => GWQSH.copy(plain(HT), '.htaccess rules copied') }
            ]
        }));

    $('#viewNginx').addEventListener('click', () =>
        modal({
            title: 'Nginx configuration',
            wide: true,
            body: '<p>Nginx ignores <span class="code-inline">.htaccess</span>. Add these lines to your site\u2019s config, then reload Nginx.</p>' +
                '<div class="code-block">' + NG + '</div>',
            actions: [
                { label: 'Close' },
                { label: 'Copy config', cls: 'btn-primary', fn: () => GWQSH.copy(plain(NG), 'Nginx config copied') }
            ]
        }));

    refresh();
});
