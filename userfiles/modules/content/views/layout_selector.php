<?php


$iframe_cont_id = false;

$data = false;

$rand = uniqid() . rand();
if (!isset($params["data-page-id"]) and !isset($params["content-id"]) and defined('PAGE_ID')) {
    $iframe_cont_id = $params["data-page-id"] = PAGE_ID;
}
if (!isset($params["data-page-id"]) and isset($params["content-id"])) {
    $iframe_cont_id = $params["data-page-id"] = $params["content-id"];
}


$live_edit_styles_check = false;
if (isset($params["live_edit_styles_check"])) {
    $live_edit_styles_check = true;
}
$template_selector_position = 'top';
if (isset($params["template-selector-position"])) {
    $template_selector_position = $params["template-selector-position"];
}


if ((!isset($params["layout_file"]) or trim($params["layout_file"]) == '') and isset($params["data-page-id"]) and intval($params["data-page-id"]) != 0) {
    $data = get_content_by_id($params["data-page-id"]);
} elseif (isset($params["show-page-id-layout"])) {
    $data = get_content_by_id($params["show-page-id-layout"]);
} elseif (isset($params["content-id"])) {
    $data = get_content_by_id($params["content-id"]);
}


if (!isset($params["layout_file"]) and isset($params["layout-file"])) {
    $params["layout_file"] = $params["layout-file"];
}
if (!isset($params["layout_file"]) and $data == false or empty($data)) {
    include('_empty_content_data.php');
}


if (isset($data['active_site_template']) and $data['active_site_template'] == '') {
    $data['active_site_template'] = ACTIVE_SITE_TEMPLATE;
}
if (isset($params["show-page-id-layout"]) and isset($params["data-page-id"])) {

} else if (isset($params["layout_file"]) and trim($params["layout_file"]) != '') {
    $params['layout_file'] = str_replace('..', '', $params['layout_file']);
    $params['layout_file'] = str_replace('____', DS, $params['layout_file']);
    $params['layout_file'] = normalize_path($params['layout_file'], false);
    $data['layout_file'] = $params["layout_file"];
}


if (!isset($params["layout_file"]) and isset($data["layout_file"])) {
    $params["layout_file"] = $data["layout_file"];
}
if (!isset($params["active_site_template"]) and isset($data["active_site_template"])) {
    $params["active_site_template"] = $data["active_site_template"];
}


$inherit_from = false;

if (!isset($params["inherit_from"]) and isset($params["inherit-from"])) {
    $params["inherit_from"] = $params["inherit-from"];
}

if ((isset($params["inherit_from"]) and $params["inherit_from"] != 0) or ($data['layout_file'] == '' and (!isset($data['layout_name']) or $data['layout_name'] == '' or $data['layout_name'] == 'inherit'))) {

    if (isset($params["inherit_from"]) and (trim($params["inherit_from"]) != '' or trim($params["inherit_from"]) != '0')) {
//


        $inherit_from_id = get_content_by_id($params["inherit_from"]);


        // $inherit_from_id = false;
        if ($inherit_from_id != false and isset($inherit_from_id['active_site_template']) and trim($inherit_from_id['active_site_template']) != 'inherit') {
            $data['active_site_template'] = $inherit_from_id['active_site_template'];
            $data['layout_file'] = $inherit_from_id['layout_file'];
            $inherit_from = $inherit_from_id;
            $data['layout_file'] = 'inherit';

        } else {
            $inh1 = mw()->content_manager->get_inherited_parent($params["inherit_from"]);

            if ($inh1 == false) {
                $inh1 = intval($params["inherit_from"]);
            }
            if ($inh1 != false) {
                $inherit_from = get_content_by_id($inh1);
                if (is_array($inherit_from) and isset($inherit_from['active_site_template'])) {
                    $data['active_site_template'] = $inherit_from['active_site_template'];
                    $data['layout_file'] = 'inherit';
                }
            }

        }
    }
}
if ((!isset($data['layout_file']) or $data['layout_file'] == NULL) and isset($data['is_home']) and ($data['is_home'] == 'y')) {
    $data['layout_file'] = 'index.php';
}

if (!isset($params["active_site_template"]) and isset($params["site-template"])) {
    $params["active_site_template"] = $params["site-template"];
}
if (isset($params["active_site_template"])) {
    $data['active_site_template'] = $params["active_site_template"];
}

if (isset($data["id"])) {
    if (!isset($iframe_cont_id) or $iframe_cont_id == false) {
        $iframe_cont_id = $data["id"];
    }
    if (!defined('ACTIVE_SITE_TEMPLATE')) {
        mw()->content_manager->define_constants($data);
    }
}

if (isset($data["active_site_template"]) and ($data["active_site_template"] == false or $data["active_site_template"] == NULL or trim($data["active_site_template"]) == '') and defined('ACTIVE_SITE_TEMPLATE')) {
    $data['active_site_template'] = ACTIVE_SITE_TEMPLATE;
}


if (isset($data['active_site_template']) and ($data['active_site_template']) == 'default') {
    $site_template_settings = get_option('current_template', 'template');
    if ($site_template_settings != false) {
        $data['active_site_template'] = $site_template_settings;
    }
}


$templates = site_templates();

$layout_options = array();


$layout_options['site_template'] = $data['active_site_template'];
$layout_options['no_cache'] = true;

$layouts = mw()->layouts_manager->get_all($layout_options);

$recomended_layouts = array();
if (isset($params['content-type'])) {

    foreach ($layouts as $k => $v) {

        $ctypes = array();
        if (isset($v['content_type'])) {
            $ctypes = explode(',', $v['content_type']);
            $ctypes = array_trim($ctypes);

        }

        if (isset($v['content_type'])
            and
            (
                trim($v['content_type']) == trim($params['content-type'])
                or (in_array($params['content-type'], $ctypes) == true)
            )
        ) {
            $v['is_recomended'] = true;
            $recomended_layouts[] = $v;
            unset($layouts[$k]);
        } else {

        }

    }

}
if (!empty($recomended_layouts)) {


    $layouts = array_merge($recomended_layouts, $layouts);
}


?>
<script>


    safe_chars_to_str = function (str) {
        if (str === undefined) {
            return;
        }
        return str.replace(/\\/g, '____').replace(/\'/g, '\\\'').replace(/\"/g, '\\"').replace(/\0/g, '____');
    }


    mw.templatePreview<?php print $rand; ?> = {
        set: function () {
            var iframe = mwd.querySelector('.preview_frame_wrapper iframe');
            var framewindow = iframe.contentWindow;
            framewindow.scrollTo(0, 0);
            mw.$('.preview_frame_wrapper').removeClass("loading");
            mw_preview_frame_object = mw.$('.preview_frame_wrapper iframe')[0];
            //$('html, body', framewindow.document).css('overflow', 'hidden');
            mw.templatePreview<?php print $rand; ?>.setHeight();
        },
        rend: function (url) {
            var holder = mw.$('.preview_frame_container');
            var wrapper = mw.$('.preview_frame_wrapper');
            var frame = '<iframe src="' + url + '" class="preview_frame_small" tabindex="-1" onload="mw.templatePreview<?php print $rand; ?>.set();" frameborder="0"></iframe>';
            holder.html(frame);
        },
        next: function () {
            var index = mw.templatePreview<?php print $rand; ?>.selector.selectedIndex;
            var next = mw.templatePreview<?php print $rand; ?>.selector.options[index + 1] !== undefined ? (index + 1) : 0;
            mw.templatePreview<?php print $rand; ?>.view(next);
        },
        prev: function () {
            var index = mw.templatePreview<?php print $rand; ?>.selector.selectedIndex;
            var prev = mw.templatePreview<?php print $rand; ?>.selector.options[index - 1] !== undefined ? (index - 1) : mw.templatePreview<?php print $rand; ?>.selector.options.length - 1;
            mw.templatePreview<?php print $rand; ?>.view(prev);
        },
        view: function (which) {
            mw.templatePreview<?php print $rand; ?>.selector.selectedIndex = which;
            mw.$("#layout_selector<?php print $rand; ?> li.active").removeClass('active');
            mw.$("#layout_selector<?php print $rand; ?> li").eq(which).addClass('active');
            $(mw.templatePreview<?php print $rand; ?>.selector).trigger('change');
        },
        setHeight: function () {
            var iframe = mwd.querySelector('.preview_frame_wrapper iframe');
            $(iframe).css({
                height: 1 * ($(window).height() - 66)
            })
            var framewindow = iframe.contentWindow;
            //iframe.style.height = framewindow.document.body.clientHeight + 'px';
            if (!this._init) {
                this._init = true
                $(window).on("resize", function () {
                    mw.templatePreview<?php print $rand; ?>.setHeight();
                })
            }

        },
        zoom: function (a) {
            if (typeof a == 'undefined') {
                var holder = mw.$('.preview_frame_wrapper');
                holder.toggleClass('zoom');
                if (holder[0] != null) {
                    var iframe = holder[0].querySelector('iframe');
                    if (iframe != null) {
                        iframe.contentWindow.scrollTo(0, 0);
                    }
                }
            }
            else if (a == 'out') {
                mw.$('.preview_frame_wrapper').removeClass('zoom');
            }
            else {
                mw.$('.preview_frame_wrapper').addClass('zoom');
            }
            mw.$('.preview_frame_wrapper iframe')[0].contentWindow.scrollTo(0, 0);
        },
        generate: function (return_url) {
            mw.$('.preview_frame_wrapper').addClass("loading");
            var template = mw.$('#active_site_template_<?php print $rand; ?> option:selected').val();
            var layout = mw.$('#active_site_layout_<?php print $rand; ?>').val();
            var is_shop = mw.$('#active_site_layout_<?php print $rand; ?> option:selected').attr('data-is-shop');
            var ctype = mw.$('#active_site_layout_<?php print $rand; ?> option:selected').attr('data-content-type');
            var stype = mw.$('#active_site_layout_<?php print $rand; ?> option:selected').attr('data-subtype');
            var stype_val = mw.$('#active_site_layout_<?php print $rand; ?> option:selected').attr('data-subtype-value');
            var inherit_from = mw.$('#active_site_layout_<?php print $rand; ?> option:selected').attr('inherit_from');


            var root = mwd.querySelector('#active_site_layout_<?php print $rand; ?>');
            var form = mw.tools.firstParentWithClass(root, 'mw_admin_edit_content_form');
            if (form != undefined && form != false) {
                if (is_shop != undefined) {
                    if (is_shop != undefined && is_shop == 'y') {
                        if (form != undefined && form.querySelector('input[name="is_shop"][value="1"]') != null) {
                            form.querySelector('input[name="is_shop"][value="1"]').checked = true;
                        }
                    }
                    else {
                        if (form != undefined && form.querySelector('input[name="is_shop"]') != null) {
                            form.querySelector('input[name="is_shop"]').value = '0'
                        }
                        if (form != undefined && form.querySelector('input[name="is_shop"][value="0"]') != null) {
                            form.querySelector('input[name="is_shop"][value="0"]').checked = true;
                        }
                    }
                }
                else {
                    <?php if(!isset($params['no_content_type_setup'])): ?>


                    if (form != undefined && form.querySelector('input[name="is_shop"]') != null) {
                        form.querySelector('input[name="is_shop"]').value = '0'
                    }
                    if (form != undefined && form.querySelector('input[name="is_shop"][value="0"]') != null) {
                        form.querySelector('input[name="is_shop"][value="0"]').checked = true;
                    }
                    <?php endif; ?>

                }
                <?php if(!isset($params['no_content_type_setup'])): ?>
                if (ctype == 'static' || ctype == 'dynamic') {
                    if (form != undefined && form.querySelector('input[name="subtype"]') != null) {
                        form.querySelector('input[name="subtype"]').value = ctype
                    }
                }
                if (stype) {
                    if (form != undefined && form.querySelector('input[name="subtype"]') != null) {
                        form.querySelector('input[name="subtype"]').value = stype
                    }
                }
                if (stype_val) {
                    if (form != undefined && form.querySelector('input[name="subtype_value"]') != null) {
                        form.querySelector('input[name="subtype_value"]').value = stype_val
                    }
                }
                <?php endif; ?>


            }


            if (template != undefined) {
                if (typeof(form) == 'object' && form.querySelector('input[name="active_site_template"]') != null) {
                    form.querySelector('input[name="active_site_template"]').value = template
                }
                var template = safe_chars_to_str(template);
                var template = template.replace('/', '___');
            }
            if (layout != undefined) {
                if (typeof(form) == 'object' && form.querySelector('input[name="layout_file"]') != null) {
                    form.querySelector('input[name="layout_file"]').value = layout
                }
                var layout = safe_chars_to_str(layout);
                var layout = layout.replace('/', '___');
            }


            <?php
            if ($iframe_cont_id == 0) {
                $iframe_start = site_url('home');
            } else {
                $iframe_start = page_link($iframe_cont_id);
            }

            ?>

            var inherit_from_param = '';
            if (inherit_from != undefined) {
                inherit_from_param = '&inherit_template_from=' + inherit_from;
            }


            var preview_template_param = '';
            if (template != undefined) {
                preview_template_param = '&preview_template=' + template;
                mw.$("#<?php print $params['id']?>").attr('active_site_template', template);
                if (template != 'default') {
                    mw.$("#selected-template-span-val").html(template);
                }


            }

            var preview_layout_param = '';
            if (layout != undefined) {
                preview_layout_param = '&preview_layout=' + layout;
                mw.$("#<?php print $params['id']?>").attr('layout_file', layout);

            }

            var preview_layout_content_type_param = '';
            <?php if(isset($params['content-type'])): ?>
            var preview_layout_content_type_param = '&content_type=<?php print $params['content-type'] ?>';

            <?php endif; ?>

            var iframe_url = '<?php print $iframe_start; ?>?no_editmode=true' + preview_template_param + preview_layout_param + '&content_id=<?php print  $iframe_cont_id  ?>' + inherit_from_param + preview_layout_content_type_param
            if (return_url == undefined) {
                mw.templatePreview<?php print $rand; ?>.rend(iframe_url);
                <?php if($params['id'] != 'mw-quick-add-choose-layout-middle-pos') { ?>

                mw.trigger('templateSelected');


                <?php  } ?>

            }
            else {

                return (iframe_url);
            }

        },
        _once: false
    }


    $(document).ready(function () {
        mw.$("#<?php print $params['id']?>").removeAttr('autoload');

        mw.templatePreview<?php print $rand; ?>.selector = mwd.getElementById('active_site_layout_<?php print $rand; ?>');

        mw.$('#active_site_template_<?php print $rand; ?>').bind("change", function (e) {
            var parent_module = $(this).parents('.module').first();
            if (parent_module != undefined) {
                var templ = $(this).val();
                //alert(templ);
                parent_module.attr('active_site_template', templ);

                mw.$("#<?php print $params['id']?>").attr('active_site_template', templ);
                mw.reload_module("#<?php print $params['id']?>", function () {

                    // $('.layouts_box_holder_small',"#<?php print $params['id']?>").toggleClass('semi_hidden');

                })


                //mw.templatePreview<?php print $rand; ?>.view();
                //  mw.reload_module('<?php print $params['type']?>', function () {
//                
//				
//            });
            }
            //mw.trigger('templateChanged');
        });

        mw.$('#active_site_layout_<?php print $rand; ?>').bind("change", function (e) {
            mw.templatePreview<?php print $rand; ?>.generate();
            mw.trigger('templateChanged');
        });

        mw.templatePreview<?php print $rand; ?>.generate();

    });

</script>
<?php if ($template_selector_position != 'none'): ?>


    <?php if (isset($params['small'])): ?>


        &nbsp;
        <!--<span onclick="$('.layouts_box_holder_small').toggleClass('semi_hidden');" class="mw-ui-btn mw-ui-btn-small" style="top: -3px;">--><?php //_e("Change template"); ?><!--</span>-->

    <?php else: ?>


        <!--    <div class="mw-ui-row ">
            <div class="mw-ui-col">
                <h2><span class="mai-templates"></span><?php /*_e("Choose template"); */ ?></h2>
            </div>
        </div>
-->

    <?php endif; ?>

<?php endif ?>

<div class="layout_selector_wrap admin-side-content">

    <?php
    if (defined('ACTIVE_SITE_TEMPLATE')) {

        if (!isset($data['active_site_template']) or (isset($data['active_site_template']) and trim($data['active_site_template']) == '' and defined('ACTIVE_SITE_TEMPLATE'))) {
            $data['active_site_template'] = ACTIVE_SITE_TEMPLATE;
        }
    }

    $global_template = get_option('current_template', 'template');
    if ($global_template == false) {
        $global_template = 'default';
    }

    $default_value_on_match = 'default';
    if (isset($params['no-default-name'])) {
        if ($data['active_site_template'] != 'default') {
            $default_value_on_match = $data['active_site_template'];

        } else {
            $default_value_on_match = $global_template;

        }
    }


    ?>
    <?php
    $is_layout_file_set = false;


    if (isset($data['layout_file']) and ('' != trim($data['layout_file']))): ?>
        <?php

        $is_layout_file_set = 1;
        if ($data['layout_file'] == 'inherit') {
            if (isset($params["layout_file"]) and trim($params["layout_file"]) != '') {
                $data['layout_file'] = $params["layout_file"];

            } else {
                $is_layout_file_set = 1;
            }
        }


        $data['layout_file'] = normalize_path($data['layout_file'], false);
        $data['layout_file'] = module_name_encode($data['layout_file']);

        ?>
    <?php endif; ?>

    <?php if (isset($data['layout_file']) and $data['layout_file'] == false) {
        $is_layout_file_set = 1;
        $data['layout_file'] = 'inherit';
    }

    $is_chosen = false;
    ?>

    <?php if ($template_selector_position != 'none'): ?>
        <div class="layouts_box_holder <?php if (isset($params['small'])): ?>layouts_box_holder_small<?php else: ?>layouts_box_holder_big<?php endif; ?>">
            <div class="mw-ui-row">
                <div class="mw-ui-col">
                    <div class="mw-ui-col-container">
                        <div class="section-header">
                            <h2 class="pull-left"><span class="mw-icon-users"></span><?php _e("Choose Page Layout"); ?></h2>
                        </div>


                        <div class="layouts_box_container">
                            <select name="preview_layout_file" class="mw-ui-field mw-edit-page-layout-selector w100" id="active_site_layout_<?php print $rand; ?>" autocomplete="off">
                                <?php if (!empty($layouts)): ?>
                                    <?php $i = 0;
                                    $is_chosen = false;
                                    foreach ($layouts as $item): ?>
                                        <?php $item['layout_file'] = normalize_path($item['layout_file'], false); ?>
                                        <?php $item['layout_file'] = module_name_encode($item['layout_file']); ?>
                                        <option value="<?php print $item['layout_file'] ?>"
                                                onclick="mw.templatePreview<?php print $rand; ?>.view('<?php print $i ?>');"
                                                data-index="<?php print $i ?>"
                                                data-layout_file="<?php print $item['layout_file'] ?>"
                                            <?php if (crc32(trim($item['layout_file'])) == crc32(trim($data['layout_file']))): ?><?php $is_chosen = 1; ?>  selected="selected"  <?php endif; ?>
                                            <?php if (isset($item['is_default']) and $item['is_default'] != false): ?>
                                                data-is-default="<?php print $item['is_default'] ?>" <?php if ($is_layout_file_set == false and $is_chosen == false): ?>   selected="selected" <?php $is_chosen = 1; ?><?php endif; ?><?php endif; ?>
                                            <?php if (isset($item['is_recomended']) and $item['is_recomended'] != false): ?>   data-is-is_recomended="<?php print $item['is_recomended'] ?>" <?php if ($is_layout_file_set == false and $is_chosen == false): ?>   selected="selected" <?php $is_chosen = 1; ?><?php endif; ?><?php endif; ?>
                                            <?php if (isset($item['content_type'])): ?>   data-content-type="<?php print $item['content_type'] ?>" <?php else: ?> data-content-type="static"  <?php endif; ?>
                                            <?php if (isset($item['is_shop'])): ?>   data-is-shop="<?php print $item['is_shop'] ?>"  <?php endif; ?>
                                            <?php if (isset($item['name'])): ?>   title="<?php print $item['name'] ?>"  <?php endif; ?>
                                            <?php if (isset($item['tag'])): ?>   data-tag="<?php print $item['tag'] ?>"  <?php endif; ?>
                                            <?php if (isset($item['subtype'])): ?>   data-subtype="<?php print $item['subtype'] ?>"  <?php endif; ?>
                                            <?php if (isset($item['subtype_value'])): ?>   data-subtype-value="<?php print $item['subtype_value'] ?>"  <?php endif; ?>
                                        >
                                            <?php print $item['name'] ?>
                                        </option>
                                        <?php $i++; endforeach; ?>
                                <?php endif; ?>
                                <?php if (!isset($params['content-type'])): ?>
                                    <option title="<?php _e("Inherit"); ?>" <?php if (isset($inherit_from) and isset($inherit_from['id'])): ?>  inherit_from="<?php print $inherit_from['id'] ?>"  <?php endif; ?>
                                            value="inherit" <?php if ($is_chosen == false and (trim($data['layout_file']) == '' or trim($data['layout_file']) == 'inherit')): ?>   selected="selected"  <?php endif; ?>>
                                        <?php _e("Inherit"); ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <script>
                    $(document).ready(function () {
                        $(document).ready(function () {
                            setTimeout(function () {
                                $('#content-title-field').focus();
                            }, 100);
                        });
                    });
                </script>
                <?php if ($template_selector_position == 'top'): ?>
                    <div class="mw-ui-col js-template-selector" <?php if (isset($params['small'])): ?>style="display: none;"<?php endif; ?>>
                        <div class="mw-ui-col-container">
                            <div class="mw-template-selector">
                                <div class="admin-section-bar">
                                    <h2 class="inline-element"><span class="mw-icon-template"></span><?php _e("Template"); ?></h2> &nbsp;
                                    <small>(This change will be affected only on the current page.)</small>
                                </div>

                                <?php if ($templates != false and !empty($templates)): ?>
                                    <select name="active_site_template" id="active_site_template_<?php print $rand; ?>"
                                            class="mw-edit-page-template-selector mw-ui-field w100">
                                        <?php foreach ($templates as $item): ?>
                                            <?php
                                            if ($global_template != 'default' and $item['dir_name'] == 'default') {
                                                $item['dir_name'] = 'mw_default';

                                            }
                                            $selected = false;
                                            $attrs = '';
                                            foreach ($item as $k => $v): ?>
                                                <?php if (is_string($v)): ?>
                                                    <?php $attrs .= "data-$k='{$v}'"; ?>
                                                <?php endif ?>
                                            <?php endforeach ?>

                                            <?php if (trim($item['dir_name']) == $global_template and $item['dir_name'] != 'default'): ?>
                                                <option value="<?php print $default_value_on_match; ?>" <?php if ($item['dir_name'] == $data['active_site_template'] and trim($data['active_site_template']) == $global_template): ?>   selected="selected" <?php $selected = true; ?><?php endif; ?>   <?php print $attrs; ?> > <?php print $item['name'] ?> </option>
                                            <?php else: ?>
                                                <option value="<?php print $item['dir_name'] ?>" <?php if ($selected == false and $item['dir_name'] == $data['active_site_template']): ?>   selected="selected"  <?php endif; ?>   <?php print $attrs; ?> > <?php print $item['name'] ?> </option>
                                            <?php endif ?>
                                        <?php endforeach; ?>
                                        <option value="default">default</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <?php if (isset($params['small'])): ?>
                    <div class="mw-ui-col" style="width: 36px;">
                        <button class="mw-ui-btn mw-ui-btn-info mw-ui-btn-outline js-show-template-selector tip" title="<?php _e('Change page template'); ?>" type="button" style="margin-top: 38px; text-align: center;"><i class="mw-icon-template m-0"></i></button>
                    </div>

                    <script>
                        $(document).ready(function () {
                            $('.js-show-template-selector').on('click', function () {
                                if ($('.js-template-selector').is(':visible')) {
                                    $('.js-template-selector').hide();

                                } else {
                                    $('.js-template-selector').show();

                                }
                            })
                        })
                    </script>
                <?php endif; ?>
                <?php endif; ?>




                <?php if ($template_selector_position == 'bottom'): ?>
                    <div class="mw-ui-col">
                        <div class="mw-ui-col-container">
                            <div class="mw-template-selector"
                                 style="<?php if (isset($params['small'])): ?>display:none;<?php endif; ?>">
                                <label class="mw-ui-label">
                                    <?php _e("Template"); ?>
                                </label>
                                <?php if ($templates != false and !empty($templates)): ?>
                                    <span class="page-template-config">
                                        <span id="selected-template-span-val"><?php print $global_template; ?></span>
                                        <span class="mw-icon-gear tip" data-tip="<?php _e("Click to change"); ?>" data-tipposition="top-center">

                                        <select name="preview_active_site_template" class="mw-ui-field mw-edit-page-template-selector"
                                                id="active_site_template_<?php print $rand; ?>">
                                            <?php foreach ($templates as $item): ?>
                                                <?php
                                                if ($global_template != 'default' and $item['dir_name'] == 'default') {
                                                    $item['dir_name'] = 'mw_default';
                                                }
                                                $selected = false;
                                                $attrs = '';

                                                foreach ($item as $k => $v): ?>
                                                    <?php if (is_string($v)): ?>
                                                        <?php $attrs .= "data-$k='{$v}'"; ?>
                                                    <?php endif ?>
                                                <?php endforeach ?>
                                                <?php if (trim($item['dir_name']) == $global_template and $item['dir_name'] != 'default'): ?>
                                                    <option
                                                            value="<?php print $default_value_on_match; ?>" <?php if ($item['dir_name'] == $data['active_site_template'] and trim($data['active_site_template']) == $global_template): ?>   selected="selected" <?php $selected = true; ?><?php endif; ?>   <?php print $attrs; ?> > <?php print $item['name'] ?> </option>
                                                <?php else: ?>
                                                    <option
                                                            value="<?php print $item['dir_name'] ?>" <?php if ($selected == false and $item['dir_name'] == $data['active_site_template']): ?>   selected="selected"  <?php endif; ?>   <?php print $attrs; ?> > <?php print $item['name'] ?> </option>
                                                <?php endif ?>
                                            <?php endforeach; ?>
                                            <option value="default">default</option>
                                        </select>
                                        </span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="<?php if ($template_selector_position != 'none'): ?>left<?php endif; ?>">
        <div class="preview_frame_wrapper loading left">
            <?php if (!isset($params['edit_page_id'])): ?>

                <span class="previewctrl prev" title="<?php _e('Previous layout'); ?>"
                      onclick="mw.templatePreview<?php print $rand; ?>.prev();"></span>
                <span class="previewctrl next" title="<?php _e('Next layout'); ?>"
                      onclick="mw.templatePreview<?php print $rand; ?>.next();"></span>


            <?php else : ?>
                <div class="preview_frame_ctrls" style="display:none;">
                    <h2>
                        <span class="mw-icon-<?php print $data['content_type']; ?>"></span><?php print $data['title']; ?>
                    </h2>
                    <a class="mw-ui-btn mw-ui-btn-medium" target="_top"
                       href="#action=editpage:<?php print $params["edit_page_id"]; ?>">
                        <?php _e("Edit Page"); ?>
                    </a>
                    <a class="mw-ui-btn mw-ui-btn-medium mw-ui-btn-blue" target="_top"
                       href="<?php print content_link($params["edit_page_id"]); ?>/editmode:y">
                        <?php _e("Go Live Edit"); ?>
                    </a>
                    <span class="mw-icon-close" title="<?php _e('Close'); ?>"
                          onclick="mw.templatePreview<?php print $rand; ?>.zoom();mw.$('.mw_overlay').remove();"></span>
                </div>
            <?php endif; ?>
            <div class="preview_frame_container"></div>
            <?php if (!isset($params['edit_page_id'])): ?>
                <?php /* <div class="mw-overlay" onclick="mw.templatePreview<?php print $rand; ?>.zoom();">&nbsp;</div> */ ?>
            <?php else: ?>
                <div class="mw-overlay mw-overlay-quick-link" onclick="mw.templatePreview<?php print $rand; ?>.zoom();"
                     ondblclick="mw.url.windowHashParam('action', 'editpage:<?php print $params["edit_page_id"]; ?>')">


                    <a class="live-block-btn" target="_top"
                       href="javascript:;"
                       onclick="window.top.mw.edit_content.handle_form_submit(true);event.stopPropagation()">

                        <?php _e("Go Live Edit"); ?>
                    </a>

                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($template_selector_position != 'none'): ?>


        <?php if ($live_edit_styles_check != false): ?>
            <module type="content/views/layout_selector_custom_css" id="layout_custom_css_clean<?php print $rand; ?>"
                    template="<?php print $data['active_site_template'] ?>"/>
        <?php endif; ?>
    <?php endif ?>

</div>