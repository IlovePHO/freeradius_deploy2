Ext.define('Rd.view.softEtherLocalBridges.gridSoftEtherLocalBridges', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridSoftEtherLocalBridges',
    multiSelect: true,
    store: 'sSoftEtherLocalBridges',
    stateful: true,
    stateId: 'gridSoftEtherLocalBridges',
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
    urlMenu: '/cake3/rd_cake/soft-ether-local-bridges/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StateGridSoftEtherLocalBridges0'},
            
            {
                text: i18n('sVirtual_Hub'),
                dataIndex: 'hub_name',
                tdCls: 'gridMain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSoftEtherLocalBridges1'
            },
            {
                text: i18n('sDevice'),
                dataIndex: 'device_name',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSoftEtherLocalBridges2'
            },
            { 
                text:   i18n('sTap_mode'),
                dataIndex: 'tap_mode',
                flex: 1,  
                xtype:  'templatecolumn', 
                tpl:    new Ext.XTemplate(
                            "<tpl if='tap_mode == true'><div class=\"fieldGreen\">"+i18n('sTrue')+"</div></tpl>",
                            "<tpl if='tap_mode == false'><div class=\"fieldRed\">"+i18n('sFalse')+"</div></tpl>"
                        ),
                    filter      : {
                        type    : 'boolean',
                        defaultValue   : false,
                        yesText : 'True',
                        noText  : 'False'
                },stateId: 'StateGridSoftEtherLocalBridges3'
            },
        ];
        me.callParent(arguments);
    }
});
