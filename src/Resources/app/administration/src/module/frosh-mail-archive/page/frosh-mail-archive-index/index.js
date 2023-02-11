const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const utils = Shopware.Utils;
import template from './frosh-mail-archive-index.twig';
import './frosh-mail-archive-index.scss';

Component.register('frosh-mail-archive-index', {
    template,
    inject: ['repositoryFactory'],
    mixins: [
        Mixin.getByName('listing'),
    ],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            page: 1,
            limit: 25,
            total: 0,
            repository: null,
            items: null,
            isLoading: true,
            filter: {
                salesChannelId: null,
                customerId: null,
                term: null
            }
        }
    },

    computed: {
        columns() {
            return [
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: 'frosh-mail-archive.list.columns.sentDate',
                    primary: true,
                    routerLink: 'frosh.mail.archive.detail'
                },
                {
                    property: 'subject',
                    dataIndex: 'subject',
                    label: 'frosh-mail-archive.list.columns.subject',
                    allowResize: true,
                    routerLink: 'frosh.mail.archive.detail'
                },
                {
                    property: 'receiver',
                    dataIndex: 'receiver',
                    label: 'frosh-mail-archive.list.columns.receiver',
                    allowResize: true
                }
            ]
        },
        mailArchiveRepository() {
            return this.repositoryFactory.create('frosh_mail_archive');
        }
    },

    methods: {
        getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);

            if (this.filter.salesChannelId) {
                criteria.addFilter(Criteria.equals('salesChannelId', this.filter.salesChannelId));
            }

            if (this.filter.customerId) {
                criteria.addFilter(Criteria.equals('customerId', this.filter.customerId));
            }

            if (this.filter.term) {
                criteria.setTerm(this.filter.term);
            }

            criteria.addSorting(Criteria.sort('createdAt', 'DESC'))

            return this.mailArchiveRepository.search(criteria, Shopware.Context.api)
                .then((searchResult) => {
                    this.items = searchResult;
                    this.total = searchResult.total;
                    this.isLoading = false;
                });
        },

        resetFilter() {
            this.filter = {
                salesChannelId: null,
                customerId: null,
                term: null
            };
        }
    },

    watch: {
        filter: {
            deep: true,
            handler: utils.debounce(function () {
                this.getList();
            }, 400)
        }
    }
});
