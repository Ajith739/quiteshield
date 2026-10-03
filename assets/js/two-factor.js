/* ============================================================

   Gracewell QuietShield — Two-Factor page logic

   Depends on: qs-core.js, QRCode.js (both loaded before this)

   ============================================================ */



document.addEventListener('DOMContentLoaded', function () {

    const { $, $$, toast, modal, confirm, esc, fmt } = GWQSH;

    const D = window.GWQSH_DATA || {};



    /* ---------- secret + QR ---------- */

    let secret = D.secret || '';

    let qrUri = D.qr_uri || '';



    function drawQr() {

        $('#secret').textContent = secret ? secret.match(/.{1,4}/g).join(' ') : 'Already configured';

        const el = $('#qr'); el.innerHTML = '';

        const copyKey = $('[data-copy="#secret"]');

        if (copyKey) copyKey.disabled = !secret;

        if (!secret) { el.textContent = 'Authenticator connected. Regenerate to set up a new device.'; return; }



        if (window.gwqshQR) {

            window.gwqshQR(el, qrUri, { size: 240, dark: '#0B1440' });

        } else {

            el.textContent = 'QR library unavailable. Use the setup key.';

        }

        if (GWQSH.animOn()) GWQSH_Motion.from('#qr', { opacity: 0, scale: .92, duration: .4, ease: 'power2.out' });

    }

    drawQr();

    const setupPill = $('#h-setup').closest('.card').querySelector('.card-head > .pill');

    function syncPersonal() {

        const effective = D.is_enabled && !D.setup_incomplete;

        setupPill.textContent = D.setup_incomplete ? 'Setup incomplete' : effective ? 'Enabled' : 'Disabled';

        setupPill.className = 'pill ' + (effective ? 'p-green' : 'p-amber');
        $('#mApp .txt small').textContent = effective ? 'Connected and working' : 'Awaiting verified setup';
        $('#mApp > svg').hidden = !effective;

        $('#personal2fa').textContent = D.is_enabled ? 'Disable your 2FA' : 'Verify and enable your 2FA';

        $('#personal2faStatus').textContent = D.is_enabled ? (effective ? 'Your authenticator is active.' : 'Your setup needs attention.') : 'Scan the QR code, then verify a six-digit code to activate protection.';

        $('#regenCodes').disabled = !D.is_enabled; $('#setupCode').hidden = !secret; $('[for="setupCode"]').hidden = !secret;

    }

    syncPersonal();

    if (!D.can_manage) { $('#h-2fas').closest('.card').hidden = true; }

    async function enroll() {
        const b = $('#personal2fa'); b.disabled = true;
        try {
            const res = await GWQSH.ajax('enable_user_2fa', { code: $('#setupCode').value.trim() });
            if (!res.success) { toast(res.data?.message || 'Invalid authentication code', 'err'); return; }
            D.is_enabled = true; D.setup_incomplete = false; secret = ''; drawQr(); syncPersonal(); users = res.data.users; drawUsers();
            toast('2FA enabled successfully');
        } finally { b.disabled = false; }
    }
    $('#personal2fa').addEventListener('click', () => {

        if (!D.is_enabled) { enroll(); return; }

        modal({ title: 'Disable your 2FA',

            body: '<p>Enter a current authenticator or unused backup code. Your authenticator, backup codes and trusted devices will be removed.</p><input class="field" id="gwqshDisableCode" autocomplete="one-time-code">',

            actions: [{ label: 'Cancel' }, { label: 'Disable 2FA', cls: 'btn-danger', fn: async bg => {

                const res = await GWQSH.ajax('disable_own_2fa', { code: bg.querySelector('#gwqshDisableCode').value.trim() });

                if (!res.success) { toast(res.data?.message || 'Could not disable 2FA', 'err'); return false; }

                location.reload();

            }}]

        });

    });

    const replacementButton = $('#verifyReplacement');

    replacementButton.type = 'button'; replacementButton.className = 'btn btn-outline'; replacementButton.textContent = 'Verify replacement authenticator';

 replacementButton.hidden = !(D.is_enabled && secret);

    replacementButton.addEventListener('click', async () => {
        replacementButton.disabled = true;
        try {
            const res = await GWQSH.ajax('confirm_2fa_secret', { code: $('#setupCode').value.trim() });
            if (!res.success) { toast(res.data?.message || 'Invalid authentication code', 'err'); return; }
            location.reload();
        } finally { replacementButton.disabled = false; }
    });

    $('#regenQr').addEventListener('click', () =>

        confirm('Regenerate your QR code?',

            'Scan and verify the new key. Your current authenticator remains active until you confirm the replacement.',

            'Regenerate',

            async () => {

                let pending = false;

                if (window.GWQSH && GWQSH.ajax) {

                    const res = await GWQSH.ajax('generate_2fa_secret');

                    if (!res.success || !res.data) { toast(res.data?.message || 'Could not generate a new key', 'warn'); return false; }

                    secret = res.data.secret;

                    qrUri = res.data.qr_uri;

                    pending = !!res.data.pending;

                }

                drawQr();

                replacementButton.hidden = !pending;

                toast('New QR code generated. Scan it in your authenticator app.');

                if (pending) setTimeout(() => modal({

                    title: 'Confirm new authenticator',

                    body: '<p>Your current authenticator remains active until you confirm the new one.</p><input class="field" id="gwqshNewCode" inputmode="numeric" maxlength="6" autocomplete="one-time-code">',

                    actions: [{ label: 'Later' }, { label: 'Confirm', cls: 'btn-primary', fn: async bg => {

                        const res = await GWQSH.ajax('confirm_2fa_secret', { code: bg.querySelector('#gwqshNewCode').value.trim() });

                        if (!res.success) { toast(res.data?.message || 'Invalid code', 'warn'); return false; }

                        secret = ''; drawQr(); toast('New authenticator confirmed');

                    }}]

                }), 200);

            }));



    /* ---------- backup codes ---------- */

    let rawCodes = Array.isArray(D.backup_codes) ? D.backup_codes : [];

    let codes = rawCodes.map(x => x.code || 'Saved code');

    let used = new Set(rawCodes.map((x, i) => x.used ? i : -1).filter(i => i >= 0));



    function drawCodes(anim) {

        const visibleCodes = codes.some((c, i) => c !== 'Saved code' && !used.has(i));

        $('#copyCodes').disabled = !visibleCodes;

        $('#dlCodes').disabled = !visibleCodes;

        let note = $('#codesSavedNotice');

        if (!note) { note = document.createElement('p'); note.id = 'codesSavedNotice'; note.className = 'muted'; $('#codes').before(note); }

        note.textContent = visibleCodes ? 'Unused codes are securely saved. You can copy them again after refresh.' : codes.length ? 'Older hashed codes remain valid but cannot be displayed. Regenerate intentionally to create an encrypted displayable set.' : 'Generate backup codes and save them somewhere safe.';

        $('#codes').innerHTML = codes.map((c, i) =>

            '<li ' + (used.has(i) ? 'hidden ' : '') + 'class="' + (used.has(i) ? 'used' : '') + '"><span>' + esc(c) + '</span>' +

            '<button class="copy-btn" ' + (c === 'Saved code' || used.has(i) ? 'disabled ' : '') + 'data-copy="' + esc(c === 'Saved code' ? '' : c) + '" data-copy-label="Code copied" aria-label="Copy code">' +

            '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +

            '<rect width="14" height="14" x="8" y="8" rx="2"/>' +

            '<path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg></button></li>'

        ).join('');

        const left = Math.max(0, codes.length - used.size);

        $('#mCodes').textContent = left + ' of ' + codes.length + ' remaining';

        $('#usedN').textContent = used.size;

        $('#usedN').nextElementSibling.textContent = '/ ' + codes.length;

        const pct = codes.length ? Math.round(left / codes.length * 100) : 0;

        $('#usedPct').textContent = pct + '%';

        GWQSH.setBar($('#usedBar'), pct);

        if (anim && GWQSH.animOn()) GWQSH_Motion.from('#codes li', { rotationX: 90, opacity: 0, stagger: .04, duration: .35, ease: 'power2.out' });

    }

    drawCodes();



    $('#regenCodes').addEventListener('click', () =>

        confirm('Regenerate backup codes?',

            'All current codes will stop working immediately. Save the new ones somewhere safe.',

            'Regenerate Codes',

            async () => {

                if (window.GWQSH && GWQSH.ajax) {

                    const res = await GWQSH.ajax('generate_backup_codes');

                    if (res.success && res.data && res.data.backup_codes) {

                        codes = res.data.backup_codes.map(x => x.code);

                        used = new Set();

                    } else { toast(res.data?.message || 'Could not generate backup codes', 'err'); return false; }

                }

                drawCodes(true);

                toast('10 new backup codes generated');

            }));



    $('#copyCodes').addEventListener('click', async e =>

        GWQSH.copy(codes.filter((c, i) => !used.has(i) && c !== 'Saved code').join('\n'), 'Backup codes copied', e.currentTarget, { iconOnly: true, silent: true }));



    $('#dlCodes').addEventListener('click', () => {

        const uLogin = window.GWQSH_CONFIG?.user?.login || 'admin';

        GWQSH.download('quietshield-backup-codes.txt',

            'Gracewell QuietShield backup codes for ' + uLogin + '\nEach code can only be used once.\n\n' +

            codes.filter((c, i) => !used.has(i) && c !== 'Saved code').join('\n') + '\n');

        toast('Backup codes downloaded');

    });



    /* ---------- methods ---------- */

    $('#mApp').addEventListener('click', () =>

        modal({

            title: 'Authenticator app',

            body: '<p>' + (D.is_enabled ? 'Your authenticator app is connected. Codes refresh every 30 seconds.' : 'Scan your QR code, then verify a code to connect your app.') + '</p>' +

                '<p class="muted">To move to a new phone, regenerate the QR code and scan it with the new device.</p>'

        }));



    let devices = Array.isArray(D.devices) ? D.devices : [];

    $('#mDevN').textContent = devices.length + ' trusted device' + (devices.length === 1 ? '' : 's');



    function devicesModal() {

        const html = () => devices.length

            ? '<div class="divide">' + devices.map((d, i) =>

                '<div class="lrow"><span class="tile sm t-blue">' +

                '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">' +

                '<rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8M12 17v4"/></svg></span>' +

                '<div class="txt"><b>' + esc(d[0]) + '</b><small>' + esc(d[1]) + '</small></div>' +

                '<button class="btn btn-danger btn-sm" data-rm="' + i + '">Remove</button></div>'

            ).join('') + '</div>'

            : '<p class="muted">No trusted devices. You\u2019ll be asked for a code on every login.</p>';



        modal({

            title: 'Trusted devices',

            body: '<div id="devList">' + html() + '</div>',

            onOpen: bg => {

                bg.addEventListener('click', async e => {

                    const b = e.target.closest('[data-rm]'); if (!b) return;

                    const idx = +b.dataset.rm;

                    b.disabled = true;

                    const res = await GWQSH.ajax('remove_trusted_device', { index: idx });

                    if (!res.success) { b.disabled = false; toast(res.data?.message || 'Could not revoke device', 'err'); return; }

                    const d = devices[idx]; devices = res.data.devices;

                    bg.querySelector('#devList').innerHTML = html();

                    $('#mDevN').textContent = devices.length + ' trusted device' + (devices.length === 1 ? '' : 's');

                    toast(d[0] + ' removed');

                });

            }

        });

    }

    $('#mDevices').addEventListener('click', devicesModal);



    /* ---------- 2FA settings ---------- */

    $$('[data-policy]').forEach(sw => GWQSH.setSwitch(sw, !!D.settings?.[sw.dataset.policy]));

    $('#save2fa').addEventListener('click', async e => {

        const b = e.currentTarget; b.disabled = true; const old = b.innerHTML; b.textContent = 'Saving…';

        const payload = {}; $$('[data-policy]').forEach(sw => payload[sw.dataset.policy] = sw.getAttribute('aria-checked') === 'true' ? 1 : 0);

        const res = await GWQSH.ajax('save_2fa_settings', payload);

        b.disabled = false; b.innerHTML = old;

        if (!res.success) { toast(res.data?.message || 'Could not save 2FA settings', 'err'); $$('[data-policy]').forEach(sw => GWQSH.setSwitch(sw, !!D.settings?.[sw.dataset.policy])); return; }

        D.settings = res.data.settings; toast(res.data.message); location.reload();

    });



    /* ---------- users ---------- */

    let users = Array.isArray(D.users) ? D.users : [];

    const stCls = { Enabled: 'c-green', Disabled: 'c-red', 'Setup incomplete': 'c-amber' };



    function drawUsers() {

        const q = $('#uSearch').value.trim().toLowerCase();

        const r = $('#uRole').value;

        const s = $('#uStatus').value;



        const rows = users.filter(x => (!q || x.u.toLowerCase().includes(q)) && (!r || x.role === r) && (!s || x.st === s));



        $('#users').innerHTML = rows.length

            ? rows.map(x => {

                const act = x.enrolled || !x.me ? 'Manage' : 'Enable';

                return '<tr data-u="' + esc(x.u) + '" data-id="' + x.id + '">' +

                    '<td><span class="user-cell"><span class="uav' + (x.me ? ' me' : '') + '">' + esc(x.u[0].toUpperCase()) + '</span>' + esc(x.u) + '</span></td>' +

                    '<td><span class="role">' + esc(x.role) + '</span></td>' +

                    '<td><span class="status ' + stCls[x.st] + '">' + x.st + '</span></td>' +

                    '<td>' + esc(x.m) + '<small class="muted">' + x.backup_remaining + ' backup codes remaining' + (x.required ? ' · Setup required' : '') + '</small></td>' +

                    '<td>' + esc(x.a) + '</td>' +

                    '<td style="text-align:center"><button class="btn btn-outline btn-sm act-btn" data-act="' + act + '" type="button">' + act + '</button></td>' +

                    '<td><button class="kebab" data-more type="button" aria-label="More actions for ' + esc(x.u) + '">' +

                    '<svg class="i" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg></button></td></tr>';

            }).join('')

            : '<tr class="empty-row"><td colspan="7">No users match these filters. Clear the search or pick another role.</td></tr>';



        const on = users.filter(x => x.st === 'Enabled').length;

        const total = users.length || 1;

        const pct = Math.round(on / total * 100);

        $('#activeN').textContent = on;

        $('#activeN').nextElementSibling.textContent = '/ ' + users.length;

        $('#activePct').textContent = pct + '%';

        GWQSH.setBar($('#activeBar'), pct);

        GWQSH.labelTable($('#users').closest('table'));

    }



    ['#uSearch', '#uRole', '#uStatus'].forEach(s => $(s).addEventListener('input', drawUsers));



    $('#users').addEventListener('click', async e => {

        const tr = e.target.closest('tr[data-u]'); if (!tr) return;

        const x = users.find(y => y.u === tr.dataset.u);

        const b = e.target.closest('[data-act]');



        if (b && b.dataset.act === 'Enable') {

            if (!x.me) {

                toast('This user must enroll their own authenticator.', 'info');

                return;

            }

            modal({

                title: 'Confirm your authenticator',

                body: '<p>Enter the current six-digit code from your authenticator app.</p><input class="field" id="gwqshEnrollCode" inputmode="numeric" maxlength="6" autocomplete="one-time-code">',

                actions: [{ label: 'Cancel' }, { label: 'Enable 2FA', cls: 'btn-primary', fn: async bg => {

                    const code = bg.querySelector('#gwqshEnrollCode').value.trim();

                    const res = await GWQSH.ajax('enable_user_2fa', { user_id: x.id, code });

                    if (!res.success) { toast(res.data?.message || 'Invalid code', 'warn'); return false; }

                    users = res.data.users;

                    drawUsers();

                    D.is_enabled = true; secret = ''; drawQr(); syncPersonal();

                    toast('2FA enabled for ' + x.u);

                }}]

            });

        } else if (b && b.dataset.act === 'Resend') {

            toast('This user must open their own 2FA page to enroll.', 'info');

        } else if (b && b.dataset.act === 'Manage') {

            modal({

                title: 'Manage 2FA for ' + esc(x.u),

                body: '<p>Method: <b>' + esc(x.m) + '</b><br>Last verified: ' + esc(x.a) + '</p>' +

                    '<p class="muted">Resetting removes their authenticator and backup codes. They\u2019ll set up 2FA again at next login.</p>',

                actions: [

                    { label: 'Close' },

                    ...(x.me ? [] : [{ label: x.required ? 'Remove requirement' : 'Require setup', cls: 'btn-outline', fn: async () => {

                        const res = await GWQSH.ajax('require_user_2fa', { user_id: x.id, required: x.required ? 0 : 1 });

                        if (!res.success) { toast(res.data?.message || 'Could not save requirement', 'err'); return false; }

                        users = res.data.users; drawUsers(); toast(res.data.message);

                    }}]),

                    {

                        label: 'Reset 2FA', cls: 'btn-danger', fn: async () => {

                            if (x.me) {

                                toast('Use \u201cRegenerate QR Code\u201d to refresh your own 2FA', 'info');

                                return;

                            }

                            if (window.GWQSH && GWQSH.ajax) {

                                const res = await GWQSH.ajax('reset_user_2fa', { user_id: x.id });

                                if (res.success && res.data && res.data.users) {

                                    users = res.data.users;

                                } else { toast(res.data?.message || 'Could not reset 2FA', 'err'); return false; }

                            } else {

                                x.st = 'Disabled'; x.m = '—'; x.a = '—';

                            }

                            drawUsers(); toast('2FA reset for ' + x.u, 'warn');

                        }

                    }

                ]

            });

        } else if (e.target.closest('[data-more]')) {

            modal({

                title: esc(x.u),

                body: '<p>Role: ' + esc(x.role) + '<br>2FA: ' + esc(x.st) + '</p>',

                actions: [

                    { label: 'Close' },

                    { label: 'View activity', cls: 'btn-outline', fn: () => { location.href = (window.GWQSH_PAGES ? window.GWQSH_PAGES['activity-log'] : '#') + '&user=' + encodeURIComponent(x.u); } }

                ]

            });

        }

    });

    drawUsers();

});
