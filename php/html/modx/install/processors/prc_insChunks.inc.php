<?php
if (!sessionv('chunk') && !sessionv('installdata')) {
    return;
}

echo '<h3>' . lang('chunks') . ':</h3>';
foreach ($tplChunks as $i => $tplInfo) {
    if (
        !in_array($i, sessionv('chunk'))
        &&
        (!sessionv('installdata') || !in_array('sample', $tplInfo['installset']))
    ) {
        continue;
    }

    if (!is_file($tplInfo['tpl_file_path'])) {
        echo ng(
            db()->escape($tplInfo['name'])
            , sprintf(
                "%s '%s' %s"
                , lang('unable_install_chunk')
                , $tplInfo['tpl_file_path']
                , lang('not_found')
            )
        );
        continue;
    }
    $field = array(
        'name' => $tplInfo['name'],
        'description' => $tplInfo['description'],
        'snippet' => preg_replace(
            "@^.*?/\*\*.*?\*/\s+@s"
            , ''
            , file_get_contents($tplInfo['tpl_file_path'])
            , 1
        ),
        'category' => getCreateDbCategory($tplInfo['category'])
    );

    $rs = db()->select(
        '*'
        , '[+prefix+]site_htmlsnippets'
        , sprintf(
            "name='%s'"
            , db()->escape($tplInfo['name'])
        )
    );
    if (!$rs) {
        if(!db()->insert(db()->escape($field), '[+prefix+]site_htmlsnippets')) {
            $errors++;
            showError();
            return;
        }
        echo ok($tplInfo['name'], lang('installed'));
    } else {
        if ($tplInfo['overwrite'] !== 'false') {
            $updated = db()->update(
                db()->escape($field)
                , '[+prefix+]site_htmlsnippets'
                , sprintf("name='%s'", $tplInfo['name'])
            );
        } else {
            $swap_name = $tplInfo['name'] . '-' . str_replace('.', '_', $modx_version);
            $i = 0;
            while ($i < 100) {
                $field['name'] = $i ? sprintf('%s(%s)', $swap_name, $i) : $swap_name;
                $rs = db()->select(
                    '*'
                    , '[+prefix+]site_htmlsnippets'
                    , sprintf(
                        "name='%s'"
                        , db()->escape($field['name'])
                    )
                );
                if (!db()->getRecordCount($rs)) {
                    break;
                }
                $i++;
            }
            if(!db()->insert(db()->escape($field), '[+prefix+]site_htmlsnippets')) {
                $errors++;
                showError();
                return;
            }
        }
        echo ok($tplInfo['name'], lang('upgraded'));
    }
}
