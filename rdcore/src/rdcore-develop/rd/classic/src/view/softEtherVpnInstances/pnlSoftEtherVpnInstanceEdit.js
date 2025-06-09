Ext.define('Rd.view.softEtherVpnInstances.pnlSoftEtherVpnInstanceEdit', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSoftEtherVpnInstanceEdit',
    autoScroll: true,
    plain: true,
    itemId: 'pnlSoftEtherVpnInstanceEdit',
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
        labelWidth: Rd.config.labelWidth,
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
                items: [
                    {
                        xtype: 'textfield',
                        itemId: 'id',
                        name: "id",
                        hidden: true
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sIP_Address'),
                        name: "ip_address",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        vtype: 'IPAddress',
                        labelClsExtra: 'lblRdReq',
                    },
                    /*{
                        xtype: 'textfield',
                        fieldLabel: i18n('sAdmin_name'),
                        name: "admin_name",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq'
                    },*/
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sPassword'),
                        name: "password",
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
