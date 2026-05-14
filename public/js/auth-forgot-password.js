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
