import './page/frosh-mail-archive-index/index.js';
import './page/frosh-mail-archive-detail/index.js';
import './component/frosh-mail-resend-history/index.js';
import './acl/index.js';

Shopware.Module.register('frosh-mail-archive', {
    type: 'plugin',
    name: 'frosh-mail-archive.title',
    title: 'frosh-mail-archive.title',
    description: '',
    color: '#9AA8B5',
    icon: 'regular-envelope',
    entity: 'frosh_mail_archive',

    routes: {
        list: {
            component: 'frosh-mail-archive-index',
            path: 'list',
            meta: {
                privilege: 'frosh_mail_archive:read',
                parentPath: 'sw.settings.index.plugins',
            },
        },
        detail: {
            component: 'frosh-mail-archive-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'frosh.mail.archive.list',
                privilege: 'frosh_mail_archive:read',
            },
            props: {
                default: ($route) => {
                    return { archiveId: $route.params.id };
                },
            },
        },
    },

    settingsItem: [
        {
            group: 'plugins',
            to: 'frosh.mail.archive.list',
            icon: 'regular-envelope',
            name: 'frosh-mail-archive.title',
            privilege: 'frosh_mail_archive:read',
        },
    ],
});
