import './page/frosh-mail-archive-index/index';
import './page/frosh-mail-archive-detail/index';

Shopware.Module.register('frosh-mail-archive', {
    type: 'plugin',
    name: 'Mail Archive',
    title: 'Mail Archive',
    description: 'Description for your custom module',
    color: '#62ff80',
    icon: 'default-object-lab-flask',

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

    navigation: [{
        label: 'Mail Archive',
        color: '#62ff80',
        path: 'frosh.mail.archive.list',
        icon: 'default-object-lab-flask'
    }]
});
