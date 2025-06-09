Ext.define('Rd.view.softEtherWireguard.pnlSoftEtherWireguard', {
    extend: 'Ext.tab.Panel',
    alias: 'widget.pnlSoftEtherWireguard',
    border: false,
    plain: true,
    cls: 'subTab',
    requires: [
        'Rd.view.softEtherWireguard.pnlSoftEtherWireguardEdit',
        'Rd.view.softEtherWireguard.gridSoftEtherWireguardPublicKeys'
    ],
    initComponent: function () {
        var me = this;
        me.items = [
            {
                title: i18n('sWireguard_setting'),
                itemId: 'tabSoftEtherWireguardSetting',
                layout: 'fit',
                xtype: 'pnlSoftEtherWireguardEdit',
            },
            {
                title: i18n('sClient_Public_key'),
                itemId: 'tabSoftEtherWireguardPublicKeys',
                layout: 'fit',
                xtype: 'gridSoftEtherWireguardPublicKeys',
            },

        ];
        this.callParent(arguments);
    },


});
