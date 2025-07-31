<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'smd_redirect';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.2.2';
$plugin['author'] = 'Stef Dawson';
$plugin['author_uri'] = 'https://stefdawson.com/';
$plugin['description'] = 'Redirect URLs from one place to another';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '1';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

$plugin['textpack'] = <<<EOT
#@smd_redir
#@language en, en-ca, en-gb, en-us
smd_redir_added => Redirect added
smd_redir_both => Both
smd_redir_btn_new => New redirect
smd_redir_deleting => Deleting...
smd_redir_destination => Destination
smd_redir_err_need_source => You must supply a source URL
smd_redir_saving => Saving...
smd_redir_search => Search
smd_redir_source => Source
smd_redir_tab_name => Redirects
smd_redir_updating => Updating...
#@language de
smd_redir_added => Weiterleitung hinzugefügt
smd_redir_both => Beides
smd_redir_btn_new => Neue Weiterleitung
smd_redir_deleting => Löschen...
smd_redir_destination => Ziel
smd_redir_err_need_source => Sie müssen eine Quell-URL angeben
smd_redir_saving => Speichern...
smd_redir_search => Suchen
smd_redir_source => Von
smd_redir_tab_name => Weiterleitungen
smd_redir_updating => Aktualisieren...
EOT;

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**
 * smd_redirect
 *
 * A Textpattern CMS plugin for redirecting URLs from place to place
 *
 * @author Stef Dawson
 * @link   https://stefdawson.com/
 */
if (txpinterface === 'admin') {
    global $smd_redir_event;
    $smd_redir_event = 'smd_redir';

    add_privs($smd_redir_event, '1');
    register_tab('extensions', $smd_redir_event, gTxt('smd_redir_tab_name'));
    register_callback('smd_redir_dispatcher', $smd_redir_event);
    register_callback('smd_redir_css', 'admin_side', 'head_end');
} elseif (txpinterface === 'public') {
    register_callback('smd_redirect', 'pretext_end');
}

/**
 * Jump off point for event/steps.
 *
 * @param string $evt Textpattern event
 * @param string $stp Textpattern step (action)
 */
function smd_redir_dispatcher($evt, $stp)
{
    global $smd_redir_event;

    $available_steps = array(
        'smd_redir'        => false,
        'smd_redir_create' => true,
        'smd_redir_save'   => true,
    );

    if (!$stp or !bouncer($stp, $available_steps)) {
        $stp = $smd_redir_event;
    }

    $stp();
}

/**
 * Render the plugin's CSS.
 *
 * @param string $evt Textpattern event
 * @param string $stp Textpattern step (action)
 */
function smd_redir_css($evt = '', $stp = '')
{
    global $event, $smd_redir_event;

    if ($event === $smd_redir_event) {
        $smd_redir_styles = array(
            'list' =>
             '.smd_hidden { display:none; }
              #smd_redirects { padding:0; margin:0 auto; width:auto; list-style-type:none; font-size: 13px; }
              #smd_redir_form .txp-form-field { gap: 1em; }
              @media (max-width: 47em) {
                  #smd_redir_form input[type=text] {
                  width: 100%;
                }
              }
              #smd_redir_filtform { text-align: end; }
              #smd_redirects ul { background-color: var(--clr-bkgd); }
              #smd_redirects li:nth-child(2n) { background-color: var(--clr-bkgd-box); }
              .smd_redir_title_bar { background-color: var(--clr-grad-to);
                  background-image: linear-gradient(var(--clr-grad-from),var(--clr-grad-to));
                  border-bottom: 1px solid var(--clr-brdr-dark); border-left: 0; border-right: 1px solid var(--clr-brdr); border-top: 1px solid var(--clr-brdr-lite); font-weight: bold;}
              #smd_redirects li .smd_redir_src:hover { cursor: pointer; }
              #smd_redirects li { display:grid; grid-template-columns: 3.5rem 1fr; align-items: center; margin-block-end: -1px; padding: .625em 0; color: var(--clr-text); border: 1px solid var(--clr-brdr-lite); }
              .smd_redir_item input { margin-block-end: .376923em; }
              @media (min-width: 47em) {
                #smd_redirects li .smd_redir_dest { display:flex; gap: 1rem; align-items: center; justify-content: space-between; margin-inline-end: 1rem; }
                .smd_redir_item { display:grid; grid-template-columns: 1fr 1fr; }
                .smd_redir_item input { margin-block-end: 0; }
                .smd_redir_hide-on-desktop { display: none; }
              }
              #smd_redir_save { margin-inline-end: 0.15625em; }
              button.success:hover .ui-icon, .success.ui-icon:hover {
                  filter: invert(36%) sepia(80%) saturate(582%) hue-rotate(72deg) brightness(97%) contrast(86%) !important;
              }
              .smd_redir_item input { width:70%; }
              .smd_redir_grab { text-align: center; opacity: 0.66; }
              .placeHolder div { background-color:white !important; border:dashed 1px gray !important; }'
        );

        if (class_exists('\Textpattern\UI\Style')) {
            echo Txp::get('\Textpattern\UI\Style')->setContent($smd_redir_styles['list']);
        } else {
            echo '<style>' . $smd_redir_styles['list'] . '</style>';
        }
    }
}

/**
 * Main admin interface.
 *
 * @param string $msg Status message to display
 */
function smd_redir($msg = '')
{
    global $smd_redir_event, $smd_redir_styles;

    pagetop(gTxt('smd_redir_tab_name'), $msg);

    // Grab the latest redirect points
    $redirects = smd_redir_get(1);

    $qs = array(
        "event" => $smd_redir_event,
    );

    $qsVars = "index.php" . join_qs($qs);

    // i18n values for javascript
    $red_src = gTxt('smd_redir_source');
    $red_dst = gTxt('smd_redir_destination');
    $red_del = gTxt('smd_redir_deleting');
    $red_sav = gTxt('smd_redir_saving');
    $red_upd = gTxt('smd_redir_updating');
    $red_btn_del_hint = gTxt('delete');
    $red_btn_sav_hint = gTxt('save');
    $red_btn_del = '<span class="ui-icon ui-icon-trash"></span> <span class="smd_redir_hide-on-desktop">'.gTxt('delete').'</span>';
    $red_btn_sav = '<span class="ui-icon ui-icon-check"></span> <span class="smd_redir_hide-on-desktop">'.gTxt('save').'</span>';

        echo script_js(<<<EOC
function smd_redir_togglenew() {
    // Revert any currently edited item first
    smd_redir_unedit();
    box = jQuery("#smd_redir_create");
    if (box.css("display") == "none") {
        box.show();
    } else {
        box.hide();
    }
    return false;
}

// Remove edit styling.
function smd_redir_unedit() {
    jQuery('#smd_redirects li.edited').each(function() {
        var me = jQuery(this);
        me.removeClass('edited').addClass('closed');
        ke = me.find('input[name="smd_redir_src_orig"]').val();
        vl = me.find('input[name="smd_redir_dest_orig"]').val();

        me.find('.smd_redir_item').html('<div class="smd_redir_src">' + ke + '</div><div class="smd_redir_dest">' + vl + '</div>');
    });
}

// Remove edit styling and save the current redirect items.
function smd_redir_save() {
    jQuery('#smd_redir_status').text('{$red_sav}');

    obj = jQuery('#smd_redirects li.edited');

    // Revert the input controls to regular text items
    ke = obj.find('input[name=smd_redir_src]').val();
    vl = obj.find('input[name=smd_redir_dest]').val();

    obj.find('.smd_redir_item').html('<div class="smd_redir_src">' + ke + '</div><div class="smd_redir_dest">' + vl + '</div>');
    obj.removeClass('edited').addClass('closed');

    smd_redir_post();
}

// Remove the edited item and save the remaining redirect items.
function smd_redir_delete() {
    jQuery('#smd_redir_status').text('{$red_del}');

    obj = jQuery('#smd_redirects li.edited');
    obj.remove();

    smd_redir_post();
}

// Save the redirect list to the prefs.
function smd_redir_post() {
    // Loop over the entire redirects collection, extract the original source, the new source and the destination,
    // then stuff them in a dedicated DOM element...
    var data = [];
    jQuery('#smd_redirects li').each(function(idx, obj) {
        var me = jQuery(obj);
        var orig = me.find('[name=smd_redir_src_orig]').val();
        if (me.hasClass('edited')) {
            var from = jQuery('#smd_redir_src').val();
            var dest = jQuery('#smd_redir_dest').val();
        } else {
            var from = me.find('.smd_redir_src').text();
            var dest = me.find('.smd_redir_dest').text();
        }

        if ((from && from.length > 0) && (dest && dest.length > 0)) {
            data.push({ orig: orig, from: from, dest: dest });
            // update original values in DOM after save
            me.find('[name=smd_redir_src_orig]').val(from);
            me.find('[name=smd_redir_dest_orig]').val(dest);
        }
    });

    // ... and send the entire lot off to be stored
    // TODO: handle timeout/failure etc
    jQuery.post('{$qsVars}', {
            step: "smd_redir_save",
            smd_redir_data: JSON.stringify(data),
            _txp_token : textpattern._txp_token
        },
        function(data) {

            jQuery('#smd_redir_status').text('');

            // Retrigger the search in case the results have changed after edit.
            jQuery('#smd_redir_search').keyup();
        }
    );
}

function smd_redir_filter(selector, query, nam, csense, exact) {
    var query = jQuery.trim(query);
    csense = (csense) ? "" : "i";
    query = query.replace(/ /gi, '|'); // add OR for regex query
    if (exact) {
        tmp = query.split('|');
        for (var idx = 0; idx < tmp.length; idx++) {
            tmp[idx] = '^'+tmp[idx]+'$';
        }
        query = tmp.join('|');
    }
    var re = new RegExp(query, csense);
    jQuery(selector).each(function() {
        sel = (typeof nam=="undefined" || nam=='' || nam=='smd_redir_both') ? jQuery(this) : jQuery(this).find("."+nam+"");
        if (query == '') {
            if (sel.length == 1 && sel.text() == '') {
                jQuery(this).show();
            } else {
                jQuery(this).hide();
            }
        } else {
            if (sel.text().search(re) < 0) {
                jQuery(this).hide();
            } else {
                jQuery(this).show();
            }
        }
    });
}

jQuery(function() {
    jQuery('.btnnew').on('click', function(ev) {
        smd_redir_togglenew();
    });

    jQuery("#smd_redirects").dragsort({
        dragSelector: ".smd_redir_grab",
        dragSelectorExclude: ".smd_redir_no_drag, input",
        itemSelector: "li:not(.smd_redir_no_drag)",
        dragEnd: function() {
            jQuery('#smd_redir_status').text('{$red_upd}');
            smd_redir_unedit(); // Remove any current edit status
            smd_redir_post(); // Update the list on the server with the new order
        },
        dragBetween: false,
        placeHolderTemplate: "<li class='placeHolder'><div></div></li>"
    });

    jQuery("#smd_redirects").on('click', '.btnaction', function() {
        var btn = jQuery(this);
        var wrapper = btn.parent();
        var itemSrc = wrapper.find('.smd_redir_src');
        var itemDest = wrapper.find('.smd_redir_dest');
        smd_redir_unedit();

        key = itemSrc.text();
        val = itemSrc.next().text();

        // edit action
        if (btn.hasClass('btnedit')) {
            wrapper.removeClass('closed');
            itemSrc.html('<label for="smd_redir_src" class="txp-accessibility">{$red_src}</label><input type="text" id="smd_redir_src" name="smd_redir_src" value="'+key+'">');
            itemDest.html('<label for="smd_redir_dest" class="txp-accessibility">{$red_dst}</label><input type="text" id="smd_redir_dest" name="smd_redir_dest" value="'+val+'">')
                .append('<div><button type="button" class="txp-button" id="smd_redir_save" name="smd_redir_save" onclick="smd_redir_save();" title="{$red_btn_sav_hint}">{$red_btn_sav}</button>&nbsp;<button type="button" class="txp-button" id="smd_redir_delete" name="smd_redir_delete" onclick="smd_redir_delete();" title="{$red_btn_del_hint}">{$red_btn_del}</button>');
            wrapper.addClass('edited');
            itemSrc.find('input[name="smd_redir_src"]').focus();
        }
        // close action = exit without saving
        if (btn.hasClass('btnclose')) {
            // Reset field values to pre-edited state
            var origSrc = wrapper.find('[name=smd_redir_src_orig]').val();
            var origDest = wrapper.find('[name=smd_redir_dest_orig]').val();
            jQuery('#smd_redir_src').val(origSrc);
            jQuery('#smd_redir_dest').val(origDest);
            // Return to unedited state
            smd_redir_unedit();
        }
    });

    // Search panel
    jQuery("#smd_redir_search").on('input', function(event) {
        // If esc is pressed or nothing is entered

        if (event.keyCode == 27 || jQuery(this).val() == '') {
            jQuery(this).val('');
            jQuery("#smd_redirects li").show();
            jQuery('.txp-search-clear').addClass('ui-helper-hidden');
        } else {
            smd_redir_filter('#smd_redirects li', jQuery(this).val(), jQuery("#smd_redir_filt").val(), 0, 0);
            jQuery('.txp-search-clear').removeClass('ui-helper-hidden');
        }
    });

    // Search filter dropdown
    jQuery("#smd_redir_filt").change(function(event) {
        if (jQuery('#smd_redir_search').val() == '') {
            jQuery("#smd_redirects li").show();
        } else {
            smd_redir_filter('#smd_redirects li', jQuery("#smd_redir_search").val(), jQuery(this).val(), 0, 0);
        }
    });
});
EOC);

    // Inject Drag n drop jQuery interface
    echo smd_redir_dragdrop();

    $ftypes = array(
        'smd_redir_src'  => gTxt('smd_redir_source'),
        'smd_redir_dest' => gTxt('smd_redir_destination'),
        'smd_redir_both' => gTxt('smd_redir_both'),
    );

    // Search by redirect block
    $searchForm = form(
        span(
            href(gTxt('search_clear'), array('event' => $smd_redir_event)),
            array('class' => 'txp-search-clear ui-helper-hidden')
        ) .
        tag (
            gTxt('smd_redir_search'),
            'label', array('for' => 'crit')
        ) . n .
        tag(
            selectInput('smd_redir_filt', $ftypes, '', 0, '', 'smd_redir_filt'),
            'span', array('id' => 'smd_redir_searchby')
        ) . n .
        fInput('search', 'crit', '', '', '', '', '', '', 'smd_redir_search') . n .
        eInput($smd_redir_event) . sInput('smd_redir_filter')
        , '', '', 'post', '', '', 'smd_redir_filtform'
    );

    $searchBlock =
    n . tag(
        $searchForm,
        'div', array(
                'class' => 'txp-layout-4col-3span',
                'id'    => $smd_redir_event . '_control',
            )
        );

    // Add new redirect block
    $createForm = tag(
        form(
            inputLabel(
                'smd_redir_newsource',
                fInput('text', 'smd_redir_newsource', '', '', '', '', INPUT_LARGE, '', 'smd_redir_newsource'),
                gTxt('smd_redir_source'), '', array('class' => 'txp-form-field smd_redir_newsource')
            ).
            inputLabel(
                'smd_redir_destination',
                fInput('text', 'smd_redir_destination', '', '', '', '', INPUT_LARGE, '', 'smd_redir_destination'),
                gTxt('smd_redir_destination'), '', array('class' => 'txp-form-field smd_redir_destination')
            ).
            tag(
                fInput('submit', 'smd_redir_add', gTxt('add'), 'publish', '', '', '', '', 'smd_redir_add').
                eInput($smd_redir_event).
                sInput('smd_redir_create').
                tInput(),
                'p',
                array(
                    'class' => 'txp-edit-actions'
                )
            ),
            '', '', 'post', 'txp-edit', '', 'smd_redir_form'
        ),
        'div',
        array(
            'class' => 'txp-control-panel smd_hidden',
            'id' => 'smd_redir_create'
        )
    );

    $createBlock = n . tag(
        tag(
            gTxt('smd_redir_btn_new'),
            'a', array(
                'href' => '#',
                'class' => 'txp-button btnnew'
            )
        ) .
        tag(
            '&nbsp;',
            'span', array('id' => 'smd_redir_status')
        ) . n .
        $createForm,
        'div',
        array('class' => 'txp-control-panel')
    );

    // Redirects list
    $contentBlock = tag_start('ul', array('id' => 'smd_redirects'));

        // Pseudo table header
        $contentBlock .= tag(
            span(' ') . tag(
                span(gTxt('smd_redir_source')) . span(gTxt('smd_redir_destination')),
                'div',
                array('class' => 'smd_redir_item')
            ),
            'li',
            array(
                'class' => 'smd_redir_title_bar smd_redir_no_drag'
            )
        );

    foreach ($redirects as $idx => $items) {
        // Redirect list items
        $contentBlock .= tag(
            span(
                '&#9776;',
                array(
                    'class' => 'smd_redir_grab'
                )
            ) . n .
            hInput('smd_redir_src_orig', $items['src']) . n .
            hInput('smd_redir_dest_orig', $items['dst']) . n .
            tag(
                tag(
                    $items['src'],
                    'div',
                    array('class' => 'smd_redir_src')
                ) . n .
                tag(
                    $items['dst'],
                    'div',
                    array('class' => 'smd_redir_dest')
                ),
                'div',
                array('class' => 'smd_redir_item')
            ) . n .
            tag(
                span(' ', array(
                        'class' => 'ui-icon ui-icon-pencil'
                    )
                ) .
                span(
                    gTxt('edit'),
                    array(
                        'class' => 'smd_redir_hide-on-desktop'
                    )
                ),
                'button', array(
                    'href' => '#',
                    'class' => 'txp-reduced-ui-button btnaction btnedit'
                )
            )
            . n .
            tag(
                span(' ', array(
                        'class' => 'ui-icon ui-icon-close'
                    )
                ) .
                span(
                    gTxt('close'),
                    array(
                        'class' => 'smd_redir_hide-on-desktop'
                    )
                ),
                'button', array(
                    'href' => '#',
                    'class' => 'txp-reduced-ui-button btnaction btnclose'
                )
            ),
            'li',
            array(
                'class' => 'closed'
            )
        );
    }

    $contentBlock .= n . tag_end('ul');

    // Render the page UI
    $out = n . '<div class="txp-layout">' .
    n . tag(
        hed(gTxt('smd_redir_tab_name'), 1, array('class' => 'txp-heading')),
        'div', array('class' => 'txp-layout-4col-alt')
    ) . n . $searchBlock;

    $out .= tag_start('div', array(
        'class' => 'txp-layout-1col',
        'id'    => $smd_redir_event . '_container',
    )).
    n.tag($createBlock, 'div', array('class' => 'txp-layout-cell-row txp-list-head'));

    $out .= $contentBlock;

    $out .= n . tag_end('div') .  // End of .txp-layout-1col
            n . tag_end('div');   // End of .txp-layout

    echo $out;
}

/**
 * Create a redirect from the admin side's 'New' button.
 */
function smd_redir_create()
{
    extract(gpsa(array('smd_redir_newsource', 'smd_redir_destination')));

    $out = array();

    if ($smd_redir_newsource) {
        $redirects = smd_redir_get(0);

        $found = 0;

        foreach ($redirects as $idx => $items) {
            if ($items['src'] != $smd_redir_newsource) {
                // Let existing rules through
                $out[] = array('src' => $items['src'], 'dst' => $items['dst']);
            } else {
                // Update to an existing rule
                $out[] = array('src' => $items['src'], 'dst' => $smd_redir_destination);
                $found++;
            }
        }

        // Redirect doesn't already exist so add it.
        if ($found === 0) {
            $out[] = array('src' => $smd_redir_newsource, 'dst' => $smd_redir_destination);
        }

        set_pref('smd_redirects', smd_redir_serialize($out), 'smd_redir', PREF_HIDDEN, '', 0);

        $msg = gTxt('smd_redir_added');
    } else {
        $msg = array(gTxt('smd_redir_err_need_source'), E_ERROR);
    }

    smd_redir($msg);
}

/**
 * Save the given list of redirects to the prefs array.
 */
function smd_redir_save()
{
    $data = json_decode(ps('smd_redir_data'), true);
    $out = array();

    foreach($data as $items) {
        $out[] = array('src' => $items['from'], 'dst' => $items['dest']);
    }

    set_pref('smd_redirects', smd_redir_serialize($out), 'smd_redir', PREF_HIDDEN, '', 0);
    send_xml_response();
    exit;
}

/**
 * Fetch the list of redirects currently in force.
 *
 * @param int $force Whether to force fetching the prefs from the DB to prevent stale values
 */
function smd_redir_get($force = 0)
{
    $redirects = get_pref('smd_redirects', array(), $force);
    $redirects = ($redirects) ? smd_redir_unserialize($redirects) : array();

    return $redirects;
}

/**
 * Safe-ify saved pref values, since we're dealing with preg_match patterns.
 */
function smd_redir_serialize($obj)
{
    $crush = smd_redir_check_crush();
    return chunk_split(base64_encode((($crush) ? gzcompress(serialize($obj)) : serialize($obj))));
}

/**
 * Retrieve (unescape) saved pref values for display purposes.
 */
function smd_redir_unserialize($txt)
{
    $crush = smd_redir_check_crush();
    return unserialize((($crush) ? gzuncompress(base64_decode($txt)) : base64_decode($txt)));
}

/**
 * Check if the ability to compress (gzip) content is available.
 *
 * @return bool
 */
function smd_redir_check_crush()
{
    return (function_exists('gzcompress') && function_exists('gzuncompress'));
}

/**
 * A base64-encoded version of DragSort (https://github.com/agavazov/dragsort).
 */
function smd_redir_dragdrop()
{
    return script_js(base64_decode('
IWZ1bmN0aW9uKGUpe2UuZm4uZHJhZ3NvcnQ9ZnVuY3Rpb24odCl7aWYoImRlc3Ryb3kiIT09dCl7
dmFyIG89ZS5leHRlbmQoe30sZS5mbi5kcmFnc29ydC5kZWZhdWx0cyx0KSxyPVtdLGE9bnVsbCxp
PW51bGw7cmV0dXJuIHRoaXMuZWFjaChmdW5jdGlvbih0LG4pe2UobikuaXMoInRhYmxlIikmJjE9
PT1lKG4pLmNoaWxkcmVuKCkubGVuZ3RoJiZlKG4pLmNoaWxkcmVuKCkuaXMoInRib2R5IikmJihu
PWUobikuY2hpbGRyZW4oKS5nZXQoMCkpO3ZhciBsPXtkcmFnZ2VkSXRlbTpudWxsLHBsYWNlSG9s
ZGVySXRlbTpudWxsLHBvczpudWxsLG9mZnNldDpudWxsLG9mZnNldExpbWl0Om51bGwsc2Nyb2xs
Om51bGwsY29udGFpbmVyOm4saW5pdDpmdW5jdGlvbigpe28udGFnTmFtZT0wPT09ZSh0aGlzLmNv
bnRhaW5lcikuY2hpbGRyZW4oKS5sZW5ndGg/ImxpIjplKHRoaXMuY29udGFpbmVyKS5jaGlsZHJl
bigpLmdldCgwKS50YWdOYW1lLnRvTG93ZXJDYXNlKCksby5pdGVtU2VsZWN0b3J8fChvLml0ZW1T
ZWxlY3Rvcj1vLnRhZ05hbWUpLG8uZHJhZ1NlbGVjdG9yfHwoby5kcmFnU2VsZWN0b3I9by50YWdO
YW1lKSxvLnBsYWNlSG9sZGVyVGVtcGxhdGV8fChvLnBsYWNlSG9sZGVyVGVtcGxhdGU9IjwiK28u
dGFnTmFtZSsiPiZuYnNwOzwvIitvLnRhZ05hbWUrIj4iKSxlKHRoaXMuY29udGFpbmVyKS5hZGRD
bGFzcyhvLmluaXRDbGFzcykuYXR0cigiZGF0YS1saXN0aWR4Iix0KS5vbigibW91c2Vkb3duIHRv
dWNoc3RhcnQiLHRoaXMuZ3JhYkl0ZW0pLm9uKCJkcmFnc29ydC11bmluaXQiLHRoaXMudW5pbml0
KSx0aGlzLnN0eWxlRHJhZ0hhbmRsZXJzKCEwKX0sdW5pbml0OmZ1bmN0aW9uKCl7dmFyIHQ9cltl
KHRoaXMpLmF0dHIoImRhdGEtbGlzdGlkeCIpXTtlKHQuY29udGFpbmVyKS5vZmYoIm1vdXNlZG93
biB0b3VjaHN0YXJ0Iix0LmdyYWJJdGVtKS5vZmYoImRyYWdzb3J0LXVuaW5pdCIpLHQuc3R5bGVE
cmFnSGFuZGxlcnMoITEpfSxnZXRJdGVtczpmdW5jdGlvbigpe3JldHVybiBlKHRoaXMuY29udGFp
bmVyKS5jaGlsZHJlbihvLml0ZW1TZWxlY3Rvcil9LHN0eWxlRHJhZ0hhbmRsZXJzOmZ1bmN0aW9u
KHQpe3RoaXMuZ2V0SXRlbXMoKS5tYXAoZnVuY3Rpb24oKXtyZXR1cm4gZSh0aGlzKS5pcyhvLmRy
YWdTZWxlY3Rvcik/dGhpczplKHRoaXMpLmZpbmQoby5kcmFnU2VsZWN0b3IpLmdldCgpfSkuY3Nz
KCJjdXJzb3IiLHQ/by5jdXJzb3I6ImRlZmF1bHQiKX0sZ3JhYkl0ZW06ZnVuY3Rpb24odCl7dmFy
IGE9cltlKHRoaXMpLmF0dHIoImRhdGEtbGlzdGlkeCIpXSxpPWUodC50YXJnZXQpLmNsb3Nlc3Qo
IltkYXRhLWxpc3RpZHhdID4gIitvLnRhZ05hbWUpLmdldCgwKSxuPWEuZ2V0SXRlbXMoKS5maWx0
ZXIoZnVuY3Rpb24oKXtyZXR1cm4gdGhpcz09aX0pLmxlbmd0aD4wO2lmKCEoMSE9PXQud2hpY2gm
JjAhPT10LndoaWNofHxlKHQudGFyZ2V0KS5pcyhvLmRyYWdTZWxlY3RvckV4Y2x1ZGUpfHxlKHQu
dGFyZ2V0KS5jbG9zZXN0KG8uZHJhZ1NlbGVjdG9yRXhjbHVkZSkubGVuZ3RoPjApJiZuKXt0LnBy
ZXZlbnREZWZhdWx0KCk7Zm9yKHZhciBsPXQudGFyZ2V0OyFlKGwpLmlzKG8uZHJhZ1NlbGVjdG9y
KTspe2lmKGw9PXRoaXMpcmV0dXJuO2w9bC5wYXJlbnROb2RlfWUobCkuYXR0cigiZGF0YS1jdXJz
b3IiLGUobCkuY3NzKCJjdXJzb3IiKSksZShsKS5jc3MoImN1cnNvciIsIm1vdmUiKTt2YXIgZD10
aGlzLHM9ZnVuY3Rpb24oKXthLmRyYWdTdGFydC5jYWxsKGQsdCksZShhLmNvbnRhaW5lcikub2Zm
KCJtb3VzZW1vdmUgdG91Y2htb3ZlIixzKX07ZShhLmNvbnRhaW5lcikub24oIm1vdXNlbW92ZSB0
b3VjaG1vdmUiLHMpLm9uKCJtb3VzZXVwIHRvdWNoZW5kIixmdW5jdGlvbigpe2UoYS5jb250YWlu
ZXIpLm9mZigibW91c2Vtb3ZlIHRvdWNobW92ZSIscyksZShsKS5jc3MoImN1cnNvciIsZShsKS5h
dHRyKCJkYXRhLWN1cnNvciIpKX0pfX0sZHJhZ1N0YXJ0OmZ1bmN0aW9uKHQpe251bGwhPWEmJm51
bGwhPWEuZHJhZ2dlZEl0ZW0mJmEuZHJvcEl0ZW0oKSx0LmNoYW5nZWRUb3VjaGVzJiZ0LmNoYW5n
ZWRUb3VjaGVzWzBdJiYodC5wYWdlWD10LmNoYW5nZWRUb3VjaGVzWzBdLnBhZ2VYLHQucGFnZVk9
dC5jaGFuZ2VkVG91Y2hlc1swXS5wYWdlWSksKGE9cltlKHRoaXMpLmF0dHIoImRhdGEtbGlzdGlk
eCIpXSkuZHJhZ2dlZEl0ZW09ZSh0LnRhcmdldCkuY2xvc2VzdCgiW2RhdGEtbGlzdGlkeF0gPiAi
K28udGFnTmFtZSksYS5kcmFnZ2VkSXRlbS5hdHRyKCJkYXRhLW9yaWdwb3MiLGUodGhpcykuYXR0
cigiZGF0YS1saXN0aWR4IikrIi0iK2UoYS5jb250YWluZXIpLmNoaWxkcmVuKCkuaW5kZXgoYS5k
cmFnZ2VkSXRlbSkpO3ZhciBpPXBhcnNlSW50KGEuZHJhZ2dlZEl0ZW0uY3NzKCJtYXJnaW5Ub3Ai
KSksbj1wYXJzZUludChhLmRyYWdnZWRJdGVtLmNzcygibWFyZ2luTGVmdCIpKTtpZihhLm9mZnNl
dD1hLmRyYWdnZWRJdGVtLm9mZnNldCgpLGEub2Zmc2V0LnRvcD10LnBhZ2VZLWEub2Zmc2V0LnRv
cCsoaXNOYU4oaSk/MDppKS0xLGEub2Zmc2V0LmxlZnQ9dC5wYWdlWC1hLm9mZnNldC5sZWZ0Kyhp
c05hTihuKT8wOm4pLTEsIW8uZHJhZ0JldHdlZW4pe3ZhciBsPTA9PWUoYS5jb250YWluZXIpLm91
dGVySGVpZ2h0KCk/TWF0aC5tYXgoMSxNYXRoLnJvdW5kKC41K2EuZ2V0SXRlbXMoKS5sZW5ndGgq
YS5kcmFnZ2VkSXRlbS5vdXRlcldpZHRoKCkvZShhLmNvbnRhaW5lcikub3V0ZXJXaWR0aCgpKSkq
YS5kcmFnZ2VkSXRlbS5vdXRlckhlaWdodCgpOmUoYS5jb250YWluZXIpLm91dGVySGVpZ2h0KCk7
YS5vZmZzZXRMaW1pdD1lKGEuY29udGFpbmVyKS5vZmZzZXQoKSxhLm9mZnNldExpbWl0LnJpZ2h0
PWEub2Zmc2V0TGltaXQubGVmdCtlKGEuY29udGFpbmVyKS5vdXRlcldpZHRoKCktYS5kcmFnZ2Vk
SXRlbS5vdXRlcldpZHRoKCksYS5vZmZzZXRMaW1pdC5ib3R0b209YS5vZmZzZXRMaW1pdC50b3Ar
bC1hLmRyYWdnZWRJdGVtLm91dGVySGVpZ2h0KCl9dmFyIGQ9YS5kcmFnZ2VkSXRlbS5oZWlnaHQo
KSxzPWEuZHJhZ2dlZEl0ZW0ud2lkdGgoKTtpZigidHIiPT09by50YWdOYW1lPyhhLmRyYWdnZWRJ
dGVtLmNoaWxkcmVuKCkuZWFjaChmdW5jdGlvbigpe2UodGhpcykud2lkdGgoZSh0aGlzKS53aWR0
aCgpKX0pLGEucGxhY2VIb2xkZXJJdGVtPWEuZHJhZ2dlZEl0ZW0uY2xvbmUoKS5hdHRyKCJkYXRh
LXBsYWNlaG9sZGVyIiwhMCksYS5kcmFnZ2VkSXRlbS5hZnRlcihhLnBsYWNlSG9sZGVySXRlbSks
YS5wbGFjZUhvbGRlckl0ZW0uY2hpbGRyZW4oKS5lYWNoKGZ1bmN0aW9uKCl7ZSh0aGlzKS5jc3Mo
e2JvcmRlcldpZHRoOjAsd2lkdGg6ZSh0aGlzKS53aWR0aCgpKzEsaGVpZ2h0OmUodGhpcykuaGVp
Z2h0KCkrMX0pLmh0bWwoIiZuYnNwOyIpfSkpOihhLmRyYWdnZWRJdGVtLmFmdGVyKG8ucGxhY2VI
b2xkZXJUZW1wbGF0ZSksYS5wbGFjZUhvbGRlckl0ZW09YS5kcmFnZ2VkSXRlbS5uZXh0KCkuY3Nz
KHtoZWlnaHQ6ZCx3aWR0aDpzfSkuYXR0cigiZGF0YS1wbGFjZWhvbGRlciIsITApKSwidGQiPT09
by50YWdOYW1lKXt2YXIgYz1hLmRyYWdnZWRJdGVtLmNsb3Nlc3QoInRhYmxlIikuZ2V0KDApO2Uo
Jzx0YWJsZSBpZD0iJytjLmlkKyciIHN0eWxlPSJib3JkZXItd2lkdGg6IDBweDsiIGNsYXNzPSJk
cmFnU29ydEl0ZW0gJytjLmNsYXNzTmFtZSsnIj48dHI+PC90cj48L3RhYmxlPicpLmFwcGVuZFRv
KCJib2R5IikuY2hpbGRyZW4oKS5hcHBlbmQoYS5kcmFnZ2VkSXRlbSl9dmFyIGc9YS5kcmFnZ2Vk
SXRlbS5hdHRyKCJzdHlsZSIpO2EuZHJhZ2dlZEl0ZW0uYXR0cigiZGF0YS1vcmlnc3R5bGUiLGd8
fCIiKSxhLmRyYWdnZWRJdGVtLmNzcyh7cG9zaXRpb246ImFic29sdXRlIixvcGFjaXR5Oi44LCJ6
LWluZGV4Ijo5OTksaGVpZ2h0OmQsd2lkdGg6cyxtYXJnaW46MH0pLGEuc2Nyb2xsPXttb3ZlWDow
LG1vdmVZOjAsbWF4WDplKGRvY3VtZW50KS53aWR0aCgpLWUod2luZG93KS53aWR0aCgpLG1heFk6
ZShkb2N1bWVudCkuaGVpZ2h0KCktZSh3aW5kb3cpLmhlaWdodCgpfSxhLnNjcm9sbC5zY3JvbGxZ
PXdpbmRvdy5zZXRJbnRlcnZhbChmdW5jdGlvbigpe2lmKG8uc2Nyb2xsQ29udGFpbmVyPT13aW5k
b3cpe3ZhciB0PWUoby5zY3JvbGxDb250YWluZXIpLnNjcm9sbFRvcCgpOyhhLnNjcm9sbC5tb3Zl
WT4wJiZ0PGEuc2Nyb2xsLm1heFl8fGEuc2Nyb2xsLm1vdmVZPDAmJnQ+MCkmJihlKG8uc2Nyb2xs
Q29udGFpbmVyKS5zY3JvbGxUb3AodCthLnNjcm9sbC5tb3ZlWSksYS5kcmFnZ2VkSXRlbS5jc3Mo
InRvcCIsYS5kcmFnZ2VkSXRlbS5vZmZzZXQoKS50b3ArYS5zY3JvbGwubW92ZVkrMSkpfWVsc2Ug
ZShvLnNjcm9sbENvbnRhaW5lcikuc2Nyb2xsVG9wKGUoby5zY3JvbGxDb250YWluZXIpLnNjcm9s
bFRvcCgpK2Euc2Nyb2xsLm1vdmVZKX0sMTApLGEuc2Nyb2xsLnNjcm9sbFg9d2luZG93LnNldElu
dGVydmFsKGZ1bmN0aW9uKCl7aWYoby5zY3JvbGxDb250YWluZXI9PXdpbmRvdyl7dmFyIHQ9ZShv
LnNjcm9sbENvbnRhaW5lcikuc2Nyb2xsTGVmdCgpOyhhLnNjcm9sbC5tb3ZlWD4wJiZ0PGEuc2Ny
b2xsLm1heFh8fGEuc2Nyb2xsLm1vdmVYPDAmJnQ+MCkmJihlKG8uc2Nyb2xsQ29udGFpbmVyKS5z
Y3JvbGxMZWZ0KHQrYS5zY3JvbGwubW92ZVgpLGEuZHJhZ2dlZEl0ZW0uY3NzKCJsZWZ0IixhLmRy
YWdnZWRJdGVtLm9mZnNldCgpLmxlZnQrYS5zY3JvbGwubW92ZVgrMSkpfWVsc2UgZShvLnNjcm9s
bENvbnRhaW5lcikuc2Nyb2xsTGVmdChlKG8uc2Nyb2xsQ29udGFpbmVyKS5zY3JvbGxMZWZ0KCkr
YS5zY3JvbGwubW92ZVgpfSwxMCksZShyKS5lYWNoKGZ1bmN0aW9uKGUsdCl7dC5jcmVhdGVEcm9w
VGFyZ2V0cygpLHQuYnVpbGRQb3NpdGlvblRhYmxlKCl9KSxhLnNldFBvcyh0LnBhZ2VYLHQucGFn
ZVkpLGUoZG9jdW1lbnQpLm9uKCJtb3VzZW1vdmUgdG91Y2htb3ZlIixhLnN3YXBJdGVtcyksZShk
b2N1bWVudCkub24oIm1vdXNldXAgdG91Y2hlbmQiLGEuZHJvcEl0ZW0pLG8uc2Nyb2xsQ29udGFp
bmVyIT13aW5kb3cmJmUod2luZG93KS5vbigid2hlZWwiLGEud2hlZWwpfSxzZXRQb3M6ZnVuY3Rp
b24odCxyKXt2YXIgaT1yLXRoaXMub2Zmc2V0LnRvcCxuPXQtdGhpcy5vZmZzZXQubGVmdDtvLmRy
YWdCZXR3ZWVufHwoaT1NYXRoLm1pbih0aGlzLm9mZnNldExpbWl0LmJvdHRvbSxNYXRoLm1heChp
LHRoaXMub2Zmc2V0TGltaXQudG9wKSksbj1NYXRoLm1pbih0aGlzLm9mZnNldExpbWl0LnJpZ2h0
LE1hdGgubWF4KG4sdGhpcy5vZmZzZXRMaW1pdC5sZWZ0KSkpO3ZhciBsPXRoaXMuZHJhZ2dlZEl0
ZW0ub2Zmc2V0UGFyZW50KCkubm90KCJib2R5Iikub2Zmc2V0KCk7aWYobnVsbCE9bCYmKGktPWwu
dG9wLG4tPWwubGVmdCksby5zY3JvbGxDb250YWluZXI9PXdpbmRvdylyLT1lKHdpbmRvdykuc2Ny
b2xsVG9wKCksdC09ZSh3aW5kb3cpLnNjcm9sbExlZnQoKSxyPU1hdGgubWF4KDAsci1lKHdpbmRv
dykuaGVpZ2h0KCkrNSkrTWF0aC5taW4oMCxyLTUpLHQ9TWF0aC5tYXgoMCx0LWUod2luZG93KS53
aWR0aCgpKzUpK01hdGgubWluKDAsdC01KTtlbHNle3ZhciBkPWUoby5zY3JvbGxDb250YWluZXIp
LHM9ZC5vZmZzZXQoKTtyPU1hdGgubWF4KDAsci1kLmhlaWdodCgpLXMudG9wKStNYXRoLm1pbigw
LHItcy50b3ApLHQ9TWF0aC5tYXgoMCx0LWQud2lkdGgoKS1zLmxlZnQpK01hdGgubWluKDAsdC1z
LmxlZnQpfWEuc2Nyb2xsLm1vdmVYPTA9PT10PzA6dCpvLnNjcm9sbFNwZWVkL01hdGguYWJzKHQp
LGEuc2Nyb2xsLm1vdmVZPTA9PT1yPzA6cipvLnNjcm9sbFNwZWVkL01hdGguYWJzKHIpLHRoaXMu
ZHJhZ2dlZEl0ZW0uY3NzKHt0b3A6aSxsZWZ0Om59KX0sd2hlZWw6ZnVuY3Rpb24odCl7aWYoYSYm
by5zY3JvbGxDb250YWluZXIhPXdpbmRvdyl7dmFyIHI9ZShvLnNjcm9sbENvbnRhaW5lciksaT1y
Lm9mZnNldCgpO2lmKCh0PXQub3JpZ2luYWxFdmVudCkuY2xpZW50WD5pLmxlZnQmJnQuY2xpZW50
WDxpLmxlZnQrci53aWR0aCgpJiZ0LmNsaWVudFk+aS50b3AmJnQuY2xpZW50WTxpLnRvcCtyLmhl
aWdodCgpKXt2YXIgbj0oMD09PXQuZGVsdGFNb2RlPzE6MTApKnQuZGVsdGFZO3Iuc2Nyb2xsVG9w
KHIuc2Nyb2xsVG9wKCkrbiksdC5wcmV2ZW50RGVmYXVsdCgpfX19LGJ1aWxkUG9zaXRpb25UYWJs
ZTpmdW5jdGlvbigpe3ZhciB0PVtdO3RoaXMuZ2V0SXRlbXMoKS5ub3QoW2EuZHJhZ2dlZEl0ZW1b
MF0sYS5wbGFjZUhvbGRlckl0ZW1bMF1dKS5lYWNoKGZ1bmN0aW9uKG8pe3ZhciByPWUodGhpcyku
b2Zmc2V0KCk7ci5yaWdodD1yLmxlZnQrZSh0aGlzKS5vdXRlcldpZHRoKCksci5ib3R0b209ci50
b3ArZSh0aGlzKS5vdXRlckhlaWdodCgpLHIuZWxtPXRoaXMsdFtvXT1yfSksdGhpcy5wb3M9dH0s
ZHJvcEl0ZW06ZnVuY3Rpb24oKXtpZihudWxsIT1hLmRyYWdnZWRJdGVtKXt2YXIgdD1hLmRyYWdn
ZWRJdGVtLmF0dHIoImRhdGEtb3JpZ3N0eWxlIik7aWYoYS5kcmFnZ2VkSXRlbS5hdHRyKCJzdHls
ZSIsdCksIiI9PXQmJmEuZHJhZ2dlZEl0ZW0ucmVtb3ZlQXR0cigic3R5bGUiKSxhLmRyYWdnZWRJ
dGVtLnJlbW92ZUF0dHIoImRhdGEtb3JpZ3N0eWxlIiksYS5zdHlsZURyYWdIYW5kbGVycyghMCks
YS5wbGFjZUhvbGRlckl0ZW0uYmVmb3JlKGEuZHJhZ2dlZEl0ZW0pLGEucGxhY2VIb2xkZXJJdGVt
LnJlbW92ZSgpLGUoIltkYXRhLWRyb3B0YXJnZXRdLCAuZHJhZ1NvcnRJdGVtIikucmVtb3ZlKCks
d2luZG93LmNsZWFySW50ZXJ2YWwoYS5zY3JvbGwuc2Nyb2xsWSksd2luZG93LmNsZWFySW50ZXJ2
YWwoYS5zY3JvbGwuc2Nyb2xsWCksYS5kcmFnZ2VkSXRlbS5hdHRyKCJkYXRhLW9yaWdwb3MiKSE9
ZShyKS5pbmRleChhKSsiLSIrZShhLmNvbnRhaW5lcikuY2hpbGRyZW4oKS5pbmRleChhLmRyYWdn
ZWRJdGVtKSYmMD09by5kcmFnRW5kLmFwcGx5KGEuZHJhZ2dlZEl0ZW0pKXt2YXIgaT1hLmRyYWdn
ZWRJdGVtLmF0dHIoImRhdGEtb3JpZ3BvcyIpLnNwbGl0KCItIiksbj1lKHJbaVswXV0uY29udGFp
bmVyKS5jaGlsZHJlbigpLm5vdChhLmRyYWdnZWRJdGVtKS5lcShpWzFdKTtuLmxlbmd0aD4wP24u
YmVmb3JlKGEuZHJhZ2dlZEl0ZW0pOjA9PWlbMV0/ZShyW2lbMF1dLmNvbnRhaW5lcikucHJlcGVu
ZChhLmRyYWdnZWRJdGVtKTplKHJbaVswXV0uY29udGFpbmVyKS5hcHBlbmQoYS5kcmFnZ2VkSXRl
bSl9cmV0dXJuIGEuZHJhZ2dlZEl0ZW0ucmVtb3ZlQXR0cigiZGF0YS1vcmlncG9zIiksYS5kcmFn
Z2VkSXRlbT1udWxsLGUoZG9jdW1lbnQpLm9mZigibW91c2Vtb3ZlIHRvdWNobW92ZSIsYS5zd2Fw
SXRlbXMpLGUoZG9jdW1lbnQpLm9mZigibW91c2V1cCB0b3VjaGVuZCIsYS5kcm9wSXRlbSksby5z
Y3JvbGxDb250YWluZXIhPXdpbmRvdyYmZSh3aW5kb3cpLm9mZigid2hlZWwiLGEud2hlZWwpLCEx
fX0sc3dhcEl0ZW1zOmZ1bmN0aW9uKHQpe2lmKG51bGw9PWEuZHJhZ2dlZEl0ZW0pcmV0dXJuITE7
dC5jaGFuZ2VkVG91Y2hlcyYmdC5jaGFuZ2VkVG91Y2hlc1swXSYmKHQucGFnZVg9dC5jaGFuZ2Vk
VG91Y2hlc1swXS5wYWdlWCx0LnBhZ2VZPXQuY2hhbmdlZFRvdWNoZXNbMF0ucGFnZVkpLGEuc2V0
UG9zKHQucGFnZVgsdC5wYWdlWSk7Zm9yKHZhciBuPWEuZmluZFBvcyh0LnBhZ2VYLHQucGFnZVkp
LGw9YSxkPTA7LTE9PW4mJm8uZHJhZ0JldHdlZW4mJmQ8ci5sZW5ndGg7ZCsrKW49cltkXS5maW5k
UG9zKHQucGFnZVgsdC5wYWdlWSksbD1yW2RdO2lmKC0xPT1uKXJldHVybiExO3ZhciBzPWZ1bmN0
aW9uKCl7cmV0dXJuIGUobC5jb250YWluZXIpLmNoaWxkcmVuKCkubm90KGwuZHJhZ2dlZEl0ZW0p
fSxjPXMoKS5ub3Qoby5pdGVtU2VsZWN0b3IpLmVhY2goZnVuY3Rpb24oZSl7dGhpcy5pZHg9cygp
LmluZGV4KHRoaXMpfSk7cmV0dXJuIG51bGw9PWl8fGkudG9wPmEuZHJhZ2dlZEl0ZW0ub2Zmc2V0
KCkudG9wfHxpLmxlZnQ+YS5kcmFnZ2VkSXRlbS5vZmZzZXQoKS5sZWZ0P2UobC5wb3Nbbl0uZWxt
KS5iZWZvcmUoYS5wbGFjZUhvbGRlckl0ZW0pOmUobC5wb3Nbbl0uZWxtKS5hZnRlcihhLnBsYWNl
SG9sZGVySXRlbSksYy5lYWNoKGZ1bmN0aW9uKCl7dmFyIHQ9cygpLmVxKHRoaXMuaWR4KS5nZXQo
MCk7dGhpcyE9dCYmcygpLmluZGV4KHRoaXMpPHRoaXMuaWR4P2UodGhpcykuaW5zZXJ0QWZ0ZXIo
dCk6dGhpcyE9dCYmZSh0aGlzKS5pbnNlcnRCZWZvcmUodCl9KSxlKHIpLmVhY2goZnVuY3Rpb24o
ZSx0KXt0LmNyZWF0ZURyb3BUYXJnZXRzKCksdC5idWlsZFBvc2l0aW9uVGFibGUoKX0pLGk9YS5k
cmFnZ2VkSXRlbS5vZmZzZXQoKSwhMX0sZmluZFBvczpmdW5jdGlvbihlLHQpe2Zvcih2YXIgbz0w
O288dGhpcy5wb3MubGVuZ3RoO28rKylpZih0aGlzLnBvc1tvXS5sZWZ0PGUmJnRoaXMucG9zW29d
LnJpZ2h0PmUmJnRoaXMucG9zW29dLnRvcDx0JiZ0aGlzLnBvc1tvXS5ib3R0b20+dClyZXR1cm4g
bztyZXR1cm4tMX0sY3JlYXRlRHJvcFRhcmdldHM6ZnVuY3Rpb24oKXtvLmRyYWdCZXR3ZWVuJiZl
KHIpLmVhY2goZnVuY3Rpb24oKXt2YXIgdD1lKHRoaXMuY29udGFpbmVyKS5maW5kKCJbZGF0YS1w
bGFjZWhvbGRlcl0iKSxyPWUodGhpcy5jb250YWluZXIpLmZpbmQoIltkYXRhLWRyb3B0YXJnZXRd
Iik7dC5sZW5ndGg+MCYmci5sZW5ndGg+MD9yLnJlbW92ZSgpOjA9PT10Lmxlbmd0aCYmMD09PXIu
bGVuZ3RoJiYoInRkIj09PW8udGFnTmFtZT9lKG8ucGxhY2VIb2xkZXJUZW1wbGF0ZSkuYXR0cigi
ZGF0YS1kcm9wdGFyZ2V0IiwhMCkuYXBwZW5kVG8odGhpcy5jb250YWluZXIpOmUodGhpcy5jb250
YWluZXIpLmFwcGVuZChhLnBsYWNlSG9sZGVySXRlbS5yZW1vdmVBdHRyKCJkYXRhLXBsYWNlaG9s
ZGVyIikuY2xvbmUoKS5hdHRyKCJkYXRhLWRyb3B0YXJnZXQiLCEwKSksYS5wbGFjZUhvbGRlckl0
ZW0uYXR0cigiZGF0YS1wbGFjZWhvbGRlciIsITApKX0pfX07bC5pbml0KCksci5wdXNoKGwpfSks
dGhpc31lKHRoaXMpLnRyaWdnZXIoImRyYWdzb3J0LXVuaW5pdCIpfSxlLmZuLmRyYWdzb3J0LmRl
ZmF1bHRzPXtpdGVtU2VsZWN0b3I6IiIsZHJhZ1NlbGVjdG9yOiIiLGluaXRDbGFzczoiIixkcmFn
U2VsZWN0b3JFeGNsdWRlOiJpbnB1dCwgdGV4dGFyZWEsIGJ1dHRvbiwgc2VsZWN0LCBvcHRpb24i
LGRyYWdFbmQ6ZnVuY3Rpb24oKXt9LGRyYWdCZXR3ZWVuOiExLHBsYWNlSG9sZGVyVGVtcGxhdGU6
IiIsc2Nyb2xsQ29udGFpbmVyOndpbmRvdyxzY3JvbGxTcGVlZDo1LGN1cnNvcjoicG9pbnRlciJ9
fShqUXVlcnkpOw==
'));
}

/**
 * Perform redirects on the public site.
 *
 * @todo Is this the most efficient way of doing it?
 * @todo Set a pref for 'default_site' (could be another domain)
 */
function smd_redirect()
{
    global $pretext, $siteurl;

    $debug = gps('smd_debug');

    $the_url = parse_url($pretext['request_uri']);
    $intended = $the_url['path'] . (isset($the_url['query']) ? '?'.$the_url['query'] : '');

    if ($debug) {
        echo '++ URL / PATH TO MATCH ++';
        dmp($the_url, $intended);
    }

    // Can't use get_pref() on 404 pages *shrug*.
    $redirects = smd_redir_get(1);
    $dflt_location = get_pref('smd_redir_default_site', hu);

    foreach ($redirects as $idx => $items) {
        // Rule can't redirect to itself: ignore.
        if ($items['src'] == $items['dst']) {
            continue;
        }

        // Add pattern delimiters.
        // Detect first possible delim char that is not in use in the regex itself.
        $dlmPool = array('`', '!', '@', '|', '#', '~', '%', '/');
        $dlm = array_merge(array_diff($dlmPool, preg_split('//', $items['src'], -1)));
        $pat = (count($dlm) > 0) ? $dlm[0] . $items['src'] . $dlm[0] : $items['src'];

        if (preg_match($pat, $intended, $matches)) {
            $redir = $items['dst'];

            if ($debug) {
                echo '++ MATCHED PATTERN / REDIRECT RULE ++';
                dmp($pat, $redir);
            }

            $reps = array();

            foreach ($matches as $idx => $input) {
                if ($idx==0) continue; // Don't care about full matched pattern
                $reps['{$' . $idx . '}'] = $input;
            }

            $redir = ($redir) ? $redir : $dflt_location;
            $redir = strtr($redir, $reps);

            if ($debug) {
                echo '++ DESTINATION URL ++';
                dmp($redir);
            }

            if (!$debug) {
                ob_end_clean();
                header("HTTP/1.0 301 Moved Permanently");

                // Todo Make these a pref
                header('Cache-Control: private, no-cache, must-revalidate');
                header('Location:' . $redir, true, 301);
                die();
            }
        }
    }
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. smd_redirect

Redirect one URL to another _without_ requiring @.htaccess@. Supports standard regular expression wildcard matches.

Add rules using _Extensions -> Redirects_. Click 'New redirect' and enter a URL portion to match against, and then a destination for that URL.

* Source and destination can either be:
** relative (no preceeding slash).
** root-relative (with preceding slash).
** absolute (full URL including domain).
* Source can be anchored if you specify the regex start (^) and/or end ($) anchor characters.
* Use an empty destination to redirect to site root (pref planned to redirect to arbitrary URL).
* Click and drag the up-down arrows to reorder the rules -- mainly for convenience since redirect chains can be created regardless of order. Redirects are processed in order, top to bottom, so if you have frequently used redirects it makes sense to put them at the top for speed reasons.
* Click the source name to edit a rule.
* Source can contain standard "preg_match":http://php.net/manual/en/function.preg-match.php patterns.
* If you wrap an expression part with parentheses it becoms available as a replacement in the destination. Replacements are indexed from 1 and denoted @{$1}@, @{$2}@ and so forth.

h2. Examples

h3(#smd_eg1). Match any string

bc(block). Source: training
Destination: _empty_

Any access to @site.com/training@ or @site.com/any/other/url/parts/training@ (or in fact any use of the word 'training' in the URL) will result in being redirected to the site home page.

h3(#smd_eg2). Match string at specific place

bc(block). Source: /training
Destination: _empty_

Any access to @site.com/training@ will redirect to home page.

h3(#smd_eg3). Relative destinations

bc(block). Source: training
Destination: archive

Redirect any access to @site.com/training@ to @site.com/archive@ instead. If accessing @site.com/some/path/to/training@ you will be redirected to @site.com/some/path/to/archive@. Note that this only works if the source is the last item on the URL. See "example 7":#smd_eg7 for a generic version to replace one part.

h3(#smd_eg4). Root-relative destinations

bc(block). Source: /training
Destination: /archive

Redirect any access to @site.com/training@ to the @site.com/archive@ section.

h3(#smd_eg5). Date-based archive

bc(block). Source: date/(\d\d)-(\d\d)-(\d{2,4})
Destination: /{$3}/{$2}/{$1}

Any URL that matches something of the form @site.com/date/DD-MM-YYYY@ (or DD-MM-YY) will redirect to @site.com/YYYY/MM/DD@. Notice that @{$N}@ matches the value of the Nth set of parentheses in the source.

h3(#smd_eg6). Remove all trailing slashes

bc(block). Source: ^/(.*)/$
Destination: /{$1}

Note that without the leading slashes your home page would probably not appear.

h3(#smd_eg7). Replace part of a URL

bc(block). Source: (.*)training(.*)
Destination: {$1}documentation{$2}

Will redirect @/some/boring/training/manual@ to @/some/boring/documentation/manual@.
# --- END PLUGIN HELP ---
-->
<?php
}
?>