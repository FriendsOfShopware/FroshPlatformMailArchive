import template from './sw-customer-detail.html.twig';

Shopware.Component.override('sw-customer-detail', {
    template,

    computed: {
        showMailArchiveTab() {
            return this.acl.can('frosh_mail_archive:read');
        },

        mailArchiveRoute() {
            return {
                name: 'sw.customer.detail.mailArchive',
                params: { id: this.customerId },
            };
        },
    },
});
