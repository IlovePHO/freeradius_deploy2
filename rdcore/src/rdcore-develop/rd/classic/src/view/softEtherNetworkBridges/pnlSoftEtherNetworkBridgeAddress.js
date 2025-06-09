Ext.define('Rd.view.softEtherNetworkBridges.pnlSoftEtherNetworkBridgeAddress', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSoftEtherNetworkBridgeAddress',
    itemId: 'pnlSoftEtherNetworkBridgeAddress',

    autoScroll: true,
    plain: true,
    frame: false,
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    margin: 5,
    record: null, //We will supply each instance with a reference to the selected record.

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

    listeners: {
        show: 'loadSettings', //Trigger a load of the settings
        afterrender: 'loadSettings',

    },
    loadSettings: function (panel) {
        var me = this;
        if (me.record) {
            panel.loadRecord(me.record);
        }
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
                items: [{
                        xtype: 'hiddenfield',
                        itemId: 'id',
                        name: "id",
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sIP_Address'),
                        name: "ip_address",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        vtype: 'IPAddress',
                        labelClsExtra: 'lblRdReq'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sSubnet_mask'),
                        name: "subnet_mask",
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
