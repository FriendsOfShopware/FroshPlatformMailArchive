import './page/frosh-mail-archive-index/index';
import './page/frosh-mail-archive-detail/index';

Shopware.Module.register('frosh-mail-archive', {
    type: 'plugin',
    name: 'frosh-mail-archive.title',
    title: 'frosh-mail-archive.title',
    description: '',
    color: '#62ff80',
    icon: 'default-communication-envelope',

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
            }
        }
    },

    settingsItem: [
        {
            group: 'plugins',
            to: 'frosh.mail.archive.list',
            icon: 'default-communication-envelope',
            name: 'frosh-mail-archive.title'
        }
    ]
});
