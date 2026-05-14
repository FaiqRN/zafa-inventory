const resetContainer = document.getElementById('resetContainer');
const backLinks = document.querySelectorAll('.js-back-login');

if (resetContainer) {
  requestAnimationFrame(function () {
    resetContainer.classList.add('is-ready');
  });

  backLinks.forEach(function (link) {
    link.addEventListener('click', function (event) {
      event.preventDefault();
      resetContainer.classList.add('is-leaving');

      const targetUrl = link.getAttribute('href');
      setTimeout(function () {
        window.location.href = targetUrl;
      }, 800);
    });
  });
}

// Toggle password visibility for password field
const togglePassword1 = document.getElementById('togglePassword1');
const password = document.getElementById('password');
const eyeShow1 = document.getElementById('eyeShow1');
const eyeHide1 = document.getElementById('eyeHide1');

if (togglePassword1 && password && eyeShow1 && eyeHide1) {
  togglePassword1.addEventListener('click', function() {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    eyeShow1.classList.toggle('hide');
    eyeHide1.classList.toggle('hide');
  });
}

// Toggle password visibility for confirm password field
const togglePassword2 = document.getElementById('togglePassword2');
const passwordConfirmation = document.getElementById('password_confirmation');
const eyeShow2 = document.getElementById('eyeShow2');
const eyeHide2 = document.getElementById('eyeHide2');

if (togglePassword2 && passwordConfirmation && eyeShow2 && eyeHide2) {
  togglePassword2.addEventListener('click', function() {
    const type = passwordConfirmation.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordConfirmation.setAttribute('type', type);
    eyeShow2.classList.toggle('hide');
    eyeHide2.classList.toggle('hide');
  });
}
