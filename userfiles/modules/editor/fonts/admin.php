<?php only_admin_access(); ?>
<script type="text/javascript">
    mw.require('options.js');
</script>
<script type="text/javascript">
    $(document).ready(function () {
        mw.options.form('#<?php print $params['id'] ?>', function () {
            if (mw.notification != undefined) {
                mw.notification.success('Fonts updated');
            }
			
			
			if(typeof(window.parent.mw.wysiwyg) != 'undefined'){
				 
				var selected = [];
				$('#<?php print $params['id'] ?> .enabled_custom_fonts_table input:checked').each(function() {
					selected.push($(this).val());
				});

		 		window.parent.mw.wysiwyg.fontFamiliesExtended = [];
				window.parent.mw.wysiwyg.initExtendedFontFamilies(selected);
				window.parent.mw.wysiwyg.initFontSelectorBox();	
				
				var custom_fonts_stylesheet = window.parent.document.getElementById("mw-custom-user-css");
				if(custom_fonts_stylesheet != null){
					var custom_fonts_stylesheet_restyled = '<?php print api_url('template/print_custom_css') ?>?v='+Math.random(0,10000);
					custom_fonts_stylesheet.href = custom_fonts_stylesheet_restyled;

				}
				
			}
		
        });
        
    });
</script>
<?php $fonts= json_decode(file_get_contents(__DIR__.DS.'fonts.json'), true); ?>
<?php if(isset($fonts['fonts'])): ?>
<?php $enabled_custom_fonts = get_option("enabled_custom_fonts", "template"); 

$enabled_custom_fonts_array = array();

if(is_string($enabled_custom_fonts)){
	$enabled_custom_fonts_array = explode(',',$enabled_custom_fonts);
}
 

?>

<div class="module-live-edit-settings enabled_custom_fonts_table">
   <table width="100%" cellspacing="0" cellpadding="0" class="mw-ui-table">
    <thead>
      <tr>
        <th></th>
        <th>Select Fonts</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($fonts['fonts'] as $font): ?>
      <tr>
        <td width="30"><input type="checkbox" name="enabled_custom_fonts" <?php if(in_array($font['family'], $enabled_custom_fonts_array)): ?> checked <?php endif; ?> class="mw_option_field" option-group="template" value="<?php print $font['family']; ?>" /></td>
        <td><?php print $font['family']; ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
