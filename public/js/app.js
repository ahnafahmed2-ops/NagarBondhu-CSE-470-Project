(function () {
  // Live character counter for the description box
  document.querySelectorAll('textarea[data-counter]').forEach(function (box) {
    var out = document.getElementById(box.dataset.counter);
    var update = function () { out.textContent = box.value.length; };
    box.addEventListener('input', update);
    update();
  });

  // Preview the chosen evidence photo before uploading
  var input = document.getElementById('evidence');
  var preview = document.getElementById('evidence-preview');
  if (input && preview) {
    input.addEventListener('change', function () {
      var file = input.files[0];
      if (file && file.type.indexOf('image/') === 0) {
        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
      } else {
        preview.hidden = true;
      }
    });
  }

  // Confirm destructive actions; stop double submits
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
  });
  document.querySelectorAll('form.report-form').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type=submit]');
      if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
    });
  });
})();
