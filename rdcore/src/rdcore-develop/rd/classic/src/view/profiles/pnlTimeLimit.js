Ext.define('Rd.view.profiles.pnlTimeLimit', {
    extend      : 'Ext.panel.Panel',
    glyph       : Rd.config.icnTime,
    alias       : 'widget.pnlTimeLimit',
    requires    : [
        'Rd.view.profiles.vcTimeLimit',
        'Rd.view.components.rdSlider'
    ],
    controller  : 'vcTimeLimit',
    layout      : { type: 'vbox'},
    //layout      : { type: 'vbox', align: 'center' },
    title       : i18n("sTIME_LIMIT"),
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
			    name        : 'time_limit_enabled',
			    itemId      : 'time_limit_enabled',
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
                        itemId      : 'rgrpTimeReset',
                        columns     : 3,
                        vertical    : false,
                        width       : me.width,
                        items       : [
                            {
                                boxLabel  : i18n('sDaily'),
                                name      : 'time_reset',
                                inputValue: 'daily',
                                margin    : '0 15 0 0',
                                checked   : true
                            }, 
                            {
                                boxLabel  : i18n('sWeekly'),
                                name      : 'time_reset',
                                inputValue: 'weekly',
                                margin    : '0 0 0 15'
                            },
                            {
                                boxLabel  : i18n('sMonthly'),
                                name      : 'time_reset',
                                inputValue: 'monthly',
                                margin    : '0 0 0 15'
                            },
                            {
                                boxLabel  : i18n('sNever'),
                                name      : 'time_reset',
                                inputValue: 'never',
                                margin    : '0 15 0 0'   
                            },
                            /*{
                                boxLabel  : i18n('sTop-Up'),
                                name      : 'time_reset',
                                inputValue: 'top_up',
                                margin    : '0 0 0 15'   
                            }*/
                        ],
                        listeners   : {
					        change  : 'rgrpTimeResetChange'
				        }
                    },
                    {
                        xtype       : 'panel',
                        itemId      : 'pnlTimeTopUp',
                        hidden      : true,
                        bodyStyle   : 'background: #fff1b3',
                        html        : "<h3 style='text-align:center;color:#876f01'>"+i18n("sTop-Up_Amount_is_Per_User")+"</h3>",
                        width       : me.width-30,
                        margin      : 10
                    },
                    {
			            xtype       : 'rdSlider',
			            sliderName  : 'time_amount',
			            fieldLabel  : i18n("sAmount"),
                        minValue    : 1,
                        maxValue    : 120
			        },
                    {
                        xtype       : 'radiogroup',
                        fieldLabel  : i18n('sUnits'),
                        itemId      : 'rgrpTimeUnit',
                        columns     : 3,
                        vertical    : false,
                        items       : [
                            {
                                boxLabel  : i18n('sMinutes'),
                                name      : 'time_unit',
                                inputValue: 'min',
                                margin    : '0 15 0 0',
                                checked   : true
                            }, 
                            {
                                boxLabel  : i18n('sHours'),
                                name      : 'time_unit',
                                inputValue: 'hour',
                                margin    : '0 0 0 0'
                            },
                            {
                                boxLabel  : i18n('sDays'),
                                name      : 'time_unit',
                                inputValue: 'day',
                                margin    : '0 0 0 15'
                            }
                        ]
                    },
                    {
                        xtype       : 'radiogroup',
                        fieldLabel  : i18n('sType'),
                        itemId      : 'rgrpTimeCap',
                        columns     : 2,
                        vertical    : false,
                        items       : [
                            {
                                boxLabel  : 'Hard',
                                name      : 'time_cap',
                                inputValue: 'hard',
                                margin    : '0 15 0 0',
                                checked   : true
                            }, 
                            {
                                boxLabel  : 'Soft',
                                name      : 'time_cap',
                                inputValue: 'soft',
                                margin    : '0 0 0 15'
                            }
                        ]
                    },
                    /*{
                        xtype       : 'checkbox',
                        itemId      : 'chkTimeMac',
                        boxLabel    : 'Apply Limit Per Device (For Click-To-Connect)',
                        name        : 'time_limit_mac',
                        margin      : '0 0 0 15'
                    }*/
                ]
            }
        ];       
        this.callParent(arguments);
    }
});
