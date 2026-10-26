import ApiClient from './api_client.js';

const { Application } = Shopware;

Application.addServiceProvider('froshMailArchiveService', (container) => {
    const initContainer = Application.getContainer('init');
    return new ApiClient(initContainer.httpClient, container.loginService);
});
