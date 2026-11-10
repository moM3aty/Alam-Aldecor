  </div><!-- /.content -->
</main>
</div><!-- /.admin-shell -->

<script>
(function () {
  'use strict';

  // 1. Sidebar toggle (mobile)
  var menuBtn = document.getElementById('menuBtn');
  var sidebar = document.getElementById('sidebar');

  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (window.innerWidth <= 860 &&
          sidebar.classList.contains('open') &&
          !sidebar.contains(e.target) &&
          e.target !== menuBtn) {
        sidebar.classList.remove('open');
      }
    });
  }

  // 2. SweetAlert2 Global Confirm (Replaces window.confirm)
  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;

    e.preventDefault();
    e.stopPropagation();

    var message = el.getAttribute('data-confirm') || 'هل أنت متأكد؟';

    Swal.fire({
      title: 'هل أنت متأكد؟',
      text: message,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'نعم، تأكيد',
      cancelButtonText: 'إلغاء',
      reverseButtons: true,
      customClass: {
        popup: 'swal2-rtl'
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        if (el.tagName.toLowerCase() === 'a') {
          window.location.href = el.href;
        } else {
          var form = el.closest('form');
          if (form) {
            // Remove attribute to prevent infinite loop
            el.removeAttribute('data-confirm');
            form.submit();
          }
        }
      }
    });
  });

  // 3. Flash Messages to SweetAlert Toast
  <?php
    // We inject the flash message directly into JS if it exists
    $flashData = getFlash();
    if ($flashData):
  ?>
    Swal.fire({
      toast: true,
      position: 'top-start',
      icon: '<?= $flashData['type'] === 'ok' ? 'success' : 'error' ?>',
      title: '<?= e(addslashes($flashData['msg'])) ?>',
      showConfirmButton: false,
      timer: 4000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });
  <?php endif; ?>

  // 4. Count-up animation
  function animateCount(el) {
    var target = parseInt(el.getAttribute('data-count'), 10) || 0;
    var duration = 1400;
    var start = performance.now();
    function step(now) {
      var p = Math.min((now - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased);
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  if ('IntersectionObserver' in window) {
    var countIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          animateCount(en.target);
          countIO.unobserve(en.target);
        }
      });
    }, { threshold: 0.4 });
    document.querySelectorAll('[data-count]').forEach(function (el) {
      countIO.observe(el);
    });
  } else {
    document.querySelectorAll('[data-count]').forEach(animateCount);
  }

})();
</script>
</body>
</html>