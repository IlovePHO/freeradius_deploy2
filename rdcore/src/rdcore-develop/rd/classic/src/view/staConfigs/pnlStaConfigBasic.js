Ext.define('Rd.view.staConfigs.pnlStaConfigBasic', {
    extend: 'Ext.form.Panel',
    alias: 'widget.pnlStaConfigBasic',
    autoScroll: true,
    plain: true,
    frame: false,
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    margin: 5,
    stsConf_id: null,
    requires: [
        'Rd.view.staConfigs.cmbEapMethods',
        'Rd.view.staConfigs.cmbEncodingSchemes'
    ],
    config: {
        urlView: '/cake3/rd_cake/sta-configs/view.json',
    },
    fieldDefaults: {
        msgTarget: 'under',
        labelAlign: 'left',
        labelSeparator: '',
        labelWidth: Rd.config.labelWidth + 20,
        margin: Rd.config.fieldMargin,
        labelClsExtra: 'lblRdReq'
    },
    buttons: [{
        itemId: 'save',
        text: 'SAVE',
        scale: 'large',
        formBind: true,
        glyph: Rd.config.icnYes,
        margin: Rd.config.buttonMargin,
        ui: 'button-teal'
    }],
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
                sta_config_id: me.stsConf_id
            },
            success: function (a, b, c) {
                // console.log(b.result.data);
                var data = b.result.data;
                
                if(data.expire){
                    var expire = me.down("#expire");
                    var d = Ext.Date.parse(data.expire,'Y-m-d H:i:s');
                    expire.setValue(d);
                }
                
            }
        });

    },
    initComponent: function () {
        var me = this;
        var w_prim = 550;

        var dtTo = new Date();
        dtTo.setYear(dtTo.getFullYear() + 1);


        me.items = [{
                xtype: 'textfield',
                itemId: 'id',
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
                        itemId: 'user_id',
                        hidden: true
                    }
                ]
            },

            {
                xtype: 'textfield',
                fieldLabel: i18n('sName'),
                name: "name",
                allowBlank: false,
                blankText: i18n("sEnter_a_value"),
                labelClsExtra: 'lblRdReq'
            },
            {
                xtype: 'textfield',
                fieldLabel: i18n('sSSID'),
                name: "ssid",
                labelClsExtra: 'lblRdReq'
            },
            {
                xtype: 'textfield',
                fieldLabel: i18n('sHome_domain'),
                name: "home_domain",
                allowBlank: false,
                blankText: i18n("sEnter_a_value"),
                labelClsExtra: 'lblRdReq'
            },
            {
                xtype: 'textfield',
                fieldLabel: i18n('sFriendly_name'),
                name: "friendly_name",
                allowBlank: false,
                blankText: i18n("sEnter_a_value"),
                labelClsExtra: 'lblRdReq'
            },
            {
                xtype: 'textfield',
                fieldLabel: i18n('sRCOI'),
                name: "rcoi",
                labelClsExtra: 'lblRdReq'
            },
            {
                fieldLabel: i18n('sAuthentication_method'),
                xtype: 'cmbEapMethods',
                allowBlank: false,
                blankText: i18n('sSupply_a_value'),
                labelClsExtra: 'lblRdReq',
                itemId: 'eap_method',
                name: 'eap_method',
            },
           
            {
                fieldLabel: i18n('sHMAC_Key'),
                xtype: 'cmbEncodingSchemes',
                allowBlank: false,
                blankText: i18n('sSupply_a_value'),
                labelClsExtra: 'lblRdReq',
                itemId: 'encoding_scheme',
                name: 'encoding_scheme_id',
            },
            {
                xtype: 'datefield',
                fieldLabel: i18n('sExpire'),
                name: 'expire',
                itemId: 'expire',
                allowBlank: false,
                blankText: i18n('sEnter_a_value'),
                format: 'Y/m/d',
                // minValue: new Date(), // limited to the current date or after
                //value: dtTo
                
            },
            {
                xtype: 'checkbox',
                boxLabel: i18n('sMake_available_to_sub_providers'),
                // fieldLabel    : i18n('sMake_available_to_sub_providers'),
                name: 'available_to_siblings',
                inputValue: 'available_to_siblings',
                itemId: 'a_to_s',
                checked: false,
                cls: 'lblRd'
            },
        ];


        me.callParent(arguments);
    },
    onBtnPickOwnerClick: function (button) {
        var me = this;
        var pnl = button.up('panel');
        var updateDisplay = pnl.down('#displUser');
        var updateValue = pnl.down('#user_id');

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
