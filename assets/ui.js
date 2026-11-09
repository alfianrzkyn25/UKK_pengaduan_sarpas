/* ==========================================================================
   Pengaduan Sarpras — lapisan interaksi tampilan (tanpa mengubah logika).
   - Tema terang/gelap (ikut sistem, bisa diganti, tersimpan di browser)
   - Animasi hitung angka & grafik donat komposisi status
   - Perbaikan aksesibilitas kecil (keyboard, Esc, penghitung karakter)
   Dimuat di <head> supaya tema terpasang sebelum halaman digambar
   (tidak ada kedipan putih saat mode gelap).
   ========================================================================== */
(function () {
  'use strict';

  // Elemen <html>, tempat atribut tema (data-theme) disimpan
  var root = document.documentElement;
  // Nama kunci penyimpanan tema di localStorage browser
  var KEY = 'sarpras-tema';
  // Pendeteksi tema sistem (gelap atau terang) dari pengaturan perangkat
  var mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
  // Cek apakah user meminta animasi dikurangi (untuk aksesibilitas)
  var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

  /* ---------- Tema ---------- */
  // Ambil tema yang tersimpan di browser (null kalau belum ada)
  function savedTheme() {
    try { return localStorage.getItem(KEY); } catch (e) { return null; }
  }
  // Simpan pilihan tema ke browser
  function saveTheme(t) {
    try { localStorage.setItem(KEY, t); } catch (e) { /* mode privat: abaikan */ }
  }
  // Terapkan tema ke halaman dengan mengubah atribut data-theme
  function applyTheme(t) { root.setAttribute('data-theme', t); }
  // Tema mengikuti sistem: gelap kalau perangkat gelap
  function systemTheme() { return mq && mq.matches ? 'dark' : 'light'; }

  // Pasang tema saat halaman dibuka: pilihan user dulu, kalau belum ada ikut sistem
  applyTheme(savedTheme() || systemTheme());

  if (mq) {
    // Kalau user belum memilih tema, ikut perubahan tema sistem
    var follow = function () { if (!savedTheme()) applyTheme(systemTheme()); };
    if (mq.addEventListener) mq.addEventListener('change', follow);
    else if (mq.addListener) mq.addListener(follow);
  }

  // Ikon matahari dan bulan untuk tombol ganti tema
  var SUN = '<svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';
  var MOON = '<svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>';

  // Buat tombol ganti tema dan tempatkan di topbar
  function initThemeToggle() {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'theme-toggle';
    btn.innerHTML = SUN + MOON;

    // Samakan tampilan tombol (ikon dan label) dengan tema yang aktif
    function sync() {
      var dark = root.getAttribute('data-theme') === 'dark';
      btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
      var label = dark ? 'Pakai tampilan terang' : 'Pakai tampilan gelap';
      btn.setAttribute('aria-label', label);
      btn.title = label;
    }
    // Saat diklik: balik tema, simpan, lalu perbarui tombol
    btn.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      applyTheme(next);
      saveTheme(next);
      sync();
    });
    sync();

    // Tempel tombol di topbar; kalau tidak ada, tampilkan mengambang
    var topbar = document.querySelector('.topbar');
    if (topbar) {
      topbar.appendChild(btn);
    } else {
      btn.classList.add('floating');
      document.body.appendChild(btn);
    }
  }

  /* ---------- Angka bergerak ---------- */
  // Format angka dengan pemisah ribuan sesuai bahasa Indonesia
  function fmt(n) {
    try { return n.toLocaleString('id-ID'); } catch (e) { return String(n); }
  }
  // Animasi angka dari 0 naik ke nilai aslinya
  function countUp(el) {
    var target = parseInt(el.getAttribute('data-count'), 10);
    if (isNaN(target)) return;
    if (reduceMotion || target === 0) { el.textContent = fmt(target); return; }
    var dur = 900;
    var t0 = null;
    el.textContent = '0';
    // Satu langkah animasi, dipanggil berulang tiap frame
    function step(ts) {
      if (t0 === null) t0 = ts;
      var p = Math.min((ts - t0) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(Math.round(target * eased));
      if (p < 1) requestAnimationFrame(step);
      else el.textContent = fmt(target);
    }
    requestAnimationFrame(step);
  }

  /* ---------- Persentase di kartu statistik ---------- */
  // Tambah keterangan persentase dan batang kecil di tiap kartu statistik
  function initStatMeters() {
    var totalEl = document.querySelector('.stat-total [data-count]');
    if (!totalEl) return;
    var total = parseInt(totalEl.getAttribute('data-count'), 10) || 0;
    var totalCard = totalEl.closest('.stat');
    if (totalCard) {
      var tn = document.createElement('small');
      tn.className = 'stat-note';
      tn.textContent = 'Semua laporan';
      var tm = document.createElement('div');
      tm.className = 'meter';
      tm.setAttribute('aria-hidden', 'true');
      tm.innerHTML = '<i style="--p:100%"></i>';
      totalCard.appendChild(tn);
      totalCard.appendChild(tm);
    }
    var cards = document.querySelectorAll('.stat:not(.stat-total)');
    Array.prototype.forEach.call(cards, function (card) {
      var num = card.querySelector('[data-count]');
      if (!num) return;
      var v = parseInt(num.getAttribute('data-count'), 10) || 0;
      var pct = total > 0 ? Math.round((v / total) * 100) : 0;
      var note = document.createElement('small');
      note.className = 'stat-note';
      note.textContent = pct + '% dari total';
      var meter = document.createElement('div');
      meter.className = 'meter';
      meter.setAttribute('aria-hidden', 'true');
      meter.innerHTML = '<i style="--p:' + pct + '%"></i>';
      card.appendChild(note);
      card.appendChild(meter);
    });
  }

  /* ---------- Grafik donat komposisi status ---------- */
  // Gambar grafik donat komposisi status dari data di legend
  function initStatusChart() {
    var box = document.querySelector('[data-status-chart]');
    if (!box) return;
    var holder = box.querySelector('[data-donut]');
    var items = Array.prototype.slice.call(box.querySelectorAll('.legend li'));
    if (!holder || !items.length) return;

    var data = items.map(function (li) {
      return {
        key: li.getAttribute('data-key'),
        value: parseInt(li.getAttribute('data-value'), 10) || 0,
        label: (li.querySelector('.name') || {}).textContent || '',
        li: li,
        seg: null,
        len: 0
      };
    });
    var total = data.reduce(function (a, d) { return a + d.value; }, 0);

    // Namespace SVG supaya elemen grafik bisa dibuat lewat JavaScript
    var NS = 'http://www.w3.org/2000/svg';
    var R = 48;
    var C = 2 * Math.PI * R;
    var svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', '0 0 120 120');
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', 'Grafik donat komposisi status pengaduan');

    // Buat satu lingkaran SVG (track atau segmen) untuk grafik
    function circle(cls) {
      var c = document.createElementNS(NS, 'circle');
      c.setAttribute('cx', '60');
      c.setAttribute('cy', '60');
      c.setAttribute('r', String(R));
      c.setAttribute('class', cls);
      return c;
    }
    svg.appendChild(circle('track'));

    var nonZero = data.filter(function (d) { return d.value > 0; }).length;
    var gap = nonZero > 1 ? 2.5 : 0;
    var offset = 0;

    data.forEach(function (d) {
      var pct = total > 0 ? Math.round((d.value / total) * 100) : 0;
      // Tulis persentase di legend
      var pctEl = d.li.querySelector('.pct');
      if (pctEl) pctEl.textContent = pct + '%';
      if (!d.value || !total) return;
      // Panjang segmen ini, dihitung dari keliling lingkaran
      var full = (d.value / total) * C;
      var seg = circle('seg seg-' + d.key);
      seg.style.strokeDasharray = reduceMotion ? Math.max(full - gap, 0.5) + ' ' + C : '0 ' + C;
      seg.style.strokeDashoffset = String(-offset);
      d.seg = seg;
      d.len = Math.max(full - gap, 0.5);
      offset += full;
      svg.appendChild(seg);
    });
    // Tampilkan grafik di dalam kotak
    holder.appendChild(svg);

    var center = document.createElement('div');
    // Teks di tengah donat: total atau nilai yang sedang disorot
    center.className = 'donut-center';
    center.innerHTML = '<b></b><span></span>';
    holder.appendChild(center);
    var cNum = center.querySelector('b');
    var cLbl = center.querySelector('span');

    // Tampilkan total di tengah donat
    function showTotal() {
      cNum.textContent = fmt(total);
      cLbl.textContent = 'pengaduan';
    }
    // Tampilkan nilai dan persentase satu status di tengah donat
    function showItem(d) {
      var pct = total > 0 ? Math.round((d.value / total) * 100) : 0;
      cNum.textContent = fmt(d.value);
      cLbl.textContent = d.label + ' · ' + pct + '%';
    }
    // Sorot satu status (legend dan segmennya); yang lain diredupkan
    function highlight(active) {
      data.forEach(function (d) {
        var on = d === active;
        d.li.classList.toggle('is-active', on);
        if (d.seg) {
          d.seg.classList.toggle('hot', on);
          d.seg.classList.toggle('dim', !!active && !on);
        }
      });
      // Awalnya tampilkan total
      if (active) showItem(active); else showTotal();
    }
    showTotal();

    data.forEach(function (d) {
      // Legend bisa difokus dengan keyboard
      d.li.setAttribute('tabindex', '0');
      // Saat kursor atau fokus pindah ke legend atau segmen, sorot status itu
      d.li.addEventListener('mouseenter', function () { highlight(d); });
      d.li.addEventListener('mouseleave', function () { highlight(null); });
      d.li.addEventListener('focus', function () { highlight(d); });
      d.li.addEventListener('blur', function () { highlight(null); });
      if (d.seg) {
        d.seg.addEventListener('mouseenter', function () { highlight(d); });
        d.seg.addEventListener('mouseleave', function () { highlight(null); });
      }
    });

    // gambar segmen setelah frame pertama supaya transisi berjalan
    // Animasi segmen; dilewati kalau user minta animasi dikurangi
    if (!reduceMotion) {
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          data.forEach(function (d) {
            if (d.seg) d.seg.style.strokeDasharray = d.len + ' ' + (C - d.len);
          });
        });
      });
    }

    // Tulis kalimat persentase pengaduan yang sudah selesai
    var note = box.querySelector('[data-completion]');
    if (note) {
      var selesai = 0;
      data.forEach(function (d) { if (d.key === 'selesai') selesai = d.value; });
      note.textContent = total > 0
        ? Math.round((selesai / total) * 100) + '% pengaduan sudah selesai ditangani.'
        : 'Belum ada pengaduan yang masuk.';
    }
  }

  /* ---------- Tanggal hari ini ---------- */
  // Tampilkan tanggal hari ini dalam bahasa Indonesia
  function initToday() {
    var el = document.querySelector('[data-today]');
    if (!el) return;
    try {
      el.textContent = new Date().toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
      });
    } catch (e) { /* biarkan teks bawaan */ }
  }

  /* ---------- Sidebar: keyboard & Esc ---------- */
  // Atur sidebar: tombol keyboard dan tombol Esc untuk menutup
  function initSidebar() {
    var toggle = document.getElementById('menuToggle');
    var icon = document.querySelector('label.menu-icon');
    if (icon && toggle) {
      icon.setAttribute('role', 'button');
      icon.setAttribute('tabindex', '0');
      icon.setAttribute('aria-label', 'Buka atau tutup menu');
      // Ikon menu bisa dibuka lewat Enter atau spasi
      icon.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          toggle.checked = !toggle.checked;
        }
      });
    }
    document.addEventListener('keydown', function (e) {
      // Tekan Esc: tutup popup yang sedang terbuka atau sidebar
      if (e.key !== 'Escape') return;
      var openModal = document.querySelector('.modal-overlay.active');
      if (openModal) { openModal.classList.remove('active'); return; }
      if (toggle && toggle.checked && window.innerWidth <= 800) toggle.checked = false;
    });
  }

  /* ---------- Penghitung karakter pada textarea ---------- */
  // Tampilkan jumlah karakter di bawah textarea
  function initCharCount() {
    var areas = document.querySelectorAll('textarea[maxlength]');
    Array.prototype.forEach.call(areas, function (ta) {
      var max = parseInt(ta.getAttribute('maxlength'), 10);
      if (!max) return;
      var out = document.createElement('small');
      out.className = 'char-count';
      out.setAttribute('aria-live', 'polite');
      function update() {
        var n = ta.value.length;
        out.textContent = n + ' / ' + max;
        out.classList.toggle('near', n >= max * 0.9);
      }
      ta.addEventListener('input', update);
      ta.parentNode.insertBefore(out, ta.nextSibling);
      update();
    });
  }

  /* ---------- Tombol kirim: tanda memuat (tanpa menonaktifkan tombol) ---------- */
  // Tandai tombol kirim sedang memuat saat form dikirim
  function initSubmitState() {
    document.addEventListener('submit', function (e) {
      if (e.defaultPrevented) return;
      var form = e.target;
      var btn = form.querySelector('button[type="submit"].btn, button.btn:not([type])');
      if (btn) btn.classList.add('is-loading');
    });
    window.addEventListener('pageshow', function () {
      Array.prototype.forEach.call(document.querySelectorAll('.btn.is-loading'), function (b) {
        b.classList.remove('is-loading');
      });
    });
  }

  // Jalankan semua fungsi di atas setelah halaman siap
  function init() {
    initThemeToggle();
    Array.prototype.forEach.call(document.querySelectorAll('[data-count]'), countUp);
    initStatMeters();
    initStatusChart();
    initToday();
    initSidebar();
    initCharCount();
    initSubmitState();
  }

  // Tunggu halaman siap sebelum init, lalu jalankan
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
