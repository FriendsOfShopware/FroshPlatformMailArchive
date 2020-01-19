const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
import template from './frosh-mail-archive-index.twig';

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
            repository: null,
            items: [],
            isLoading: true,
            total: 0
        }
    },

    computed: {
        columns() {
            return [
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: this.$tc('frosh-mail-archive.list.columns.sendDate'),
                    primary: true
                },
                {
                    property: 'subject',
                    dataIndex: 'subject',
                    label: this.$tc('frosh-mail-archive.list.columns.subject'),
                    allowResize: true
                },
                {
                    property: 'receiver',
                    dataIndex: 'receiver',
                    label: this.$tc('frosh-mail-archive.list.columns.receiver'),
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

            return this.mailArchiveRepository.search(new Criteria(), Shopware.Context.api)
                .then((searchResult) => {
                    this.items = searchResult;
                    this.total = searchResult.total;
                    this.isLoading = false;
                });
        },

        updateTotal({ total }) {
            this.total = total;
        }
    }
});
