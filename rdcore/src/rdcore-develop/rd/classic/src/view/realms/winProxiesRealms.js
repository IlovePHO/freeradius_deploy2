Ext.define('Rd.view.realms.winProxiesRealms', {
    extend  : 'Ext.window.Window',
    alias   : 'widget.winProxiesRealms',
    title   : i18n('sProxy_operation'),
    layout  : 'fit',
    autoShow: false,
    width   : 450,
    height  : 400,
    iconCls: 'edit',
    glyph: Rd.config.icnEdit,
    requires: [
        'Rd.view.components.btnCommon',
        'Ext.form.field.Text',
    ],
    realm_id: null,
    realm_name:'',
    proxy_flg: false,
    initComponent: function() {
        var me = this;
        var winTitle = i18n('sProxy_operation');
        if( me.realm_name ){
            winTitle += ': '+me.realm_name;
        }
        me.setTitle(winTitle);
        
        this.items = [
            {
                xtype       : 'form',
                border      : false,
                layout      : 'anchor',
                autoScroll  : true,
                defaults    : {
                    anchor: '100%'
                },
                fieldDefaults: {
                    msgTarget   : 'under',
                    labelClsExtra: 'lblRd',
                    labelAlign  : 'left',
                    labelSeparator: '',
                    labelWidth  : Rd.config.labelWidth,
                    maxWidth    : Rd.config.maxWidth, 
                    margin      : Rd.config.fieldMargin   
                },
                defaultType: 'textfield',
                items: [
                    {
                        itemId  : 'realm_id',
                        xtype   : 'textfield',
                        name    : 'realm_id',
                        hidden  : true,
                        value   : me.realm_id
                    },
                    {
                        itemId  : 'action_rd',
                        xtype: 'radiogroup',
                        columns: 2,
                        vertical: false,
                        required: true,
                        items: [
                            { boxLabel: i18n('sEnable_proxy'), name:'rb', inputValue: 'add', checked: true },
                            { boxLabel: i18n('sDisable_proxy'), name:'rb', inputValue: 'remove' },
                        ]
                    },
                    {
                        itemId  : 'proxy_id',
                        name  : 'proxy_id',
                        xtype   : 'cmbProxies',
                        fieldLabel: i18n('sProxy'),
                    },
                ],
                buttons: [{xtype: 'btnCommon'}]
            }
        ];
        this.callParent(arguments);
    }
});
