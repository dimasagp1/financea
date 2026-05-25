import './bootstrap';

function applyAutoDismissFlash() {
	const successAlert = document.getElementById('flash-success');

	if (!successAlert) {
		return;
	}

	setTimeout(() => {
		successAlert.classList.add('opacity-0', '-translate-y-1');
		setTimeout(() => successAlert.remove(), 400);
	}, 3000);
}

function applySubmitLoadingState() {
	const forms = document.querySelectorAll('form');

	forms.forEach((form) => {
		form.addEventListener('submit', () => {
			const submitButtons = form.querySelectorAll('button[type="submit"]');

			submitButtons.forEach((button) => {
				if (button.dataset.loadingApplied === '1') {
					return;
				}

				const loadingText = button.dataset.loadingText || 'Memproses...';
				const spinner = '<svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';

				button.innerHTML = '<span class="inline-flex items-center gap-2">' + spinner + '<span>' + loadingText + '</span></span>';
				button.disabled = true;
				button.classList.add('opacity-70', 'cursor-not-allowed');
				button.dataset.loadingApplied = '1';
			});
		});
	});
}

function applySidebarToggle() {
	const sidebar = document.getElementById('app-sidebar');
	const backdrop = document.getElementById('sidebar-backdrop');
	const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
	const closeButtons = document.querySelectorAll('[data-sidebar-close]');
	const mobileMedia = window.matchMedia('(max-width: 1023px)');

	if (!sidebar || toggleButtons.length === 0) {
		return;
	}

	const storageKey = 'finance-monitoring.sidebar.hidden';

	const openMobile = () => {
		sidebar.classList.remove('-translate-x-full');
		sidebar.classList.add('translate-x-0');
		backdrop?.classList.remove('hidden');
		toggleButtons.forEach((button) => {
			button.setAttribute('aria-expanded', 'true');
			const label = button.querySelector('[data-sidebar-toggle-label]');
			const icon = button.querySelector('[data-sidebar-toggle-icon]');
			if (label) label.textContent = 'Tutup Menu';
			if (icon) {
				icon.textContent = '✕';
				icon.classList.add('rotate-90');
			}
		});
	};

	const closeMobile = () => {
		sidebar.classList.remove('translate-x-0');
		sidebar.classList.add('-translate-x-full');
		backdrop?.classList.add('hidden');
		toggleButtons.forEach((button) => {
			button.setAttribute('aria-expanded', 'false');
			const label = button.querySelector('[data-sidebar-toggle-label]');
			const icon = button.querySelector('[data-sidebar-toggle-icon]');
			if (label) label.textContent = 'Buka Menu';
			if (icon) {
				icon.textContent = '☰';
				icon.classList.remove('rotate-90');
			}
		});
	};

	const applyState = (isHidden) => {
		if (mobileMedia.matches) {
			closeMobile();
			return;
		}

		sidebar.classList.remove('-translate-x-full');
		backdrop?.classList.add('hidden');

		if (isHidden) {
			sidebar.classList.add('lg:w-0', 'lg:max-w-0', 'lg:min-w-0', 'lg:p-0', 'lg:opacity-0', 'lg:-translate-x-4', 'lg:pointer-events-none');
			sidebar.classList.remove('lg:w-72', 'lg:max-w-72', 'lg:p-6', 'lg:opacity-100', 'lg:translate-x-0');
		} else {
			sidebar.classList.remove('lg:w-0', 'lg:max-w-0', 'lg:min-w-0', 'lg:p-0', 'lg:opacity-0', 'lg:-translate-x-4', 'lg:pointer-events-none');
			sidebar.classList.add('lg:w-72', 'lg:max-w-72', 'lg:p-6', 'lg:opacity-100', 'lg:translate-x-0');
		}

		toggleButtons.forEach((button) => {
			button.setAttribute('aria-expanded', String(!isHidden));

			const label = button.querySelector('[data-sidebar-toggle-label]');
			const icon = button.querySelector('[data-sidebar-toggle-icon]');
			if (label) {
				label.textContent = isHidden ? 'Tampilkan Menu' : 'Sembunyikan Menu';
			}
			if (icon) {
				icon.textContent = isHidden ? '☰' : '✕';
				icon.classList.toggle('rotate-90', !isHidden);
			}
		});
	};

	const savedState = localStorage.getItem(storageKey);
	applyState(savedState === '1');

	const triggerToggle = () => {
		const firstToggleButton = toggleButtons[0];
		if (!firstToggleButton) {
			return;
		}

		firstToggleButton.click();
	};

	toggleButtons.forEach((button) => {
		button.addEventListener('click', () => {
			if (mobileMedia.matches) {
				const isOpen = !sidebar.classList.contains('-translate-x-full');
				if (isOpen) {
					closeMobile();
				} else {
					openMobile();
				}

				const label = button.querySelector('[data-sidebar-toggle-label]');
				const icon = button.querySelector('[data-sidebar-toggle-icon]');
				if (label) {
					label.textContent = isOpen ? 'Buka Menu' : 'Tutup Menu';
				}
				if (icon) {
					icon.textContent = isOpen ? '☰' : '✕';
					icon.classList.toggle('rotate-90', !isOpen);
				}
				return;
			}

			const currentlyHidden = sidebar.classList.contains('lg:w-0');
			const nextHidden = !currentlyHidden;

			applyState(nextHidden);
			localStorage.setItem(storageKey, nextHidden ? '1' : '0');
		});
	});

	closeButtons.forEach((button) => {
		button.addEventListener('click', closeMobile);
	});

	backdrop?.addEventListener('click', closeMobile);

	mobileMedia.addEventListener('change', () => {
		const saved = localStorage.getItem(storageKey) === '1';
		applyState(saved);
	});

	document.addEventListener('keydown', (event) => {
		const isCtrlOrCmd = event.ctrlKey || event.metaKey;
		if (!isCtrlOrCmd || event.key.toLowerCase() !== 'b') {
			return;
		}

		const activeTag = document.activeElement?.tagName?.toLowerCase();
		const isTypingContext =
			activeTag === 'input' ||
			activeTag === 'textarea' ||
			document.activeElement?.isContentEditable;

		if (isTypingContext) {
			return;
		}

		event.preventDefault();
		triggerToggle();
	});
}

function applyConfirmDialogs() {
	const confirmForms = document.querySelectorAll('form[data-confirm]');

	confirmForms.forEach((form) => {
		form.addEventListener('submit', async (event) => {
			if (form.dataset.confirmBypassed === '1') {
				delete form.dataset.confirmBypassed;
				return;
			}

			event.preventDefault();
			event.stopImmediatePropagation();

			const message = form.getAttribute('data-confirm') || 'Apakah Anda yakin ingin melanjutkan?';
			const accepted = await showConfirmModal(message);

			if (accepted) {
				form.dataset.confirmBypassed = '1';
				form.requestSubmit();
			}
		});
	});
}

function showConfirmModal(message) {
	return new Promise((resolve) => {
		const previouslyFocusedElement = document.activeElement;
		let modalRoot = document.getElementById('global-confirm-modal');

		if (!modalRoot) {
			modalRoot = document.createElement('div');
			modalRoot.id = 'global-confirm-modal';
			modalRoot.className = 'fixed inset-0 z-50 hidden items-center justify-center p-4';
			modalRoot.innerHTML = `
				<div class="absolute inset-0 bg-slate-900/50 opacity-0 transition-opacity duration-200" data-modal-overlay></div>
				<div class="relative z-10 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200 opacity-0 scale-95 transition-all duration-200" data-modal-panel>
					<h3 class="text-lg font-semibold text-slate-900">Konfirmasi Aksi</h3>
					<p class="mt-2 text-sm text-slate-600" data-modal-message></p>
					<div class="mt-6 flex justify-end gap-2">
						<button type="button" class="rounded-xl bg-slate-100 text-slate-700 px-4 py-2" data-modal-cancel>Batal</button>
						<button type="button" class="rounded-xl bg-rose-600 text-white px-4 py-2" data-modal-confirm>Lanjutkan</button>
					</div>
				</div>
			`;
			document.body.appendChild(modalRoot);
		}

		const messageEl = modalRoot.querySelector('[data-modal-message]');
		const cancelBtn = modalRoot.querySelector('[data-modal-cancel]');
		const confirmBtn = modalRoot.querySelector('[data-modal-confirm]');
		const overlay = modalRoot.querySelector('[data-modal-overlay]');
		const panel = modalRoot.querySelector('[data-modal-panel]');

		if (messageEl) {
			messageEl.textContent = message;
		}

		modalRoot.classList.remove('hidden');
		modalRoot.classList.add('flex');

		requestAnimationFrame(() => {
			overlay?.classList.remove('opacity-0');
			overlay?.classList.add('opacity-100');
			panel?.classList.remove('opacity-0', 'scale-95');
			panel?.classList.add('opacity-100', 'scale-100');
			confirmBtn?.focus();
		});

		const focusableElements = Array.from(modalRoot.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'));
		const firstFocusable = focusableElements[0] || null;
		const lastFocusable = focusableElements[focusableElements.length - 1] || null;

		const cleanup = () => {
			overlay?.classList.remove('opacity-100');
			overlay?.classList.add('opacity-0');
			panel?.classList.remove('opacity-100', 'scale-100');
			panel?.classList.add('opacity-0', 'scale-95');

			setTimeout(() => {
				modalRoot.classList.add('hidden');
				modalRoot.classList.remove('flex');
				if (previouslyFocusedElement && typeof previouslyFocusedElement.focus === 'function') {
					previouslyFocusedElement.focus();
				}
			}, 180);

			cancelBtn?.removeEventListener('click', onCancel);
			confirmBtn?.removeEventListener('click', onConfirm);
			overlay?.removeEventListener('click', onCancel);
			document.removeEventListener('keydown', onKeydown);
		};

		const onCancel = () => {
			cleanup();
			resolve(false);
		};

		const onConfirm = () => {
			cleanup();
			resolve(true);
		};

		const onKeydown = (event) => {
			if (event.key === 'Escape') {
				onCancel();
				return;
			}

			if (event.key === 'Tab' && firstFocusable && lastFocusable) {
				if (event.shiftKey && document.activeElement === firstFocusable) {
					event.preventDefault();
					lastFocusable.focus();
				} else if (!event.shiftKey && document.activeElement === lastFocusable) {
					event.preventDefault();
					firstFocusable.focus();
				}
			}
		};

		cancelBtn?.addEventListener('click', onCancel);
		confirmBtn?.addEventListener('click', onConfirm);
		overlay?.addEventListener('click', onCancel);
		document.addEventListener('keydown', onKeydown);
	});
}

document.addEventListener('DOMContentLoaded', () => {
	applyAutoDismissFlash();
	applySidebarToggle();
	applyConfirmDialogs();
	applySubmitLoadingState();
});
