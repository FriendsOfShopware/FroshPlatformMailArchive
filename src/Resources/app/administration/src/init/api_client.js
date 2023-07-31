const ApiService = Shopware.Classes.ApiService;

class ApiClient extends ApiService{
    constructor(httpClient, loginService, apiEndpoint = 'frosh-mail-archive') {
        super(httpClient, loginService, apiEndpoint);
    }

    resendMail(mailId) {
        const headers = this.getBasicHeaders({});

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/resend-mail`, {
                mailId,
            }, {
                ...this.basicConfig,
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    downloadMail(mailId) {
        const headers = this.getBasicHeaders({});

        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/download-mail`, {
                mailId,
            }, {
                ...this.basicConfig,
                headers
            })
            .then((response) => {
                const handledResponse = ApiService.handleResponse(response);

                if (!handledResponse.success) {
                    return handledResponse;
                }

                const objectUrl = window.URL.createObjectURL(new Blob([handledResponse.content]));

                const link = document.createElement('a');
                link.href = objectUrl;
                link.setAttribute('download', handledResponse.fileName);
                document.body.appendChild(link);
                link.click();
            });
    }
}

export default ApiClient;
