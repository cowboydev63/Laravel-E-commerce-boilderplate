<?php only_admin_access(); ?>



<script src="<?php print modules_url()?>editor/html_editor/html_editor.js"></script>


<script>






    $(document).ready(function () {
        var fields = mw.html_editor.get_edit_fields(true);



        mw.html_editor.build_dropdown(fields, false);
        mw.html_editor.populate_editor();

        // mw.history.load
     //   mw.html_editor.init();

//
//        $(window.parent).on('saveEnd', function () {
//            alert( 213213 );
//
//        });






        //
    })
   mw.require('<?php print modules_url()?>editor/selector.css');

</script>
<style>
    .mw-ui-box {
      margin: 20px;
      margin-bottom: 70px;
    }

    #save-toolbar{
      position: fixed;
      bottom: 0;
      left: 0;
      background: white;
      box-shadow: 0 -2px 2px rgba(0, 0, 0, .2);
      padding: 10px;
      text-align: right;
      z-index: 1;
      width: 100%;

    }

</style>
<div class="mw-ui-box   ">
          <div class="mw-ui-box-header">
<span class="mw-icon-gear"></span><span><?php _e('Sections'); ?></span>
</div>
          <div class="mw-ui-box-content"><div id="select_edit_field_wrap"></div></div>
        </div>

<div id="save-toolbar">
  <button onclick="mw.html_editor.reset_content();" class="mw-ui-btn mw-ui-btn-invert"><?php _e('Reset content'); ?></button>
</div>

