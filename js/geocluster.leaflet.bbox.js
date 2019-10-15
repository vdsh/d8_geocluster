(function ($) {

  // Replace the default options for our use case.
  Drupal.leafletBBox.geoJSONOptions = {

    pointToLayer: function(featureData, latlng) {
      if (featureData.properties.geocluster_count > 1) {
        var number = featureData.properties.geocluster_count;
          /*
        if (number >= 1000) {
          number = number.toString().substring(0, number.toString().length -3) + 'k';
        }
        */
        //var icon = new L.NumberedDivIcon({number: number});

          var c = ' marker-cluster-';
          if (number < 10) {
              c += 'small';
          } else if (number < 100) {
              c += 'medium';
          } else if (number < 1000) {
              c += 'large';
          } else if (number < 10000) {
              c += 'huge';
          } else {
              c += 'giant';
          }
          var icon = new L.DivIcon({ html: '<div><span>' + number + '</span></div>', className: 'marker-cluster' + c, iconSize: new L.Point(40, 40) });

          lMarker = new L.Marker(latlng, {icon:icon});
      }
      else {
        lMarker = new L.Marker(latlng);
      }
      return lMarker;
    },

    onEachFeature: function(featureData, layer) {
      var popupText = featureData.properties.name;

      if (featureData.properties.geocluster_count > 1) {
        layer.on('click', function(e) {
          Drupal.leafletBBox.geoJSONOptions.clickOnClustered(e, featureData, layer);
        });
      }
      else {
        layer.bindPopup(popupText);
      }
    },

    clickOnClustered: function(e, featureData, layer) {
        var map = layer._map;

        // Close any other opened popup.
        if (map._popup) {
          map._popup._source.closePopup();
        }

        map.setView(layer.getLatLng(), map._zoom + 1);
    }

  };

})(jQuery);
