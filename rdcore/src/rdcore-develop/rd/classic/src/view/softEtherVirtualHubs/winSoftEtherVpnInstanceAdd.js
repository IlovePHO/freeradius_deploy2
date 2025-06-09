Ext.define('Rd.view.softEtherVirtualHubs.winSoftEtherVirtualHubAdd', {
    extend: 'Ext.window.Window',
    alias: 'widget.winSoftEtherVirtualHubAdd',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_Virtual_Hub'),
    width: 500,
    height: 470,
    plain: true,
    border: false,
    layout: 'card',
    iconCls: 'add',
    glyph: Rd.config.icnAdd,
    autoShow: false,
    defaults: {
        border: false
    },
    no_tree: false, //If the user has no children we don't bother giving them a branchless tree
    requires: [
        'Ext.layout.container.Card',
        'Ext.form.Panel',
        'Ext.form.field.Text',
        'Ext.form.FieldContainer',
    ],
    initComponent: function () {
        var me = this;
        // title Reset in current language
        me.setTitle(i18n('sAdd_Virtual_Hub'));

        var scrnData = me.mkScrnData();
        me.items = [
            scrnData
        ];
        this.callParent(arguments);
    },

    //_______ Data form _______
    mkScrnData: function () {

        var me = this;
        var buttons = [{
            itemId: 'btnDataNext',
            text: i18n('sNext'),
            scale: 'large',
            iconCls: 'b-next',
            glyph: Rd.config.icnNext,
            formBind: true,
            margin: '0 20 40 0'
        }];


        var frmData = Ext.create('Ext.form.Panel', {
            border: false,
            layout: 'anchor',
            itemId: 'scrnData',
            autoScroll: true,
            defaults: {
                anchor: '100%'
            },
            fieldDefaults: {
                msgTarget: 'under',
                labelClsExtra: 'lblRd',
                labelAlign: 'left',
                labelSeparator: '',
                labelWidth: 170,
                margin: 15
            },
            defaultType: 'textfield',
            items: [
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sName'),
                    name: "hub_name",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sPassword'),
                    name: "password",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype       : 'checkbox',
                    fieldLabel    : i18n('sOnline'),
                    name        : 'online',
                    itemId      : 'online',
                    checked     : false,
                    inputValue  : true,
                    uncheckedValue: false,
                    labelClsExtra: 'lblRdReq'
                },


            ],
            buttons: buttons
        });
        return frmData;
    }
});
