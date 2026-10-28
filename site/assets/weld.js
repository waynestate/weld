/* WELD front page: the copy button. A few lines, no framework.
   Tries the clipboard API first; if it is unavailable (for example a
   file:// page), it selects the command so the user can copy it by hand. */
(function () {
  "use strict";

  document.querySelectorAll(".copy-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var text = btn.getAttribute("data-copy") || "";
      var label = btn.querySelector(".copy-btn__label");

      function done(ok) {
        if (!label) {
          return;
        }
        label.textContent = ok ? "Copied" : "Selected";
        setTimeout(function () {
          label.textContent = "Copy";
        }, 2000);
      }

      function selectCommand() {
        var target = document.querySelector(btn.getAttribute("data-target") || "");
        if (!target) {
          return;
        }
        var range = document.createRange();
        range.selectNodeContents(target);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(text)
          .then(function () {
            done(true);
          }, function () {
            selectCommand();
            done(false);
          });
      } else {
        selectCommand();
        done(false);
      }
    });
  });
})();
