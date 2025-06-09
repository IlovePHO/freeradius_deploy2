Ext.define('Rd.view.staConfigs.gridStaConfigs', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.gridStaConfigs',
    multiSelect: true,
    store: 'sStaConfigs',
    stateful: true,
    stateId: 'StateGridStaConfigs',
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
    urlMenu: '/cake3/rd_cake/sta-configs/menu-for-grid.json',
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
            //   {xtype: 'rownumberer',stateId: 'StategridStaConfigs0'},
            {
                text: i18n('sName'),
                dataIndex: 'name',
                tdCls: 'gridMain',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaConfigs1'
            },
            {
                text: i18n('sAvailable_to_sub_providers'),
                flex: 1,
                xtype: 'templatecolumn',
                tpl: new Ext.XTemplate(
                    "<tpl if='available_to_siblings == true'><div class=\"fieldGreen\">" + i18n("sYes") + "</div></tpl>",
                    "<tpl if='available_to_siblings == false'><div class=\"fieldRed\">" + i18n("sNo") + "</div></tpl>"
                ),
                dataIndex: 'available_to_siblings',
                filter: {
                    type: 'boolean',
                    defaultValue: false,
                    yesText: 'Yes',
                    noText: 'No'
                },
                stateId: 'StategridStaConfigs2'
            },
            {
                text: i18n('sSSID'),
                dataIndex: 'ssid',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaConfigs3'
            },
            {
                text: i18n('sHome_domain'),
                dataIndex: 'home_domain',
                hidden: true,
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaConfigs9'
            },
            {
                text: i18n('sFriendly_name'),
                dataIndex: 'friendly_name',
                hidden: true,
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaConfigs10'
            },
            {
                text: i18n('sRCOI'),
                dataIndex: 'rcoi',
                hidden: true,
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaConfigs11'
            },
            {
                text: i18n('sAuthentication_method'),
                dataIndex: 'eap_method',
                tdCls: 'gridTree',
                flex: 1,
                filter: {
                    type: 'string'
                },
                stateId: 'StategridStaConfigs4'
            },
            {
                text: i18n('sExpire'),
                dataIndex: 'expire',
                tdCls: 'gridTree',
                flex: 1,
                xtype: 'datecolumn',
                format: 'Y/m/d',
                filter: {
                    type: 'date'
                },
                stateId: 'StategridStaConfigs5'
            },
            {
                text: i18n('sRealm_Bulk'),
                sortable: false,
                flex: 1,
                xtype: 'templatecolumn',
                tpl: new Ext.XTemplate(
                    '<tpl for="realms">',
                    "<div>{name}</div>",
                    '</tpl>'
                ),
                dataIndex: 'realms',
                filter: {
                    type: 'list',
                    store: 'sRealms'
                },
                stateId: 'StategridStaConfigs6'
            },
            {
                text: i18n('sOrganization'),
                sortable: false,
                flex: 1,
                xtype: 'templatecolumn',
                tpl: new Ext.XTemplate(
                    '<tpl for="sub_groups">',
                    "<div>{name}</div>",
                    '</tpl>'
                ),
                dataIndex: 'sub_groups',
                filter: {
                    type: 'list',
                    store: 'sRealms'
                },
                stateId: 'StategridStaConfigs7'
            },

            {
                xtype: 'actioncolumn',
                text: 'Actions',
                width: 80,
                stateId: 'StategridStaConfigs8',
                items: [{
                        iconCls: 'txtRed x-fa fa-trash',
                        tooltip: 'Delete',
                        isDisabled: function (grid, rowIndex, colIndex, items, record) {
                            //if (record.get('delete') == true) {
                            if (grid.up('gridStaConfigs').down('#delete')) {
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
                            if (grid.up('gridStaConfigs').down('#edit')) {
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
