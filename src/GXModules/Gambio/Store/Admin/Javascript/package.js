/* --------------------------------------------------------------
   package.js 2020-05-05
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
 */

/**
 * Starts the activation of a theme by folder name inside shop.
 *
 * @param data
 */
const activateTheme = async (data) => {
	const formData = new FormData();
	formData.append('themeStorageName', data.folder_name_inside_shop || data.filename || data.themeStorageName);
	
	try {
		await GambioStore.callShop('./admin.php?do=GambioStoreAjax/ActivateTheme', {
			method: 'POST',
			body: formData
		});
		GambioStore.messenger.sendMessage('activation_succeeded');
	} catch {
		GambioStore.messenger.sendMessage('activation_failed')
	}
	
}

/**
 * Checks with a given theme name if this theme is currently active.
 *
 * @param themeName
 * @returns {Promise<Response>}
 */
const isThemeActive = (themeName) => {
	return GambioStore.callShop('admin.php?do=GambioStoreAjax/IsThemeActive&themeName=' + themeName);
};

/**
 * Starts installing a package.
 * This loops until the package is 100% installed (until the request body contains {done: true}).
 * during each installation request, a callback is invoked, e.g. for progress bars.
 *
 * @param data
 * @param progressCallback {function} invoked between each installation request. Progress-Bars may hook into this.
 * @returns {Promise<>} Resolves when installed. Rejects upon error.
 */
const installPackage = async (data, progressCallback) => {
	const formData = new FormData();
	let progress = 0;
	
	formData.append('gambioStoreData', JSON.stringify(data));
	
	let response = await GambioStore.callShop('admin.php?do=GambioStoreAjax/InstallPackage', {
		method: 'post',
		body: formData
	});
	
	while (progress !== 100) {
		response = await new Promise((resolve, reject) => {
			setTimeout(() => {
				GambioStore.callShop('admin.php?do=GambioStoreAjax/InstallPackage', {
					method: 'post',
					body: formData
				})
					.then(resolve)
					.catch(reject);
			}, 500)
		});
		
		if (!response.success) {
			throw new Error('Package not installed!');
		}
		
		progress = response.progress ? response.progress : progress;
		progressCallback(progress);
	}
	
	await new Promise((resolve, reject) => {
		setTimeout(() => {
			showClearCache();
			GambioStore.clearShopCache()
				.then(resolve)
				.catch(reject);
		}, 500)
	});
}

/**
 * Reloads the page if the admin session is expired.
 * Forces the user to log out.
 * @returns {Promise<void>}
 */
const reloadPageOnInactiveSession = async () => {
	try {
		await GambioStore.callShop('admin.php?do=GambioStoreAjax/IsSessionActive', {
			method: 'get',
			redirect: 'error'
		});
	} catch (error) {
		location.reload();
	}
};

/**
 * Uninstall a theme
 *
 * @param data
 */
const uninstallPackage = async (data) => {
	await reloadPageOnInactiveSession();
	
	const formData = new FormData();
	const $installingPackageModal = $('.installing-package.modal');
	const modalBody = document
		.getElementsByClassName('installing-package modal').item(0)
		.getElementsByClassName('modal-body').item(0);
	const modalBodyInnerHtml = modalBody.innerHTML;
	
	formData.append('gambioStoreData', JSON.stringify(data));
	
	try {
		await GambioStore.callShop('admin.php?do=GambioStoreAjax/UninstallPackage', {
			method: 'post',
			body: formData
		});
		
		await new Promise((resolve, reject) => {
			setTimeout(() => {
				showClearCache();
				$installingPackageModal.modal('show');
				GambioStore.clearShopCache()
					.then(resolve)
					.catch(reject);
			}, 500)
		});
		
		GambioStore.messenger.sendMessage('uninstall_succeeded');
	} catch (error) {
		GambioStore.messenger.sendMessage('uninstall_failed', error.context || error);
	} finally {
		$installingPackageModal.modal('hide');
		modalBody.innerHTML = modalBodyInnerHtml;
	}
}

/**
 * Starting the installation process that needs to be done for a package installation to the shop.
 * @param data
 * @return {Promise<void>}
 */
const startPackageInstallation = async (data) => {
	const $installingPackageModal = $('.installing-package.modal');
	const progressDescription = document
		.getElementsByClassName('installing-package modal').item(0)
		.getElementsByClassName('progress-description').item(0);
	const modalBody = document
		.getElementsByClassName('installing-package modal').item(0)
		.getElementsByClassName('modal-body').item(0);
	const modalBodyInnerHtml = modalBody.innerHTML;
	
	try {
		updateProgressCallback(0);
		$installingPackageModal.modal('show');
		progressDescription.textContent = GambioStore.translation.translate('INSTALLING_PACKAGE');
		
		await installPackage(data, updateProgressCallback);
		
		if (data.details.type === 'theme') {
			const response = await isThemeActive(data.details.folder_name_inside_shop || data.details.filename
				|| data.details.themeStorageName);
			if (response.isActive === true) {
				await activateTheme(data.details);
			}
		}
		
		GambioStore.messenger.sendMessage('installation_succeeded')
	} catch (error) {
		GambioStore.messenger.sendMessage('installation_failed');
	} finally {
		$installingPackageModal.modal('hide');
		modalBody.innerHTML = modalBodyInnerHtml;
	}
}

/**
 * Callback function to update the progressbar in the gui.
 * @param progress
 */
const updateProgressCallback = (progress) => {
	const progressBar = document
		.getElementsByClassName('installing-package modal').item(0)
		.getElementsByClassName('progress-bar').item(0);
	
	progressBar['aria-valuenow'] = progress;
	progressBar.style.width = progress + '%';
	progressBar.textContent = progress + '%';
};

/**
 * Replaces the progressbar in the modal with a loading spinner and emptying cache infromation.
 */
const showClearCache = () => {
	const cacheClearingText = GambioStore.translation.translate('CLEARING_CACHE');
	const description = `<p class="progress-description">${cacheClearingText}</p>`
	const loadingSpinner = '<br><div class="text-center"><i class="fas fa-spinner text-primary fa-spin fa-3x loading-spinner"></i></div>'
	const modalBody = document
		.getElementsByClassName('installing-package modal').item(0)
		.getElementsByClassName('modal-body').item(0);
	
	modalBody.innerHTML = '';
	modalBody.innerHTML = description + loadingSpinner
};

/**
 * Install process to install packages by data.
 *
 * @param data
 * @return {Promise<void>}
 */
const install = async (data) => {
	await reloadPageOnInactiveSession();
	
	const progressDescription = document
		.getElementsByClassName('installing-package modal').item(0)
		.getElementsByClassName('progress-description').item(0);
	
	progressDescription.textContent = GambioStore.translation.translate('PREPARING_PACKAGE');
	
	await startPackageInstallation(data);
}

window.addEventListener('DOMContentLoaded', () => {
	GambioStore.messenger.listenToMessage('start_installation_process', install);
	GambioStore.messenger.listenToMessage('uninstall_theme', uninstallPackage);
	GambioStore.messenger.listenToMessage('uninstall_package', uninstallPackage);
	GambioStore.messenger.listenToMessage('activate_theme', activateTheme);
});
