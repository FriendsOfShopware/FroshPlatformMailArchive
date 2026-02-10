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
            resendIsLoading: false,
            resendIsSuccessful: false,
            downloadIsLoading: false,
            downloadIsSuccessful: false,
            resendCounter: 0,
            selectedEmailAdress: null,
            resendItem: null,
            showResendModal: false
        };
    },

    props: {
        archiveId: {
            type: String,
            required: true,
        },
    },

    mixins: [Mixin.getByName('notification')],

    created() {
        this.loadMail();
    },
    watch: {
        archiveId() {
            this.loadMail();
        },
    },
    computed: {
        resendKey() {
            return this.archive.id + this.resendCounter;
        },
        repository() {
            return this.repositoryFactory.create('frosh_mail_archive');
        },
        createdAtDate() {
            const locale = Shopware.State.getters.adminLocaleLanguage || 'en';
            const options = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            };

            return new Intl.DateTimeFormat(locale, options).format(
                new Date(this.archive.createdAt)
            );
        },
        receiverText() {
            let text = [];

            Object.keys(this.archive.receiver).forEach((key) => {
                text.push(`${this.archive.receiver[key]} <${key}>`);
            });

            return text.join(',');
        },
        senderText() {
            let text = [];

            Object.keys(this.archive.sender).forEach((key) => {
                text.push(`${this.archive.sender[key]} <${key}>`);
            });

            return text.join(',');
        },
        htmlText() {
            return this.getContent(this.archive.htmlText);
        },
        plainText() {
            return this.getContent(this.archive.plainText);
        },
        attachmentsColumns() {
            return [
                {
                    property: 'fileName',
                    label: this.$t(
                        'frosh-mail-archive.detail.attachments.file-name'
                    ),
                    rawData: true,
                },
                {
                    property: 'fileSize',
                    label: this.$t(
                        'frosh-mail-archive.detail.attachments.size'
                    ),
                    rawData: true,
                },
                {
                    property: 'contentType',
                    label: this.$t(
                        'frosh-mail-archive.detail.attachments.type'
                    ),
                    rawData: true,
                },
            ];
        },
    },

    methods: {
        resendMailPreview(){
            item = this.archive
            this.isLoading = true;
            this.showResendModal = true;
            const email = Object.keys(item.receiver)[0];
            this.selectedEmailAdress = email;
            this.resendItem = item;
        },
        loadMail() {
            const criteria = new Criteria();
            criteria.addAssociation('attachments');
            criteria.addAssociation('customer');
            criteria.addAssociation('order');
            criteria.addAssociation('flow');

            this.repository
                .get(this.archiveId, Shopware.Context.api, criteria)
                .then((archive) => {
                    this.archive = archive;
                });
        },
        getContent(html) {
            const binary = new TextEncoder().encode(html);
            let result = '';
            binary.forEach(b => result += String.fromCharCode(b));

            return (
                'data:text/html;charset=utf-8;base64,' + btoa(result)
            );
        },
        openCustomer() {
            this.$router.push({
                name: 'sw.customer.detail',
                params: { id: this.archive.customer.id },
            });
        },
        resendFinish() {
            this.resendIsSuccessful = false;
        },
        downloadFinish() {
            this.downloadIsSuccessful = false;
        },
        resendMail() {
            this.froshMailArchiveService
                .resendMail(this.archive.id, this.selectedEmailAdress)
                .then(() => {
                    this.resendIsSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc(
                            'frosh-mail-archive.detail.resend-success-notification.title'
                        ),
                        message: this.$tc(
                            'frosh-mail-archive.detail.resend-success-notification.message'
                        ),
                    });
                })
                .catch(() => {
                    this.resendIsSuccessful = false;
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
                    this.resendIsLoading = false;
                    this.resendCounter++;
                    this.showResendModal = false;
                });
        },
        closeResendModal(){
            this.showResendModal = false;
            this.isLoading = false;
        },
        downloadMail() {
            this.downloadIsLoading = true;

            this.froshMailArchiveService
                .downloadMail(this.archive.id)
                .then(() => {
                    this.downloadIsSuccessful = true;
                })
                .catch(() => {
                    this.downloadIsSuccessful = false;
                })
                .finally(() => {
                    this.downloadIsLoading = false;
                });
        },
        downloadAttachment(attachmentId) {
            this.froshMailArchiveService.downloadAttachment(attachmentId);
        },
        formatSize(bytes) {
            const thresh = 1024;
            const dp = 1;
            let formatted = bytes;

            if (Math.abs(bytes) < thresh) {
                return bytes + ' B';
            }

            const units = [
                'KiB',
                'MiB',
                'GiB',
                'TiB',
                'PiB',
                'EiB',
                'ZiB',
                'YiB',
            ];
            let index = -1;
            const reach = 10 ** dp;

            do {
                formatted /= thresh;
                ++index;
            } while (
                Math.round(Math.abs(formatted) * reach) / reach >= thresh &&
                index < units.length - 1
            );

            return formatted.toFixed(dp) + ' ' + units[index];
        },
    },
});
