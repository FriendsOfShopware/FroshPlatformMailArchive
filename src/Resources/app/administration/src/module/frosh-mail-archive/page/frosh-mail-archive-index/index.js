const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const utils = Shopware.Utils;
import template from './frosh-mail-archive-index.twig';
import './frosh-mail-archive-index.scss';

Component.register('frosh-mail-archive-index', {
    template,
    inject: ['repositoryFactory', 'froshMailArchiveService'],
    mixins: [Mixin.getByName('listing'), Mixin.getByName('notification')],

    metaInfo() {
        return {
            title: this.$createTitle(),
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
                transportState: null,
                customerId: null,
                term: null,
            },
            selectedItems: {},
            selectedEmailAdress: null,
            resendItem: null,
            showResendModal: false
        };
    },

    computed: {
        columns() {
            return [
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: 'frosh-mail-archive.list.columns.sentDate',
                    primary: true,
                    routerLink: 'frosh.mail.archive.detail',
                },
                {
                    property: 'transportState',
                    dataIndex: 'transportState',
                    label: 'frosh-mail-archive.list.columns.transportState',
                    allowResize: true,
                },
                {
                    property: 'subject',
                    dataIndex: 'subject',
                    label: 'frosh-mail-archive.list.columns.subject',
                    allowResize: true,
                    routerLink: 'frosh.mail.archive.detail',
                },
                {
                    property: 'receiver',
                    dataIndex: 'receiver',
                    label: 'frosh-mail-archive.list.columns.receiver',
                    allowResize: true,
                },
            ];
        },
        mailArchiveRepository() {
            return this.repositoryFactory.create('frosh_mail_archive');
        },
        date() {
            return Shopware.Filter.getByName('date');
        },
        transportStateOptions() {
            return [
                {
                    value: 'failed',
                    label: this.translateState('failed'),
                },
                {
                    value: 'sent',
                    label: this.translateState('sent'),
                },
                {
                    value: 'pending',
                    label: this.translateState('pending'),
                },
                {
                    value: 'resent',
                    label: this.translateState('resent'),
                },
            ];
        },
    },

    methods: {
        closeResendModal(){
            this.showResendModal = false;
            this.isLoading = false;
        },
        translateState(state) {
            return this.$tc(`frosh-mail-archive.state.${state}`);
        },

        updateData(query) {
            for (const filter in this.filter) {
                this.filter[filter] = query[filter] ?? null;
            }
        },

        saveFilters() {
            this.updateRoute(
                {
                    limit: this.limit,
                    page: this.page,
                    term: this.term,
                    sortBy: this.sortBy,
                    sortDirection: this.sortDirection,
                    naturalSorting: this.naturalSorting,
                },
                this.filter
            );
        },

        getList() {
            this.isLoading = true;

            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);

            if (this.filter.transportState) {
                criteria.addFilter(
                    Criteria.equals(
                        'transportState',
                        this.filter.transportState
                    )
                );
            }

            if (this.filter.salesChannelId) {
                criteria.addFilter(
                    Criteria.equals(
                        'salesChannelId',
                        this.filter.salesChannelId
                    )
                );
            }

            if (this.filter.customerId) {
                criteria.addFilter(
                    Criteria.equals('customerId', this.filter.customerId)
                );
            }

            if (this.filter.term) {
                criteria.setTerm(this.filter.term);
            }

            criteria.addSorting(Criteria.sort('createdAt', 'DESC'));

            return this.mailArchiveRepository
                .search(criteria, Shopware.Context.api)
                .then((searchResult) => {
                    this.items = searchResult;
                    this.total = searchResult.total;
                    this.isLoading = false;
                    this.saveFilters();
                });
        },

        resendMailPreview(item){
            this.isLoading = true;
            this.showResendModal = true;
            const email = Object.keys(item.receiver)[0];
            this.selectedEmailAdress = email;
            this.resendItem = item;
        },
        resendMail(item, email = Object.keys(item.receiver)[0]) {
            this.froshMailArchiveService
                .resendMail(item.id, email)
                .then(async () => {
                    this.createNotificationSuccess({
                        title: this.$tc(
                            'frosh-mail-archive.detail.resend-success-notification.title'
                        ),
                        message: this.$tc(
                            'frosh-mail-archive.detail.resend-success-notification.message'
                        ),
                    });
                    await this.getList();
                })
                .catch(() => {
                    this.createNotificationError({
                        title: this.$tc(
                            'frosh-mail-archive.detail.resend-error-notification.title'
                        ),
                        message: this.$tc(
                            'frosh-mail-archive.detail.resend-error-notification.message'
                        ),
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                    this.showResendModal = false;
                });
        },

        onBulkResendClick() {
            const ids = Object.keys(this.selectedItems);
            if (ids.length === 0) {
                return;
            }
            this.isLoading = true;

            Promise.all(
                ids.map((id) => {
                    return this.froshMailArchiveService.resendMail(id);
                })
            ).finally(async () => {
                this.$refs.table?.resetSelection();
                await this.getList();
                this.isLoading = false;
            });
        },

        onSelectionChanged(selection) {
            this.selectedItems = selection;
        },

        resetFilter() {
            this.filter = {
                salesChannelId: null,
                customerId: null,
                term: null,
            };
        },
    },

    watch: {
        filter: {
            deep: true,
            handler: utils.debounce(function () {
                this.getList();
            }, 400),
        },
    },
});
