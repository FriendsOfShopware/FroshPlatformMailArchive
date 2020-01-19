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
                    label: 'Send-Date',
                    primary: true
                },
                {
                    property: 'subject',
                    dataIndex: 'subject',
                    label: 'Subject',
                    allowResize: true
                },
                {
                    property: 'receiver',
                    dataIndex: 'receiver',
                    label: 'Receiver',
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
