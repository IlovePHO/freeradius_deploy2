Ext.define('Rd.view.subGroups.gridSubGroups', {
    extend:'Ext.grid.Panel',
    alias       : 'widget.gridSubGroups',
    multiSelect: true,

    stateful: true,
    stateId: 'StateGridSubGroups',
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
    urlMenu: '/cake3/rd_cake/sub-groups/menu-for-grid.json',
    
    realm_id: null,
    idp_id: null,
    selRecord: false,
    autoReload: null,


    initComponent: function(){
        var me      = this;
        //console.log(me.realm_id,me.idp_id);

        me.tbar     = Ext.create('Rd.view.components.ajaxToolbar',{'url': me.urlMenu});
        
        //menu in the cell
        me.menu_grid = new Ext.menu.Menu({
            items: [
                { text: i18n('sSynchronize'), glyph: Rd.config.icnRedirect,
                  handler: function(){
                     me.fireEvent('menuItemClick',me,'synchronize'); 
                    }
                }
                
            ]
        });
        me.columns = [{
                text: i18n('sName'),
                dataIndex: 'name',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSG1'
            },
            {
                text: i18n('sPath'),
                dataIndex: 'path',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSG2'
            },
            {
                text: i18n('sProfile'),
                dataIndex: 'profile',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridSGP3'
            },
            {
                text: i18n('sDescription'),
                dataIndex: 'description',
                flex: 2,
                filter: {
                    type: 'string'
                },
                hidden: true,
                stateId: 'StateGridSGP4'
            },
            {
                xtype       : 'actioncolumn',
                text        : 'Actions',
                width       : 80,
                stateId     : 'StateGridSGP9',
                sortable: false,
                items       : [
                    {
                        iconCls: 'txtRed x-fa fa-trash',
                        tooltip: 'Delete',
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('delete') == true) {
                            if(grid.up('gridSubGroups').down('#delete')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                        handler: function (view, rowIndex, colIndex, item, e, record, row) {
                            this.fireEvent('itemClick', view, rowIndex, colIndex, item, e, record, row, 'delete');
                        }
                    },
                    {
                        iconCls: 'txtBlue x-fa fa-pen',
                        tooltip: 'Edit',
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('update') == true) {
                            if(grid.up('gridSubGroups').down('#edit')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                        handler: function (view, rowIndex, colIndex, item, e, record, row) {
                            this.fireEvent('itemClick', view, rowIndex, colIndex, item, e, record, row, 'update');
                        }
                    },
                    {
                        iconCls: 'txtGreen x-fa fa-bars',
                        tooltip: 'More Actions',
                        handler: function (view, rowIndex, colIndex, item, e, record) {
                            var position = e.getXY();
                            e.stopEvent();
                            me.selRecord = record;
                            me.view = view;
                            me.menu_grid.showAt(position);
                        },
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            if( grid.up('gridSubGroups').down('#synchronize')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                    }
                ]
            }
        ];
        
        me.store   = Ext.create('Rd.store.sSubGroups');
        extraParams={};
        if( me.idp_id ){
            extraParams.idp_id =  me.idp_id;
        }else if( me.realm_id ){
            extraParams.realm_id =  me.realm_id;
        }
        if( !Ext.Object.isEmpty(extraParams) ){
           me.store.getProxy().setExtraParams(extraParams);
        }
        me.store.load();
    
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
