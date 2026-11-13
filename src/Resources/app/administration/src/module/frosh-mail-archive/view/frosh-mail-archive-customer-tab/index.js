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
            loadFailed: false,
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
            const customerFilters = [
                Criteria.equals('customerId', this.customer.id),
            ];

            if (this.customer.email) {
                customerFilters.push(
                    Criteria.contains(
                        'receiver',
                        JSON.stringify(this.customer.email)
                    )
                );
            }

            criteria.addFilter(Criteria.multi('OR', customerFilters));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return criteria;
        },

        customerIdentity() {
            return JSON.stringify([this.customer.id, this.customer.email]);
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
        customerIdentity: {
            immediate: true,
            handler() {
                this.page = 1;
                this.loadMails();
            },
        },
    },

    methods: {
        async loadMails() {
            this.isLoading = true;
            this.loadFailed = false;

            try {
                this.mails = await this.mailArchiveRepository.search(
                    this.mailCriteria
                );
            } catch {
                this.mails = null;
                this.loadFailed = true;
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
