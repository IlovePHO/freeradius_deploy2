Ext.define('Rd.view.homeServerPools.gridHomeServers', {
    extend:'Ext.grid.Panel',
    alias       : 'widget.gridHomeServers',
    multiSelect: true,

    stateful: true,
    stateId: 'StateGridHomeServers',
    stateEvents:['groupclick','columnhide'],
    border: false,
    requires: [
        'Rd.view.components.ajaxToolbar',
        'Ext.toolbar.Paging',
        'Ext.ux.ProgressBarPager'
    ],
    viewConfig: {
        loadMask:true
    },
    urlMenu: '/cake3/rd_cake/home-servers/menu-for-grid.json',
    
    hsp_id      : null,
    hsp_name    : null,
    autoReload  : null,


    initComponent: function(){
        var me      = this;

        me.tbar     = Ext.create('Rd.view.components.ajaxToolbar',{'url': me.urlMenu});
        
        
        me.columns = [{
                text: i18n('sName'),
                dataIndex: 'name',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHS1'
            },
            {
                text: i18n('sSecret'),
                dataIndex: 'secret',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHS2'
            },
            {
                text: i18n('sType'),
                dataIndex: 'type',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHSP3'
            },
            {
                text: i18n('sProtocol'),
                dataIndex: 'proto',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHSP4'
            },
            {
                text: i18n('sIP_Address'),
                dataIndex: 'ipaddr',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHSP5'
            },
            {
                text: i18n('sPort_No'),
                dataIndex: 'port',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHSP6'
            },
            {
                text: i18n('sStatus_check'),
                dataIndex: 'status_check',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHSP7'
            },
            {
                text: i18n('sPriority'),
                dataIndex: 'priority',
                flex: 1,
                filter: {
                    type: 'number'
                },
                stateId: 'StateGridHSP8'
            },
            {
                text: i18n('sDescription'),
                dataIndex: 'description',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHSP9'
            },
        ];
        
        me.store = Ext.create(Ext.data.Store,{
            extend  : 'Ext.data.Store',
            model   : 'Rd.model.mHomeServer',
            pageSize: 100,
            remoteSort: true,
            remoteFilter: true,
            proxy: {
                type    : 'ajax',
                format  : 'json',
                batchActions: true, 
                url     : '/cake3/rd_cake/home-servers/index.json',
                extraParams : { 'home_server_pool_id': me.hsp_id },
                reader: {
                    type            : 'json',
                    rootProperty    : 'items',
                    messageProperty : 'message',
                    totalProperty   : 'totalCount' //Required for dynamic paging
                },
                simpleSortMode      : true //This will only sort on one column (sort) and a direction(dir) value ASC or DESC
            },
            autoLoad: true
        });

        me.bbar = [{
            xtype       : 'pagingtoolbar',
            store       : me.store,
            displayInfo : true,
            plugins     : {
                'ux-progressbarpager': true
            }
        }];
        
        this.callParent(arguments);
    },
    reload: function(){
        var me      = this;

        me.getSelectionModel().deselectAll(true);
        me.getStore().load();
    },
});
