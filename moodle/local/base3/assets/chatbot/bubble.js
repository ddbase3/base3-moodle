(function() {
	'use strict';

	if (window.base3MoodleChatbotInitialized) {
		return;
	}

	window.base3MoodleChatbotInitialized = true;

	function init() {
		var config = document.getElementById('base3-chatbot-config');
		if (!config) {
			return;
		}

		if (document.getElementById('base3-chatbot-fab')) {
			return;
		}

		var ajaxUrl = config.getAttribute('data-ajax-url') || '';
		var buttonLabel = config.getAttribute('data-button-label') || '';
		var launcherIcon = config.getAttribute('data-launcher-icon') || '';
		var headerIcon = config.getAttribute('data-header-icon') || '';
		var headerTitle = config.getAttribute('data-header-title') || buttonLabel;
		var strings = {
			close: config.getAttribute('data-close-label') || '',
			loading: config.getAttribute('data-loading-label') || '',
			loadError: config.getAttribute('data-load-error') || '',
			modes: {
				overlay: config.getAttribute('data-mode-overlay-label') || '',
				modal: config.getAttribute('data-mode-modal-label') || '',
				docked: config.getAttribute('data-mode-docked-label') || '',
				cover: config.getAttribute('data-mode-cover-label') || '',
				fullscreen: config.getAttribute('data-mode-fullscreen-label') || ''
			}
		};
		var settings = readSettings(config);

		if (ajaxUrl === '') {
			return;
		}

		var state = {
			loaded: false,
			loading: false,
			mode: '',
			fullscreenReturnMode: '',
			resizeFrame: 0,
			sizeSaveTimer: 0
		};

		var launcher = createLauncher(buttonLabel, launcherIcon);
		var panel = createPanel(headerIcon, headerTitle, strings, settings);

		installAutoResize(panel, state, settings);
		installContainerResizeObserver(panel, state, settings);
		installManualResize(panel, state, settings);

		launcher.button.addEventListener('click', function() {
			var mode = getInitialMode(settings);
			openPanel(panel, launcher, state, settings, strings, mode, true);
			loadChatbot(panel, state, settings, ajaxUrl, strings);
		});

		Object.keys(panel.modeButtons).forEach(function(mode) {
			panel.modeButtons[mode].addEventListener('click', function() {
				setMode(panel, state, settings, strings, mode, true);
			});
		});

		panel.closeButton.addEventListener('click', function() {
			closePanel(panel, launcher, state, settings);
		});

		document.addEventListener('keydown', function(event) {
			if (event.key !== 'Escape' || !panel.container.classList.contains('is-open')) {
				return;
			}

			if (state.mode === 'fullscreen') {
				return;
			}

			closePanel(panel, launcher, state, settings);
		});

		document.addEventListener('fullscreenchange', function() {
			if (!panel.container.classList.contains('is-open')) {
				return;
			}

			if (state.mode !== 'fullscreen' || document.fullscreenElement === panel.container) {
				return;
			}

			var returnMode = state.fullscreenReturnMode;
			if (returnMode !== '' && isModeEnabled(settings, returnMode)) {
				setMode(panel, state, settings, strings, returnMode, false);
				return;
			}

			var nextMode = getFirstEnabledMode(settings, 'fullscreen');
			if (nextMode !== '') {
				setMode(panel, state, settings, strings, nextMode, false);
				return;
			}

			closePanel(panel, launcher, state, settings);
		});

		restoreOpenState(panel, launcher, state, settings, strings, ajaxUrl);
	}

	function readSettings(config) {
		var group = config.getAttribute('data-config-group') || 'default';
		var name = config.getAttribute('data-config-name') || 'default';

		return {
			modes: {
				overlay: readBoolAttribute(config, 'data-mode-overlay-enabled', true),
				modal: readBoolAttribute(config, 'data-mode-modal-enabled', true),
				docked: readBoolAttribute(config, 'data-mode-docked-enabled', false),
				cover: readBoolAttribute(config, 'data-mode-cover-enabled', false),
				fullscreen: readBoolAttribute(config, 'data-mode-fullscreen-enabled', false)
			},
			resizable: {
				overlay: readBoolAttribute(config, 'data-resizable-overlay', false),
				modal: readBoolAttribute(config, 'data-resizable-modal', false),
				docked: readBoolAttribute(config, 'data-resizable-docked', false)
			},
			rememberLastMode: readBoolAttribute(config, 'data-remember-last-mode', false),
			reopen: {
				overlay: readBoolAttribute(config, 'data-reopen-overlay', false),
				docked: readBoolAttribute(config, 'data-reopen-docked', false)
			},
			storagePrefix: 'base3-moodle-chatbot:' + group + ':' + name
		};
	}

	function readBoolAttribute(element, name, defaultValue) {
		var value = element.getAttribute(name);
		if (value === null || value === '') {
			return defaultValue;
		}

		return value === '1';
	}

	function createLauncher(buttonLabel, launcherIcon) {
		var launcher = document.createElement('div');
		launcher.id = 'base3-chatbot-launcher';

		var button = document.createElement('button');
		button.type = 'button';
		button.id = 'base3-chatbot-fab';
		button.setAttribute('aria-label', buttonLabel);
		button.setAttribute('title', buttonLabel);

		if (launcherIcon !== '') {
			button.classList.add('has-icon');

			var icon = document.createElement('img');
			icon.className = 'base3-chatbot-launcher-icon';
			icon.src = launcherIcon;
			icon.alt = '';
			button.appendChild(icon);
		} else {
			button.textContent = buttonLabel;
		}

		launcher.appendChild(button);
		document.body.appendChild(launcher);

		return {
			container: launcher,
			button: button
		};
	}

	function createPanel(headerIcon, headerTitle, strings, settings) {
		var backdrop = document.createElement('div');
		backdrop.id = 'base3-chatbot-backdrop';
		document.body.appendChild(backdrop);

		var panel = document.createElement('div');
		panel.id = 'base3-chatbot-panel';
		panel.setAttribute('role', 'dialog');

		var header = document.createElement('div');
		header.id = 'base3-chatbot-panel-header';

		var brand = document.createElement('div');
		brand.id = 'base3-chatbot-panel-brand';

		if (headerIcon !== '') {
			var logo = document.createElement('img');
			logo.id = 'base3-chatbot-panel-logo';
			logo.src = headerIcon;
			logo.alt = '';
			brand.appendChild(logo);
		}

		if (headerTitle !== '') {
			var title = document.createElement('div');
			title.id = 'base3-chatbot-panel-title';
			title.textContent = headerTitle;
			brand.appendChild(title);
			panel.setAttribute('aria-label', title.textContent || '');
		}

		var actions = document.createElement('div');
		actions.id = 'base3-chatbot-panel-actions';

		var modeButtons = {};
		['overlay', 'modal', 'docked', 'cover', 'fullscreen'].forEach(function(mode) {
			if (!isModeEnabled(settings, mode)) {
				return;
			}

			var button = createHeaderButton(
				'base3-chatbot-mode-' + mode,
				strings.modes[mode],
				getModeIcon(mode)
			);
			button.setAttribute('data-chatbot-mode', mode);
			modeButtons[mode] = button;
			actions.appendChild(button);
		});

		var closeButton = createHeaderButton(
			'base3-chatbot-close',
			strings.close,
			'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
		);
		actions.appendChild(closeButton);

		header.appendChild(brand);
		header.appendChild(actions);

		var body = document.createElement('div');
		body.id = 'base3-chatbot-panel-body';
		body.innerHTML = '<div class="base3-chatbot-loading"></div>';
		body.firstChild.textContent = strings.loading;

		var resizeHandle = document.createElement('div');
		resizeHandle.className = 'base3-chatbot-resize-handle';
		resizeHandle.setAttribute('aria-hidden', 'true');

		panel.appendChild(header);
		panel.appendChild(body);
		panel.appendChild(resizeHandle);
		document.body.appendChild(panel);

		return {
			container: panel,
			header: header,
			body: body,
			backdrop: backdrop,
			modeButtons: modeButtons,
			closeButton: closeButton,
			resizeHandle: resizeHandle,
			contentResizeObserver: null,
			containerResizeObserver: null,
			mutationObserver: null,
			windowResizeHandler: null
		};
	}

	function createHeaderButton(id, label, iconHtml) {
		var button = document.createElement('button');
		button.type = 'button';
		button.id = id;
		button.className = 'base3-chatbot-header-action';
		button.setAttribute('aria-label', label);
		button.setAttribute('title', label);
		button.innerHTML = iconHtml;

		return button;
	}

	function getModeIcon(mode) {
		switch (mode) {
			case 'overlay':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="11" y="11" width="8" height="8" rx="1" fill="currentColor"/></svg>';

			case 'modal':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="7" y="7" width="10" height="10" rx="1" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';

			case 'docked':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M14 3v18" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M15 4h5v16h-5z" fill="currentColor"/></svg>';

			case 'cover':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><rect x="5.5" y="5.5" width="13" height="13" rx="1" fill="currentColor"/></svg>';

			case 'fullscreen':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		}

		return '';
	}

	function openPanel(panel, launcher, state, settings, strings, mode, requestFullscreen) {
		panel.container.classList.add('is-open');
		launcher.container.classList.add('panel-open');
		setMode(panel, state, settings, strings, mode, requestFullscreen);
	}

	function closePanel(panel, launcher, state, settings) {
		panel.container.classList.remove('is-open');
		panel.backdrop.classList.remove('is-visible');
		launcher.container.classList.remove('panel-open');
		removeDockedShell();
		clearStoredOpenMode(settings);

		exitPanelFullscreen(panel);
	}

	function setMode(panel, state, settings, strings, mode, requestFullscreen) {
		if (!isModeEnabled(settings, mode)) {
			mode = getFirstEnabledMode(settings, '');
		}

		if (mode === '') {
			return;
		}

		var previousMode = state.mode;
		if (mode === 'fullscreen' && previousMode !== '' && previousMode !== 'fullscreen') {
			state.fullscreenReturnMode = previousMode;
		}

		if (previousMode === 'fullscreen' && mode !== 'fullscreen') {
			exitPanelFullscreen(panel);
		}

		clearModeClasses(panel.container);
		clearExplicitPanelSize(panel.container);
		panel.container.classList.add('mode-' + mode);
		panel.container.setAttribute('data-mode', mode);
		state.mode = mode;

		var resizable = isResizableMode(settings, mode);
		panel.container.classList.toggle('is-resizable', resizable);
		if (resizable) {
			applyStoredSize(panel, state, settings, mode);
		}

		panel.backdrop.classList.toggle(
			'is-visible',
			panel.container.classList.contains('is-open') && mode === 'modal'
		);

		updateModeButtons(panel, mode);
		updateDockedShell(panel, state);
		persistLastMode(settings, mode);
		persistOpenMode(settings, mode, panel.container.classList.contains('is-open'));

		if (mode === 'overlay') {
			scheduleInitialPanelResize(panel, state, settings);
		}

		if (mode === 'fullscreen' && requestFullscreen) {
			enterFullscreen(panel, state, settings, strings);
		}
	}

	function exitPanelFullscreen(panel) {
		if (document.fullscreenElement !== panel.container || typeof document.exitFullscreen !== 'function') {
			return;
		}

		var result = document.exitFullscreen();
		if (result && typeof result.catch === 'function') {
			result.catch(function() {});
		}
	}

	function enterFullscreen(panel, state, settings, strings) {
		if (document.fullscreenElement === panel.container) {
			return;
		}

		if (typeof panel.container.requestFullscreen !== 'function') {
			return;
		}

		var result = panel.container.requestFullscreen();
		if (!result || typeof result.catch !== 'function') {
			return;
		}

		result.catch(function() {
			var returnMode = state.fullscreenReturnMode;
			if (returnMode !== '' && isModeEnabled(settings, returnMode)) {
				setMode(panel, state, settings, strings, returnMode, false);
			}
		});
	}

	function clearModeClasses(container) {
		['overlay', 'modal', 'docked', 'cover', 'fullscreen'].forEach(function(mode) {
			container.classList.remove('mode-' + mode);
		});
	}

	function updateModeButtons(panel, currentMode) {
		Object.keys(panel.modeButtons).forEach(function(mode) {
			panel.modeButtons[mode].hidden = mode === currentMode;
		});
	}

	function isModeEnabled(settings, mode) {
		return settings.modes[mode] === true;
	}

	function getInitialMode(settings) {
		if (settings.rememberLastMode) {
			var rememberedMode = storageGet(window.localStorage, getLastModeStorageKey(settings));
			if (rememberedMode !== null && isModeEnabled(settings, rememberedMode)) {
				return rememberedMode;
			}
		} else {
			storageRemove(window.localStorage, getLastModeStorageKey(settings));
		}

		return getFirstEnabledMode(settings, '');
	}

	function getFirstEnabledMode(settings, excludedMode) {
		var modes = ['overlay', 'modal', 'docked', 'cover', 'fullscreen'];
		for (var i = 0; i < modes.length; i++) {
			if (modes[i] !== excludedMode && isModeEnabled(settings, modes[i])) {
				return modes[i];
			}
		}

		return '';
	}

	function persistLastMode(settings, mode) {
		if (!settings.rememberLastMode) {
			return;
		}

		storageSet(window.localStorage, getLastModeStorageKey(settings), mode);
	}

	function persistOpenMode(settings, mode, open) {
		if (!open) {
			clearStoredOpenMode(settings);
			return;
		}

		if (mode === 'overlay' && settings.reopen.overlay) {
			storageSet(window.sessionStorage, getOpenModeStorageKey(settings), mode);
			return;
		}

		if (mode === 'docked' && settings.reopen.docked) {
			storageSet(window.sessionStorage, getOpenModeStorageKey(settings), mode);
			return;
		}

		clearStoredOpenMode(settings);
	}

	function clearStoredOpenMode(settings) {
		storageRemove(window.sessionStorage, getOpenModeStorageKey(settings));
	}

	function restoreOpenState(panel, launcher, state, settings, strings, ajaxUrl) {
		var mode = storageGet(window.sessionStorage, getOpenModeStorageKey(settings));
		if (mode === null) {
			return;
		}

		var mayReopen = (
			(mode === 'overlay' && settings.reopen.overlay)
			|| (mode === 'docked' && settings.reopen.docked)
		);

		if (!mayReopen || !isModeEnabled(settings, mode)) {
			clearStoredOpenMode(settings);
			return;
		}

		openPanel(panel, launcher, state, settings, strings, mode, false);
		loadChatbot(panel, state, settings, ajaxUrl, strings);
	}

	function getLastModeStorageKey(settings) {
		return settings.storagePrefix + ':last-mode';
	}

	function getOpenModeStorageKey(settings) {
		return settings.storagePrefix + ':open-mode';
	}

	function getSizeStorageKey(settings, mode) {
		return settings.storagePrefix + ':size:' + mode;
	}

	function isResizableMode(settings, mode) {
		return settings.resizable[mode] === true;
	}

	function clearExplicitPanelSize(container) {
		container.style.removeProperty('width');
		container.style.removeProperty('height');
	}

	function applyStoredSize(panel, state, settings, mode) {
		var raw = storageGet(window.localStorage, getSizeStorageKey(settings, mode));
		if (raw === null) {
			return;
		}

		var size;
		try {
			size = JSON.parse(raw);
		} catch (e) {
			storageRemove(window.localStorage, getSizeStorageKey(settings, mode));
			return;
		}

		if (!size || typeof size.width !== 'number' || size.width <= 0) {
			return;
		}

		panel.container.style.width = String(Math.round(size.width)) + 'px';

		if (mode !== 'docked' && typeof size.height === 'number' && size.height > 0) {
			panel.container.style.height = String(Math.round(size.height)) + 'px';
		}
	}

	function scheduleSizePersistence(panel, state, settings) {
		if (state.sizeSaveTimer) {
			window.clearTimeout(state.sizeSaveTimer);
		}

		state.sizeSaveTimer = window.setTimeout(function() {
			state.sizeSaveTimer = 0;
			saveCurrentSize(panel, state, settings);
		}, 120);
	}

	function saveCurrentSize(panel, state, settings) {
		if (!panel.container.classList.contains('is-open') || !isResizableMode(settings, state.mode)) {
			return;
		}

		var rect = panel.container.getBoundingClientRect();
		if (rect.width <= 0 || rect.height <= 0) {
			return;
		}

		var size = {
			width: Math.round(rect.width)
		};

		if (state.mode !== 'docked') {
			size.height = Math.round(rect.height);
		}

		storageSet(
			window.localStorage,
			getSizeStorageKey(settings, state.mode),
			JSON.stringify(size)
		);
	}

	function installManualResize(panel, state, settings) {
		panel.resizeHandle.addEventListener('pointerdown', function(event) {
			var mode = state.mode;
			if (!panel.container.classList.contains('is-open') || !isResizableMode(settings, mode)) {
				return;
			}

			event.preventDefault();

			var startX = event.clientX;
			var startY = event.clientY;
			var startRect = panel.container.getBoundingClientRect();

			function onPointerMove(moveEvent) {
				var width = startRect.width;
				var height = startRect.height;

				if (mode === 'overlay') {
					width += startX - moveEvent.clientX;
					height += startY - moveEvent.clientY;
				} else if (mode === 'modal') {
					width += (moveEvent.clientX - startX) * 2;
					height += (moveEvent.clientY - startY) * 2;
				} else if (mode === 'docked') {
					width += startX - moveEvent.clientX;
				}

				var bounds = getResizeBounds(mode);
				width = clamp(width, bounds.minWidth, bounds.maxWidth);
				panel.container.style.width = String(Math.round(width)) + 'px';

				if (mode !== 'docked') {
					height = clamp(height, bounds.minHeight, bounds.maxHeight);
					panel.container.style.height = String(Math.round(height)) + 'px';
				}

				if (mode === 'docked') {
					updateDockedShell(panel, state);
				}
			}

			function onPointerUp() {
				window.removeEventListener('pointermove', onPointerMove);
				window.removeEventListener('pointerup', onPointerUp);
				window.removeEventListener('pointercancel', onPointerUp);
				saveCurrentSize(panel, state, settings);
			}

			window.addEventListener('pointermove', onPointerMove);
			window.addEventListener('pointerup', onPointerUp);
			window.addEventListener('pointercancel', onPointerUp);
		});
	}

	function getResizeBounds(mode) {
		var mobile = window.innerWidth <= 767;

		if (mode === 'docked') {
			var dockMaxWidth = Math.max(1, window.innerWidth * (mobile ? 0.9 : 0.8));
			return {
				minWidth: Math.min(mobile ? 280 : 360, dockMaxWidth),
				maxWidth: dockMaxWidth,
				minHeight: window.innerHeight,
				maxHeight: window.innerHeight
			};
		}

		var padding = mode === 'modal' ? (mobile ? 24 : 48) : (mobile ? 24 : 40);
		var maxWidth = Math.max(1, window.innerWidth - padding);
		var maxHeight = Math.max(1, window.innerHeight - padding);

		return {
			minWidth: Math.min(420, maxWidth),
			maxWidth: maxWidth,
			minHeight: Math.min(350, maxHeight),
			maxHeight: maxHeight
		};
	}

	function installContainerResizeObserver(panel, state, settings) {
		if (typeof ResizeObserver === 'undefined') {
			return;
		}

		panel.containerResizeObserver = new ResizeObserver(function() {
			if (!panel.container.classList.contains('is-open')) {
				return;
			}

			if (state.mode === 'docked') {
				updateDockedShell(panel, state);
			}

			if (isResizableMode(settings, state.mode)) {
				scheduleSizePersistence(panel, state, settings);
			}
		});

		panel.containerResizeObserver.observe(panel.container);
	}

	function updateDockedShell(panel, state) {
		var docked = panel.container.classList.contains('is-open') && state.mode === 'docked';
		if (!docked) {
			removeDockedShell();
			return;
		}

		var width = Math.ceil(panel.container.getBoundingClientRect().width || 0);
		if (width <= 0) {
			return;
		}

		document.body.classList.add('base3-chatbot-docked');
		document.body.style.setProperty('--base3-chatbot-dock-width', String(width) + 'px');
	}

	function removeDockedShell() {
		document.body.classList.remove('base3-chatbot-docked');
		document.body.style.removeProperty('--base3-chatbot-dock-width');
	}

	function loadChatbot(panel, state, settings, ajaxUrl, strings) {
		if (state.loaded || state.loading) {
			scheduleInitialPanelResize(panel, state, settings);
			return;
		}

		state.loading = true;
		panel.body.textContent = '';
		panel.body.classList.add('has-iframe');
		var loading = document.createElement('div');
		loading.className = 'base3-chatbot-loading';
		loading.textContent = strings.loading;
		panel.body.appendChild(loading);

		// The unmodified ClientStack client runs as its own HTML document.
		// This preserves native ES module imports and isolates Moodle CSS.
		var frame = document.createElement('iframe');
		frame.className = 'base3-chatbot-iframe';
		frame.title = panel.container.getAttribute('aria-label') || 'Chatbot';
		frame.setAttribute('allow', 'microphone');
		frame.addEventListener('load', function() {
			loading.remove();
			state.loaded = true;
			state.loading = false;
			bindContentResizeObserver(panel, state, settings);
			scheduleInitialPanelResize(panel, state, settings);
		});
		frame.addEventListener('error', function() {
			loading.remove();
			state.loading = false;
			panel.body.classList.remove('has-iframe');
			showError(panel.body, strings.loadError, '');
		});
		frame.src = ajaxUrl;
		panel.body.appendChild(frame);
		scheduleInitialPanelResize(panel, state, settings);
	}

	function showError(container, message, detail) {
		container.innerHTML = '';

		var error = document.createElement('div');
		error.className = 'base3-chatbot-error';
		error.appendChild(document.createTextNode(message));

		if (detail !== '') {
			error.appendChild(document.createElement('br'));
			error.appendChild(document.createTextNode(detail));
		}

		container.appendChild(error);
	}

	function installAutoResize(panel, state, settings) {
		if (panel.container.getAttribute('data-autoresize-installed') === '1') {
			return;
		}

		panel.container.setAttribute('data-autoresize-installed', '1');

		if (typeof ResizeObserver !== 'undefined') {
			panel.contentResizeObserver = new ResizeObserver(function() {
				schedulePanelResize(panel, state, settings);
			});
		}

		if (typeof MutationObserver !== 'undefined') {
			panel.mutationObserver = new MutationObserver(function() {
				bindContentResizeObserver(panel, state, settings);
				schedulePanelResize(panel, state, settings);
			});

			panel.mutationObserver.observe(panel.body, {
				childList: true,
				subtree: true,
				attributes: true,
				characterData: true
			});
		}

		panel.windowResizeHandler = function() {
			schedulePanelResize(panel, state, settings);
			if (state.mode === 'docked') {
				updateDockedShell(panel, state);
			}
		};

		window.addEventListener('resize', panel.windowResizeHandler);

		bindContentResizeObserver(panel, state, settings);
	}

	function bindContentResizeObserver(panel, state, settings) {
		if (!panel.contentResizeObserver) {
			return;
		}

		panel.contentResizeObserver.disconnect();
		panel.contentResizeObserver.observe(panel.body);

		Array.prototype.forEach.call(panel.body.children, function(child) {
			panel.contentResizeObserver.observe(child);
		});

		schedulePanelResize(panel, state, settings);
	}

	function scheduleInitialPanelResize(panel, state, settings) {
		schedulePanelResize(panel, state, settings);

		window.setTimeout(function() {
			schedulePanelResize(panel, state, settings);
		}, 80);

		window.setTimeout(function() {
			schedulePanelResize(panel, state, settings);
		}, 200);

		window.setTimeout(function() {
			schedulePanelResize(panel, state, settings);
		}, 500);

		window.setTimeout(function() {
			schedulePanelResize(panel, state, settings);
		}, 1000);
	}

	function schedulePanelResize(panel, state, settings) {
		if (!panel.container.classList.contains('is-open')) {
			return;
		}

		if (state.mode !== 'overlay' || isResizableMode(settings, 'overlay')) {
			return;
		}

		if (state.resizeFrame) {
			return;
		}

		state.resizeFrame = window.requestAnimationFrame(function() {
			state.resizeFrame = 0;
			updatePanelSize(panel, state, settings);
		});
	}

	function updatePanelSize(panel, state, settings) {
		if (state.mode !== 'overlay' || isResizableMode(settings, 'overlay')) {
			return;
		}

		var width = measurePanelWidth(panel);
		panel.container.style.setProperty('--base3-chatbot-panel-width', String(width) + 'px');
	}

	function measurePanelWidth(panel) {
		var minWidth = 420;
		var defaultWidth = 720;
		var viewportPadding = window.innerWidth <= 767 ? 24 : 40;
		var availableWidth = Math.max(0, window.innerWidth - viewportPadding);
		var effectiveMinWidth = Math.min(minWidth, availableWidth);
		var maxWidth = Math.max(effectiveMinWidth, availableWidth);
		var headerWidth = Math.ceil(panel.header.scrollWidth || 0);
		var contentWidth = Math.ceil(Math.max(panel.body.scrollWidth + 2, headerWidth));

		return clamp(Math.max(contentWidth, defaultWidth), effectiveMinWidth, maxWidth);
	}

	function storageGet(storage, key) {
		try {
			return storage.getItem(key);
		} catch (e) {
			return null;
		}
	}

	function storageSet(storage, key, value) {
		try {
			storage.setItem(key, value);
		} catch (e) {
		}
	}

	function storageRemove(storage, key) {
		try {
			storage.removeItem(key);
		} catch (e) {
		}
	}

	function clamp(value, min, max) {
		return Math.min(Math.max(value, min), max);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
