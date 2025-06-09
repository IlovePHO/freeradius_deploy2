Ext.define('Rd.view.nas.pnlNasMaps', {
    extend: 'Ext.panel.Panel',
    alias :'widget.pnlNasMaps',
    header: false,
    layout: 'fit',
    requires: [
        'Rd.view.components.ajaxToolbar',
        'Rd.view.components.cmpLeafletMapView',
    ],
    store       : undefined,
    items       : [],
    urlMenu     : '/cake3/rd_cake/nas/menu_for_maps.json',
    markers     : [],
    iconBlueMark     : 'resources/images/map_markers/blue-dot.png', 
    iconYellowMark   : 'resources/images/map_markers/yellow-dot.png',
    shadowUrl   : 'resources/css/images/marker-shadow.png',
    centerLatLng: undefined,
    mapOptions  : {},
    config: {
        urlMapDelete:       '/cake3/rd_cake/nas/delete_map.json',
        urlMapSave:         '/cake3/rd_cake/nas/edit_map.json',
    },
    listeners   : {
        afterrender : 'OnPnlAfterrender',
    },
    initComponent: function(){
        var me      = this;
        me.tbar     = Ext.create('Rd.view.components.ajaxToolbar',{'url': me.urlMenu});
        
        //default Japan
        var center  = [38.50, 137.35];
        var zoom    = 5;
        
        if(me.mapOptions.zoom){ //We assume the rest will also be there
            zoom    = me.mapOptions.zoom
        }
        if(me.centerLatLng.lat && me.centerLatLng.lng){
            center  = [me.centerLatLng.lat,me.centerLatLng.lng];
        }
        
        me.store = Ext.create('Rd.store.sNas', { pageSize: 9999,
            listeners: {
                load: function(store, records, success, options) {
                    me.clearLayers();
                    //me.store.each(function(item) {
                    Ext.each(records, function(item) {
                         //console.log(item.data);
                         if(item.data.lat && item.data.lon){
                            me.addMarker(item.data,'stored');
                         }
                     });
                    }
            }
        });

        
        me.items = [{ 
            xtype           : 'cmpLeafletMapView',
            initialLocation : center,
            initialZoomLevel: zoom,
        }];
        
        
        me.callParent(arguments);
    },
    clearLayers: function(){
        var me = this;
        me.markers=[];
        me.down('cmpLeafletMapView').getFgMarkers().clearLayers();
        me.down('cmpLeafletMapView').getFgPolygons().clearLayers();
    },
    OnPnlAfterrender: function(){
        //console.log('OnPnlAfterrender');
        var me = this;
        me.createMap();
    },
    getMap(){
        var me = this;
        if( !me.map ){
            me.map = me.down('cmpLeafletMapView').getMap();
        }
        return me.map;
    },
    getMapPreferences(){
        var me = this;
        var map = me.getMap();
        var info = {};
        if(map){
            info = {
                zoom: map.getZoom(),
                center: map.getCenter(),
            };
        }
        return info;
    },
    getStatusMsg:function(status){
        var me = this;
        var msg={
            'stored': i18n('sStored_position'),
            'new'   : i18n('sNew_position'),
            'edit'  : i18n('sNew_position'),
        }
        return msg[status]? msg[status] :msg['new'];
    },
    getLIcon:function(status){
        var me = this;
        var icon    = new L.Icon({
            iconUrl     : (status=='stored'? me.iconBlueMark: me.iconYellowMark),
            iconSize    : [32, 32],
            iconAnchor  : [16, 32],
            popupAnchor : [1, -34],
            shadowUrl   : me.shadowUrl,
            shadowSize  : [59, 32]
        });
        return icon;
    },
    addMarker: function(item,status){
        var me = this;
        var MapView = me.down('cmpLeafletMapView');
        var map = me.getMap();
        
        var marker =me.markers[item.id];
        if(marker){
            map.panTo(marker.getLatLng());
            if( !marker.isPopupOpen() ){
                marker.openPopup();
            }
            return;
        }
        
        var icon    = me.getLIcon(status);
        
        //stard column lng=lon
        if( item.lon && !item.lng ){
            item.lng = item.lon;
        }
        //new marker
        if( !Ext.isNumeric(item.lat) && !Ext.isNumeric(item.lng) ){
            var cp = map.getCenter();
            item.lat = cp.lat;
            item.lng = cp.lng;
        }
        var cId ='mpNasPopC'+item.id;
        marker = L.marker(
            [item.lat , item.lng],
            {
                icon:icon,
                title: item.shortname,
                draggable: true,
                cId:cId,
                nasId:item.id,
                status:status,
                panel:false
            });
        

        marker.bindPopup("<div id=\""+cId+"\" style=\"width:360px;\"> </div>",{maxWidth:400});
        marker.on({
            //click: me.markerClick,
            popupopen: function(event){
                var marker = event.target;
                var position = marker.getLatLng();
                item.lat = position.lat;
                item.lng = position.lng;
                
                //ExtJs panel to popup content
                me.markerInfo(marker,item);
            },
            popupclose: function(event){
                var marker = event.target;
                //pop content is closed destroy
                if(marker.options.panel){
                    marker.options.panel.destroy();
                    marker.options.panel = false;
                }
                //console.log('popupclose');
            },
            dragend:function(event){
                var marker = event.target;
                //var position = marker.getLatLng();
                //console.log('markerDragEnd',marker,position);
                marker.openPopup();
            },
            dragstart:function(event){
                var marker = event.target;
                var position = marker.getLatLng();
                //console.log('markerDragStart',marker);
                if(marker.options.status=='stored'){
                    marker.options.status='edit';
                    setTimeout(function(){
                        var icon    = me.getLIcon(marker.options.status);
                        marker.setIcon(icon);
                    },100);
                }
                
                marker.options.lastOlgPos = position;
            },
            
        });
        marker.addTo(MapView.getFgMarkers());
        if(status=='new'){
            marker.openPopup();
        }
        me.markers[item.id]=marker;
        return marker;
    },
    markerInfo:function(marker,item){
        var me = this;
        var opt = marker.options;
        item.message = me.getStatusMsg(opt.status);
        if(marker.options.panel){
            var pn = marker.options.panel;
            var d = pn.getData();
            pn.setData(item);
        }else{
            var infoTpl = new Ext.Template([
                    "<div class=\"divMapAction\">",
                        "<p style=\"margin:2px 0;\"> {message} </p>",
                        "<div style=\"clear:both;\"></div>",
                        "<label class=\"lblMap\">"+ i18n("sLatitude")+"  </label><label class=\"lblValue\"> {lat}</label>",
                        "<div style=\"clear:both;\"></div>",
                        "<label class=\"lblMap\">"+i18n("sLongitude")+"  </label><label class=\"lblValue\"> {lng}</label>",
                        "<div style=\"clear:both;\"></div>",
                    "</div>"
                    ]
                );
            var popInfo = Ext.create('Ext.panel.Panel', {
                alias   : 'widget.pnlMapsNasEdit',
                title: item.nasname+' '+item.shortname,
                renderTo: Ext.get(opt.cId),
                height	: 180,
                width   : 360,
                maxWidth: 400,
                tpl		: infoTpl,
                layout	: 'fit',
                data    : item,
                buttonAlign: 'center',
                buttons	: [
                    {
                        xtype   : 'button',
                        text    : i18n('sSave'),
                        scale   : 'large',
                        glyph   : Rd.config.icnYes,
                        listeners   : {
                            click : me.OnSaveMarker,
                            scope : me
                        } 
                    },
                    {
                        xtype   : 'button',
                        text    : i18n('sCancel'),
                        scale   : 'large',
                        glyph   : Rd.config.icnClose,
                        listeners   : {
                            click : me.OnCancelMarker,
                            scope : me
                        } 
                    },
                    {
                        xtype   : 'button',
                        text    : i18n('sDelete'),
                        scale   : 'large',
                        glyph   : Rd.config.icnDelete,
                        listeners   : {
                            click : me.OnDeleteMarker,
                            scope : me
                        } 
                    }  
                ]
                });
            marker.options.panel = popInfo;
        }
        
        return popInfo;
    },
    clearMarkers: function(){
        var me = this;
        me.getView().down('cmpLeafletMapView').getFgMarkers().clearLayers();
    },
    createMap: function() {
        var me = this;
        me.clearLayers();
        me.store.load(); 
    },
    //Marker to Nas position
    OnSaveMarker:function(button){
        var me = this;
        var pnl = button.up('panel');
        var d = pnl.getData();
        var marker = me.markers[d.id];
        marker.closePopup();
        //console.log('OnSaveMarker',button,pnl,d);
        
        Ext.Ajax.request({
            url: me.getUrlMapSave(),
            method: 'GET',
            params: {
                id: d.id,
                lat: d.lat,
                lon: d.lng
            },
            success: function(response){
                var jsonData    = Ext.JSON.decode(response.responseText);
                if(jsonData.success){
                    var status = 'stored';
                    var icon    = me.getLIcon(status);
                    marker.setIcon(icon);
                    marker.options.status=status;
                    
                    Ext.ux.Toaster.msg(
                        i18n('sItem_updated'),
                        i18n('sItem_updated_fine'),
                        Ext.ux.Constants.clsInfo,
                        Ext.ux.Constants.msgInfo
                    );
                }   
            },
            scope: me
        });
        
        
    },
    OnCancelMarker:function(button){
        var me = this;
        var pnl = button.up('panel');
        //console.log('OnCancelMarker',button,pnl);
        var d = pnl.getData();
        var marker = me.markers[d.id];
        //Cancel to last marker move
        if(marker.options.lastOlgPos){
            marker.closePopup();
            marker.setLatLng( marker.options.lastOlgPos );
            delete marker.options.lastOlgPos;
        }
    },
    OnDeleteMarker:function(button){
        var me = this;
        var pnl = button.up('panel');
        var d = pnl.getData();
        var marker = me.markers[d.id];
        //console.log('OnDeleteMarker',button,pnl,d);

        Ext.Ajax.request({
            url: me.getUrlMapDelete(),
            method: 'GET',
            params: {
                id: d.id
            },
            success: function(response){
                var jsonData    = Ext.JSON.decode(response.responseText);
                if(jsonData.success){
                    marker.closePopup();
                    marker.remove();
                    me.markers[d.id] =null;
                    Ext.ux.Toaster.msg(
                        i18n('sItem_deleted'),
                        i18n('sItem_deleted_fine'),
                        Ext.ux.Constants.clsInfo,
                        Ext.ux.Constants.msgInfo
                    );
                }   
            },
            scope: me
        });
    },
});