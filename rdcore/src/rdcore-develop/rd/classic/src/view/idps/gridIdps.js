Ext.define('Rd.view.idps.gridIdps', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridIdps',
    multiSelect: true,
    store: 'sIdps',
    stateful: true,
    stateId: 'StategridIdps',
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
    urlMenu: '/cake3/rd_cake/idps/menu-for-grid.json',
    plugins: 'gridfilters', //*We specify this
    initComponent: function () {
        var me = this;
        var sTypes = Ext.create('Rd.store.sIdpsTypes');
        var sAuthTypes = Ext.create('Rd.store.sIdpsAuthTypes');
        
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
        //menu in the cell
        me.menu_grid = new Ext.menu.Menu({
            items: [
                { text: i18n('sSynchronize'), glyph: Rd.config.icnRedirect,
                  handler: function(){
                     me.fireEvent('menuItemClick',me,'synchronize'); 
                    }
                },
                { text: i18n('sOrganization'), glyph: Rd.config.icnFolder,
                  handler: function(){
                     me.fireEvent('menuItemClick',me,'subGroups'); 
                    }
                },
                
            ]
        });
        
        me.columns = [
            //   {xtype: 'rownumberer',stateId: 'StategridIdps0'},
            /*{
                text: i18n('sOwner'),
                dataIndex: 'owner',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridIdps1',
                hidden: true
            },*/
            {
                text: i18n('sName'),
                dataIndex: 'name',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridIdps2'
            },
            {
                text: i18n('sType'),
                dataIndex: 'type',
                flex: 1,
                filter: {
                    type: 'string'
                },
                renderer: function(value){
                    var i = sTypes.find('type',value);
                    if(i>=0){
                        value=sTypes.getAt(i).get('name');
                    }
                    return value;
                },
                stateId: 'StategridIdps3'
            },
            {
                text: i18n('sAuth_type'),
                dataIndex: 'auth_type',
                flex: 1,
                filter: {
                    type: 'string'
                },
                renderer: function(value){
                    var i = sAuthTypes.find('value',value);
                    if(i>=0){
                        value=sAuthTypes.getAt(i).get('name');
                    }
                    return value;
                },
                stateId: 'StategridIdps4'
            },
            {
                text: i18n('sRealm'),
                dataIndex: 'realm_name',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridIdps5'
            },
            {
                text: i18n('sDomain'),
                dataIndex: 'domain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridIdps6'
            },
            {
                xtype       : 'actioncolumn',
                text        : 'Actions',
                width       : 80,
                stateId     : 'StategridIdps7',
                sortable: false,
                items       : [
                    {
                        iconCls: 'txtRed x-fa fa-trash',
                        tooltip: 'Delete',
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('delete') == true) {
                            if(grid.up('gridIdps').down('#delete')){
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
                            if(grid.up('gridIdps').down('#edit')){
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
                            if(grid.up('gridIdps').down('#synchronize')||grid.up('gridIdps').down('#subGroups')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                    }
                ]
            }

        ];
        me.callParent(arguments);
    }
});
