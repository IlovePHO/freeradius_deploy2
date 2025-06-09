Ext.define('Rd.controller.cAttributeConverts', {
    extend: 'Ext.app.Controller',
    owner: undefined,
    user_id: undefined,
    actionIndex: function (pnl) {

        var me = this;

        if (me.populated) {
            return;
        }
        pnl.add({
            xtype: 'gridAttributeConverts',
            padding: Rd.config.gridPadding,
            border: false,
            plain: true
        });
        pnl.on({
            activate: me.gridActivate,
            scope: me
        });
        me.populated = true;

    },

    views: [
        'attributeConverts.gridAttributeConverts',
        'attributeConverts.frmAttributeConvert',
        'components.cmbVendor',
        'components.cmbAttribute',
        'nas.cmbNasTypes',
    ],
    stores: [
        'sAttributeConverts',
        'sVendors',
        'sAttributes',
        'sNasTypes',
    ],
    models: [
        'mAttributeConvert',
        'mVendor',
        'mAttribute',
        'mNasType',
    ],
    selectedRecord: null,
    config: {
        urlAdd: '/cake3/rd_cake/attribute-converts/add.json',
        urlDelete: '/cake3/rd_cake/attribute-converts/delete.json',

    },
    refs: [{
        ref: 'grid',
        selector: 'gridAttributeConverts'
    }, ],
    init: function () {
        var me = this;
        if (me.inited) {
            return;
        }
        me.inited = true;
        me.control({
            'gridAttributeConverts #reload': {
                click: me.reload
            },
            'gridAttributeConverts #add': {
                click: me.add
            },
            'gridAttributeConverts #delete': {
                click: me.del
            },
            'gridAttributeConverts #cmbSrcVendor': {
                change: me.cmbVendorChange
            },
            'gridAttributeConverts #cmbDstVendor': {
                change: me.cmbVendorChange
            },

        });
    },
    cmbVendorChange: function (cmb) {
        var me = this;
        var value = cmb.getValue();
        var grid = cmb.up('gridAttributeConverts');
        var attrId = cmb.getItemId() == 'cmbSrcVendor' ? '#cmbSrcAttribute' : '#cmbDstAttribute';
        var cmbAttr = grid.down(attrId);
        cmbAttr.reset();
        console.log("Combo thing changed:" + cmb.getItemId() + ':' + attrId + ':' + cmbAttr.getStore().getStoreId());
        //Cause this to result in a reload of the Attribute combo
        cmbAttr.getStore().getProxy().setExtraParam('vendor', value);
        cmbAttr.getStore().load();
    },
    gridActivate: function (g) {
        var me = this;
        var grid = g.down('grid');
        if (grid) {
            grid.getStore().load();
        } else {
            g.getStore().load();
        }
    },

    reload: function () {
        var me = this;
        me.getGrid().getSelectionModel().deselectAll(true);
        me.getGrid().getStore().load();
    },

    add: function (button) {
        var me = this;
        var form = me.getGrid().down('frmAttributeConvert');
        form.submit({
            clientValidation: true,
            url: me.getUrlAdd(),
            success: function (form, action) {
                form.reset();
                me.getStore('sAttributeConverts').load();
                Ext.ux.Toaster.msg(
                    i18n('sNew_item_created'),
                    i18n('sItem_created_fine'),
                    Ext.ux.Constants.clsInfo,
                    Ext.ux.Constants.msgInfo
                );
            },
            failure: function (form, action) {
                switch (action.failureType) {
                    case Ext.form.action.Action.CLIENT_INVALID:
                        Ext.ux.Toaster.msg(
                            i18n('sProblems_creating_item'),
                            i18n('sForm_fields_may_not_be_submitted_with_invalid_values'),
                            Ext.ux.Constants.clsWarn,
                            Ext.ux.Constants.msgWarn
                        );
                        break;
                    case Ext.form.action.Action.CONNECT_FAILURE:
                        Ext.ux.Toaster.msg(
                            i18n('sProblems_creating_item'),
                            i18n('sAjax_communication_failed'),
                            Ext.ux.Constants.clsWarn,
                            Ext.ux.Constants.msgWarn
                        );
                        break;
                    case Ext.form.action.Action.SERVER_INVALID:
                        var message = action.result.message? ( action.result.message.message ? action.result.message.message : action.result.message) : i18n('sError_encountered');
                        Ext.ux.Toaster.msg(
                            i18n('sProblems_creating_item'),
                            message,
                            Ext.ux.Constants.clsWarn,
                            Ext.ux.Constants.msgWarn
                        );
                        me.getStore('sAttributeConverts').load(); //Reload from server
                        break;
                }
            }
        });

    },

    select: function (grid, record) {
        var me = this;
        //Adjust the Edit and Delete buttons accordingly...

        //Dynamically update the top toolbar
        var tb = me.getGrid().down('toolbar[dock=top]');

        var del = record.get('delete');
        if (del == true) {
            if (tb.down('#delete') != null) {
                tb.down('#delete').setDisabled(false);
            }
        } else {
            if (tb.down('#delete') != null) {
                tb.down('#delete').setDisabled(true);
            }
        }
    },

    del: function () {
        var me = this;
        //Find out if there was something selected
        if (me.getGrid().getSelectionModel().getCount() == 0) {
            Ext.ux.Toaster.msg(
                i18n('sSelect_an_item'),
                i18n('sFirst_select_an_item_to_delete'),
                Ext.ux.Constants.clsWarn,
                Ext.ux.Constants.msgWarn
            );
        } else {
            Ext.MessageBox.confirm(i18n('sConfirm'), i18n('sAre_you_sure_you_want_to_do_that_qm'), function (val) {
                if (val == 'yes') {
                    var selected = me.getGrid().getSelectionModel().getSelection();
                    var list = [];
                    Ext.Array.forEach(selected, function (item) {
                        var id = item.getId();
                        Ext.Array.push(list, {
                            'id': id
                        });
                    });
                    Ext.Ajax.request({
                        url: me.getUrlDelete(),
                        method: 'POST',
                        jsonData: list,
                        success: function(response){
                            var jsonData    = Ext.JSON.decode(response.responseText);
                            if(jsonData.success){
                                console.log('success');
                                Ext.ux.Toaster.msg(
                                    i18n('sItem_deleted'),
                                    i18n('sItem_deleted_fine'),
                                    Ext.ux.Constants.clsInfo,
                                    Ext.ux.Constants.msgInfo
                                );
                            }else{
                                //Server error
                                var message = jsonData.message? ( jsonData.message.message ? jsonData.message.message : jsonData.message) : __('sError_encountered');
                                Ext.ux.Toaster.msg(
                                    i18n('sProblems_deleting_item'),
                                    message,
                                    Ext.ux.Constants.clsWarn,
                                    Ext.ux.Constants.msgWarn
                                );
                            }
                                me.reload(); //Reload from server
                        },
                        failure: Ext.ux.ajaxFail
                    });
                }
            });
        }
    },


});
