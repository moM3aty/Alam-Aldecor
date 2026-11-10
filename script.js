// script.js
(function () {
  'use strict';

  var root = document.documentElement;
  var header = document.getElementById('siteHeader');
  var toggleBtn = document.getElementById('menuToggle');
  var closeBtn = document.getElementById('menuClose');
  var overlay = document.getElementById('menuOverlay');
  var panel = document.getElementById('mobileMenu');
  var mobileLinks = panel.querySelectorAll('.mobile-link');
  var allLinks = document.querySelectorAll('[data-section]');
  var sections = Array.prototype.slice.call(document.querySelectorAll('main > section[id]'));
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var hasObserver = 'IntersectionObserver' in window;
  var DESKTOP_MIN = 1280;
  var currentId = '';
  var ticking = false;

  function rand(min, max) {
    return Math.random() * (max - min) + min;
  }

  function setActive(id) {
    if (id === currentId) {
      return;
    }
    currentId = id;
    Array.prototype.forEach.call(allLinks, function (link) {
      var isActive = link.getAttribute('data-section') === id;
      link.classList.toggle('active', isActive);
      if (isActive) {
        link.setAttribute('aria-current', 'page');
      } else {
        link.removeAttribute('aria-current');
      }
    });
  }

  function updateOnScroll() {
    var y = window.pageYOffset || root.scrollTop || 0;
    header.classList.toggle('scrolled', y > 24);

    if (sections.length) {
      var line = header.offsetHeight + window.innerHeight * 0.25;
      var activeId = sections[0].id;

      for (var i = 0; i < sections.length; i++) {
        if (sections[i].getBoundingClientRect().top <= line) {
          activeId = sections[i].id;
        }
      }

      if (window.innerHeight + y >= root.scrollHeight - 4) {
        activeId = sections[sections.length - 1].id;
      }

      setActive(activeId);
    }

    ticking = false;
  }

  function requestTick() {
    if (!ticking) {
      ticking = true;
      window.requestAnimationFrame(updateOnScroll);
    }
  }

  function scrollToId(id) {
    var target = document.getElementById(id);
    if (!target) {
      return;
    }
    var top = id === 'home' ? 0 : target.getBoundingClientRect().top + (window.pageYOffset || root.scrollTop) - header.offsetHeight + 1;
    window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
  }

  function openMenu() {
    root.classList.add('menu-open');
    panel.setAttribute('aria-hidden', 'false');
    toggleBtn.setAttribute('aria-expanded', 'true');
    toggleBtn.setAttribute('aria-label', 'إغلاق القائمة');
    window.setTimeout(function () {
      closeBtn.focus({ preventScroll: true });
    }, 60);
  }

  function closeMenu(returnFocus) {
    if (!root.classList.contains('menu-open')) {
      return;
    }
    root.classList.remove('menu-open');
    panel.setAttribute('aria-hidden', 'true');
    toggleBtn.setAttribute('aria-expanded', 'false');
    toggleBtn.setAttribute('aria-label', 'فتح القائمة');
    if (returnFocus) {
      toggleBtn.focus({ preventScroll: true });
    }
  }

  toggleBtn.addEventListener('click', function () {
    if (root.classList.contains('menu-open')) {
      closeMenu(true);
    } else {
      openMenu();
    }
  });

  closeBtn.addEventListener('click', function () {
    closeMenu(true);
  });

  overlay.addEventListener('click', function () {
    closeMenu(true);
  });

  overlay.addEventListener('touchmove', function (e) {
    e.preventDefault();
  }, { passive: false });

  panel.addEventListener('touchmove', function (e) {
    if (panel.scrollHeight <= panel.clientHeight) {
      e.preventDefault();
    }
  }, { passive: false });

  Array.prototype.forEach.call(mobileLinks, function (link) {
    link.addEventListener('click', function (e) {
      var id = link.getAttribute('data-section');
      e.preventDefault();
      closeMenu(false);
      window.setTimeout(function () {
        scrollToId(id);
        try {
          history.replaceState(null, '', '#' + id);
        } catch (err) {
          return;
        }
      }, 120);
    });
  });

  document.addEventListener('keydown', function (e) {
    if (!root.classList.contains('menu-open')) {
      return;
    }
    if (e.key === 'Escape') {
      closeMenu(true);
      return;
    }
    if (e.key === 'Tab') {
      var focusables = panel.querySelectorAll('button, a[href]');
      var first = focusables[0];
      var last = focusables[focusables.length - 1];
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });

  window.addEventListener('resize', function () {
    if (window.innerWidth >= DESKTOP_MIN) {
      closeMenu(false);
    }
    requestTick();
  });

  window.addEventListener('scroll', requestTick, { passive: true });
  window.addEventListener('load', requestTick);
  window.addEventListener('hashchange', requestTick);

  function buildParticles() {
    var box = document.getElementById('particles');
    if (!box || reduceMotion) {
      return;
    }
    var count = window.innerWidth < 768 ? 5 : 9;
    var frag = document.createDocumentFragment();
    for (var i = 0; i < count; i++) {
      var el = document.createElement('span');
      var size = rand(10, 28);
      el.className = 'particle';
      el.style.left = rand(3, 95) + '%';
      el.style.width = size + 'px';
      el.style.height = size + 'px';
      el.style.animationDuration = rand(14, 26) + 's';
      el.style.animationDelay = '-' + rand(0, 24) + 's';
      el.style.setProperty('--drift', rand(-50, 50) + 'px');
      frag.appendChild(el);
    }
    box.appendChild(frag);
  }

  function setupParallax() {
    var layer = document.getElementById('heroParallax');
    var hero = document.getElementById('home');
    var canHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (!layer || !hero || reduceMotion || !canHover) {
      return;
    }
    var px = 0;
    var py = 0;
    var frame = 0;
    var apply = function () {
      layer.style.transform = 'translate3d(' + px + 'px,' + py + 'px,0)';
      frame = 0;
    };
    hero.addEventListener('pointermove', function (e) {
      var rect = hero.getBoundingClientRect();
      px = ((e.clientX - rect.left) / rect.width - 0.5) * -20;
      py = ((e.clientY - rect.top) / rect.height - 0.5) * -20;
      if (!frame) {
        frame = window.requestAnimationFrame(apply);
      }
    });
    hero.addEventListener('pointerleave', function () {
      px = 0;
      py = 0;
      if (!frame) {
        frame = window.requestAnimationFrame(apply);
      }
    });
  }

  function animateCount(el) {
    var target = parseInt(el.getAttribute('data-count'), 10) || 0;
    var suffix = el.getAttribute('data-suffix') || '';
    var duration = 1600;
    var start = null;
    function step(ts) {
      if (start === null) {
        start = ts;
      }
      var p = Math.min((ts - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased) + suffix;
      if (p < 1) {
        window.requestAnimationFrame(step);
      }
    }
    window.requestAnimationFrame(step);
  }

  function setupReveal() {
    var revealItems = Array.prototype.slice.call(document.querySelectorAll('[data-rv]'));
    var galItems = Array.prototype.slice.call(document.querySelectorAll('[data-gal]'));
    var counters = Array.prototype.slice.call(document.querySelectorAll('[data-count]'));

    if (!hasObserver || reduceMotion) {
      root.classList.add('no-io');
      galItems.forEach(function (el) {
        el.classList.add('in');
      });
      return;
    }

    revealItems.forEach(function (el, i) {
      el.classList.add('rv');
      el.style.transitionDelay = (i % 7) * 0.08 + 's';
    });

    counters.forEach(function (el) {
      el.textContent = '0' + (el.getAttribute('data-suffix') || '');
    });

    var revealObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });

    revealItems.forEach(function (el) {
      revealObserver.observe(el);
    });

    var galObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var idx = galItems.indexOf(entry.target);
          entry.target.style.transitionDelay = (idx % 4) * 0.09 + 's';
          entry.target.classList.add('in');
          window.setTimeout(function () {
            entry.target.style.transitionDelay = '0s';
          }, 1200);
          galObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -4% 0px' });

    galItems.forEach(function (el) {
      galObserver.observe(el);
    });

    var countObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          countObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.6 });

    counters.forEach(function (el) {
      countObserver.observe(el);
    });
  }

  function setupTabs() {
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.about-tab'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.about-panel'));

    function selectTab(tab, focus) {
      tabs.forEach(function (t) {
        var on = t === tab;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        t.setAttribute('tabindex', on ? '0' : '-1');
      });
      panels.forEach(function (p) {
        p.hidden = p.id !== tab.getAttribute('aria-controls');
      });
      if (focus) {
        tab.focus();
      }
    }

    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        selectTab(tab, false);
      });
      tab.addEventListener('keydown', function (e) {
        var next = -1;
        if (e.key === 'ArrowLeft') {
          next = (index + 1) % tabs.length;
        } else if (e.key === 'ArrowRight') {
          next = (index - 1 + tabs.length) % tabs.length;
        } else if (e.key === 'Home') {
          next = 0;
        } else if (e.key === 'End') {
          next = tabs.length - 1;
        }
        if (next > -1) {
          e.preventDefault();
          selectTab(tabs[next], true);
        }
      });
    });
  }

  function hideBrokenImages() {
    var imgs = Array.prototype.slice.call(document.querySelectorAll('.media-photo img, .svc-photo img, .gal-item img'));
    imgs.forEach(function (img) {
      img.addEventListener('error', function () {
        img.style.display = 'none';
      });
    });
  }

  function setupSlider() {
    var track = document.getElementById('tstTrack');
    var pager = document.getElementById('tstPager');
    var nextBtn = document.getElementById('tstNext');
    var prevBtn = document.getElementById('tstPrev');

    if (!track || !pager) {
      return;
    }

    var cards = Array.prototype.slice.call(track.querySelectorAll('.tst-card'));
    var pages = cards.length;
    var active = 0;
    var timer = null;
    var paused = false;
    var visible = false;
    var tick = false;

    function isRtl() {
      return window.getComputedStyle(track).direction === 'rtl';
    }

    function calcPages() {
      var cardW = cards[0].getBoundingClientRect().width + 16;
      var inView = Math.max(1, Math.round(track.clientWidth / cardW));
      return Math.max(1, cards.length - inView + 1);
    }

    function mark(index) {
      active = index;
      var dots = pager.children;
      for (var i = 0; i < dots.length; i++) {
        dots[i].classList.toggle('is-active', i === index);
        dots[i].setAttribute('aria-selected', i === index ? 'true' : 'false');
      }
    }

    function goTo(index, smooth) {
      var i = Math.max(0, Math.min(index, pages - 1));
      var tr = track.getBoundingClientRect();
      var cr = cards[i].getBoundingClientRect();
      var delta = isRtl() ? cr.right - tr.right : cr.left - tr.left;
      track.scrollBy({ left: delta, behavior: smooth && !reduceMotion ? 'smooth' : 'auto' });
      mark(i);
    }

    function buildDots() {
      pages = calcPages();
      pager.innerHTML = '';
      for (var i = 0; i < pages; i++) {
        var d = document.createElement('button');
        d.type = 'button';
        d.className = 'tst-dot';
        d.setAttribute('role', 'tab');
        d.setAttribute('aria-label', 'الرأي ' + (i + 1));
        (function (index) {
          d.addEventListener('click', function () {
            goTo(index, true);
            start();
          });
        })(i);
        pager.appendChild(d);
      }
      mark(Math.min(active, pages - 1));
    }

    function detect() {
      var tr = track.getBoundingClientRect();
      var rtl = isRtl();
      var best = 0;
      var bestDist = Infinity;
      for (var i = 0; i < cards.length; i++) {
        var cr = cards[i].getBoundingClientRect();
        var dist = Math.abs(rtl ? cr.right - tr.right : cr.left - tr.left);
        if (dist < bestDist) {
          bestDist = dist;
          best = i;
        }
      }
      var max = track.scrollWidth - track.clientWidth;
      if (max > 0 && Math.abs(track.scrollLeft) >= max - 4) {
        best = pages - 1;
      }
      mark(Math.min(best, pages - 1));
      tick = false;
    }

    function next() {
      goTo(active + 1 >= pages ? 0 : active + 1, true);
    }

    function prev() {
      goTo(active - 1 < 0 ? pages - 1 : active - 1, true);
    }

    function stop() {
      if (timer) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    function start() {
      stop();
      if (reduceMotion || paused || !visible) {
        return;
      }
      timer = window.setInterval(next, 5500);
    }

    nextBtn.addEventListener('click', function () {
      next();
      start();
    });

    prevBtn.addEventListener('click', function () {
      prev();
      start();
    });

    track.addEventListener('scroll', function () {
      if (!tick) {
        tick = true;
        window.requestAnimationFrame(detect);
      }
    }, { passive: true });

    track.addEventListener('mouseenter', function () {
      paused = true;
      stop();
    });

    track.addEventListener('mouseleave', function () {
      paused = false;
      start();
    });

    track.addEventListener('touchstart', function () {
      paused = true;
      stop();
    }, { passive: true });

    track.addEventListener('touchend', function () {
      window.setTimeout(function () {
        paused = false;
        start();
      }, 4000);
    }, { passive: true });

    track.addEventListener('focusin', function () {
      paused = true;
      stop();
    });

    track.addEventListener('focusout', function () {
      paused = false;
      start();
    });

    track.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        next();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        prev();
      }
    });

    var resizeTimer = null;
    window.addEventListener('resize', function () {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(function () {
        buildDots();
        goTo(active, false);
      }, 150);
    });

    if (hasObserver) {
      var io = new IntersectionObserver(function (entries) {
        visible = entries[0].isIntersecting;
        start();
      }, { threshold: 0.3 });
      io.observe(track);
    } else {
      visible = true;
    }

    buildDots();
    start();
  }

  function setupWhyCards() {
    var cards = Array.prototype.slice.call(document.querySelectorAll('.why-card'));
    var canHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (!cards.length || !canHover) {
      return;
    }
    cards.forEach(function (card) {
      card.addEventListener('pointermove', function (e) {
        var rect = card.getBoundingClientRect();
        card.style.setProperty('--mx', (e.clientX - rect.left) + 'px');
        card.style.setProperty('--my', (e.clientY - rect.top) + 'px');
      });
    });
  }

  function setupForm() {
    var form = document.getElementById('ctForm');
    if (!form) {
      return;
    }

    var nameInput = document.getElementById('ctName');
    var phoneInput = document.getElementById('ctPhone');
    var cityInput = document.getElementById('ctCity');
    var serviceInput = document.getElementById('ctService');
    var msgInput = document.getElementById('ctMsg');
    var errorBox = document.getElementById('ctError');
    var WA_NUMBER = '966544371877';

    function toLatinDigits(str) {
      return str
        .replace(/[\u0660-\u0669]/g, function (d) {
          return String(d.charCodeAt(0) - 1632);
        })
        .replace(/[\u06F0-\u06F9]/g, function (d) {
          return String(d.charCodeAt(0) - 1776);
        });
    }

    function showError(text, field) {
      errorBox.classList.remove('is-ok');
      errorBox.textContent = text;
      if (field) {
        field.classList.add('is-invalid');
        field.focus();
      }
    }

    function clearErrors() {
      errorBox.textContent = '';
      errorBox.classList.remove('is-ok');
      nameInput.classList.remove('is-invalid');
      phoneInput.classList.remove('is-invalid');
    }

    [nameInput, phoneInput].forEach(function (input) {
      input.addEventListener('input', function () {
        input.classList.remove('is-invalid');
        errorBox.textContent = '';
      });
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      clearErrors();

      var name = nameInput.value.trim();
      var phone = toLatinDigits(phoneInput.value).replace(/[\s\-()]/g, '');
      var city = cityInput.value;
      var service = serviceInput.value;
      var message = msgInput.value.trim();

      if (name.length < 2) {
        showError('من فضلك اكتب اسمك.', nameInput);
        return;
      }

      if (!/^\+?\d{9,15}$/.test(phone)) {
        showError('من فضلك اكتب رقم جوال صحيحاً.', phoneInput);
        return;
      }

      var lines = [
        'السلام عليكم، أرغب في الاستفسار عن خدماتكم.',
        '',
        'الاسم: ' + name,
        'رقم الجوال: ' + phone
      ];

      if (city) {
        lines.push('المدينة: ' + city);
      }

      if (service) {
        lines.push('الخدمة المطلوبة: ' + service);
      }

      if (message) {
        lines.push('التفاصيل: ' + message);
      }
var fd = new FormData();
fd.append('name', name);
fd.append('phone', phone);
fd.append('city', city);
fd.append('service', service);
fd.append('message', message);
if (navigator.sendBeacon) navigator.sendBeacon('save-message.php', fd);
      var url = 'https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(lines.join('\n'));
      var win = window.open(url, '_blank', 'noopener');

      if (!win) {
        window.location.href = url;
      }

      errorBox.classList.add('is-ok');
      errorBox.textContent = 'تم تجهيز رسالتك، أكمل الإرسال من واتساب.';
    });
  }

  function setYear() {
    var year = document.getElementById('sfYear');
    if (year) {
      year.textContent = String(new Date().getFullYear());
    }
  }

  buildParticles();
  setupParallax();
  setupReveal();
  setupTabs();
  hideBrokenImages();
  setupSlider();
  setupWhyCards();
  setupForm();
  setYear();
  updateOnScroll();
})();