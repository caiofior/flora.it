$(document).ready(function() {
      $( "#taxasearch" ).autocomplete({
      source: "?action=taxasearch",
      select: function( e, ui ) {
         window.location.href = ui.item.value;
         e.preventDefault();
      },
      change: function (e, ui) {
          if (!ui.item)
              $(this).val("");
      }
    });
    $(".more_info").click (function (e){
       $(this).parent().siblings("div").show();
       e.preventDefault();
    });
});