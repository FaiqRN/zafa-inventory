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
      // No errors, hide splash screen immediately
      splashScreen.style.display = 'none';
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
// 1. Password tersembunyi secara default.
// 2. Klik ikon mata untuk menampilkan/menyembunyikan password secara manual.
// ============================================

const togglePassword = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');
const eyeHide = document.getElementById('eyeHide');
const eyeShow = document.getElementById('eyeShow');

if (togglePassword && passwordInput && eyeHide && eyeShow) {
  // Event: User klik icon mata (manual toggle)
  togglePassword.addEventListener('click', function () {
    const currentType = passwordInput.getAttribute('type');

    if (currentType === 'password') {
      // Password TERSEMBUNYI -> TAMPILKAN
      passwordInput.setAttribute('type', 'text');
      eyeHide.classList.remove('hide');
      eyeShow.classList.add('hide');
      this.setAttribute('title', 'Hide password');
    } else {
      // Password TERLIHAT -> SEMBUNYIKAN
      passwordInput.setAttribute('type', 'password');
      eyeHide.classList.add('hide');
      eyeShow.classList.remove('hide');
      this.setAttribute('title', 'Show password');
    }
  });
}
