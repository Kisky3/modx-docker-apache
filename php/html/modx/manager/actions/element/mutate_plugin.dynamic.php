<?php
if (!isset($modx) || !$modx->isLoggedin()) {
    exit;
}

switch ((int)$_REQUEST['a']) {
    case 102:
        if (!$modx->hasPermission('edit_plugin')) {
            $e->setError(3);
            $e->dumpError();
        }
        break;
    case 101:
        if (!$modx->hasPermission('new_plugin')) {
            $e->setError(3);
            $e->dumpError();
        }
        break;
    default:
        $e->setError(3);
        $e->dumpError();
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

// check to see the plugin editor isn't locked
$rs = db()->select('*', '[+prefix+]active_users', "action='102' AND id='{$id}'");
$row = db()->getRow($rs);
if (1 < db()->getRecordCount($rs) && $row['internalKey'] != $modx->getLoginUserID()) {
    $msg = sprintf($_lang['lock_msg'], $row['username'], $_lang['plugin']);
    $e->setError(5, $msg);
    $e->dumpError();
}
// end check for lock

if (isset($_GET['id']) && preg_match('@^[1-9][0-9]*$@', $_GET['id'])) {
    $rs = db()->select('*', '[+prefix+]site_plugins', "id='{$id}'");
    $total = db()->getRecordCount($rs);
    $pluginObject = (object)db()->getRow($rs);

    if (1 < $total):
        echo "Multiple plugins sharing same unique id. Not good.<p>";
        exit;
    elseif ($total < 1):
        header("Location: {$modx->config['site_url']}");
    endif;

    $_SESSION['itemname'] = $pluginObject->name;
} else {
    $_SESSION['itemname'] = 'New Plugin';
}
?>
    <script language="JavaScript">

        jQuery(function () {
            var readonly = <?php echo ($pluginObject->locked == 1 || $pluginObject->locked === 'on') ? '1' : '0'; ?>;
            if (readonly == 1) {
                jQuery('textarea,input[type=text]').prop('readonly', true);
                jQuery('select').addClass('readonly');
                jQuery('#Button1').hide();
                jQuery('input[name="locked"]').click(function () {
                    jQuery('#Button1').toggle();
                });
            }
            jQuery('input[name="locked"]').click(function () {
                jQuery('textarea,input[type=text]').prop('readonly', jQuery(this).prop('checked'));
                jQuery('select').toggleClass('readonly');
            });
            setTimeout('showParameters()', 10);
            jQuery('select[name="categoryid"]').change(function () {
                if (jQuery(this).val() == '-1') {
                    jQuery('#newcategry').fadeIn();
                } else {
                    jQuery('#newcategry').fadeOut();
                    jQuery('input[name="newcategory"]').val('');
                }
            });
        });

        function duplicaterecord() {
            if (confirm("<?php echo $_lang['confirm_duplicate_record'] ?>") == true) {
                documentDirty = false;
                document.location.href = "index.php?id=<?php echo $_REQUEST['id']; ?>&a=105";
            }
        }

        function deletedocument() {
            if (confirm("<?php echo $_lang['confirm_delete_plugin']; ?>") == true) {
                documentDirty = false;
                document.location.href = "index.php?id=" + document.mutate.id.value + "&a=104";
            }
        }

        function setTextWrap(ctrl, b) {
            if (!ctrl) return;
            ctrl.wrap = (b) ? "soft" : "off";
        }

        // Current Params/Configurations
        var currentParams = {};

        function showParameters(ctrl) {
            var c, p, df, cp;
            var ar, desc, value, key, dt;

            currentParams = {}; // reset;

            if (ctrl) {
                f = ctrl.form;
            } else {
                f = document.forms['mutate'];
                if (!f) return;
            }

            // setup parameters
            tr = document.getElementById('displayparamrow');
            dp = (f.properties.value) ? f.properties.value.split("&") : "";
            if (!dp) tr.style.display = 'none';
            else {
                t = '<table style="width:300px;margin-top:20px;margin-bottom:20px;">';
                for (p = 0; p < dp.length; p++) {
                    dp[p] = (dp[p] + '').replace(/^\s|\s$/, ""); // trim
                    ar = dp[p].split("=");
                    key = ar[0];        // param
                    ar = (ar[1] + '').split(";");
                    desc = ar[0];	// description
                    dt = ar[1];		// data type
                    value = decode((ar[2]) ? ar[2] : '');

                    // store values for later retrieval
                    if (key && (dt == 'list' || dt == 'list-multi')) currentParams[key] = [desc, dt, value, ar[3]];
                    else if (key) currentParams[key] = [desc, dt, value];

                    if (dt) {
                        switch (dt) {
                            case 'int':
                                c = '<input type="text" name="prop_' + key + '" id="prop_' + key + '" value="' + value + '" size="30" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)" />';
                                break;
                            case 'menu':
                                value = ar[3];
                                c = '<select name="prop_' + key + '" id="prop_' + key + '" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)">';
                                ls = (ar[2] + '').split(",");
                                if (currentParams[key] == ar[2]) currentParams[key] = ls[0]; // use first list item as default
                                for (i = 0; i < ls.length; i++) {
                                    c += '<option value="' + ls[i] + '"' + ((ls[i] == value) ? ' selected="selected"' : '') + '>' + ls[i] + '</option>';
                                }
                                c += '</select>';
                                break;
                            case 'list':
                                value = ar[3];
                                ls = (ar[2] + '').split(",");
                                if (currentParams[key] == ar[2]) currentParams[key] = ls[0]; // use first list item as default
                                c = '<select name="prop_' + key + '" id="prop_' + key + '" size="' + ls.length + '" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)">';
                                for (i = 0; i < ls.length; i++) {
                                    c += '<option value="' + ls[i] + '"' + ((ls[i] == value) ? ' selected="selected"' : '') + '>' + ls[i] + '</option>';
                                }
                                c += '</select>';
                                break;
                            case 'list-multi':
                                value = typeof ar[3] !== 'undefined' ? (ar[3] + '').replace(/^\s|\s$/, "") : '';
                                arrValue = value.split(",");
                                ls = (ar[2] + '').split(",");
                                if (currentParams[key] == ar[2]) currentParams[key] = ls[0]; // use first list item as default
                                c = '<select name="prop_' + key + '" id="prop_' + key + '" size="' + ls.length + '" multiple="multiple" style="width:168px" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)">';
                                for (i = 0; i < ls.length; i++) {
                                    if (arrValue.length) {
                                        var found = false;
                                        for (j = 0; j < arrValue.length; j++) {
                                            if (ls[i] == arrValue[j]) {
                                                found = true;
                                            }
                                        }
                                        if (found == true) {
                                            c += '<option value="' + ls[i] + '" selected="selected">' + ls[i] + '</option>';
                                        } else {
                                            c += '<option value="' + ls[i] + '">' + ls[i] + '</option>';
                                        }
                                    } else {
                                        c += '<option value="' + ls[i] + '">' + ls[i] + '</option>';
                                    }
                                }
                                c += '</select>';
                                break;
                            case 'textarea':
                                c = '<textarea class="phptextarea" name="prop_' + key + '" id="prop_' + key + '" cols="50" rows="4" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)">' + value + '</textarea>';
                                break;
                            default:  // string
                                c = '<input type="text" name="prop_' + key + '" id="prop_' + key + '" value="' + value + '" size="30" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)" />';
                                break;

                        }
                        t += '<tr><td><div>' + desc + '</div><div style="padding-bottom:10px;">' + c + '</div></td></tr>';
                    }

                }
                t += '</table>';
                td = document.getElementById('displayparams');
                td.innerHTML = t;
                tr.style.display = '';
            }
            implodeParameters();
        }

        function setParameter(key, dt, ctrl) {
            var v;
            if (!ctrl) return null;
            switch (dt) {
                case 'int':
                    ctrl.value = parseInt(ctrl.value);
                    if (isNaN(ctrl.value)) ctrl.value = 0;
                    v = ctrl.value;
                    break;
                case 'menu':
                    v = ctrl.options[ctrl.selectedIndex].value;
                    currentParams[key][3] = v;
                    implodeParameters();
                    return;
                    break;
                case 'list':
                    v = ctrl.options[ctrl.selectedIndex].value;
                    currentParams[key][3] = v;
                    implodeParameters();
                    return;
                    break;
                case 'list-multi':
                    var arrValues = [];
                    for (var i = 0; i < ctrl.options.length; i++) {
                        if (ctrl.options[i].selected) {
                            arrValues.push(ctrl.options[i].value);
                        }
                    }
                    currentParams[key][3] = arrValues.toString();
                    implodeParameters();
                    return;
                    break;
                default:
                    v = ctrl.value + '';
                    break;
            }
            currentParams[key][2] = v;
            implodeParameters();
        }

        // implode parameters
        function implodeParameters() {
            var v, p, s = '';
            for (p in currentParams) {
                if (currentParams[p]) {
                    v = currentParams[p].join(";");
                    if (s && v) s += ' ';
                    if (v) s += '&' + p + '=' + encode(v);
                }
            }
            document.forms['mutate'].properties.value = s;
        }

        function encode(s) {
            s = s + '';
            s = s.replace(/\=/g, '%3D'); // =
            s = s.replace(/\&/g, '%26'); // &
            return s;
        }

        function decode(s) {
            s = s + '';
            s = s.replace(/\%3D/g, '='); // =
            s = s.replace(/\%26/g, '&'); // &
            return s;
        }

        jQuery(function () {

            // Try and populate config fields from the text that is pasted in the PHP box
            jQuery('#phptextarea').bind('blur', function () {

                // Get the value of the php text field
                var src = jQuery('#phptextarea').val();

                // Is  something in there?
                if (src == '') {
                    jQuery('input[name="sysevents[]"]').removeAttr('checked'); // Untick all sys events
                    jQuery('#pluginName').val('');
                    jQuery('#pluginDescription').val('');
                    jQuery('#propertiesBox').val('');
                    jQuery('#newcategory').val('');
                    jQuery('#categoryid option').removeAttr('selected')
                    showParameters(jQuery('#propertiesBox'));
                    return;
                }

                var find_block = /\/\*\*([\s\S]*)\*\//;
                var theBlock = find_block.exec(src);
                if (theBlock == null) {
                    return;
                } else {
                    src = theBlock[1];
                }

                var theParams = {};

                // Go through each line and transform into something useful
                var lines = src.split(/\n/);
                lines.each(function (theLine, index) {

                    //theLine = theLine.search(/\s\*\s([A-Za-z0-9])/);
                    var docblock_regexp = /\s\*\s(.+)/;
                    theLine = docblock_regexp.exec(theLine);

                    if (theLine != null) { // Does it have words on it?
                        if (!theParams.name) { // The first non-null line we come across should be the name
                            theParams.name = theLine[1];
                        } else if (!theParams.description) { // The second non-null line we come across should be the description
                            theParams.description = theLine[1];
                        } else {

                            var line_regexp = /@([A-Za-z_]+)\s+(.*)/;
                            var theLineDetail = line_regexp.exec(theLine[1]);

                            if (theLineDetail) {
                                switch (theLineDetail[1]) {
                                    case 'internal':
                                        var theInternalLineDetail = line_regexp.exec(theLineDetail[2]);
                                        theParams[theInternalLineDetail[1]] = theInternalLineDetail[2];
                                        break;
                                    default:
                                        theParams[theLineDetail[1]] = theLineDetail[2];
                                        break;
                                }
                            }
                        }
                    }

                });


                // Populate the events
                if (theParams.events) {
                    var events = theParams.events.split(',');

                    // Untick all sys events
                    $$('input[name^=sysevents]').removeProperty('checked');

                    events.each(function (i) {
                        $(i.trim()).setProperty('checked', 'checked');
                    });
                }


                // Populate the name
                if (theParams.name) $('pluginName').setProperty('value', theParams.name);

                // Populate the description
                var version = (theParams.version) ? '<strong>' + theParams.version + '</strong> ' : '';
                if (theParams.description) $('pluginDescription').setProperty('value', version + theParams.description);

                // If old param values are set, keep a record of them
                var oldParams = currentParams;

                // Set new default params
                if (theParams.properties) {
                    $('propertiesBox').value = theParams.properties;
                    showParameters($('propertiesBox'));
                }


                // Populate the properties from any old existing values
                if (oldParams) {

                    // Go through each old param, and set its value if it exists
                    $each(oldParams, function (oldParam, oldName) {

                        var theField = $('prop_' + oldName);

                        if (!theField) return;

                        switch (oldParam[1]) {
                            case 'list':
                            case 'menu':
                                var oldValue = oldParam[3];
                                theField.setProperty('value', oldValue);
                                setParameter(oldName, oldParam[1], theField);
                                break;

                            case 'list-multi':
                                // Not supporting list-multi yet, as it is broken anyway
                                break;

                            default:
                                var oldValue = oldParam[2];
                                theField.setProperty('value', oldValue);
                                setParameter(oldName, oldParam[1], theField);
                                break;
                        }

                    });
                }

                // Select the correct dropdown value
                var modx_category_found = false;
                $$('#categoryid option').removeProperty('selected').each(function (opt) {
                    if (opt.text.trim() == theParams.modx_category.trim()) {
                        opt.setProperty('selected', 'selected');
                        modx_category_found = true;
                    }
                });


                // If not found in the dropdown, create a new category
                if (!modx_category_found && theParams.modx_category) {
                    $('newcategory').setProperty('value', theParams.modx_category);
                }

            });
        });

    </script>

    <form name="mutate" id="mutate" method="post" action="index.php?a=103" enctype="multipart/form-data">
        <?php
        // invoke OnPluginFormPrerender event
        $tmp = array("id" => $id);
        $evtOut = $modx->invokeEvent("OnPluginFormPrerender", $tmp);
        if (is_array($evtOut)) {
            echo implode("", $evtOut);
        }
        ?>
        <input type="hidden" name="id" value="<?php echo $pluginObject->id; ?>">
        <input type="hidden" name="mode" value="<?php echo $_GET['a']; ?>">

        <h1><?php echo $_lang['plugin_title']; ?></h1>

        <div id="actions">
            <ul class="actionButtons">
                <?php if ($modx->hasPermission('save_plugin')): ?>
                    <li id="Button1" class="mutate">
                        <a href="#"
                           onclick="documentDirty=false;jQuery('#mutate').submit();jQuery('#Button1').hide();jQuery('input,textarea,select').addClass('readonly');">
                            <img src="<?php echo $_style["icons_save"] ?>"/> <?php echo $_lang['update'] ?>
                        </a>
                        <span class="and"> + </span>
                        <select id="stay" name="stay">
                            <option id="stay1"
                                    value="1" <?php echo selected($_REQUEST['stay'] == '1'); ?> ><?php echo $_lang['stay_new'] ?></option>
                            <option id="stay2"
                                    value="2" <?php echo selected($_REQUEST['stay'] == '2'); ?> ><?php echo $_lang['stay'] ?></option>
                            <option id="stay3"
                                    value="" <?php echo selected($_REQUEST['stay'] == ''); ?> ><?php echo $_lang['close'] ?></option>
                        </select>
                    </li>
                <?php endif; ?>
                <?php
                if ($_GET['a'] == '102') {
                    $params = array(
                        'onclick' => 'duplicaterecord();',
                        'icon' => $_style['icons_resource_duplicate'],
                        'label' => $_lang['duplicate']
                    );
                    if ($modx->hasPermission('new_plugin')) {
                        echo $modx->manager->ab($params);
                    }
                    $params = array(
                        'onclick' => 'deletedocument();',
                        'icon' => $_style['icons_delete_document'],
                        'label' => $_lang['delete']
                    );
                    if ($modx->hasPermission('delete_plugin')) {
                        echo $modx->manager->ab($params);
                    }
                }
                $params = array(
                    'onclick' => "document.location.href='index.php?a=76';",
                    'icon' => $_style['icons_cancel'],
                    'label' => $_lang['cancel']
                );
                echo $modx->manager->ab($params);
                ?>
            </ul>
        </div>
        <div class="sectionBody">
            <div class="tab-pane" id="pluginPane">

                <!-- General -->
                <div class="tab-page" id="tabPlugin">
                    <h2 class="tab"><?php echo $_lang["settings_general"] ?></h2>
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <th align="left"><?php echo $_lang['plugin_name']; ?></th>
                            <td align="left"><input id="pluginName" name="name" type="text" maxlength="100"
                                                    value="<?php echo htmlspecialchars($pluginObject->name); ?>"
                                                    class="inputBox" style="width:300px;"></td>
                        </tr>
                        <tr>
                            <td align="left" valign="top" colspan="2"><label><input name="disabled"
                                                                                    type="checkbox" <?php echo $pluginObject->disabled == 1 ? "checked='checked'" : ""; ?>
                                                                                    value="on"
                                                                                    class="inputBox"> <?php echo $pluginObject->disabled == 1 ? "<span class='warning'>" . $_lang['plugin_disabled'] . "</span></label>" : $_lang['plugin_disabled']; ?>
                            </td>
                        </tr>
                    </table>
                    <!-- PHP text editor start -->
                    <div style="width:100%;position:relative">
                        <div style="padding:3px 8px; overflow:hidden;zoom:1; background-color:#eeeeee; border:1px solid #c3c3c3; border-bottom:none;margin-top:5px;">
                            <span style="float:left;font-weight:bold;"><?php echo $_lang['plugin_code']; ?></span>
                            <span style="float:right;color:#707070;"><?php echo $_lang['wrap_lines']; ?><input
                                        name="wrap" type="checkbox" "checked="checked" class="inputBox" onclick="setTextWrap(document.mutate.post,this.checked)" /></span>
                        </div>
                        <textarea dir="ltr" name="post" style="width:100%; height:370px;" wrap="soft"
                                  class="phptextarea"
                                  id="phptextarea"><?php echo htmlspecialchars($pluginObject->plugincode); ?></textarea>
                    </div>
                    <!-- PHP text editor end -->
                </div>

                <!-- Configuration/Properties -->
                <div class="tab-page" id="tabProps">
                    <h2 class="tab"><?php echo $_lang["settings_config"] ?></h2>
                    <?php
                    $field = 'sm.id,sm.name,sm.guid';
                    $from = '[+prefix+]site_modules sm ' .
                        'INNER JOIN [+prefix+]site_module_depobj smd ON smd.module=sm.id AND smd.type=30 ' .
                        'INNER JOIN [+prefix+]site_plugins sp ON sp.id=smd.resource';
                    $where = "smd.resource='$id' AND sm.enable_sharedparams='1'";
                    $ds = db()->select($field, $from, $where, 'sm.name');
                    $guid_total = db()->getRecordCount($ds);
                    if ($guid_total > 0) {
                        $options = '';
                        while ($row = db()->getRow($ds)) {
                            $options .= "<option value='" . $row['guid'] . "'" . selected($pluginObject->moduleguid == $row["guid"]) . ">" . htmlspecialchars($row["name"]) . "</option>";
                        }
                    }
                    ?>
                    <table>
                        <?php
                        if ($guid_total > 0) {
                            ?>
                            <tr>
                                <th align="left"><?php echo $_lang['import_params']; ?>:&nbsp;&nbsp;</th>
                                <td align="left">
                                    <select name="moduleguid" style="width:300px;">
                                        <option>&nbsp;</option>
                                        <?php echo $options; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="left" valign="top"><span style="width:300px;"><span
                                                class="comment"><?php echo $_lang['import_params_msg']; ?></span></span><br/>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr>
                            <th align="left" valign="top"><?php echo $_lang['plugin_config']; ?>:</th>
                            <td align="left" valign="top">
                                <textarea class="phptextarea inputBox" name="properties" id="propertiesBox"
                                          onblur='showParameters(this);'
                                          onChange="showParameters(this);"><?php echo $pluginObject->properties; ?></textarea><br/><input
                                        type="button" value="<?php echo $_lang['update_params']; ?>"
                                        onclick="showParameters(this);"/></td>
                        </tr>
                        <tr id="displayparamrow">
                            <td valign="top" align="left">&nbsp;</td>
                            <td align="left" id="displayparams">&nbsp;</td>
                        </tr>
                    </table>
                </div>

                <!-- System Events -->
                <div class="tab-page" id="tabEvents">
                    <h2 class="tab"><?php echo $_lang["settings_events"] ?></h2>
                    <p><?php echo $_lang['plugin_event_msg']; ?></p>
                    <table>
                        <?php

                        // get selected events
                        if (is_numeric($id) && $id > 0) {
                            $evts = array();
                            $rs = db()->select('*', '[+prefix+]site_plugin_events', "pluginid='{$id}'");
                            while ($row = db()->getRow($rs)) {
                                $evts[] = $row['evtid'];
                            }
                        } else {
                            if (isset($pluginObject->sysevents) && is_array($pluginObject->sysevents)) {
                                $evts = $pluginObject->sysevents;
                            } else {
                                $evts = array();
                            }
                        }

                        // display system events
                        $evtnames = array();
                        $services = array(
                            "Parser Service Events",
                            "Manager Access Events",
                            "Web Access Service Events",
                            "Cache Service Events",
                            "Template Service Events",
                            "User Defined Events"
                        );
                        $rs = db()->select('*', '[+prefix+]system_eventnames', '', 'service DESC, groupname, name');
                        if (db()->getRecordCount($rs) == 0) {
                            echo '<tr><td>&nbsp;</td></tr>';
                        } else {
                            $g = 0;
                            $srvID = '';
                            $grpName = '';
                            while ($row = db()->getRow($rs)) {
                                // display records
                                if ($srvID != $row['service']) {
                                    $g++;
                                    $srvID = $row['service'];
                                    if (count($evtnames) > 0) {
                                        echoEventRows($evtnames);
                                    }
                                    echo '<tr><td colspan="2"><div class="split" style="margin:10px 0;"></div></td></tr>';
                                    echo '<tr><td colspan="2"><b>' . "[{$g}] " . $services[$srvID - 1] . '</b></td></tr>';
                                }
                                // display group name
                                if ($grpName != $row['groupname']) {
                                    $g++;
                                    if (count($evtnames) > 0) {
                                        echoEventRows($evtnames);
                                    }
                                    echo '<tr><td colspan="2"><div class="split" style="margin:10px 0;"></div></td></tr>';
                                    echo '<tr><td colspan="2"><b>' . "[{$g}] " . $row['groupname'] . '</b></td></tr>';
                                    $grpName = $row['groupname'];
                                }
                                $evtid = $row['id'];
                                $evtnames[] = '<input name="sysevents[]" type="checkbox"' . checked(in_array($row['id'],
                                        $evts)) . ' class="inputBox" value="' . $row['id'] . '" id="' . $row['name'] . '"/><label for="' . $row['name'] . '"' . bold(in_array($row['id'],
                                        $evts)) . '>' . "[{$evtid}] " . $row['name'] . '</label>' . "\n";
                                if (count($evtnames) == 2) {
                                    echoEventRows($evtnames);
                                }
                            }
                        }
                        if (count($evtnames) > 0) {
                            echoEventRows($evtnames);
                        }

                        function echoEventRows(&$evtnames) {
                            echo "<tr><td>" . join("</td><td>", $evtnames) . "</td></tr>";
                            $evtnames = array();
                        }

                        ?>
                    </table>
                </div>
                <div class="tab-page" id="tabInfo">
                    <h2 class="tab"><?php echo $_lang['settings_properties']; ?></h2>
                    <table>
                        <tr>
                            <th align="left"><?php echo $_lang['existing_category']; ?>:&nbsp;&nbsp;</th>
                            <td align="left"><select name="categoryid" id="categoryid" style="width:300px;">
                                    <option value="0"><?php echo $_lang["no_category"]; ?></option>
                                    <?php
                                    $ds = $modx->manager->getCategories();
                                    if ($ds) {
                                        foreach ($ds as $n => $v) {
                                            echo "<option value='" . $v['id'] . "'" . selected($pluginObject->category == $v["id"]) . ">" . htmlspecialchars($v["category"]) . "</option>";
                                        }
                                    }
                                    ?>
                                    <option value="-1">&gt;&gt; <?php echo $_lang["new_category"]; ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr id="newcategry" style="display:none;">
                            <th align="left" valign="top" style="padding-top:5px;"><?php echo $_lang['new_category']; ?>
                                :
                            </th>
                            <td align="left" valign="top" style="padding-top:5px;"><input name="newcategory"
                                                                                          id="newcategory" type="text"
                                                                                          maxlength="45" value=""
                                                                                          class="inputBox"
                                                                                          style="width:300px;"></td>
                        </tr>
                        <tr>
                            <th align="left"><?php echo $_lang['plugin_desc']; ?>:&nbsp;&nbsp;</th>
                            <td align="left"><textarea id="pluginDescription" name="description"
                                                       style="padding:0;height:4em;"><?php echo $pluginObject->description; ?></textarea>
                            </td>
                        </tr>
                        <?php if ($modx->hasPermission('save_plugin') == 1) { ?>
                            <tr>
                                <td align="left" valign="top" colspan="2">
                                    <label><input name="locked"
                                                  type="checkbox" <?php echo $pluginObject->locked == 1 ? "checked='checked'" : ""; ?>
                                                  value="on" class="inputBox">
                                        <b><?php echo $_lang['lock_plugin']; ?></b> <span
                                                class="comment"><?php echo $_lang['lock_plugin_msg']; ?></span></label>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                </div>
                <div class="tab-page" id="tabHelp">
                    <h2 class="tab">ヘルプ</h2>
                    <?php echo $_lang['plugin_msg']; ?>
                </div>
            </div>
        </div>
        <script>
            var tpstatus = <?php echo (($modx->config['remember_last_tab'] == 2) || ($_GET['stay'] == 2)) ? 'true' : 'false';?>;
            tp = new WebFXTabPane(document.getElementById("pluginPane"), tpstatus);
        </script>
        <?php
        // invoke OnPluginFormRender event
        $tmp = array("id" => $id);
        $evtOut = $modx->invokeEvent("OnPluginFormRender", $tmp);
        if (is_array($evtOut)) {
            echo implode("", $evtOut);
        }
        ?>
    </form>
<?php

function bold($cond = false) {
    if ($cond !== false) {
        return ' style="background-color:#777;color:#fff;"';
    } else {
        return;
    }
}
