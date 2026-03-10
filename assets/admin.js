document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.hmpro-delete-preset').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			const name = this.dataset.name || 'this preset';
			if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
				e.preventDefault();
			}
		});
	});
});
