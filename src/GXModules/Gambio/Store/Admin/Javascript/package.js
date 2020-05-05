/* --------------------------------------------------------------
   package.js 2020-05-04
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
 */
import messenger from './messenger'
import callShop from './callShop'
import translation from './translation'

/**
 * @param folderNameInsideShop
 */
const activateTheme = (folderNameInsideShop) => {
	const formData = new FormData();
	formData.append('folderNameInsideShop', folderNameInsideShop);
	
	callShop('./admin.php?do=GambioStoreAjax/ActivateTheme', {
		method: 'POST',
		body: formData
	}).then(() => messenger.sendMessage('activation_succeeded'))
		.catch(() => messenger.sendMessage('activation_failed'));
}

/**
 * @param themeName
 * @returns {Promise<Response>}
 */
const isThemeActive = (themeName) => {
	return callShop('admin.php?do=GambioStoreAjax/IsThemeActive&themeName=' + themeName);
};

/**
 * Starts installing a package.
 * This loops until the package is 100% installed (until the request body contains {done: true}).
 * during each installation request, a callback is invoked, e.g. for progress bars.
 *
 * @param data
 * @param progressCallback {function} invoked between each installation request. Progress-Bars may hook into this.
 * @returns {Promise<unknown>} Resolves when installed. Rejects upon error.
 */
const installPackage = async (data, progressCallback = () => null) => {
	const formData = new FormData();
	formData.append('gambioStoreData', JSON.stringify(data));
	
	const doPackageInstallation = async () => {
		const response = await callShop('admin.php?do=GambioStoreAjax/InstallPackage', {
			method: 'post',
			body: formData
		});
		progressCallback(response);
		
		if (response.done !== true) {
			await doPackageInstallation;
		}
	}
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
		await callShop('admin.php?do=GambioStoreAjax/checkPermission', {method: 'post', body: formData})
		return true;
	} catch {
		return false;
	}
}

/**
 * Uninstall a theme
 *
 * @param {String} folderNameInsideShop Theme data
 */
const uninstallPackage = (folderNameInsideShop) => {
	const formData = new FormData();
	formData.append('folderNameInsideShop', folderNameInsideShop)
	
	callShop('admin.php?do=GambioStoreAjax/uninstallPackage', {method: 'post', body: formData})
		.then(() => messenger.sendMessage('uninstall_succeeded'))
		.catch(() => messenger.sendMessage('uninstall_failed', data))
}

/**
 * Starting the installation process that needs to be done for a package installation to the shop.
 * @param data
 * @return {Promise<void>}
 */
const startPackageInstallation = async (data) => {
	// By checking whether a gallery object is present,
	// we can determine if this is a theme or not.
	try {
		$progressDescription.text(translation.translate('INSTALLING_PACKAGE'));
		await installPackage(data, updateProgressCallback);
		
		if (data.details.gallery) {
			const response = isThemeActive(data.details.folderNameInsideShop);
			if (response.isActive === true) {
				await activateTheme(data.details.folderNameInsideShop);
			}
		}
		
		messenger.sendMessage('installation_succeeded')
	} catch {
		messenger.sendMessage('installation_failed');
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
	const $installingPackageModal = $('.installing-package.modal');
	const $progressBar = $installingPackageModal.find('.progress .progress-bar');
	
	let progressPercentage = Math.ceil(progress * 100);
	
	if (progressPercentage < 0) {
		progressPercentage = 0;
	} else if (progressPercentage > 100) {
		progressPercentage = 100;
	}
	
	$progressBar.prop('aria-valuenow', progressPercentage);
	$progressBar.css('width', progressPercentage + '%');
	$progressBar.text(progressPercentage + '%');
};

/**
 * Install process to install packages by data.
 *
 * @param data
 * @return {Promise<void>}
 */
const install = async (data) => {
	const $installingPackageModal = $('.installing-package.modal');
	const $progressDescription = $installingPackageModal.find('.progress-description');
	
	$progressDescription.text(translation.translate('PREPARING_PACKAGE'));
	
	updateProgressCallback({progress: 0}); // always set to 0 initially
	$installingPackageModal.modal('show');
	
	const filePermission = await isFilePermissionCorrect(data);
	
	if (filePermission === false) {
		messenger.sendMessage('ftp_data_requested');
		return;
	}
	
	await startPackageInstallation(data);
}

window.addEventListener('DOMContentLoaded', () => {
	messenger.listenToMessage('start_installation_process', install);
	messenger.listenToMessage('uninstall_theme', data => uninstallPackage(data.fileName));
	messenger.listenToMessage('activate_theme', data => activateTheme(data.fileName));
});
