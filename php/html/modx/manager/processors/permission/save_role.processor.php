<?php
if (!isset($modx) || !$modx->isLoggedin()) {
    exit;
}
if (!$modx->hasPermission('save_role')) {
    $e->setError(3);
    $e->dumpError();
}

$input = $_POST;
extract($input);

$name = $modx->db->escape(trim($name));
$description = $modx->db->escape(trim($description));

if (!isset($name) || empty($name)) {
    echo 'Please enter a name for this role!';
    exit;
}

$edit_parser = (isset ($edit_parser)) ? $edit_parser : '0';
$save_parser = (isset ($save_parser)) ? $save_parser : '0';

// setup fields
$fields = compact(explode(',',
    'name,description,frames,home,view_document,new_document,save_document,move_document,publish_document,delete_document,empty_trash,action_ok,logout,help,messages,new_user,edit_user,logs,edit_parser,save_parser,edit_template,settings,credits,new_template,save_template,delete_template,edit_snippet,new_snippet,save_snippet,delete_snippet,edit_chunk,new_chunk,save_chunk,delete_chunk,empty_cache,edit_document,change_password,error_dialog,about,file_manager,save_user,delete_user,save_password,edit_role,save_role,delete_role,new_role,access_permissions,bk_manager,new_plugin,edit_plugin,save_plugin,delete_plugin,new_module,edit_module,save_module,delete_module,exec_module,view_eventlog,delete_eventlog,manage_metatags,edit_doc_metatags,new_web_user,edit_web_user,save_web_user,delete_web_user,web_access_permissions,view_unpublished,import_static,export_static,remove_locks,view_schedule'));

if ($_POST['mode'] == '38') {
    $search_name = $name;
} elseif ($_POST['mode'] == '35') {
    $rs = $modx->db->select('name', '[+prefix+]user_roles', "id='{$id}'");
    $row = $modx->db->getRow($rs);
    if ($row['name'] !== $name) {
        $search_name = $row['name'];
    }
} else {
    $search_name = false;
}

if ($search_name !== false && $_POST['mode'] == '38') {
    $rs = $modx->db->select('id', '[+prefix+]user_roles', "name='{$search_name}'");
    if (0 < $modx->db->getRecordCount($rs)) {
        echo "An error occured while attempting to save the new role.";
        exit;
    }
}

switch ($_POST['mode']) {
    case '38' :
        $id = $modx->db->insert($fields, '[+prefix+]user_roles');
        break;
    case '35' :
        $rs = $modx->db->update($fields, '[+prefix+]user_roles', "id='{$id}'");
        if ($rs) {
            $cache_path = "{$modx->config['base_path']}assets/cache/rolePublishing.idx.php";
            if (is_file($cache_path)) {
                $role = unserialize(file_get_contents($cache_path));
            }
            $role[$id] = time();
            file_put_contents($cache_path, serialize($role));
        } else {
            echo "An error occured while attempting to update the role. <br />" . $modx->db->getLastError();
            exit;
        }
        break;
    default :
        echo "Erm... You supposed to be here now?";
        exit;
}
header('Location: index.php?a=86');
