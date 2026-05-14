// ============================================
// SPLASH SCREEN FUNCTIONALITY
// ============================================

const splashScreen = document.getElementById('splash-screen');
const welcomeContent = document.getElementById('splash-content-welcome');
const rejectedContent = document.getElementById('splash-content-rejected');
const errorDetail = document.getElementById('error-detail');

if (splashScreen && welcomeContent && rejectedContent && errorDetail) {
  const hasErrors = splashScreen.dataset.hasErrors === '1';
  const errorMessage = splashScreen.dataset.errorMessage || 'Username atau password tidak valid';

  window.addEventListener('load', function () {
    if (hasErrors) {
      // Show rejected state
      splashScreen.className = 'rejected';
      welcomeContent.style.display = 'none';
      rejectedContent.style.display = 'flex';
      rejectedContent.style.flexDirection = 'column';
      rejectedContent.style.alignItems = 'center';
      errorDetail.textContent = errorMessage;

      setTimeout(function () {
        splashScreen.classList.add('fade-out');
        setTimeout(function() {
          splashScreen.style.display = 'none';
        }, 500);
      }, 2800);
    } else {
      // Show welcome state
      splashScreen.className = 'welcome';
      welcomeContent.style.display = 'flex';
      welcomeContent.style.flexDirection = 'column';
      welcomeContent.style.alignItems = 'center';
      rejectedContent.style.display = 'none';

      setTimeout(function () {
        splashScreen.classList.add('fade-out');
        setTimeout(function() {
          splashScreen.style.display = 'none';
        }, 500);
      }, 2000);
    }
  });
}

// ============================================
// AUTH PANEL TRANSITION
// ============================================

const authContainer = document.getElementById('authContainer');
const forgotLinks = document.querySelectorAll('.js-forgot-link');
const backLinks = document.querySelectorAll('.js-back-login');

if (authContainer) {
  const startMode = authContainer.dataset.startMode;

  if (startMode === 'forgot') {
    authContainer.classList.add('is-forgot');
  }

  forgotLinks.forEach(function(link) {
    link.addEventListener('click', function(event) {
      event.preventDefault();
      authContainer.classList.add('is-forgot');
    });
  });

  backLinks.forEach(function(link) {
    link.addEventListener('click', function(event) {
      event.preventDefault();
      authContainer.classList.remove('is-forgot');
    });
  });
}

// ============================================
// FORGOT SUCCESS COUNTDOWN
// ============================================

const countdownContainer = document.getElementById('countdown-container');
const countdownEl = document.getElementById('countdown');
const resendForm = document.getElementById('resend-form');

if (countdownContainer && countdownEl && resendForm) {
  const cooldownUntilRaw = countdownContainer.dataset.cooldownUntil || '0';
  const cooldownUntil = parseInt(cooldownUntilRaw, 10) || 0;

  function updateCountdown() {
    const now = Math.floor(Date.now() / 1000);
    const remaining = cooldownUntil - now;

    if (remaining <= 0) {
      countdownContainer.style.display = 'none';
      resendForm.style.display = 'block';
      return;
    }

    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;
    countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

    setTimeout(updateCountdown, 1000);
  }

  updateCountdown();
}

// ============================================
// PASSWORD SHOW/HIDE FUNCTIONALITY
// ============================================
// BEHAVIOR:
// 1. Saat halaman dibuka: password TERLIHAT (type="text"), icon HIDE (eyeHide tampil)
// 2. Saat user mengetik: password TETAP TERLIHAT
// 3. Setelah berhenti 1.5 detik: password AUTO-TERTUTUP (type="password"), icon SHOW (eyeShow tampil)
// 4. Klik icon: toggle manual antara show/hide
// ============================================

const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const eyeHide = document.getElementById('eyeHide');
const eyeShow = document.getElementById('eyeShow');

if (togglePassword && passwordInput && eyeHide && eyeShow) {
  let typingTimer;
  let isManuallyToggled = false;

  // Event: User mengetik password
  passwordInput.addEventListener('input', function() {
    // Clear timer sebelumnya
    clearTimeout(typingTimer);

    // Saat mengetik: password TETAP TERLIHAT (kecuali user manual hide)
    if (!isManuallyToggled) {
      passwordInput.setAttribute('type', 'text');
      eyeHide.classList.remove('hide');
      eyeShow.classList.add('hide');
      togglePassword.setAttribute('title', 'Hide password');
    }

    // Set timer untuk auto-hide setelah 1.5 detik tidak mengetik
    typingTimer = setTimeout(function() {
      if (!isManuallyToggled) {
        // AUTO-HIDE: password jadi dots
        passwordInput.setAttribute('type', 'password');
        eyeHide.classList.add('hide');
        eyeShow.classList.remove('hide');
        togglePassword.setAttribute('title', 'Show password');
      }
    }, 200); // 0.2 detik
  });

  // Event: User klik icon mata (manual toggle)
  togglePassword.addEventListener('click', function () {
    // Clear auto-hide timer
    clearTimeout(typingTimer);

    const currentType = passwordInput.getAttribute('type');

    if (currentType === 'password') {
      // Password TERSEMBUNYI -> TAMPILKAN
      passwordInput.setAttribute('type', 'text');
      eyeHide.classList.remove('hide');
      eyeShow.classList.add('hide');
      this.setAttribute('title', 'Hide password');
      isManuallyToggled = true;
    } else {
      // Password TERLIHAT -> SEMBUNYIKAN
      passwordInput.setAttribute('type', 'password');
      eyeHide.classList.add('hide');
      eyeShow.classList.remove('hide');
      this.setAttribute('title', 'Show password');
      isManuallyToggled = true;
    }
  });

  // Event: User mulai mengetik lagi (reset flag manual)
  passwordInput.addEventListener('keydown', function() {
    if (isManuallyToggled) {
      isManuallyToggled = false;
    }
  });

  // Event: User focus ke field password (jika ada isi, tampilkan)
  passwordInput.addEventListener('focus', function() {
    if (!isManuallyToggled && passwordInput.value.length > 0) {
      passwordInput.setAttribute('type', 'text');
      eyeHide.classList.remove('hide');
      eyeShow.classList.add('hide');
      togglePassword.setAttribute('title', 'Hide password');
    }
  });
}
