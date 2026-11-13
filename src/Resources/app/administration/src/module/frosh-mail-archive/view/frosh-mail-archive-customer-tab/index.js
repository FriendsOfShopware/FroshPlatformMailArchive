import template from './frosh-mail-archive-customer-tab.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('frosh-mail-archive-customer-tab', {
    template,

    inject: ['repositoryFactory'],

    props: {
        customer: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            mails: null,
            isLoading: false,
            page: 1,
            limit: 10,
        };
    },

    computed: {
        mailArchiveRepository() {
            return this.repositoryFactory.create('frosh_mail_archive');
        },

        columns() {
            return [
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    sortable: false,
                    label: 'frosh-mail-archive.list.columns.sentDate',
                    primary: true,
                    routerLink: 'frosh.mail.archive.detail',
                },
                {
                    property: 'subject',
                    dataIndex: 'subject',
                    sortable: false,
                    label: 'frosh-mail-archive.list.columns.subject',
                    routerLink: 'frosh.mail.archive.detail',
                },
                {
                    property: 'transportState',
                    dataIndex: 'transportState',
                    sortable: false,
                    label: 'frosh-mail-archive.list.columns.transportState',
                },
            ];
        },

        mailCriteria() {
            const criteria = new Criteria(this.page, this.limit);
            criteria.addFilter(
                Criteria.contains(
                    'receiver',
                    JSON.stringify(this.customer.email)
                )
            );
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return criteria;
        },

        fullArchiveRoute() {
            return {
                name: 'frosh.mail.archive.list',
                query: { term: this.customer.email },
            };
        },

        date() {
            return Shopware.Filter.getByName('date');
        },
    },

    watch: {
        'customer.email': {
            immediate: true,
            handler() {
                this.page = 1;
                this.loadMails();
            },
        },
    },

    methods: {
        async loadMails() {
            if (!this.customer.email) {
                this.mails = null;

                return;
            }

            this.isLoading = true;

            try {
                this.mails = await this.mailArchiveRepository.search(
                    this.mailCriteria
                );
            } finally {
                this.isLoading = false;
            }
        },

        onPageChange({ page, limit }) {
            this.page = page;
            this.limit = limit;
            this.loadMails();
        },

        translateState(state) {
            return this.$tc(`frosh-mail-archive.state.${state}`);
        },
    },
});
