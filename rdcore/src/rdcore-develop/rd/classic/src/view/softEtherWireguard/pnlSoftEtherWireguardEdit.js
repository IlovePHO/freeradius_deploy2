Ext.define('Rd.view.softEtherWireguard.pnlSoftEtherWireguardEdit', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSoftEtherWireguardEdit',
    itemId: 'pnlSoftEtherWireguardEdit',
    
    autoScroll: true,
    plain: true,
    frame: false,
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    margin: 5,
    fieldDefaults: {
        msgTarget: 'under',
        labelAlign: 'left',
        labelSeparator: '',
        labelWidth: 200,
        margin: Rd.config.fieldMargin,
        labelClsExtra: 'lblRdReq'
    },
    requires: [
        'Ext.form.field.Text',
    ],
    config: {
        urlView: '/cake3/rd_cake/soft-ether-wireguard/view.json',
    },
    listeners: {
        show: 'loadSettings', //Trigger a load of the settings
        afterrender: 'loadSettings',
    },
    loadSettings: function (panel) {
        var me = this;

        me.load({
            url: me.getUrlView(),
            method: 'GET',
            success: function (a, b, c) {
                //console.log(b.result.data);
            }
        });

    },
    initComponent: function () {
        var me = this;

        me.buttons = [{
            itemId: 'save',
            text: 'SAVE',
            scale: 'large',
            formBind: true,
            glyph: Rd.config.icnYes,
            margin: Rd.config.buttonMargin,
            ui: 'button-teal'
        }];
        me.items = [{
                xtype: 'panel',
                bodyPadding: 10,
                items: [
                    {
                        xtype: 'checkbox',
                        fieldLabel: i18n('sEnabled'),
                        name: 'enabled',
                        checked: false,
                        inputValue: true,
                        uncheckedValue: false,
                        labelClsExtra: 'lblRdReq',
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sPreshared_key'),
                        name: "preshared_key",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sPrivate_key'),
                        name: "private_key",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq'
                    },

                ],
            },

        ];

        this.callParent(arguments);
    },


});
