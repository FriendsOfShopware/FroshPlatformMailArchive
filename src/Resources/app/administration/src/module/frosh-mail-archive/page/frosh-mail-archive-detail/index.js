const { Component } = Shopware;
import template from './frosh-mail-archive-detail.twig';
import './frosh-mail-archive-detail.scss';

Component.register('frosh-mail-archive-detail', {
    template,
    inject: ['repositoryFactory', 'froshMailArchiveService'],

    data() {
        return {
            archive: null,
            resendIsLoading: false,
            resendIsSuccessful: false,
            downloadIsLoading: false,
            downloadIsSuccessful: false,
        }
    },

    created() {
        this.repository = this.repositoryFactory.create('frosh_mail_archive');
        this.repository.get(this.$route.params.id, Shopware.Context.api).then(archive => {
            this.archive = archive;
        })
    },
    computed: {
        createdAtDate() {
            const locale = Shopware.State.getters.adminLocaleLanguage || 'en';
            const options = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };

            return new Intl.DateTimeFormat(locale, options).format(new Date(this.archive.createdAt));
        },
        receiverText() {
            let text = [];

            Object.keys(this.archive.receiver).forEach(key => {
                text.push(`${this.archive.receiver[key]} <${key}>`);
            });

            return text.join(',');
        },
        senderText() {
            let text = [];

            Object.keys(this.archive.sender).forEach(key => {
                text.push(`${this.archive.sender[key]} <${key}>`);
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
            this.resendIsLoading = true;

            this.froshMailArchiveService.resendMail(this.archive.id).then(() => {
                this.resendIsLoading = false;
                this.resendIsSuccessful = true;
            }).catch(() => {
                this.resendIsLoading = false;
                this.resendIsSuccessful = false;
            });
        },
        downloadMail() {
            this.downloadIsLoading = true;

            this.froshMailArchiveService.downloadMail(this.archive.id).then(() => {
                this.downloadIsLoading = false;
                this.downloadIsSuccessful = true;
            }).catch(() => {
                this.downloadIsLoading = false;
                this.downloadIsSuccessful = false;
            });
        }
    }
});
