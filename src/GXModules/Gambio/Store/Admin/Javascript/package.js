/* --------------------------------------------------------------
   package.js 2020-04-30
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2020 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------
 */
import messenger from './messenger'
import callShop from './callShop'

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
const installPackage = (data, progressCallback = () => null) => {
    const formData = new FormData();
    formData.append('gambioStoreData', JSON.stringify(data));
    
    const doPackageInstallation = new Promise((resolve, reject) => {
        callShop('admin.php?do=GambioStoreAjax/InstallPackage', {
            method: 'post',
            body: formData
        }).then(response => {
            progressCallback(response);
            if (response.done === true) {
                resolve();
            } else {
                doPackageInstallation.then(resolve).catch(reject);
            }
        }).catch(reject);
    })
    
    return doPackageInstallation;
}

/**
 * Ensure that file permissions for a theme installation are valid.
 *
 * @param data
 * @returns {Promise<unknown>}
 */
const checkFilePermissions = (data) => {
    return new Promise(((resolve, reject) => {
        const formData = new FormData();
        formData.append('gambioStoreData', JSON.stringify(data));
        
        callShop('admin.php?do=GambioStoreAjax/checkPermission', {method: 'post', body: formData})
            .then(resolve)
            .catch(reject);
    }))
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

const install = (data) => {
    const $installingPackageModal = $('.installing-package.modal');
    const $progressDescription = $installingPackageModal.find('.progress-description');
    const $progressBar = $installingPackageModal.find('.progress .progress-bar');
    
    $progressDescription.text(jse.core.lang.translate('PREPARING_PACKAGE', 'gambio_store'));
    
    const updateProgressCallback = ({progress}) => {
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
    
    updateProgressCallback({progress: 0}); // always set to 0 initially
    
    $installingPackageModal.modal('show');
    
    checkFilePermissions(data).then(() => {
            $progressDescription.text(jse.core.lang.translate('INSTALLING_PACKAGE', 'gambio_store'));
            installPackage(data, updateProgressCallback)
                .then(() => {
                    // By checking whether a gallery object is present,
                    // we can determine if this is a theme or not.
                    if (data.details.gallery) {
                        // find out if the installed theme was an update of the currently active theme
                        isThemeActive(data.details.folderNameInsideShop)
                            .then(response => {
                                if (response.isActive === true) {
                                    // and if so, activate it again for the content manager entries
                                    activateTheme(data.details.folderNameInsideShop)
                                        .then(() => messenger.endMessage('installation_succeeded'))
                                        .catch(() => messenger.sendMessage('installation_failed'))
                                } else {
                                    messenger.sendMessage('installation_succeeded')
                                }
                            }).catch(() => messenger.sendMessage('installation_failed'));
                    } else {
                        messenger.sendMessage('installation_succeeded')
                    }
                })
                .catch(() => messenger.sendMessage('installation_failed'))
                .finally(() => {
                    updateProgressCallback({progress: 1});
                    setTimeout(() => {
                        $installingPackageModal.modal('hide');
                    }, 2000);
                });
        }
    ).catch(() => messenger.sendMessage('ftp_data_requested'));
}

window.addEventListener('DOMContentLoaded', () => {
    messenger.listenToMessage('start_installation_process', install);
    messenger.listenToMessage('uninstall_theme', data => uninstallPackage(data.fileName));
    messenger.listenToMessage('activate_theme', data => activateTheme(data.fileName));
});
