if(jQuery !== undefined){
  (function(jQuery, wp){
    jQuery( window ).on( 'load', function() {
      var dm_interval = setInterval(function(){
        var dm_images = jQuery('.attachments li');
        if(dm_images.length){
          console.log(dm_images.length);
          clearInterval(dm_interval);
          initDownloadMedia(dm_images);
        }
      }, 100);

      var dm_clicks = {};

      function initDownloadMedia(){
        jQuery('.thumbnail').append('<a class="dm_button">');
        jQuery('.thumbnail').on('click', '.dm_button', function(e){
          var dm_attachment_id = parseInt(jQuery(this).closest('.attachment').attr('data-id'), 10);
          if(dm_clicks[dm_attachment_id] === undefined){
            jQuery.get(dm_var.admin_url + '?action=' + dm_var.action + '&id=' + dm_attachment_id, function(imageJSON){

              var image = JSON.parse(imageJSON);

              dm_clicks[dm_attachment_id] = image;
              fakeClick(image);
            });
          }else{
            fakeClick(dm_clicks[dm_attachment_id]);
          }
          e.stopPropagation();
        }, );
      }

      function fakeClick(image){
        var fakeClick = jQuery("<a>")
            .attr("href", image.url)
            .attr("download", image.title)
            .appendTo("body");
        fakeClick[0].click();
        fakeClick.remove();
      }

    });
  })(jQuery, wp);
}
