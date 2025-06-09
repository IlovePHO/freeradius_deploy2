Ext.define('Rd.view.encodingSchemes.pnlEncodingSchemeEdit', {
    extend      : 'Ext.form.Panel',
    alias       : 'widget.pnlEncodingSchemeEdit',
    autoScroll	: true,
    plain       : true,
	itemId		: 'pnlEncodingSchemeEdit',
    layout: {
        type: 'vbox',
        pack: 'start',
        align: 'stretch'
    },
    record  : null, //We will supply each instance with a reference to the selected record.
    defaults    : {
            border: false
    },
    fieldDefaults: {
        msgTarget       : 'under',
        labelAlign      : 'left',
        labelSeparator  : '',
        labelWidth      : Rd.config.labelWidth,
        margin          : Rd.config.fieldMargin,
        labelClsExtra   : 'lblRdReq'
    },

    requires: [
        'Ext.form.field.Text',
    ],
    
    listeners       : {
        show        : 'loadSettings', //Trigger a load of the settings
        afterrender : 'loadSettings',
        
    },
    loadSettings: function(panel){ 
        var me = this;
        if( me.record ){
            panel.loadRecord(me.record);
        }
    },
    initComponent: function(){
    
        var me = this;
        
        //Set default values for from and to:
        var dtTo    = new Date();
        dtTo.setYear(dtTo.getFullYear() + 1);
        
        me.buttons = [
            {
                itemId  : 'save',
                text    : 'SAVE',
                scale   : 'large',
                formBind: true,
                glyph   : Rd.config.icnYes,
                margin  : Rd.config.buttonMargin,
                ui      : 'button-teal'
            }
        ]; 
        me.items = [
            {
                xtype       : 'panel',
                bodyPadding : 10,
                items       : [
                    {
                        xtype       : 'textfield',
                        itemId      : 'id',
                        name        : "id",
                        hidden      : true
                    },

                    {
                        xtype: 'textfield',
                        fieldLabel: i18n('sName'),
                        itemId: 'name',
                        name: "name",
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        labelClsExtra: 'lblRdReq'
                    },
                    {
                        xtype: 'datefield',
                        fieldLabel: i18n('sExpire'),
                        itemId: 'expire',
                        name: 'expire',
                        allowBlank: false,
                        blankText: i18n('sEnter_a_value'),
                        format:'Y/m/d',
                        // minValue: new Date(), // limited to the current date or after
                        value: dtTo
                    }
                ],
            },
           
        ];
        
        this.callParent(arguments);
    },
  

});
