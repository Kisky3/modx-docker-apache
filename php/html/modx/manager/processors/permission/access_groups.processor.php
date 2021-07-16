<?php
if (!isset($modx) || !$modx->isLoggedin()) {
    exit;
}
if (!$modx->hasPermission('access_permissions')) {
    $e->setError(3);
    $e->dumpError();
}

// access group processor.
// figure out what the user wants to do...

// Get table names (alphabetical)
$tbl_document_groups = $modx->getFullTableName('document_groups');
$tbl_documentgroup_names = $modx->getFullTableName('documentgroup_names');
$tbl_member_groups = $modx->getFullTableName('member_groups');
$tbl_membergroup_access = $modx->getFullTableName('membergroup_access');
$tbl_membergroup_names = $modx->getFullTableName('membergroup_names');

$updategroupaccess = false;

switch ($_REQUEST['operation']) {
    case "add_user_group" :
        $groupname = $_REQUEST['newusergroup'];
        if (empty($groupname)) {
            warning("no group name specified");
        } else {
            $f['name'] = $modx->db->escape($groupname);
            $groupid = $modx->db->insert_ignore($f, $tbl_membergroup_names);
            if (!$groupid) {
                warning("Failed to insert new group. Possible duplicate group name?");
            }
            // invoke OnManagerCreateGroup event
            $tmp = array(
                'groupid' => $groupid,
                'groupname' => $groupname,
            );
            $modx->invokeEvent('OnManagerCreateGroup', $tmp);
        }
        break;
    case "add_document_group" :
        $groupname = $_REQUEST['newdocgroup'];
        if (empty($groupname)) {
            warning("no group name specified");
        } else {
            $f['name'] = $modx->db->escape($groupname);
            $groupid = $modx->db->insert_ignore($f, $tbl_documentgroup_names);
            if (!$groupid) {
                warning("Failed to insert new group. Possible duplicate group name?");
            }

            // invoke OnCreateDocGroup event
            $tmp = array(
                'groupid' => $groupid,
                'groupname' => $groupname,
            );
            $modx->invokeEvent('OnCreateDocGroup', $tmp);
        }
        break;
    case "delete_user_group" :
        $updategroupaccess = true;
        $usergroup = (int)$_REQUEST['usergroup'];
        if (empty($usergroup)) {
            warning("No user group name specified for deletion");
        } else {
            if (!$modx->db->delete($tbl_membergroup_names, "id='{$usergroup}'")) {
                warning("Unable to delete group. SQL failed.");
            }
            if (!$modx->db->delete($tbl_membergroup_access, "membergroup='{$usergroup}'")) {
                warning("Unable to delete group from access table. SQL failed.");
            }
            if (!$modx->db->delete($tbl_member_groups, "user_group='{$usergroup}'")) {
                warning("Unable to delete user-group links. SQL failed.");
            }
        }
        break;
    case "delete_document_group" :
        $group = (int)$_REQUEST['documentgroup'];
        if (empty($group)) {
            warning("No document group name specified for deletion");
        } else {
            if (!$modx->db->delete($tbl_documentgroup_names, "id='{$group}'")) {
                warning("Unable to delete group. SQL failed.");
            }
            if (!$modx->db->delete($tbl_membergroup_access, "documentgroup='{$group}'")) {
                warning("Unable to delete group from access table. SQL failed.");
            }
            if (!$modx->db->delete($tbl_document_groups, "document_group='{$group}'")) {
                warning("Unable to delete document-group links. SQL failed.");
            }
        }
        break;
    case "rename_user_group" :
        $newgroupname = $modx->db->escape($_REQUEST['newgroupname']);
        if (empty($newgroupname)) {
            warning("no group name specified");
        }

        $groupid = (int)$_REQUEST['groupid'];
        if (empty($groupid)) {
            warning("No group id specified");
        }

        $f['name'] = $newgroupname;
        if (!$modx->db->update($f, $tbl_membergroup_names, "id='{$groupid}'", '', '1')) {
            warning("Failed to update group name. Possible duplicate group name?");
        }
        break;
    case "rename_document_group" :
        $newgroupname = $modx->db->escape($_REQUEST['newgroupname']);
        if (empty($newgroupname)) {
            warning("no group name specified");
        }

        $groupid = (int)$_REQUEST['groupid'];
        if (empty($groupid)) {
            warning("No group id specified");
        }
        $f['name'] = $newgroupname;
        if (!$modx->db->update($f, $tbl_documentgroup_names, "id='{$groupid}'", '', '1')) {
            warning("Failed to update group name. Possible duplicate group name?");
        }
        break;
    case "add_document_group_to_user_group" :
        $updategroupaccess = true;
        $usergroup = (int)$_REQUEST['usergroup'];
        $docgroup = (int)$_REQUEST['docgroup'];
        $where = "membergroup='{$usergroup}' AND documentgroup='{$docgroup}'";
        $limit = $modx->db->getValue($modx->db->select('count(*)', $tbl_membergroup_access, $where));
        if ($limit <= 0) {
            $f = array();
            $f['membergroup'] = $usergroup;
            $f['documentgroup'] = $docgroup;
            if (!$modx->db->insert_ignore($f, $tbl_membergroup_access)) {
                warning("Failed to link document group to user group");
            }
        } else {
            warning("User that coupling already exists"); //alert user that coupling already exists?
        }
        break;
    case "remove_document_group_from_user_group" :
        $updategroupaccess = true;
        $coupling = (int)$_REQUEST['coupling'];
        if (!$modx->db->delete($tbl_membergroup_access, "id='{$coupling}'")) {
            warning("Failed to remove document group from user group");
        }
        break;
    default :
        warning("No operation set in request.");
}

// secure manager documents - flag as private
if ($updategroupaccess == true) {
    include(MODX_CORE_PATH . 'secure_mgr_documents.inc.php');
    secureMgrDocument();

    // Update the private group column
    $sql = 'UPDATE ' . $tbl_documentgroup_names . ' AS dgn ' .
        'LEFT JOIN ' . $tbl_membergroup_access . ' AS mga ON mga.documentgroup = dgn.id ' .
        'SET dgn.private_memgroup = (mga.membergroup IS NOT NULL)';
    $rs = $modx->db->query($sql);
}

header('Location: index.php?a=40');

function warning($msg) {
    exit($msg);
}