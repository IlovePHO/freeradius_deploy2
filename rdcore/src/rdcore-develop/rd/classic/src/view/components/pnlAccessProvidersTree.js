Ext.define('Rd.view.components.pnlAccessProvidersTree', {
    extend      :'Ext.tree.Panel',
    alias       :'widget.pnlAccessProvidersTree',
    useArrows   : true,
    store       : 'sAccessProvidersTree',
    rootVisible : true,
    rowLines    : true,
    layout      : 'fit',
    stripeRows  : true,
    border      : false,
    requires    : [   
        'Rd.store.sAccessProvidersTree'
    ],
   /* tbar: [
        { xtype: 'tbtext', text: i18n('sSelect_the_owner'), cls: 'lblWizard' }
    ],*/
    columns: [],
    /* i18n: columns seting is Move to initComponent,*/
    buttons: [
            {
                itemId  : 'btnTreeNext',
                text    : i18n('sNext'),
                scale   : 'large',
                glyph   : Rd.config.icnNext,
                margin  : Rd.config.buttonMargin
            }
        ],
    listeners : {
        beforerender: function(pnl){
            pnl.getStore().load();
        }
    },
    initComponent: function() {
        var me = this;
        me.columns= [
            {
                xtype: 'treecolumn', //this is so we know which column will show the tree
                text: i18n('sOwner'),
                sortable: true,
                flex: 1,
                dataIndex: 'username',
                tdCls: 'gridTree'
            }
        ];
        this.callParent(arguments);
    },
});




