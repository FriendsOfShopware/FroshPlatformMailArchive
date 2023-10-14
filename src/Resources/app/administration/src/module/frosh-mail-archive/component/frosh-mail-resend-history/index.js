const {Criteria} = Shopware.Data;
import template from './frosh-mail-resend-history.html.twig';

Shopware.Component.register('frosh-mail-resend-history', {
    props: {
        sourceMailId: {
            required: true,
            type: String
        },
        currentMailId: {
            required: true,
            type: String
        }
    },
    template,
    data() {
        return {
            resentMails: [],
            isLoading: false,
            columns: [{
                property: 'createdAt',
                label: this.$tc('frosh-mail-archive.detail.resend-grid.column-created-at'),
                primary: true,
            }, {
                property: 'success',
                label: this.$tc('frosh-mail-archive.detail.resend-grid.column-state'),
                sortable: false,
            }]
        }
    },
    inject: ['repositoryFactory'],
    computed: {
        mailArchiveRepository() {
            return this.repositoryFactory.create('frosh_mail_archive');
        }
    },
    async created() {
        this.isLoading = true;
        await this.loadMails();
        this.isLoading = false;
    },
    methods: {
        async loadMails() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.multi('OR', [
                Criteria.equals('id', this.sourceMailId),
                Criteria.equals('sourceMailId', this.sourceMailId)
            ]));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            this.resentMails = await this.mailArchiveRepository.search(criteria, Shopware.Context.api);
        },
        navigateToDetailPage(id) {
            this.$router.push({name: 'frosh.mail.archive.detail', params: {id}})
        }
    }
});
