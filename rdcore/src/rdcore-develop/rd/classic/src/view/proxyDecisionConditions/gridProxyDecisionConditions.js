Ext.define('Rd.view.proxyDecisionConditions.gridProxyDecisionConditions', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridProxyDecisionConditions',
    multiSelect: true,
    store: 'sProxyDecisionConditions',
    stateful: true,
    stateId: 'StateGridProxyDecisionConditions',
    stateEvents: ['groupclick', 'columnhide'],
    border: false,
    requires: [
        'Rd.view.components.ajaxToolbar',
        'Ext.toolbar.Paging',
        'Ext.ux.ProgressBarPager'
    ],
    viewConfig: {
        loadMask: true
    },
    urlMenu: '/cake3/rd_cake/proxy-decision-conditions/menu-for-grid.json',
    plugins: 'gridfilters', //*We specify this
    initComponent: function () {
        var me = this;
        me.bbar = [{
            xtype: 'pagingtoolbar',
            store: me.store,
            displayInfo: true,
            plugins: {
                'ux-progressbarpager': true
            }
        }];

        me.tbar = Ext.create('Rd.view.components.ajaxToolbar', {
            'url': me.urlMenu
        });
        
        me.columns = [
            //   {xtype: 'rownumberer',stateId: 'StateGridProxyDC0'},
            {
                text: i18n('sSSID_Regex'),
                dataIndex: 'ssid',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridProxyDC1'
            },

            {
                text: i18n('sUser_name_Regex'),
                dataIndex: 'user_name_regex',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridProxyDC2'
            },
            {
                text: i18n('sForwarding_proxy'),
                dataIndex: 'proxy_name',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridProxyDC3'
            },
            {
                text: i18n('sPriority'),
                dataIndex: 'priority',
                flex: 1,
                filter: {
                    type: 'number'
                },
                stateId: 'StateGridProxyDC4'
            },
        ];
        me.callParent(arguments);
    }
});
