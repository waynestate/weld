/* ============================================================================
   WELD front page: the copy button, and the sparks.

   Vanilla, no framework, no network. Everything that moves is gated twice:
   the <head> script only adds .motion when the system has no reduced-motion
   request, and motionOn() checks again before anything starts. With reduced
   motion the page you get is the finished weld: every word visible from the
   first paint, no typing, no particles. Screen readers always get the full
   text, because typing only changes opacity; the letters are in the DOM from
   the start.

   Sections: 1. setup  2. spark layer  3. hero  4. typing  5. journey
             6. cast  7. copy buttons  8. the torch (shh)
   ========================================================================== */
(function () {
  "use strict";

  /* --------------------------------------------------------------- 1. setup */

  var root = document.documentElement;
  var reduce = window.matchMedia ? window.matchMedia("(prefers-reduced-motion: reduce)") : null;

  function motionOn() {
    return root.classList.contains("motion") && !(reduce && reduce.matches);
  }

  // If someone flips reduced motion on mid-visit, cool the whole page at once:
  // without .motion every animated state in the CSS falls away.
  if (reduce && reduce.addEventListener) {
    reduce.addEventListener("change", function (e) {
      if (e.matches) {
        root.classList.remove("motion");
        Sparks.clear();
      }
    });
  }

  var hasIO = "IntersectionObserver" in window;

  function onceVisible(els, threshold, fn) {
    if (!hasIO) {
      els.forEach(fn);
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          io.unobserve(entry.target);
          fn(entry.target);
        }
      });
    }, { threshold: threshold, rootMargin: "0px 0px -8% 0px" });
    els.forEach(function (el) {
      io.observe(el);
    });
  }

  function rand(a, b) {
    return a + Math.random() * (b - a);
  }

  var SPARK_COLOURS = ["#fff1b8", "#ffe08a", "#ffcc33", "#ffcc33", "#ff9f43"];

  /* -------------------------------------------------------- 2. spark layer */

  // One fixed canvas over the page for loose sparks: bursts, the beads'
  // drips, the torch. It only runs while there are sparks in the air.
  var Sparks = (function () {
    var canvas = null;
    var ctx = null;
    var parts = [];
    var running = false;
    var last = 0;
    var dpr = 1;

    function ensure() {
      if (canvas) {
        return;
      }
      canvas = document.createElement("canvas");
      canvas.className = "spark-layer";
      canvas.setAttribute("aria-hidden", "true");
      document.body.appendChild(canvas);
      ctx = canvas.getContext("2d");
      size();
      window.addEventListener("resize", size);
    }

    function size() {
      dpr = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = Math.round(window.innerWidth * dpr);
      canvas.height = Math.round(window.innerHeight * dpr);
    }

    // x, y in viewport pixels. opts: count, speed, spread (radians around
    // angle), angle (0 = right, -PI/2 = up), life (ms), gravity.
    function emit(x, y, opts) {
      if (!motionOn()) {
        return;
      }
      ensure();
      opts = opts || {};
      var n = opts.count || 12;
      for (var i = 0; i < n; i++) {
        var ang = (opts.angle === undefined ? -Math.PI / 2 : opts.angle) + rand(-1, 1) * (opts.spread === undefined ? Math.PI : opts.spread);
        var sp = rand(0.35, 1) * (opts.speed || 0.5);
        parts.push({
          x: x,
          y: y,
          vx: Math.cos(ang) * sp,
          vy: Math.sin(ang) * sp,
          life: 0,
          max: rand(0.6, 1) * (opts.life || 750),
          w: rand(1, 2.2),
          g: opts.gravity === undefined ? 0.0016 : opts.gravity,
          c: SPARK_COLOURS[(Math.random() * SPARK_COLOURS.length) | 0]
        });
      }
      if (parts.length > 600) {
        parts.splice(0, parts.length - 600);
      }
      if (!running) {
        running = true;
        last = performance.now();
        requestAnimationFrame(tick);
      }
    }

    function tick(now) {
      var dt = Math.min(now - last, 40);
      last = now;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.globalCompositeOperation = "lighter";
      ctx.lineCap = "round";
      for (var i = parts.length - 1; i >= 0; i--) {
        var p = parts[i];
        p.life += dt;
        if (p.life >= p.max) {
          parts.splice(i, 1);
          continue;
        }
        p.vy += p.g * dt;
        p.vx *= 0.995;
        p.x += p.vx * dt;
        p.y += p.vy * dt;
        var k = 1 - p.life / p.max;
        // A spark is a streak: draw it along its own velocity.
        ctx.globalAlpha = k;
        ctx.strokeStyle = p.c;
        ctx.lineWidth = p.w * (0.5 + k * 0.5);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        ctx.lineTo(p.x - p.vx * 18, p.y - p.vy * 18);
        ctx.stroke();
      }
      ctx.globalAlpha = 1;
      if (parts.length) {
        requestAnimationFrame(tick);
      } else {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        running = false;
      }
    }

    // Follow an element (a bead's tip) for a while and let sparks drip off it.
    function trail(el, ms, perFrame) {
      if (!motionOn() || !el) {
        return;
      }
      var start = performance.now();
      (function step(now) {
        if (now - start > ms) {
          return;
        }
        var r = el.getBoundingClientRect();
        if (r.width || r.height) {
          emit(r.left + r.width / 2, r.top + r.height / 2, {
            count: perFrame || 1,
            speed: 0.32,
            spread: 1.3,
            angle: -Math.PI / 2,
            life: 650
          });
        }
        requestAnimationFrame(step);
      })(start);
    }

    function clear() {
      parts.length = 0;
    }

    return { emit: emit, trail: trail, clear: clear };
  })();

  /* ---------------------------------------------------------------- 3. hero */

  var heroIcon = document.querySelector(".hero__icon");
  var heroFlash = document.querySelector(".hero__flash");

  // The visor sits at 68% across, 46% down the helmet mark.
  function visorPoint() {
    var r = heroIcon.getBoundingClientRect();
    return { x: r.left + r.width * 0.68, y: r.top + r.height * 0.46 };
  }

  function strike(big) {
    if (!heroIcon) {
      return;
    }
    var v = visorPoint();
    Sparks.emit(v.x, v.y, { count: big ? 70 : 28, speed: big ? 0.75 : 0.55, spread: Math.PI, life: big ? 1100 : 800 });
  }

  if (motionOn() && heroIcon) {
    // The arc strikes as the helmet lands (the CSS flash fires at 650ms).
    setTimeout(function () {
      strike(true);
    }, 700);

    // Click the helmet for another arc. The flash is rate-limited to once a
    // second, so nobody can click it into strobing.
    var lastFlash = 0;
    heroIcon.addEventListener("click", function () {
      strike(false);
      var now = Date.now();
      if (heroFlash && now - lastFlash > 1000) {
        lastFlash = now;
        heroFlash.classList.remove("is-struck");
        void heroFlash.offsetWidth;
        heroFlash.classList.add("is-struck");
      }
    });

    // The bead under "one piece." drips sparks while it is laid down.
    setTimeout(function () {
      Sparks.trail(document.querySelector(".seam__runner .spark"), 880, 2);
    }, 1450);

    embers(document.querySelector(".hero__embers"));
  }

  // Embers: a few slow motes of gold rising off the helmet, like the air above
  // a fresh weld. Paused when the hero is off screen or the tab is hidden.
  function embers(canvas) {
    if (!canvas || !canvas.getContext) {
      return;
    }
    var ctx = canvas.getContext("2d");
    var w = 0;
    var h = 0;
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var motes = [];
    var visible = true;
    var raf = 0;
    var last = 0;

    function size() {
      var r = canvas.getBoundingClientRect();
      w = r.width;
      h = r.height;
      canvas.width = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
    }

    function spawn(initial) {
      return {
        x: w * rand(0.22, 0.8),
        y: initial ? h * rand(0.15, 0.85) : h * rand(0.62, 0.86),
        vy: -rand(0.012, 0.035),
        drift: rand(0, Math.PI * 2),
        r: rand(0.8, 2.1),
        life: initial ? rand(0, 3000) : 0,
        max: rand(3500, 7000),
        c: SPARK_COLOURS[(Math.random() * SPARK_COLOURS.length) | 0]
      };
    }

    function frame(now) {
      var dt = Math.min(now - last, 50);
      last = now;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.clearRect(0, 0, w, h);
      ctx.globalCompositeOperation = "lighter";
      for (var i = 0; i < motes.length; i++) {
        var m = motes[i];
        m.life += dt;
        if (m.life > m.max) {
          motes[i] = m = spawn(false);
        }
        m.drift += dt * 0.0015;
        m.y += m.vy * dt;
        m.x += Math.sin(m.drift) * 0.05;
        var t = m.life / m.max;
        var a = t < 0.15 ? t / 0.15 : 1 - (t - 0.15) / 0.85;
        ctx.globalAlpha = Math.max(0, a) * 0.85;
        ctx.fillStyle = m.c;
        ctx.beginPath();
        ctx.arc(m.x, m.y, m.r, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.globalAlpha = 1;
      raf = requestAnimationFrame(frame);
    }

    function play() {
      if (!raf && visible && !document.hidden && motionOn()) {
        last = performance.now();
        raf = requestAnimationFrame(frame);
      }
    }

    function pause() {
      if (raf) {
        cancelAnimationFrame(raf);
        raf = 0;
      }
    }

    size();
    for (var i = 0; i < 22; i++) {
      motes.push(spawn(true));
    }
    if ("ResizeObserver" in window) {
      new ResizeObserver(size).observe(canvas);
    }
    if (hasIO) {
      new IntersectionObserver(function (entries) {
        visible = entries[0].isIntersecting;
        if (visible) {
          play();
        } else {
          pause();
        }
      }).observe(canvas);
    }
    document.addEventListener("visibilitychange", function () {
      if (document.hidden) {
        pause();
      } else {
        play();
      }
    });
    // Embers drift in once the helmet has landed.
    setTimeout(play, 900);
  }

  /* -------------------------------------------------------------- 4. typing */

  // Split a [data-type] element's text into one span per character. The text
  // is unchanged for assistive tech and for copy and paste.
  function splitChars(el) {
    var text = el.textContent;
    el.textContent = "";
    var spans = [];
    for (var i = 0; i < text.length; i++) {
      var s = document.createElement("span");
      s.className = "ch";
      s.textContent = text.charAt(i);
      el.appendChild(s);
      spans.push(s);
    }
    return spans;
  }

  // Get a terminal ready to type: split its command, hide it (.is-armed).
  // Returns start(delay), which types the command, then shows the output a
  // line at a time, like a real shell that is quick but not instant, and
  // ends with .is-done (the prompt and its blinking caret).
  function prepType(host) {
    var target = host.querySelector("[data-type]");
    if (!target) {
      return function () {};
    }
    var chars = splitChars(target);
    var outs = Array.prototype.slice.call(host.querySelectorAll(".term__out"));
    host.classList.add("is-armed");
    var i = 0;

    function typeNext() {
      if (!motionOn()) {
        finish();
        return;
      }
      if (i > 0) {
        chars[i - 1].classList.remove("cur");
      }
      if (i >= chars.length) {
        setTimeout(nextLine, 260);
        return;
      }
      chars[i].classList.add("on", "cur");
      var ch = chars[i].textContent;
      i++;
      // A small human wobble; a beat longer after a space.
      setTimeout(typeNext, (ch === " " ? 70 : 32) + Math.random() * 40);
    }

    var line = 0;
    function nextLine() {
      if (line >= outs.length) {
        host.classList.add("is-done");
        return;
      }
      outs[line].classList.add("on");
      line++;
      setTimeout(nextLine, 140 + Math.random() * 160);
    }

    function finish() {
      chars.forEach(function (c) {
        c.classList.add("on");
        c.classList.remove("cur");
      });
      outs.forEach(function (o) {
        o.classList.add("on");
      });
      host.classList.add("is-done");
    }

    return function start(delay) {
      setTimeout(typeNext, delay || 0);
    };
  }

  if (motionOn()) {
    var quick = document.querySelector(".quickstart");
    if (quick) {
      prepType(quick)(1700);
    }

    // Arm every terminal now (hidden); type each as it scrolls into view.
    var terms = Array.prototype.slice.call(document.querySelectorAll("[data-term]"));
    var starters = terms.map(prepType);
    onceVisible(terms, 0.5, function (t) {
      starters[terms.indexOf(t)](350);
    });
  }

  /* ------------------------------------------------------------- 5. journey */

  // The seam between the stops is laid down when the journey scrolls into
  // view: 3s for six stops, one every 0.6s, sparks dripping off the tip.
  var journey = document.querySelector("[data-journey]");
  if (journey && motionOn()) {
    journey.classList.add("is-armed");
    onceVisible([journey], 0.6, function () {
      journey.classList.add("is-welded");
      Sparks.trail(journey.querySelector(".journey__runner .spark"), 2950, 1);
      Array.prototype.forEach.call(journey.querySelectorAll(".journey__icon"), function (icon, n) {
        setTimeout(function () {
          var r = icon.getBoundingClientRect();
          Sparks.emit(r.left + r.width / 2, r.top + r.height / 2, { count: 14, speed: 0.45, life: 700 });
        }, n * 600);
      });
    });
  }

  /* ---------------------------------------------------------------- 6. cast */

  if (motionOn()) {
    // The seams under the three steps and the checks pipeline are laid down
    // as they scroll into view, the way the journey's is.
    var seams = Array.prototype.slice.call(document.querySelectorAll(".steps, .pipeline__stops"));
    seams.forEach(function (seam) {
      seam.classList.add("is-armed");
    });
    onceVisible(seams, 0.4, function (seam) {
      seam.classList.add("is-welded");
    });

    // Cards rise in a beat apart, row by row.
    var cards = Array.prototype.slice.call(document.querySelectorAll(".cast-card, .bench-card, .path-card, .step, .choice, .pipeline__stop"));
    cards.forEach(function (card) {
      card.classList.add("reveal", "is-armed");
    });
    var batch = [];
    var flushTimer = 0;
    onceVisible(cards, 0.15, function (card) {
      batch.push(card);
      clearTimeout(flushTimer);
      flushTimer = setTimeout(function () {
        batch.forEach(function (c, n) {
          c.style.setProperty("--d", n * 90 + "ms");
          c.classList.add("is-in");
          // Once it has landed, hand the transform back to the hover styles.
          setTimeout(function () {
            c.classList.remove("reveal", "is-armed", "is-in");
            c.style.removeProperty("--d");
          }, 1000 + n * 90);
        });
        batch = [];
      }, 30);
    });

    // A warm glow follows the pointer across each card (fine pointers only).
    if (window.matchMedia("(hover: hover) and (pointer: fine)").matches) {
      Array.prototype.forEach.call(document.querySelectorAll(".cast-card"), function (card) {
        var glow = document.createElement("span");
        glow.className = "cast-card__glow";
        glow.setAttribute("aria-hidden", "true");
        card.insertBefore(glow, card.firstChild);
        var pending = false;
        var px = 0;
        var py = 0;
        card.addEventListener("pointermove", function (e) {
          var r = card.getBoundingClientRect();
          px = e.clientX - r.left;
          py = e.clientY - r.top;
          if (!pending) {
            pending = true;
            requestAnimationFrame(function () {
              glow.style.transform = "translate3d(" + px + "px," + py + "px,0)";
              pending = false;
            });
          }
        });
      });
    }
  }

  /* -------------------------------------------------------- 7. copy buttons */

  // Tries the clipboard API first; if it is unavailable (for example a
  // file:// page), it selects the command so the user can copy it by hand.
  document.querySelectorAll(".copy-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var text = btn.getAttribute("data-copy") || "";
      var label = btn.querySelector(".copy-btn__label");

      function done(ok) {
        if (ok) {
          // A small shower of sparks: copied, and tacked in place.
          var r = btn.getBoundingClientRect();
          Sparks.emit(r.left + r.width / 2, r.top + 4, { count: 22, speed: 0.5, spread: 1.1, life: 700 });
        }
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
        navigator.clipboard.writeText(text).then(function () {
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

  /* ------------------------------------------------------------ 8. the torch */

  // Type w-e-l-d anywhere on the page and the pointer becomes a torch for a
  // dozen seconds. Bring your own welding mask.
  var toast = document.querySelector(".torch-toast");
  var keys = "";
  var torchUntil = 0;
  var toastTimer = 0;

  function say(text, ms) {
    if (!toast) {
      return;
    }
    toast.textContent = text;
    toast.classList.add("is-on");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toast.classList.remove("is-on");
    }, ms);
  }

  function lightTorch() {
    if (!motionOn()) {
      say("$ weld --torch\n✓ torch lit, sparks off: your system asks for less motion. Respect.", 5000);
      return;
    }
    torchUntil = Date.now() + 12000;
    say("$ weld --torch\n✓ torch lit for 12 seconds. Move the pointer. Safety glasses on.", 4500);
    if (heroIcon && heroIcon.getBoundingClientRect().bottom > 0) {
      strike(true);
    }
    setTimeout(function () {
      if (Date.now() >= torchUntil) {
        say("✓ torch off. Nice bead.", 2500);
      }
    }, 12050);
  }

  document.addEventListener("keydown", function (e) {
    var t = e.target;
    if (e.ctrlKey || e.metaKey || e.altKey || (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName)))) {
      return;
    }
    if (!e.key || e.key.length !== 1) {
      return;
    }
    keys = (keys + e.key.toLowerCase()).slice(-4);
    if (keys === "weld") {
      keys = "";
      lightTorch();
    }
  });

  var lastTorch = 0;
  document.addEventListener("pointermove", function (e) {
    if (Date.now() > torchUntil) {
      return;
    }
    var now = performance.now();
    if (now - lastTorch < 16) {
      return;
    }
    lastTorch = now;
    Sparks.emit(e.clientX, e.clientY, { count: 3, speed: 0.45, spread: Math.PI, life: 600 });
  }, { passive: true });

  // For whoever opens the console: a little welcome, and a hint.
  if (window.console && console.log) {
    console.log("%cWELD%c  Many parts, one piece.\nLooking under the hood? It's welded shut. (Kidding: type w-e-l-d on the page.)",
      "font:900 20px Lato,sans-serif;color:#ffcc33;background:#0c5449;padding:4px 10px;border-radius:4px",
      "color:inherit");
  }
})();
