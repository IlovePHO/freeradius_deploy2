Ext.define('Rd.view.softEtherVirtualHubs.gridSoftEtherVirtualHubs', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridSoftEtherVirtualHubs',
    multiSelect: true,
    store: 'sSoftEtherVirtualHubs',
    stateful: true,
    stateId: 'StateGridSoftEtherVirtualHubs',
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
    urlMenu: '/cake3/rd_cake/soft-ether-virtual-hubs/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StategridSoftEtherVirtualHubs0'},
            {
                text: i18n('sName'),
                dataIndex: 'hub_name',
                tdCls: 'gridMain',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSoftEtherVirtualHubs1'
            },

            {
                text: i18n('sPassword'),
                dataIndex: 'password',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSoftEtherVirtualHubs2'
            },
            {
                text: i18n('sDefault_gateway'),
                dataIndex: 'default_gateway',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSoftEtherVirtualHubs3'
            },
            {
                text: i18n('sDefault_subnet'),
                dataIndex: 'default_subnet',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridSoftEtherVirtualHubs4'
            },
            {
                text:   i18n('sOnline'),
                flex: 1,  
                xtype:  'templatecolumn', 
                tpl:    new Ext.XTemplate(
                            "<tpl if='online == true'><div class=\"fieldGreen\">"+i18n("sYes")+"</div></tpl>",
                            "<tpl if='online == false'><div class=\"fieldRed\">"+i18n("sNo")+"</div></tpl>"
                        ),
                dataIndex: 'online',
                filter      : {
                    type    : 'boolean',
                    defaultValue   : false,
                    yesText : 'Yes',
                    noText  : 'No'
                },stateId: 'StategridSoftEtherVirtualHubs5'
            },
            {
                xtype: 'actioncolumn',
                text: 'Actions',
                width: 80,
                stateId: 'StategridSoftEtherVirtualHubs6',
                items: [
                    {
                        iconCls: 'txtRed x-fa fa-trash',
                        tooltip: i18n('sDelete'),
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('delete') == true) {
                            if(grid.up('gridSoftEtherVirtualHubs').down('#delete')){
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
                        tooltip: i18n('sEdit'),
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('update') == true) {
                            if(grid.up('gridSoftEtherVirtualHubs').down('#edit')){
                                return false;
                            } else {
                                return true;
                            }
                        },
                        handler: function (view, rowIndex, colIndex, item, e, record, row) {
                            this.fireEvent('itemClick', view, rowIndex, colIndex, item, e, record, row, 'update');
                        }
                    },

                ]
            }
        ];
        me.callParent(arguments);
    }
});
