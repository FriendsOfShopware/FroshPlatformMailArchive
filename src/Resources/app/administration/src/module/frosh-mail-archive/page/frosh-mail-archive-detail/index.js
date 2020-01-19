const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
import template from './frosh-mail-archive-detail.twig';
import './frosh-mail-archive-detail.scss';

Component.register('frosh-mail-archive-detail', {
    template,
    inject: ['repositoryFactory', 'froshMailArchiveService'],

    data() {
        return {
            archive: null,
            isLoading: false,
            isSuccessful: false,
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('frosh_mail_archive');
        this.repository.get(this.$route.params.id, Shopware.Context.api).then(archive => {
            this.archive = archive;
        })
    },
    computed: {
        receiverText() {
            let text = [];

            Object.keys(this.archive.receiver).forEach(key => {
                text.push(`${key} <${this.archive.receiver[key]}>`);
            });

            return text.join(',');
        },
        htmlText() {
            return this.getContent(this.archive.htmlText);
        },
        plainText() {
            return this.getContent(this.archive.plainText);
        }
    },

    methods: {
        getContent(html) {
            return 'data:text/html;base64,' + btoa(unescape(encodeURIComponent(html.replace(/[\u00A0-\u2666]/g, function(c) {
                return '&#' + c.charCodeAt(0) + ';';
            }))));
        },
        openCustomer() {
            this.$router.push({
                name: 'sw.customer.detail',
                params: { id: this.archive.customer.id }
            });
        },
        resendMail() {
            this.isLoading = true;

            this.froshMailArchiveService.resendMail(this.archive.id).then(() => {
                this.isLoading = false;
                this.isSuccessful = true;
            }).catch(() => {
                this.isLoading = false;
                this.isSuccessful = false;
            });
        }
    }
});
