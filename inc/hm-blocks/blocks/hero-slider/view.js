(function () {
	"use strict";

	function clamp(n, min, max) {
		n = parseInt(n, 10);
		if (!n || isNaN(n)) n = min;
		if (n < min) n = min;
		if (n > max) n = max;
		return n;
	}

	function initBlock(block) {
		var slidesRoot = block.querySelector(".hmpro-hero__slides");
		if (!slidesRoot) return;

		var slides = Array.prototype.slice.call(block.querySelectorAll(".hmpro-hero-slide"));
		if (!slides.length) return;

		var prevBtn = block.querySelector(".hmpro-hero__arrow--prev");
		var nextBtn = block.querySelector(".hmpro-hero__arrow--next");
		var dots = Array.prototype.slice.call(block.querySelectorAll(".hmpro-hero__dot"));

		var autoplay = slidesRoot.getAttribute("data-autoplay") === "1";
		var interval = clamp(slidesRoot.getAttribute("data-interval"), 1500, 20000);

		var idx = 0;
		var timer = null;
		var paused = false;

		function setActive(next) {
			var len = slides.length;
			if (len < 2) return;

			if (next < 0) next = len - 1;
			if (next >= len) next = 0;

			idx = next;

			slides.forEach(function (s, i) {
				if (i === idx) {
					s.classList.add("is-active");
					s.setAttribute("aria-hidden", "false");
				} else {
					s.classList.remove("is-active");
					s.setAttribute("aria-hidden", "true");
				}
			});

			dots.forEach(function (d, i) {
				var active = (i === idx);
				d.classList.toggle("is-active", active);
				d.setAttribute("aria-selected", active ? "true" : "false");
				d.setAttribute("tabindex", active ? "0" : "-1");
			});
		}

		function next() { setActive(idx + 1); }
		function prev() { setActive(idx - 1); }

		function stop() {
			if (timer) {
				clearInterval(timer);
				timer = null;
			}
		}

		function start() {
			stop();
			if (!autoplay) return;
			if (slides.length < 2) return;
			timer = setInterval(function () {
				if (paused) return;
				next();
			}, interval);
		}

		if (prevBtn) {
			prevBtn.addEventListener("click", function (e) {
				e.preventDefault();
				prev();
				start();
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener("click", function (e) {
				e.preventDefault();
				next();
				start();
			});
		}

		if (dots && dots.length) {
			dots.forEach(function (d) {
				d.addEventListener("click", function (e) {
					e.preventDefault();
					var n = parseInt(d.getAttribute("data-index"), 10);
					if (!isNaN(n)) {
						setActive(n);
						start();
					}
				});
			});
		}

		// Pause on hover / focus.
		block.addEventListener("mouseenter", function () { paused = true; });
		block.addEventListener("mouseleave", function () { paused = false; });
		block.addEventListener("focusin", function () { paused = true; });
		block.addEventListener("focusout", function () { paused = false; });

		// Init.
		setActive(0);
		start();
	}

	function onReady() {
		var blocks = Array.prototype.slice.call(document.querySelectorAll(".hmpro-hero-slider"));
		if (!blocks.length) return;
		blocks.forEach(function (b) { initBlock(b); });
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", onReady);
	} else {
		onReady();
	}
})();