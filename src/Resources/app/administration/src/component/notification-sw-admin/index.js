const {Component, State} = Shopware;
const {Criteria} = Shopware.Data;

Component.override('sw-admin', {
    inject: ['repositoryFactory'],

    async created() {
        const repository = this.repositoryFactory.create('frosh_mail_archive');

        const criteria = new Criteria();
        criteria.addFilter(Criteria.equals('emlPath', null));
        criteria.setLimit(1);

        repository.searchIds(criteria).then((result) => {
            if (result.total > 0) {
                State.dispatch('notification/createNotification', {
                    variant: 'error',
                    system: true,
                    autoClose: false,
                    growl: true,
                    title: this.$tc('frosh-mail-archive.missingMigration.title'),
                    message: this.$tc('frosh-mail-archive.missingMigration.description'),
                });
            }
        });
    }
});
