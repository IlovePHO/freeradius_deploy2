Ext.define('Rd.view.subGroups.pnlSubGroupsEdit', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlSubGroupsEdit',
    autoScroll: true,
    plain: true,
    itemId: 'pnlSubGroupsEdit',
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },

    sg_id: null,

    fieldDefaults: {
        msgTarget: 'under',
        labelClsExtra: 'lblRd',
        labelAlign: 'left',
        labelSeparator: '',
        labelClsExtra: 'lblRd',
        labelWidth: Rd.config.labelWidth,
        margin: Rd.config.fieldMargin
    },
    defaultType: 'textfield',

    config: {
        urlView: '/cake3/rd_cake/sub-groups/view.json'
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
            params: {
                sub_groups_id: me.sg_id
            },
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

        me.items = [
            {
                itemId: 'id',
                xtype: 'textfield',
                name: "id",
                hidden: true
            },
            {
                xtype: 'fieldcontainer',
                itemId: 'fcPickOwner',
                layout: {
                    type: 'hbox',
                    align: 'begin',
                    pack: 'start'
                },
                items: [{
                        itemId: 'owner',
                        xtype: 'displayfield',
                        fieldLabel: i18n('sOwner'),
                        name: 'username',
                        itemId: 'displUser',
                        margin: 0,
                        padding: 0,
                        width: 360,
                    },
                    {
                        xtype: 'button',
                        text: 'Pick Owner',
                        margin: 5,
                        padding: 5,
                        ui: 'button-green',
                        itemId: 'btnPickOwner',
                        width: 100,
                        listeners: {
                            click: me.onBtnPickOwnerClick
                        }
                    },
                    {
                        xtype: 'textfield',
                        name: "user_id",
                        itemId: 'hiddenUser',
                        hidden: true,
                    }
                ]
            },
            {
                xtype: 'displayfield',
                fieldLabel: i18n('sName'),
                name: 'name',        
                labelClsExtra: 'lblRdReq'
            },
            {
                xtype: 'displayfield',
                fieldLabel: i18n('sPath'),
                name: 'path',
            },

            {
                fieldLabel: i18n('sProfile'),
                xtype:'combobox',
                store: 'sProfiles',
                valueField      : 'id',
                displayField    : 'name',
                typeAhead       : true,
                name: 'profile_id',
                allowBlank: true,
                blankText: i18n('sSupply_a_value'),
                labelClsExtra: 'lblRdReq',
                getSubmitValue:function(){
                    //Do not send when this field is empty 
                    var value = this.getValue();
                    if(Ext.isEmpty(value)) {
                        return null;
                    }
                    return value;
                }
            },
            {
                xtype       	: 'textfield',
                fieldLabel  	: i18n('sDescription'),
                name        	: 'description',
                allowBlank  	: true,
                labelClsExtra	: 'lblRdReq'
            },
        ];

        me.callParent(arguments);
    },

    onBtnPickOwnerClick: function (button) {
        var me = this;
        var pnl = button.up('panel');
        var updateDisplay = pnl.down('#displUser');
        var updateValue = pnl.down('#hiddenUser');

        console.log("Clicked Change Owner");
        if (!Ext.WindowManager.get('winSelectOwnerId')) {
            var w = Ext.widget('winSelectOwner', {
                id: 'winSelectOwnerId',
                updateDisplay: updateDisplay,
                updateValue: updateValue
            });
            w.show();
        }
    },
});
