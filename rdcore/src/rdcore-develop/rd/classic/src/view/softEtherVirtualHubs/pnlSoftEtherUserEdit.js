Ext.define('Rd.view.softEtherVirtualHubs.pnlSoftEtherUserEdit', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSoftEtherUserEdit',
    autoScroll: true,
    plain: true,
    frame: false,
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    margin: 5,
    vHub_id: null,
    grid_id: null, //users grid
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
                    /*{
                        xtype: 'textfield',
                        itemId: 'hub_id',
                        name: "hub_id",
                        hidden: true
                    },*/
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
            },

        ];

        this.callParent(arguments);
    },


});
