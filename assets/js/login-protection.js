/* ============================================================
   Gracewell QuietShield — Login Protection page logic
   Depends on: qs-core.js (loaded before this, both deferred)
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {
  const { $, $$, toast, modal, confirm, esc, fmt } = GWQSH;
  const D = window.GWQSH_DATA || {};

  GWQSH.setCount($('.lp-stats .stat:nth-child(2) [data-count]'), Number(D.failed_24h || 0));
  const trend = $('.lp-stats .trend'); if (trend) trend.hidden = true;
  const prefix = $('#loginurl .pre');
  const loginBase = window.GWQSH_CONFIG?.login_base || window.GWQSH_CONFIG?.home_url || window.location.origin + '/';
  const loginSuffix = window.GWQSH_CONFIG?.login_suffix ?? '/';
  if (prefix) prefix.textContent = loginBase;
  /* ---------- master protection toggle ---------- */
  const sw = $('#protSwitch');
  const isMasterOn = D.settings?.enabled !== undefined ? !!D.settings.enabled : true;
  GWQSH.setSwitch(sw, isMasterOn);
  const card = $('#protCard');
  card.classList.toggle('off', !isMasterOn);
  $('#protTitle').textContent = isMasterOn ? 'Protection Active' : 'Protection Paused';
  const p = $('#protPill');
  p.className = 'pill ' + (isMasterOn ? 'p-green' : 'p-red');
  p.textContent = isMasterOn ? 'Enabled' : 'Disabled';
  $('#protDesc').textContent = isMasterOn ? 'Failed login attempts are monitored.' : 'Login protection is paused. Enable it to limit failed attempts.';

  sw.addEventListener('gwqsh:toggle', async e => {
    const on = e.detail.on;
    const result = await GWQSH.ajax('toggle_login_protection', { on: on ? 1 : 0 });
    if (!result.success) { GWQSH.setSwitch(sw, !on); toast(result.data?.message || 'Could not save protection', 'err'); return; }
    card.classList.toggle('off', !on);
    $('#protTitle').textContent = on ? 'Protection Active' : 'Protection Paused';
    p.className = 'pill ' + (on ? 'p-green' : 'p-red');
    p.textContent = on ? 'Enabled' : 'Disabled';
    $('#protDesc').textContent = on
      ? 'Failed login attempts are monitored.'
      : 'Login attempts are not being limited. Turn protection back on to block brute-force attacks.';
    toast(on ? 'Brute-force protection enabled' : 'Brute-force protection paused', on ? 'ok' : 'warn');

  });

  /* ---------- settings ---------- */
  const DEF = { maxAttempts: 5, timeWindow: '15', lockDur: '30', repeatLock: 4, repeatDur: '24' };
  if (D.settings) {
    if (D.settings.max_attempts) $('#maxAttempts').value = D.settings.max_attempts;
    if (D.settings.time_window) $('#timeWindow').value = D.settings.time_window;
    if (D.settings.lockout_duration) $('#lockDur').value = D.settings.lockout_duration;
    if (D.settings.repeat_lockouts) $('#repeatLock').value = D.settings.repeat_lockouts;
    if (D.settings.repeat_duration) $('#repeatDur').value = D.settings.repeat_duration;
  }

  const durLbl = v => +v < 60 ? v + ' min' : (+v / 60) + (+v === 60 ? ' hour' : ' hours');
  function applyStat() {
    const d = $('#lockDur').value;
    $('#lockoutStat').textContent = durLbl(d);
    $('#lockoutMeta').textContent = 'After ' + $('#maxAttempts').value + ' failed attempts';
  }
  applyStat();

  function resetForm() { Object.entries(DEF).forEach(([k, v]) => $('#' + k).value = v); }

  $('#saveSettings').addEventListener('click', async e => {
    const b = e.currentTarget, html = b.innerHTML;
    b.disabled = true; b.textContent = 'Saving…';

    if (window.GWQSH && GWQSH.ajax) {
      const savedResponse = await GWQSH.ajax('save_login_settings', {
        max_attempts: $('#maxAttempts').value,
        time_window: $('#timeWindow').value,
        lockout_duration: $('#lockDur').value,
        repeat_lockouts: $('#repeatLock').value,
        repeat_duration: $('#repeatDur').value
      });
      if (!savedResponse.success) { b.disabled = false; b.innerHTML = html; toast(savedResponse.data?.message || 'Could not save settings', 'err'); return false; }
    }

    b.disabled = false; b.innerHTML = html; applyStat();
    toast('Login protection settings saved');
  });

  const doReset = async () => {
    resetForm(); applyStat();
    if (window.GWQSH && GWQSH.ajax) {
      const savedResponse = await GWQSH.ajax('save_login_settings', {
        max_attempts: 5,
        time_window: 15,
        lockout_duration: 30,
        repeat_lockouts: 4,
        repeat_duration: 24
      });
      if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
    }
    toast('Settings reset to defaults', 'info');
  };
  $('#resetDefaults').addEventListener('click', doReset);
  $('#restoreDefaults').addEventListener('click', () =>
    confirm('Restore default settings?',
      'Maximum attempts, time window and lockout durations will return to their recommended values.',
      'Restore Defaults', doReset));

  /* ---------- login activity chart ---------- */
  let cur = {
    L: D.chart?.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    S: D.chart?.successful || [0, 0, 0, 0, 0, 0, 0],
    B: D.chart?.blocked || [0, 0, 0, 0, 0, 0, 0],
    T: D.chart?.labels || []
  };

  const chart = GWQSH.lineChart($('#loginChart'), {
    height: 170, labels: cur.L, yMax: Math.max(10, Math.ceil(Math.max(...cur.S, ...cur.B, 5) / 10) * 10), yStep: 5, pin: 3,
    aria: 'Successful and blocked login attempts per day',
    tipTitle: i => cur.L[i],
    series: [
      { name: 'Successful', color: '#22C55E', data: cur.S },
      { name: 'Blocked',    color: '#EF4444', data: cur.B, area: false }
    ]
  });

  $('#actRange').addEventListener('change', async e => {
    const d = +e.target.value;
    $('#actSub').textContent = 'Successful vs blocked login attempts (last ' + d + ' days)';
    if (window.GWQSH && GWQSH.ajax) {
      const res = await GWQSH.ajax('get_login_data', { days: d });
      if (res.success && res.data && res.data.chart) {
        cur.L = res.data.chart.labels;
        cur.S = res.data.chart.successful;
        cur.B = res.data.chart.blocked;
      }
    }
    chart.update({
      labels: cur.L, pin: d === 7 ? 3 : null,
      series: [
        { name: 'Successful', color: '#22C55E', data: cur.S },
        { name: 'Blocked',    color: '#EF4444', data: cur.B, area: false }
      ]
    });
  });

  /* ---------- locked IPs ---------- */
  let locked = Array.isArray(D.locked_ips) ? D.locked_ips.map(x => ({
    ip: x.ip, n: x.attempts, until: x.until, rem: x.remaining
  })) : [];

  function drawLocked() {
    $('#lockedBody').innerHTML = locked.length
      ? locked.map(r => '<tr data-ip="' + esc(r.ip) + '"><td class="ip-cell">' + esc(r.ip) + '</td><td>' + r.n + '</td><td>' + esc(r.until) + '</td><td class="remain">' + esc(r.rem) + '</td><td style="text-align:center"><button class="unlock" type="button" aria-label="Unlock ' + esc(r.ip) + '">Unlock</button></td></tr>').join('')
      : '<tr class="empty-row"><td colspan="5">No IPs are locked right now. Blocked addresses will appear here.</td></tr>';
    $$('#lockedPill,#lockedStatPill').forEach(el => { el.textContent = locked.length + ' Locked'; });
    GWQSH.setCount($('#lockedCount'), locked.length);
  }

  $('#lockedBody').addEventListener('click', async e => {
    const b = e.target.closest('.unlock'); if (!b) return;
    const tr = b.closest('tr'), ip = tr.dataset.ip;

    if (window.GWQSH && GWQSH.ajax) {
      const savedResponse = await GWQSH.ajax('unlock_ip', { ip });
      if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
    }

    const done = () => {
      const i = locked.findIndex(x => x.ip === ip);
      if (i !== -1) locked.splice(i, 1);
      drawLocked();
      toast(ip + ' unlocked', 'ok');
    };
    if (GWQSH.animOn()) GWQSH_Motion.to(tr, { opacity: 0, x: 20, duration: .25, onComplete: done });
    else done();
  });
  drawLocked();

  /* ---------- allowlist ---------- */
  let ips = Array.isArray(D.allow_ips) ? D.allow_ips.slice() : [];
  const okIc = '<svg class="i ok" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="m8.5 12 2.5 2.5 4.5-5" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  const xIc = '<svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';

  function drawIps(newIp) {
    $('#chips').innerHTML = ips.map(ip =>
      '<li class="chip" data-ip="' + esc(ip) + '">' + okIc +
      '<span title="' + esc(ip) + '">' + esc(ip) + '</span>' +
      '<button type="button" aria-label="Remove ' + esc(ip) + '">' + xIc + '</button></li>'
    ).join('');
    $$('#allowPill,#allowStatPill').forEach(el => { el.textContent = ips.length + (ips.length === 1 ? ' IP' : ' IPs'); });
    GWQSH.setCount($('#allowCount'), ips.length);
    if (newIp && GWQSH.animOn()) {
      const el = $('.chip[data-ip="' + CSS.escape(newIp) + '"]');
      el && GWQSH_Motion.from(el, { scale: .8, opacity: 0, duration: .35, ease: 'back.out(2)' });
    }
  }

  $('#chips').addEventListener('click', async e => {
    const b = e.target.closest('button'); if (!b) return;
    const li = b.closest('.chip'), ip = li.dataset.ip;

    if (window.GWQSH && GWQSH.ajax) {
      const savedResponse = await GWQSH.ajax('remove_allow_ip', { ip });
      if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
    }

    const fin = () => {
      const i = ips.indexOf(ip); if (i !== -1) ips.splice(i, 1); drawIps();
      toast(ip + ' removed from allowlist', 'ok');
    };
    GWQSH.animOn() ? GWQSH_Motion.to(li, { scale: .85, opacity: 0, duration: .2, onComplete: fin }) : fin();
  });

  const v4 = /^(25[0-5]|2[0-4]\d|1?\d?\d)(\.(25[0-5]|2[0-4]\d|1?\d?\d)){3}(\/([0-9]|[12]\d|3[0-2]))?$/, v6 = /^[0-9a-f:]+$/i;
  const validIp = s => v4.test(s) || (s.includes(':') && v6.test(s) && s.length <= 39);

  $('#addIpBtn').addEventListener('click', () => {
    $('#addIpBtn').classList.add('hide');
    $('#addForm').classList.remove('hide');
    $('#ipInput').focus();
  });
  const closeAdd = () => {
    $('#addForm').classList.add('hide');
    $('#addIpBtn').classList.remove('hide');
    $('#ipInput').value = '';
    $('#ipErr').classList.remove('show');
    $('#ipInput').classList.remove('invalid');
  };
  $('#cancelAdd').addEventListener('click', closeAdd);

  $('#addForm').addEventListener('submit', async e => {
    e.preventDefault();
    const v = $('#ipInput').value.trim();
    if (!validIp(v)) { $('#ipErr').textContent = 'Enter a valid IPv4 or IPv6 address.'; $('#ipErr').classList.add('show'); $('#ipInput').classList.add('invalid'); return; }
    if (ips.includes(v)) { $('#ipErr').textContent = v + ' is already on the allowlist.'; $('#ipErr').classList.add('show'); return; }

    if (window.GWQSH && GWQSH.ajax) {
      const savedResponse = await GWQSH.ajax('add_allow_ip', { ip: v });
      if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
    }

    ips.push(v); drawIps(v); closeAdd(); toast(v + ' added to allowlist');
  });

  $('#ipInput').addEventListener('input', () => { $('#ipErr').classList.remove('show'); $('#ipInput').classList.remove('invalid'); });
  $('#copyAllow').addEventListener('click', () => GWQSH.copy(ips.join('\n'), ips.length + ' IPs copied'));

  $('#addMine').addEventListener('click', async () => {
    const me = D.client_ip || '';
    if (!me) { toast('Your IP address is unavailable', 'warn'); return; }
    if (ips.includes(me)) { toast('Your IP is already allowlisted', 'info'); return; }
    if (window.GWQSH && GWQSH.ajax) {
      const savedResponse = await GWQSH.ajax('add_allow_ip', { ip: me });
      if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
    }
    ips.push(me); drawIps(me); toast('Your IP was added to allowlist');
  });

  $('#clearAllow').addEventListener('click', () =>
    confirm('Remove all allowlisted IPs?',
      'These addresses will be subject to lockouts again. You can add them back at any time.',
      'Remove all',
      async () => {
        if (window.GWQSH && GWQSH.ajax) {
          const savedResponse = await GWQSH.ajax('clear_allowlist');
          if (!savedResponse.success) { toast(savedResponse.data?.message || 'Could not complete this action', 'err'); return false; }
        }
        ips = []; drawIps(); toast('Allowlist cleared', 'warn');
      },
      true));
  drawIps();

  /* ---------- custom login URL ---------- */
  const slug = $('#loginSlug'), chg = $('#changeUrl'), cancelU = $('#cancelUrl');
  if ($('#gwqshRecoveryUrl') && D.recovery_url) {
    $('#gwqshRecoveryUrl').textContent = D.recovery_url;
  }
  if (D.settings && D.settings.custom_slug) {
    slug.value = D.settings.custom_slug;
  }
  let saved = slug.value;
  let urlActive = !!D.settings?.custom_slug_enabled;
  const toggleUrl = document.createElement('button');
  toggleUrl.type = 'button'; toggleUrl.className = 'switch'; toggleUrl.id = 'toggleLoginUrl';
  const currentUrl = document.createElement('p'); currentUrl.className = 'muted'; currentUrl.style.overflowWrap = 'anywhere';
  toggleUrl.setAttribute('role', 'switch'); toggleUrl.setAttribute('aria-label', 'Custom Login URL'); $('#loginurl .card-head').append(toggleUrl); $('.url-input-block').after(currentUrl);
  const urlPill = $('#loginurl .card-head .pill');
  function syncUrlStatus() {
    urlPill.textContent = urlActive ? 'Enabled' : 'Disabled'; urlPill.className = 'pill p-dot ' + (urlActive ? 'p-green' : 'p-gray');
    toggleUrl.setAttribute('aria-checked', String(urlActive));
    currentUrl.textContent = 'Configured login URL: ' + loginBase + saved + loginSuffix;
  }
  toggleUrl.addEventListener('click', async () => {
    if (chg.disabled) return;
    toggleUrl.disabled = true; toggleUrl.setAttribute('aria-busy', 'true');
    chg.disabled = true;
    const res = await GWQSH.ajax('save_login_slug', { slug: saved, enabled: urlActive ? 0 : 1 });
    chg.disabled = false;
    toggleUrl.disabled = false; toggleUrl.removeAttribute('aria-busy');
    if (!res.success) { syncUrlStatus(); toast(res.data?.message || 'Could not save the login URL setting', 'err'); return; }
    urlActive = res.data.enabled;
    if (res.data.recovery_url) $('#gwqshRecoveryUrl').textContent = res.data.recovery_url;
    syncUrlStatus(); endEdit(); toast(res.data.message);
  });
  syncUrlStatus();
  chg.textContent = 'Save'; slug.readOnly = false;

  $('#copyUrl').addEventListener('click', e => { GWQSH.copy(loginBase + saved + loginSuffix, 'Login URL copied', e.currentTarget); });

  chg.addEventListener('click', async () => {
    if (chg.disabled) return;
    const v = slug.value.trim().toLowerCase();
    if (!/^[a-z0-9-]{3,40}$/.test(v) || v === 'wp-admin' || v === 'wp-login') {
      $('#slugErr').classList.add('show'); slug.focus(); return;
    }

    if (window.GWQSH && GWQSH.ajax) {
      chg.disabled = true; chg.textContent = 'Saving…';
      toggleUrl.disabled = true;
      const res = await GWQSH.ajax('save_login_slug', { slug: v, enabled: urlActive ? 1 : 0 });
      chg.disabled = false;
      toggleUrl.disabled = false;
      if (!res.success) { chg.textContent = 'Save URL'; toast(res.data?.message || 'Could not save the login URL', 'warn'); return; }
      if (res.data?.recovery_url) $('#gwqshRecoveryUrl').textContent = res.data.recovery_url;
    }

    saved = v; syncUrlStatus();
    slug.value = v; endEdit();
    toast('Login URL changed to /' + v + '. Bookmark the new address.');
  });

  function endEdit() {
    slug.readOnly = false;
    chg.textContent = 'Save';
    chg.classList.replace('btn-primary', 'btn-outline');
    cancelU.classList.add('hide');
    $('#slugErr').classList.remove('show');
  }
  cancelU.addEventListener('click', () => { slug.value = saved; endEdit(); });
  slug.addEventListener('keydown', e => { if (e.key === 'Enter') chg.click(); if (e.key === 'Escape') cancelU.click(); });

  /* bubble micro-motion */
  if (GWQSH.animOn()) GWQSH_Motion.fromTo('.bubble', { y: 4, opacity: 0 }, { y: 0, opacity: 1, duration: .5, delay: 1.4, ease: 'back.out(2)' });
});
