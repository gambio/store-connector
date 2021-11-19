/* --------------------------------------------------------------
 configurationPage.js 2021-11-19
 Gambio GmbH
 http://www.gambio.de
 Copyright (c) 2021 Gambio GmbH
 Released under the GNU General Public License (Version 2)
 [http://www.gnu.org/licenses/gpl-2.0.html]
 --------------------------------------------------------------
 */

const dockerNetworkUrl = `${window.location.protocol}//${window.location.hostname}`;

const urls = {
    PRODUCTION: {
        GUI: "https://store.gambio.com",
        API: "https://api.store.gambio.com"
    },
    STAGE: {
        GUI: "https://stage.store.gambio.com/a",
        API: "https://api.stage.store.gambio.com/a"
    },
    DOCKER: {
        GUI: `${dockerNetworkUrl}:6900`,
        API: `${dockerNetworkUrl}:6800`
    }
};

const requestNewAuthHeaders = async () => {
    await GambioStore.callShop("admin.php?do=GambioStoreAjax/requestNewAuth");
};

const resetToken = () => {
    window.location.replace("admin.php?do=GambioStore&reset-token");
};

const getInputElements = () => {
    const guiUrlElement = document.getElementById("store-gui-url");
    const apiUrlElement = document.getElementById("store-api-url");

    return { guiUrlElement, apiUrlElement };
};

const updateProductionUrls = () => {
    const { guiUrlElement, apiUrlElement } = getInputElements();

    guiUrlElement.value = urls.PRODUCTION.GUI;
    apiUrlElement.value = urls.PRODUCTION.API;
};

const updateStageUrls = () => {
    const { guiUrlElement, apiUrlElement } = getInputElements();

    guiUrlElement.value = urls.STAGE.GUI;
    apiUrlElement.value = urls.STAGE.API;
};

const updateDockerUrls = () => {
    const { guiUrlElement, apiUrlElement } = getInputElements();

    guiUrlElement.value = urls.DOCKER.GUI;
    apiUrlElement.value = urls.DOCKER.API;
};

window.addEventListener("DOMContentLoaded", () => {
    const setToProductionButtonElement = document.getElementById(
        "set-to-production-button"
    );
    const setToStageButtonElement = document.getElementById(
        "set-to-stage-button"
    );

    const setToDockerButtonElement = document.getElementById(
        "set-to-docker-button"
    );

    const requestNewAuthButtonElement = document.getElementById(
        "request-new-auth-button"
    );

    const resetTokenButtonElement = document.getElementById(
        "reset-token-button"
    );

    setToProductionButtonElement.addEventListener(
        "click",
        updateProductionUrls
    );
    setToStageButtonElement.addEventListener("click", updateStageUrls);
    setToDockerButtonElement.addEventListener("click", updateDockerUrls);
    requestNewAuthButtonElement.addEventListener(
        "click",
        requestNewAuthHeaders
    );
    resetTokenButtonElement.addEventListener("click", resetToken);
});
