(function () {
	"use strict";

	function initSlider(root) {
		var delay = parseInt(root.getAttribute("data-delay"), 10);
		if (!delay || delay < 1500) {
			delay = 4500;
		}

		var slides = Array.prototype.slice.call(root.querySelectorAll(".hmpro-hb-slide"));
		if (slides.length < 2) {
			return;
		}

		var idx = 0;

		function setActive(nextIdx) {
			slides.forEach(function (slide, i) {
				if (i === nextIdx) {
					slide.classList.add("is-active");
				} else {
					slide.classList.remove("is-active");
				}
			});
			idx = nextIdx;
		}

		setActive(0);
		window.setInterval(function () {
			var next = idx + 1;
			if (next >= slides.length) {
				next = 0;
			}
			setActive(next);
		}, delay);
	}

	function onReady() {
		var roots = document.querySelectorAll(".hmpro-hb-slides");
		if (!roots || !roots.length) {
			return;
		}
		roots.forEach(function (r) {
			initSlider(r);
		});
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", onReady);
	} else {
		onReady();
	}
})();
