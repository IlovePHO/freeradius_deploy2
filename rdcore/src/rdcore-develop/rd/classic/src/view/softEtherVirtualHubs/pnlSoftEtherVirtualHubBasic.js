Ext.define('Rd.view.softEtherVirtualHubs.pnlSoftEtherVirtualHubBasic', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSoftEtherVirtualHubBasic',
    itemId: 'pnlSoftEtherVirtualHubBasic',
    
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
                items: [
                    {
                        xtype: 'textfield',
                        itemId: 'id',
                        name: "id",
                        hidden: true
                    },
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
                        xtype: 'textfield',
                        fieldLabel: i18n('sDefault_gateway'),
                        name: "default_gateway",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        vtype: 'IPAddress',
                        labelClsExtra: 'lblRdReq'
                    },
                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sDefault_subnet'),
                        name: "default_subnet",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq'
                    },
                    {
                        xtype       : 'checkbox',
                        fieldLabel    : i18n('sOnline'),
                        name        : 'online',
                        itemId      : 'online',
                        inputValue  : true,
                        uncheckedValue: false,
                        labelClsExtra: 'lblRdReq'
                    },

                ],
            },

        ];

        this.callParent(arguments);
    },


});
