Shopware.Service('privileges').addPrivilegeMappingEntry({
    category: 'permissions',
    parent: 'settings',
    key: 'frosh_mail_archive',
    roles: {
        viewer: {
            privileges: [
                'frosh_mail_archive:read',
                'frosh_mail_archive_attachment:read',
            ],
            dependencies: [],
        },
        editor: {
            privileges: ['frosh_mail_archive:resend'],
            dependencies: ['frosh_mail_archive.viewer'],
        },
        deleter: {
            privileges: [
                'frosh_mail_archive:delete',
                'frosh_mail_archive_attachment:delete',
            ],
            dependencies: ['frosh_mail_archive.viewer'],
        },
    },
});
