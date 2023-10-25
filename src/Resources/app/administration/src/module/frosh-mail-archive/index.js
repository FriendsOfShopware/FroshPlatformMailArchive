import './page/frosh-mail-archive-index/index';
import './page/frosh-mail-archive-detail/index';
import './component/frosh-mail-resend-history'

Shopware.Module.register('frosh-mail-archive', {
    type: 'plugin',
    name: 'frosh-mail-archive.title',
    title: 'frosh-mail-archive.title',
    description: '',
    color: '#243758',
    icon: 'regular-envelope',
    entity: 'frosh_mail_archive',

    routes: {
        list: {
            component: 'frosh-mail-archive-index',
            path: 'list'
        },
        detail: {
            component: 'frosh-mail-archive-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'frosh.mail.archive.list'
            },
            props: {
                default: ($route) => {
                    return { archiveId: $route.params.id };
                },
            },
        }
    },

    settingsItem: [
        {
            group: 'plugins',
            to: 'frosh.mail.archive.list',
            icon: 'regular-envelope',
            name: 'frosh-mail-archive.title',
            privilege: 'frosh_mail_archive:read'
        }
    ]
});
