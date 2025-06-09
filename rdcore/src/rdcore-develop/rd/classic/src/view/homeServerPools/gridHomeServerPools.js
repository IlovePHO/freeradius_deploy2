Ext.define('Rd.view.homeServerPools.gridHomeServerPools' ,{
    extend:'Ext.grid.Panel',
    alias : 'widget.gridHomeServerPools',
    multiSelect: true,
    store : 'sHomeServerPools',
    stateful: true,
    stateId: 'StateGridHomeServerPools',
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
    urlMenu: '/cake3/rd_cake/home-server-pools/menu-for-grid.json',

    initComponent: function(){
        var me  = this;
        me.bbar = [{
            xtype       : 'pagingtoolbar',
            store       : me.store,
            displayInfo : true,
            plugins     : {
                'ux-progressbarpager': true
            }
        }];
        me.tbar     = Ext.create('Rd.view.components.ajaxToolbar',{'url': me.urlMenu});

        me.columns  = [
            { text: i18n('sName'),         dataIndex: 'name',   tdCls: 'gridMain', flex: 1,filter: {type: 'string'},stateId: 'StateGridHSP1'},
            { 
                text:   i18n('sAvailable_to_sub_providers'),
                flex: 1,  
                xtype:  'templatecolumn', 
                tpl:    new Ext.XTemplate(
                            "<tpl if='available_to_siblings == true'><div class=\"fieldGreen\">"+i18n("sYes")+"</div></tpl>",
                            "<tpl if='available_to_siblings == false'><div class=\"fieldRed\">"+i18n("sNo")+"</div></tpl>"
                        ),
                dataIndex: 'available_to_siblings',
                filter      : {
                    type    : 'boolean',
                    defaultValue   : false,
                    yesText : 'Yes',
                    noText  : 'No'
                },stateId: 'StateGridHSP2'
            },
            { text: i18n('sType'),         dataIndex: 'type',   flex: 1,filter: {type: 'string'},stateId: 'StateGridHSP3'},
            {
                text: i18n('sDescription'),
                dataIndex: 'description',
                flex: 2,
                filter: {
                    type: 'string'
                },
                stateId: 'StateGridHSP4'
            },
        ];        
        me.callParent(arguments);
    }
});
