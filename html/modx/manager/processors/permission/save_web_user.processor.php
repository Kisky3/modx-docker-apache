<?php
if (!isset($modx) || !$modx->isLoggedin()) {
    exit;
}
if (!$modx->hasPermission('save_web_user')) {
    $e->setError(3);
    $e->dumpError();
}
global $_style;
if (isset($_POST['id']) && preg_match('@^[0-9]+$@', $_POST['id'])) {
    $id = $_POST['id'];
}
$oldusername = $_POST['oldusername'];
$newusername = trim($modx->input_post('newusername', 'New User'));
$fullname = $modx->db->escape($_POST['fullname']);
$genpassword = $_POST['newpassword'];
$passwordgenmethod = $_POST['passwordgenmethod'];
$passwordnotifymethod = $_POST['passwordnotifymethod'];
$specifiedpassword = $_POST['specifiedpassword'];
$email = $modx->db->escape($_POST['email']);
$oldemail = $_POST['oldemail'];
$phone = $modx->db->escape($_POST['phone']);
$mobilephone = $modx->db->escape($_POST['mobilephone']);
$fax = $modx->db->escape($_POST['fax']);
$dob = !empty ($_POST['dob']) ? $modx->toTimeStamp($_POST['dob']) : 0;
$country = $_POST['country'];
$street = $modx->db->escape($_POST['street']);
$city = $modx->db->escape($_POST['city']);
$state = $modx->db->escape($_POST['state']);
$zip = $modx->db->escape($_POST['zip']);
$gender = !empty($_POST['gender']) ? $_POST['gender'] : 0;
$photo = $modx->db->escape($_POST['photo']);
$comment = $modx->db->escape($_POST['comment']);
$role = !empty($_POST['role']) ? $_POST['role'] : 0;
$failedlogincount = !empty($_POST['failedlogincount']) ? $_POST['failedlogincount'] : 0;
$blocked = !empty($_POST['blocked']) ? $_POST['blocked'] : 0;
$blockeduntil = !empty($_POST['blockeduntil']) ? $modx->toTimeStamp($_POST['blockeduntil']) : 0;
$blockedafter = !empty($_POST['blockedafter']) ? $modx->toTimeStamp($_POST['blockedafter']) : 0;
$user_groups = $_POST['user_groups'];

// verify password
if ($passwordgenmethod === 'spec' && $_POST['specifiedpassword'] != $_POST['confirmpassword']) {
    webAlert('Password typed is mismatched');
    exit;
}

// verify email
if (!isset($modx->config['required_email_wuser'])) {
    $modx->config['required_email_wuser'] = 1;
}

if ($modx->config['required_email_wuser']) {
    if ($email == '' || !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,20}$/i', $email)) {
        webAlert("E-mail address doesn't seem to be valid!");
        exit;
    }
}

switch ($_POST['mode']) {
    case '87' : // new user
        // check if this user name already exist
        $rs = $modx->db->select(
            'id'
            , '[+prefix+]web_users'
            , sprintf("username='%s'", $modx->db->escape($newusername))
        );
        if ($modx->db->getRecordCount($rs)) {
            webAlert('User name is already in use!');
            exit;
        }

        // check if the email address already exist
        if ($modx->config['required_email_wuser']) {
            $rs = $modx->db->select(
                'id'
                , '[+prefix+]web_user_attributes'
                , sprintf("email='%s'", $email)
            );
            if (!$rs) {
                webAlert(sprintf('An error occurred while attempting to retrieve all users with email %s.', $email));
                exit;
            }
            if ($modx->db->getRecordCount($rs)) {
                webAlert("Email is already in use!");
                exit;
            }
        }

        // generate a new password for this user
        if ($specifiedpassword != '' && $passwordgenmethod === 'spec') {
            if (strlen($specifiedpassword) < 6) {
                webAlert('Password is too short!');
                exit;
            }
            $newpassword = $specifiedpassword;
        } elseif ($specifiedpassword == '' && $passwordgenmethod === 'spec') {
            webAlert("You didn't specify a password for this user!");
            exit;
        } elseif ($passwordgenmethod === 'g') {
            $newpassword = generate_password(8);
        } else {
            webAlert('No password generation method specified!');
            exit;
        }

        // invoke OnBeforeWUsrFormSave event
        $tmp = array(
            'mode' => 'new',
            'id' => null
        );
        $modx->invokeEvent('OnBeforeWUsrFormSave', $tmp);

        // create the user account
        $fields = array();
        $fields['username'] = $newusername;
        $fields['password'] = md5($newpassword);
        $internalKey = $modx->db->insert(
            $fields
            , '[+prefix+]web_users'
        );
        if (!$internalKey) {
            webAlert("An error occurred while attempting to save the user.");
            exit;
        }

        $fields = compact('internalKey', 'fullname', 'role', 'email', 'phone', 'mobilephone', 'fax', 'zip', 'street',
            'city', 'state', 'country', 'gender', 'dob', 'photo', 'comment', 'blocked', 'blockeduntil', 'blockedafter');
        $rs = $modx->db->insert(
            $fields
            , '[+prefix+]web_user_attributes'
        );
        if (!$rs) {
            webAlert("An error occurred while attempting to save the user's attributes.");
            exit;
        }

        // Save User Settings
        saveUserSettings($internalKey);

        // invoke OnWebSaveUser event
        $tmp = array(
            'mode' => 'new',
            'userid' => $internalKey,
            'username' => $newusername,
            'userpassword' => $newpassword,
            'useremail' => $email,
            'userfullname' => $fullname
        );
        $modx->invokeEvent('OnWebSaveUser', $tmp);

        // invoke OnWUsrFormSave event
        $tmp = array(
            'mode' => 'new',
            'id' => $internalKey
        );
        $modx->invokeEvent('OnWUsrFormSave', $tmp);

        /*******************************************************************************/
        // put the user in the user_groups he/ she should be in
        // first, check that up_perms are switched on!
        if ($modx->config['use_udperms'] == 1) {
            if ($user_groups) {
                $field = array();
                foreach ($user_groups as $user_group) {
                    $field['webgroup'] = (int)$user_group;
                    $field['webuser'] = $internalKey;
                    $rs = $modx->db->insert(
                        $field
                        , '[+prefix+]web_groups'
                    );
                    if (!$rs) {
                        webAlert("An error occurred while attempting to add the user to a web group.");
                        exit;
                    }
                }
            }
        }
        // end of user_groups stuff!

        if ($modx->config['required_email_wuser'] && $passwordnotifymethod === 'e') {
            sendMailMessage($email, $newusername, $newpassword, $fullname);
            if ($_POST['stay'] != '') {
                $a = ($_POST['stay'] == '2') ? "88&id=" . $internalKey : '87';
                $header = "Location: index.php?a=" . $a . "&stay=" . $_POST['stay'];
            } else {
                $header = 'Location: index.php?a=99';
            }
            header($header);
        } else {
            if ($_POST['stay'] != '') {
                $a = ($_POST['stay'] == '2') ? "88&id=" . $internalKey : '87';
                $stayUrl = "index.php?a=" . $a . "&stay=" . $_POST['stay'];
            } else {
                $stayUrl = 'index.php?a=99';
            }

            include_once(MODX_MANAGER_PATH . 'actions/header.inc.php');
            ?>
            <h1><?php echo $_lang['web_user_title']; ?></h1>

            <div id="actions">
                <ul class="actionButtons">
                    <li class="mutate"><a href="<?php echo $stayUrl ?>"><img
                                    src="<?php echo $_style['icons_save'] ?>"/> <?php echo $_lang['close']; ?></a></li>
                </ul>
            </div>

            <div class="section">
                <div class="sectionHeader"><?php echo $_lang['web_user_title']; ?></div>
                <div class="sectionBody">
                    <div id="disp">
                        <p>
                            <?php echo sprintf($_lang['password_msg'], $newusername, $newpassword); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php
            include_once(MODX_MANAGER_PATH . 'actions/footer.inc.php');
        }
        break;
    case '88' : // edit user
        // generate a new password for this user
        if ($genpassword == 1) {
            if ($specifiedpassword != '' && $passwordgenmethod === 'spec') {
                if (strlen($specifiedpassword) < 6) {
                    webAlert("Password is too short!");
                    exit;
                }

                $newpassword = $specifiedpassword;
            } elseif ($specifiedpassword == '' && $passwordgenmethod === 'spec') {
                webAlert("You didn't specify a password for this user!");
                exit;
            } elseif ($passwordgenmethod === 'g') {
                $newpassword = generate_password(8);
            } else {
                webAlert("No password generation method specified!");
                exit;
            }
            $updatepasswordsql = ", password=MD5('" . $modx->db->escape($newpassword) . "') ";
        }
        if ($modx->config['required_email_wuser'] && $passwordnotifymethod === 'e') {
            sendMailMessage($email, $newusername, $newpassword, $fullname);
        }

        // check if the username already exist
        $rs = $modx->db->select(
            'id'
            , '[+prefix+]web_users'
            , sprintf("username='%s'", $modx->db->escape($newusername))
        );
        if (!$rs) {
            webAlert("An error occurred while attempting to retrieve all users with username $newusername.");
            exit;
        }
        $limit = $modx->db->getRecordCount($rs);
        if ($limit > 0) {
            $row = $modx->db->getRow($rs);
            if ($row['id'] != $id) {
                webAlert('User name is already in use!');
                exit;
            }
        }

        // check if the email address already exists
        if ($modx->config['required_email_wuser']) {
            $rs = $modx->db->select(
                'internalKey'
                , '[+prefix+]web_user_attributes'
                , sprintf("email='%s'", $email));
            if (!$rs) {
                webAlert("An error occurred while attempting to retrieve all users with email $email.");
                exit;
            }
            if (0 < $modx->db->getRecordCount($rs)) {
                $row = $modx->db->getRow($rs);
                if ($row['internalKey'] != $id) {
                    webAlert("Email is already in use!");
                    exit;
                }
            }
        }

        // invoke OnBeforeWUsrFormSave event
        $tmp = array(
            'mode' => 'upd',
            'id' => $id
        );
        $modx->invokeEvent('OnBeforeWUsrFormSave', $tmp);

        // update user name and password
        $sql = sprintf(
            "UPDATE %s SET username='%s'%s WHERE id='%s'"
            , $modx->getFullTableName('web_users')
            , $modx->db->escape($newusername)
            , $updatepasswordsql
            , $id
        );
        $rs = $modx->db->query($sql);
        if (!$rs) {
            webAlert("An error occurred while attempting to update the user's data.");
            exit;
        }

        $fields = compact('fullname', 'role', 'email', 'phone', 'mobilephone', 'fax', 'zip', 'street', 'city', 'state',
            'country',
            'gender', 'dob', 'photo', 'comment', 'failedlogincount', 'blocked', 'blockeduntil', 'blockedafter');
        $rs = $modx->db->update(
            $fields
            , $modx->getFullTableName('web_user_attributes')
            , sprintf("internalKey='%s'", $id)
        );
        if (!$rs) {
            webAlert("An error occurred while attempting to update the user's attributes.");
            exit;
        }

        // Save User Settings
        saveUserSettings($id);

        // invoke OnWebSaveUser event
        $tmp = array(
            'mode' => 'upd',
            'userid' => $id,
            'username' => $newusername,
            'userpassword' => $newpassword,
            'useremail' => $email,
            'userfullname' => $fullname,
            'oldusername' => ($oldusername != $newusername) ? $oldusername : '',
            'olduseremail' => ($oldemail != $email) ? $oldemail : ''
        );
        $modx->invokeEvent('OnWebSaveUser', $tmp);

        // invoke OnWebChangePassword event
        if ($updatepasswordsql) {
            $tmp = array(
                'userid' => $id,
                'username' => $newusername,
                'userpassword' => $newpassword
            );
        }
        $modx->invokeEvent('OnWebChangePassword', $tmp);

        // invoke OnWUsrFormSave event
        $tmp = array(
            'mode' => 'upd',
            'id' => $id
        );
        $modx->invokeEvent('OnWUsrFormSave', $tmp);

        /*******************************************************************************/
        // put the user in the user_groups he/ she should be in
        // first, check that up_perms are switched on!
        if ($modx->config['use_udperms'] == 1) {
            // as this is an existing user, delete his/ her entries in the groups before saving the new groups
            $rs = $modx->db->delete($modx->getFullTableName('web_groups'), "webuser='" . $id . "'");
            if (!$rs) {
                webAlert("An error occurred while attempting to delete previous user_groups entries.");
                exit;
            }
            if ($user_groups) {
                foreach ($user_groups as $group) {
                    $sql = sprintf(
                        "INSERT INTO %s (webgroup, webuser) VALUES('%s', '%s')"
                        , $modx->getFullTableName('web_groups')
                        , $group
                        , $id
                    );
                    if (!$modx->db->query($sql)) {
                        webAlert(sprintf(
                                'An error occurred while attempting to add the user to a user_group.<br />%s;'
                                , $sql)
                        );
                        exit;
                    }
                }
            }
        }
        // end of user_groups stuff!
        /*******************************************************************************/

        if ($genpassword == 1 && $passwordnotifymethod === 's') {
            if ($_POST['stay'] != '') {
                $a = ($_POST['stay'] == '2') ? "88&id=" . $id : "87";
                $stayUrl = "index.php?a=" . $a . "&stay=" . $_POST['stay'];
            } else {
                $stayUrl = "index.php?a=99";
            }

            include_once(MODX_MANAGER_PATH . 'actions/header.inc.php');
            ?>
            <h1><?php echo $_lang['web_user_title']; ?></h1>

            <div id="actions">
                <ul class="actionButtons">
                    <li class="mutate"><a href="<?php echo $stayUrl ?>"><img
                                    src="<?php echo $_style["icons_save"] ?>"/> <?php echo $_lang['close']; ?></a></li>
                </ul>
            </div>

            <div class="section">
                <div class="sectionHeader"><?php echo $_lang['web_user_title']; ?></div>
                <div class="sectionBody">
                    <div id="disp">
                        <p><?php echo sprintf($_lang["password_msg"], $newusername, $newpassword); ?></p>
                    </div>
                </div>
            </div>
            <?php

            include_once(MODX_MANAGER_PATH . 'actions/footer.inc.php');
        } else {
            if ($_POST['stay'] != '') {
                $a = ($_POST['stay'] == '2') ? "88&id=$id" : "87";
                $header = "Location: index.php?a=" . $a . "&stay=" . $_POST['stay'];
            } else {
                $header = "Location: index.php?a=99";
            }
            header($header);
        }
        break;
    default :
        webAlert('Unauthorized access');
        exit;
}

// Send an email to the user
function sendMailMessage($email, $uid, $pwd, $ufn) {
    global $modx, $websignupemail_message;
    global $emailsender;
    global $site_name, $site_url;
    $message = sprintf($websignupemail_message, $uid, $pwd); // use old method
    // replace placeholders
    $ph['username'] = $uid;
    $ph['uid'] = $uid;
    $ph['fullname'] = $ufn;
    $ph['ufn'] = $ufn;
    $ph['manager_email'] = $emailsender;
    $ph['saddr'] = $emailsender;
    $ph['pwd'] = $pwd;
    $ph['password'] = $pwd;
    $ph['sname'] = $site_name;
    $ph['semail'] = $emailsender;
    $ph['surl'] = $site_url;
    $message = $modx->parseText($message, $ph);
    $message = $modx->mergeSettingsContent($message);
    $message = $modx->mergeChunkContent($message);
    $message = $modx->rewriteUrls($message);

    if ($modx->sendmail($email, $message) === false) //ignore mail errors in this cas
    {
        webAlert("Error while sending mail to " . $email);
        exit;
    }
}

// Save User Settings
function saveUserSettings($id) {
    global $modx;

    $settings = array(
        'login_home',
        'allowed_ip',
        'allowed_days'
    );

    foreach ($settings as $name) {
        $modx->db->delete('[+prefix+]web_user_settings', "webuser='" . $id . "' and setting_name='" . $name . "'");
        $value = $_POST[$name];
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        if ($value != '') {
            $field = array();
            $field['webuser'] = $id;
            $field['setting_name'] = $name;
            $field['setting_value'] = $modx->db->escape($value);
            $modx->db->insert($field, $modx->getFullTableName('web_user_settings'));
        }
    }
}

// converts date format dd-mm-yyyy to php date

// Web alert -  sends an alert to web browser
function webAlert($msg) {
    global $id, $modx;
    $mode = $_POST['mode'];
    $url = "index.php?a=" . $mode . ($mode == '88' ? "&id=" . $id : '');
    $modx->manager->saveFormValues($mode);
    $modx->webAlertAndQuit($msg, $url);
}

// Generate password
function generate_password($length = 10) {
    return substr(str_shuffle('abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, $length);
}
