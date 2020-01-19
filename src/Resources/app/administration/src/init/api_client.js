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
}

export default ApiClient;
