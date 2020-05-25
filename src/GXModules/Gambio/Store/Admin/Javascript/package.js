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
	formData.append('themeStorageName', data.folder_name_inside_shop || data.filename);
	
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
const installPackage = async (data, progressCallback = () => null) => {
	const formData = new FormData();
	formData.append('gambioStoreData', JSON.stringify(data));
	
	const doPackageInstallation = async () => {
		const response = await GambioStore.callShop('admin.php?do=GambioStoreAjax/InstallPackage', {
			method: 'post',
			body: formData
		});
		progressCallback(response);
		
		if (response.done !== true) {
			await doPackageInstallation;
		}
		
		return true;
	}
	
	await doPackageInstallation();
}

/**
 * Ensure that file permissions for a theme installation are valid.
 *
 * @param data
 * @returns {Promise<boolean>}
 */
const isFilePermissionCorrect = async (data) => {
	const formData = new FormData();
	
	formData.append('gambioStoreData', JSON.stringify(data));
	try {
		await GambioStore.callShop('admin.php?do=GambioStoreAjax/CheckFilePermissions', {
			method: 'post',
			body: formData
		})
		return true;
	} catch {
		return false;
	}
}

/**
 * Uninstall a theme
 *
 * @param data
 */
const uninstallPackage = async (data) => {
	const formData = new FormData();
	
	formData.append('gambioStoreData', JSON.stringify(data));
	
	try {
		await GambioStore.callShop('admin.php?do=GambioStoreAjax/uninstallPackage', {
			method: 'post',
			body: formData
		});
		GambioStore.messenger.sendMessage('uninstall_succeeded');
	} catch {
		GambioStore.messenger.sendMessage('uninstall_failed', data);
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
	
	// By checking whether a gallery object is present,
	// we can determine if this is a theme or not.
	try {
		progressDescription.textContent = GambioStore.translation.translate('INSTALLING_PACKAGE');
		await installPackage(data, updateProgressCallback);
		
		if (data.details.gallery) {
			const response = isThemeActive(data.details.folder_name_inside_shop || data.details.filename);
			if (response.isActive === true) {
				await activateTheme(data.details.folder_name_inside_shop || data.details.filename);
			}
		}
		
		GambioStore.messenger.sendMessage('installation_succeeded')
	} catch {
		GambioStore.messenger.sendMessage('installation_failed');
	} finally {
		updateProgressCallback({progress: 1});
		setTimeout(() => {
			$installingPackageModal.modal('hide');
		}, 2000);
	}
}

/**
 * Callback function to update the progressbar in the gui.
 * @param progress
 */
const updateProgressCallback = ({progress}) => {
	const progressBar = document
		.getElementsByClassName('installing-package modal').item(0)
		.getElementsByClassName('progress-bar').item(0);
	
	let progressPercentage = Math.ceil(progress * 100);
	
	if (progressPercentage < 0) {
		progressPercentage = 0;
	} else if (progressPercentage > 100) {
		progressPercentage = 100;
	}
	
	progressBar['aria-valuenow'] = progressPercentage;
	progressBar.style.width = progressPercentage + '%';
	progressBar.textContent = progressPercentage + '%';
};

/**
 * Install process to install packages by data.
 *
 * @param data
 * @return {Promise<void>}
 */
const install = async (data) => {
	const $installingPackageModal = $('.installing-package.modal');
	const progressDescription = document
		.getElementsByClassName('installing-package modal').item(0)
		.getElementsByClassName('progress-description').item(0);
	
	progressDescription.textContent = GambioStore.translation.translate('PREPARING_PACKAGE');
	
	updateProgressCallback({progress: 0}); // always set to 0 initially
	
	$installingPackageModal.modal('show');
	await startPackageInstallation(data);
}

window.addEventListener('DOMContentLoaded', () => {
	GambioStore.messenger.listenToMessage('start_installation_process', install);
	GambioStore.messenger.listenToMessage('uninstall_theme', uninstallPackage);
	GambioStore.messenger.listenToMessage('uninstall_package', uninstallPackage);
	GambioStore.messenger.listenToMessage('activate_theme', activateTheme);
});
