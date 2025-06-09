Ext.define('Rd.view.softEtherL2tp.pnlSoftEtherL2tp', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSoftEtherL2tp',
    autoScroll: true,
    plain: true,
    itemId: 'pnlSoftEtherL2tp',
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    record: null, //We will supply each instance with a reference to the selected record.
    defaults: {
        border: false
    },
    fieldDefaults: {
        msgTarget: 'under',
        labelAlign: 'left',
        labelSeparator: '',
        labelWidth: Rd.config.labelWidth+20,
        margin: Rd.config.fieldMargin,
        labelClsExtra: 'lblRdReq'
    },
    requires: [
        'Ext.form.field.Text',
    ],
    config: {
        urlView: '/cake3/rd_cake/soft-ether-l2tp/view.json',
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
                        fieldLabel: i18n('sL2TP_IPsec_Enabled'),
                        name: 'l2tp_ipsec_enabled',
                        checked: false,
                        inputValue: true,
                        uncheckedValue: false,
                        labelClsExtra: 'lblRdReq',
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sIpsec_pre-shared_key'),
                        name: "ipsec_secret",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq',
                    },
                    {
                        xtype: 'cmbL2tpVirtualHub',
                        fieldLabel: i18n('sDefault_Virtual_Hub'),
                        name: 'l2tp_defaulthub',
                        allowBlank: false,
                        blankText: i18n('sSupply_a_value'),
                        labelClsExtra: 'lblRdReq',
                        valueField: 'hub_name'
                    },

                ],
            },

        ];

        this.callParent(arguments);
    },


});
