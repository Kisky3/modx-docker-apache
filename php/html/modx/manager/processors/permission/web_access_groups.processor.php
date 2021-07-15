<?php
if (!isset($modx) || !$modx->isLoggedin()) {
    exit;
}
if (!$modx->hasPermission('web_access_permissions')) {
    $e->setError(3);
    $e->dumpError();
}

// web access group processor.
// figure out what the user wants to do...

// Get table names (alphabetical)
$tbl_documentgroup_names = $modx->getFullTableName('documentgroup_names');
$tbl_webgroup_access = $modx->getFullTableName('webgroup_access');

$updategroupaccess = false;
$operation = $_REQUEST['operation'];

switch ($operation) {
    case 'add_user_group' :
        $newgroup = $_REQUEST['newusergroup'];
        if (empty($newgroup)) {
            exit('no group name specified');
        } else {
            $f['name'] = $modx->db->escape($newgroup);
            if (!$id = $modx->db->insert($f, '[+prefix+]webgroup_names')) {
                exit('Failed to insert new group. Possible duplicate group name?');
            }

            // invoke OnWebCreateGroup event
            $tmp = array(
                'groupid' => $id,
                'groupname' => $newgroup,
            );
            $modx->invokeEvent('OnWebCreateGroup', $tmp);
        }
        break;
    case 'add_document_group' :
        $newgroup = $_REQUEST['newdocgroup'];
        if (empty($newgroup)) {
            exit('no group name specified');
        } else {
            $f['name'] = $modx->db->escape($newgroup);
            if (!$id = $modx->db->insert($f, $tbl_documentgroup_names)) {
                exit("Failed to insert new group. Possible duplicate group name?");
            }

            // invoke OnCreateDocGroup event
            $tmp = array(
                'groupid' => $id,
                'groupname' => $newgroup,
            );
            $modx->invokeEvent('OnCreateDocGroup', $tmp);
        }
        break;
    case 'delete_user_group' :
        $updategroupaccess = true;
        $usergroup = (int)$_REQUEST['usergroup'];
        if (empty($usergroup)) {
            exit("No user group name specified for deletion");
        } else {
            if (!$rs = $modx->db->delete('[+prefix+]webgroup_names', "id='{$usergroup}'")) {
                exit('Unable to delete group. SQL failed.');
            }
            if (!$rs = $modx->db->delete($tbl_webgroup_access, "webgroup='{$usergroup}'")) {
                exit('Unable to delete group from access table. SQL failed.');
            }
            if (!$rs = $modx->db->delete('[+prefix+]web_groups', "webuser='{$usergroup}'")) {
                exit('Unable to delete user-group links. SQL failed.');
            }
        }
        break;
    case 'delete_document_group' :
        $group = (int)$_REQUEST['documentgroup'];
        if (empty($group)) {
            exit("No document group name specified for deletion");
        } else {
            if (!$rs = $modx->db->delete($tbl_documentgroup_names, "id='{$group}'")) {
                exit("Unable to delete group. SQL failed.");
            }
            if (!$rs = $modx->db->delete($tbl_webgroup_access, "documentgroup='{$group}'")) {
                exit("Unable to delete group from access table. SQL failed.");
            }
            if (!$rs = $modx->db->delete('[+prefix+]document_groups', "document_group='{$group}'")) {
                exit("Unable to delete document-group links. SQL failed.");
            }
        }
        break;
    case 'rename_user_group' :
        $newgroupname = $modx->db->escape($_REQUEST['newgroupname']);
        if (empty($_REQUEST['newgroupname'])) {
            exit("no group name specified");
        }
        $groupid = intval($_REQUEST['groupid']);
        if (empty($groupid)) {
            exit("No group id specified");
        }
        $f['name'] = $modx->db->escape($_REQUEST['newgroupname']);
        if (!$rs = $modx->db->update($f, '[+prefix+]webgroup_names', "id='{$groupid}'", '', 1)) {
            exit("Failed to update group name. Possible duplicate group name?");
        }
        break;
    case 'rename_document_group' :
        $newgroupname = $modx->db->escape($_REQUEST['newgroupname']);
        if (empty($_REQUEST['newgroupname'])) {
            exit("no group name specified");
        }
        $groupid = (int)$_REQUEST['groupid'];
        if (empty($groupid)) {
            exit("No group id specified");
        }
        $f['name'] = $modx->db->escape($_REQUEST['newgroupname']);
        if (!$rs = $modx->db->update($f, $tbl_documentgroup_names, "id='{$groupid}'", '', 1)) {
            exit("Failed to update group name. Possible duplicate group name?");
        }
        break;
    case 'add_document_group_to_user_group' :
        $updategroupaccess = true;
        $usergroup = (int)$_REQUEST['usergroup'];
        $docgroup = (int)$_REQUEST['docgroup'];
        $where = "webgroup='{$usergroup}' AND documentgroup='{$docgroup}'";
        $total = $modx->db->getValue($modx->db->select('count(*)', $tbl_webgroup_access, $where));
        if ($total <= 0) {
            $f['webgroup'] = $usergroup;
            $f['documentgroup'] = $docgroup;
            if (!$rs = $modx->db->insert($f, $tbl_webgroup_access)) {
                exit("Failed to link document group to user group");
            }
        }
        break;
    case 'remove_document_group_from_user_group' :
        $updategroupaccess = true;
        $coupling = (int)$_REQUEST['coupling'];
        if (!$rs = $modx->db->delete($tbl_webgroup_access, "id='{$coupling}'")) {
            exit("Failed to remove document group from user group");
        }
        break;
    default :
        exit("No operation set in request.");
}

// secure web documents - flag as private
if ($updategroupaccess == true) {
    include(MODX_CORE_PATH . 'secure_web_documents.inc.php');
    secureWebDocument();

    // Update the private group column
    $sql = 'UPDATE ' . $tbl_documentgroup_names . ' AS dgn ' .
        'LEFT JOIN ' . $tbl_webgroup_access . ' AS wga ON wga.documentgroup = dgn.id ' .
        'SET dgn.private_webgroup = (wga.webgroup IS NOT NULL)';
    $rs = $modx->db->query($sql);
}

header('Location: index.php?a=91');
