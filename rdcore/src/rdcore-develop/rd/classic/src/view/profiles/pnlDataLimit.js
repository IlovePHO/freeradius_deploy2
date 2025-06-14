Ext.define('Rd.view.profiles.pnlDataLimit', {
    extend      : 'Ext.panel.Panel',
    glyph       : Rd.config.icnData,
    alias       : 'widget.pnlDataLimit',
    requires    : [
        'Rd.view.profiles.vcDataLimit',
        'Rd.view.components.rdSlider'
    ],
    controller  : 'vcDataLimit',
    layout      : 'vbox',
    title       : i18n("sDATA_LIMIT"),
    initComponent: function(){
        var me      = this;
        var w_sec   = 350;
        var w_rd    = 68;
        me.width    = 550;
        me.padding  = 5;
        me.items    = [
			{
			    xtype       : 'sldrToggle',
			    fieldLabel  : i18n('sEnable_fs_Disable'),
			    userCls     : 'sldrDark',
			    name        : 'data_limit_enabled',
			    itemId      : 'data_limit_enabled',
			    value       : 1,
			    listeners   : {
					change  : 'sldrToggleChange'
				}
			},
			{ 
			    xtype       : 'container',
			    itemId      : 'cntDetail',
			    items       : [
			        {
                        xtype       : 'radiogroup',
                        fieldLabel  : i18n('sReset'),
                        itemId      : 'rgrpDataReset',
                        columns     : 3,
                        vertical    : false,
                        width       : me.width,
                        items       : [
                            {
                                boxLabel  : i18n('sDaily'),
                                name      : 'data_reset',
                                inputValue: 'daily',
                                margin    : '0 15 0 0',
                                checked   : true
                            }, 
                            {
                                boxLabel  : i18n('sWeekly'),
                                name      : 'data_reset',
                                inputValue: 'weekly',
                                margin    : '0 0 0 15'
                            },
                            {
                                boxLabel  : i18n('sMonthly'),
                                name      : 'data_reset',
                                inputValue: 'monthly',
                                margin    : '0 0 0 15'
                            },
                            {
                                boxLabel  : i18n('sNever'),
                                name      : 'data_reset',
                                inputValue: 'never',
                                margin    : '0 15 0 0'   
                            },
                            /*{
                                boxLabel  : i18n('sTop-Up'),
                                name      : 'data_reset',
                                inputValue: 'top_up',
                                margin    : '0 0 0 15'   
                            }*/
                        ],
                        listeners   : {
					        change  : 'rgrpDataResetChange'
				        }
                    },
                    {
                        xtype   : 'panel',
                        itemId  : 'pnlDataTopUp',
                        hidden  : true,
                        bodyStyle   : 'background: #fff1b3',
                        html    : "<h3 style='text-align:center;color:#876f01'>"+i18n("sTop-Up_Amount_is_Per_User")+"</h3>",
                        width   : me.width-30,
                        margin  : 10
                    },
                    {
			            xtype       : 'rdSlider',
			            sliderName  : 'data_amount',
			            fieldLabel  : i18n("sAmount"),
			            minValue    : 1,
                        maxValue    : 999
			        },
                    {
                        xtype       : 'radiogroup',
                        fieldLabel  : i18n('sUnits'),
                        itemId      : 'rgrpDataUnit',
                        columns     : 2,
                        vertical    : false,
                        items       : [
                            {
                                boxLabel  : 'MB',
                                name      : 'data_unit',
                                inputValue: 'mb',
                                margin    : '0 15 0 0',
                                checked   : true
                            }, 
                            {
                                boxLabel  : 'GB',
                                name      : 'data_unit',
                                inputValue: 'gb',
                                margin    : '0 0 0 15'
                            }
                        ]
                    },
                    {
                        xtype       : 'radiogroup',
                        fieldLabel  : i18n('sType'),
                        itemId      : 'rgrpDataCap',
                        columns     : 2,
                        vertical    : false,
                        items       : [
                            {
                                boxLabel  : 'Hard',
                                name      : 'data_cap',
                                inputValue: 'hard',
                                margin    : '0 15 0 0',
                                checked   : true
                            }, 
                            {
                                boxLabel  : 'Soft',
                                name      : 'data_cap',
                                inputValue: 'soft',
                                margin    : '0 0 0 15'
                            }
                        ]
                    },
                    /*{
                        xtype       : 'checkbox',
                        itemId      : 'chkDataMac',
                        boxLabel    : 'Apply Limit Per Device (For Click-To-Connect)',
                        name        : 'data_limit_mac',
                        margin      : '0 0 0 15'
                    }*/
                ]
            }
        ];       
        this.callParent(arguments);
    }
});
