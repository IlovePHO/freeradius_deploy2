Ext.define('Rd.view.attributeConverts.gridAttributeConverts' ,{
    extend      : 'Ext.grid.Panel',
    alias       : 'widget.gridAttributeConverts',
    multiSelect : true,
    store       : 'sAttributeConverts',
    stateful    : true,
    stateId     : 'StateGridDc1',
    stateEvents : ['groupclick','columnhide'],
    border      : false,
    requires    : [
        'Rd.view.components.ajaxToolbar',
        'Ext.toolbar.Paging',
        'Ext.ux.ProgressBarPager'
    ],
    viewConfig: {
        loadMask:true
    },
    urlMenu: '/cake3/rd_cake/attribute-converts/menu-for-grid.json',
    plugins     : 'gridfilters',  //*We specify this
    initComponent: function(){
        var me      = this;
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
			{ text: i18n('sSource_attribute'),         dataIndex: 'src',  tdCls: 'gridSrc', flex: 1,filter: {type: 'string'},stateId: 'StateGridAttributeConvert2'},
			{ text: i18n('sDestination_attribute'),         dataIndex: 'dst',  tdCls: 'gridDst', flex: 1,filter: {type: 'string'},stateId: 'StateGridAttributeConvert3'},
			{ text: i18n('sNAS_Type'),         dataIndex: 'nas_type',  tdCls: 'gridNasType', flex: 1,filter: {type: 'string'},stateId: 'StateGridAttributeConvert4'},
        ];     
        me.callParent(arguments);
    }
});
