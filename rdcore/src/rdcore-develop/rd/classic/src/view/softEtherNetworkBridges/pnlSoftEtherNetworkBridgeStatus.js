Ext.define('Rd.view.softEtherNetworkBridges.pnlSoftEtherNetworkBridgeStatus', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSoftEtherNetworkBridgeStatus',
    itemId: 'pnlSoftEtherNetworkBridgeStatus',

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
                        xtype: 'hiddenfield',
                        itemId: 'id',
                        name: "id",
                    },
                    {
                        xtype: 'radiogroup',
                        //  fieldLabel  : i18n('sStatus'),
                        //   labelWidth  : 50,
                        columns: 2,
                        vertical: false,
                        items: [{
                                boxLabel: '<span class="lblRdReq">'+i18n('sRunning')+'<span>',
                                name: 'status',
                                inputValue: 1,
                            },
                            {
                                boxLabel: '<span class="lblRdReq">'+i18n('sStop')+'<span>',
                                name: 'status',
                                inputValue: 0,
                                checked:true
                            },
                        ]
                    },

                ],
            },

        ];

        this.callParent(arguments);
    },


});
