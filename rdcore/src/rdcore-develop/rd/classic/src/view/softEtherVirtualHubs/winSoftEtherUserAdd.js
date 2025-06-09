Ext.define('Rd.view.softEtherVirtualHubs.winSoftEtherUserAdd', {
    extend: 'Ext.window.Window',
    alias: 'widget.winSoftEtherUserAdd',
    closable: true,
    draggable: true,
    resizable: true,
    title: i18n('sAdd_VPN_user'),
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
    vHub_id: null,
    requires: [
        'Ext.layout.container.Card',
        'Ext.form.Panel',
        'Ext.form.field.Text',
        'Ext.form.FieldContainer',
    ],
    initComponent: function () {
        var me = this;
        // title Reset in current language
        me.setTitle(i18n('sAdd_VPN_user'));

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
                    itemId: 'hub_id',
                    name: "hub_id",
                    value: me.vHub_id,
                    hidden: true
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sName'),
                    name: "user_name",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sReal_Name'),
                    name: "real_name",
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textfield',
                    fieldLabel: i18n('sPassword'),
                    name: "auth_password",
                    allowBlank: false,
                    blankText: i18n('sEnter_a_value'),
                    labelClsExtra: 'lblRdReq'
                },
                {
                    xtype: 'textareafield',
                    fieldLabel: i18n('sNote'),
                    name: "note",
                    grow: true,
                    labelClsExtra: 'lblRdReq'
                },

            ],
            buttons: buttons
        });
        return frmData;
    }
});
