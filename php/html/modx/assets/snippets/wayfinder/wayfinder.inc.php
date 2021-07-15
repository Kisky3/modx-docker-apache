<?php
/*
::::::::::::::::::::::::::::::::::::::::
 Snippet name: Wayfinder
 Short Desc: builds site navigation
 Version: 2.1
 Authors:
    Kyle Jaebker (muddydogpaws.com)
    Ryan Thrash (vertexworks.com)
 Date: February 27, 2006
::::::::::::::::::::::::::::::::::::::::
*/

class Wayfinder {
    public $_config;
    public $_templates;
    public $_css;
    public $docs = array();
    public $parentTree = array();
    public $hasChildren = array();
    public $tvList = array();
    public $debugInfo = array();

    function __construct() {
    }

    public function run() {
        global $modx;
        if ($this->_config['debug']) {
            $this->addDebugInfo('settings','Settings','Settings','Settings used to create this menu.',$this->_config);
            $this->addDebugInfo('settings','CSS','CSS Settings','Available CSS options.',$this->_css);
        }
        //setup here checking array
        $this->parentTree = $modx->getParentIds($this->_config['hereId']);
        $this->parentTree[] = $this->_config['hereId'];
        //Load the templates
        $this->checkTemplates();
        //Register any scripts
        if ($this->_config['cssTpl'] || $this->_config['jsTpl']) {
            $this->regJsCss();
        }
        //Get all of the documents
        $this->docs = $this->getData();

        if (!empty($this->docs)) {
            ksort($this->docs);                   //Sort documents by level for proper wrapper substitution
            return $this->buildMenu(); //build the menu
        }

        $noneReturn = $this->_config['debug'] ? '<p style="color:#f00">No documents found for menu.</p>' : '';
        return $noneReturn;
    }

    function buildMenu() {
        $output = '';
        foreach ($this->docs as $level => $parents) {
            foreach ($parents as $parentId => $children) {
                if ($this->_config['hideSubMenus'] && !$this->isHere($parentId) && 1<$level) {
                    continue;
                }

                $subMenu = $this->buildSubMenu($children, $level);
                if ($level==1 && (!$this->_config['displayStart'] || $this->_config['id']==0)) {
                    $output = $subMenu;
                    continue;
                }

                if($level==0 && $this->_config['displayStart']) {
                    $output = $subMenu;
                    continue;
                }

                $wrapper_text = sprintf('[+wf.wrapper.%s+]', $parentId);
                if(strpos($output,$wrapper_text)!==false) {
                    $output = str_replace(
                        $wrapper_text
                        , $subMenu
                        , $output
                    );
                }
            }
        }

        //Return the final Menu
        return $output;
    }

    function buildSubMenu($subDocs,$level) {
        global $modx;

        $subMenuOutput = '';
        $counter = 1;
        $total = count($subDocs);

        //Loop through each document to render output
        foreach ($subDocs as $docId => $docInfo) {
            $docInfo['level'] = $level;
            $docInfo['first'] = $counter==1 ? 1 : 0;
            //Determine if last item in group
            if ($counter == $total && 0 < $total) {
                $docInfo['last'] = 1;
            } else {
                $docInfo['last'] = 0;
            }

            //Determine if document has children
            if(in_array($docInfo['id'],$this->hasChildren)) {
                $docInfo['hasChildren'] = 1;
                $numChildren            = count($this->docs[$level+1][$docInfo['id']]);
            } else {
                $docInfo['hasChildren'] = 0;
                $numChildren            = 0;
            }

            //Render the row output
            $subMenuOutput .= $this->renderRow($docInfo,$numChildren,$counter);
            //Update counter for last check
            $counter++;
        }

        if ($level < 1) return $subMenuOutput;

        //Determine wrapper class
        if ($level==1) $wrapperClass = 'outercls';
        else           $wrapperClass = 'innercls'; // 1<$level

        //Get the class names for the wrapper
        $classNames = $this->setItemClass($wrapperClass, 0, 0, 0, $level);

        $ph = array();
        $ph['wf.wrapper']    = $subMenuOutput;
        $ph['wf.classes']    = $classNames ? sprintf(' class="%s"',$classNames) : '';
        $ph['wf.classnames'] = $classNames;
        $ph['wf.level']      = $level;
        //Determine which wrapper template to use
        if ($this->_templates['innerTpl'] && $wrapperClass === 'innercls') {
            $tpl = $this->_templates['innerTpl'];
        } else {
            $tpl = $this->_templates['outerTpl'];
        }

        //Process the wrapper
        $subMenuOutput = $modx->parseText($tpl,$ph);
        //Debug
        if ($this->_config['debug']) {
            $info = array();
            $info['template'] = ($tpl==$this->_templates['innerTpl']) ? 'innerTpl':'outerTpl';
            foreach ($ph as $k=>$v) {
                if ($k !== 'wf.wrapper') {
                    $info[sprintf('[+%s+]', $k)] = $v;
                }
            }
            $groupkey = $docInfo['parent'];
            $header  = "Wrapper for items with parent {$groupkey}.";
            $message = 'These fields were used when processing the wrapper for the following documents.';
            $this->addDebugInfo('wrapper',$groupkey,$header,$message,$info);
        }
        //Return the submenu
        return $subMenuOutput;
    }

    //render each rows output
    function renderRow(&$resource,$numChildren,$curNum) {
        global $modx;
        $refid = $resource['id'];

        // Determine fields for use from referenced resource
        if ($this->_config['useReferenced'] && $resource['type'] === 'reference' && preg_match('@^[1-9][0-9]*$@',$resource['content'])) {
            if ($this->_config['useReferenced'] === 'id') {
                // if id only, do not need get referenced data
                $resource['id'] = $resource['content'];
            } elseif ($referenced = $modx->getDocument($resource['content'])) {
                if (in_array($this->_config['useReferenced'], explode(',', '1,*'))) {
                    $this->_config['useReferenced'] = array_keys($resource);
                }
                if (!is_array($this->_config['useReferenced'])) {
                    $this->_config['useReferenced'] = preg_split("/[\s,]+/", $this->_config['useReferenced']);
                }
                $this->_config['useReferenced'] = array_diff($this->_config['useReferenced'], explode(',', 'content,parent,isfolder'));

                if ($resource[$this->_config['textOfLinks']]) {
                    $linkTextField = $this->_config['textOfLinks'];
                } else {
                    $linkTextField = 'pagetitle';
                }

                foreach ($this->_config['useReferenced'] as $field) {
                    if (isset($referenced[$field])) {
                        $resource[$field] = $referenced[$field];
                    }
                    if (in_array($field, array('linktext', $linkTextField), true)) {
                        $resource['linktext'] = $referenced[$linkTextField];
                    }
                    if (in_array($field, array('title', $this->_config['titleOfLinks']), true)) {
                        $resource['title'] = $referenced[$this->_config['titleOfLinks']];
                    }
                }
            }
        }

        //Determine which template to use
        if ($this->_config['displayStart'] && $resource['level'] == 0) {
            $usedTemplate = 'startItemTpl';
        } elseif ($resource['id'] == $modx->documentIdentifier
            && $resource['isfolder']
            && $this->_templates['parentRowHereTpl']
            && ($resource['level'] < $this->_config['level'] || $this->_config['level'] == 0)
            && $numChildren) {
            $usedTemplate = 'parentRowHereTpl';
        } elseif ($resource['id'] == $modx->documentIdentifier && $this->_templates['innerHereTpl'] && $resource['level'] > 1) {
            $usedTemplate = 'innerHereTpl';
        } elseif ($resource['id'] == $modx->documentIdentifier && $this->_templates['hereTpl']) {
            $usedTemplate = 'hereTpl';
        } elseif ($resource['isfolder']
            && $this->_templates['activeParentRowTpl']
            && ($resource['level'] < $this->_config['level'] || $this->_config['level'] == 0)
            && $this->isHere($resource['id'])
            && $numChildren) {
            $usedTemplate = 'activeParentRowTpl';
        } elseif ($resource['isfolder']
            && ($resource['template']=='0' || is_numeric(strpos($resource['link_attributes'],'rel="category"')))
            && $this->_templates['categoryFoldersTpl']
            && ($resource['level'] < $this->_config['level'] || $this->_config['level'] == 0)) {
            $usedTemplate = 'categoryFoldersTpl';
        } elseif ($resource['isfolder']
            && $this->_templates['parentRowTpl']
            && ($resource['level'] < $this->_config['level'] || $this->_config['level'] == 0)
            && $numChildren) {
            $usedTemplate = 'parentRowTpl';
        } elseif ($resource['level'] > 1 && $this->_templates['innerRowTpl']) {
            $usedTemplate = 'innerRowTpl';
        } elseif ($resource['last'] && $this->_templates['lastRowTpl']) {
            $usedTemplate = 'lastRowTpl';
        } else {
            $usedTemplate = 'rowTpl';
        }
        //Setup the new wrapper name and get the class names
        $useSub = $resource['hasChildren'] ? "[+wf.wrapper.{$refid}+]" : '';
        $classNames = $this->setItemClass('rowcls',$resource['id'],$resource['first'],$resource['last'],$resource['level'],$resource['hasChildren'],$resource['type']);
        $useClass = $classNames ? $useClass = sprintf(' class="%s"',$classNames) : '';

        //Setup the row id if a prefix is specified
        if ($this->_config['rowIdPrefix']) {
            $useId = sprintf(' id="%s%s"', $this->_config['rowIdPrefix'], $resource['id']);
        }
        else {
            $useId = '';
        }

        //Load row values into placholder array
        $ph = array();
        $ph['wf.wrapper']      = $useSub;
        $ph['wf.classes']      = $useClass;
        $ph['wf.classnames']   = $classNames;
        $ph['wf.link']         = $resource['link'];
        $ph['url']             = $resource['link'];
        $ph['wf.title']        = !$this->_config['entityEncode'] ? $resource['title']       : $modx->hsc($resource['title']);
        $ph['wf.linktext']     = !$this->_config['entityEncode'] ? $resource['linktext']    : $modx->hsc($resource['linktext']);
        $ph['wf.id']           = $useId;
        $ph['wf.alias']        = $resource['alias'];
        $ph['wf.attributes']   = $resource['link_attributes'];
        $ph['wf.docid']        = $resource['id'];
        $ph['wf.introtext']    = !$this->_config['entityEncode'] ? $resource['introtext']   : $modx->hsc($resource['introtext']);
        $ph['wf.description']  = !$this->_config['entityEncode'] ? $resource['description'] : $modx->hsc($resource['description']);
        $ph['wf.subitemcount'] = $numChildren;
        $ph['wf.refid']        = $refid;
        $ph['wf.menuindex']    = $resource['menuindex'];
        $ph['wf.iterator']     = $curNum;

        //Add document variables to the placeholder array
        foreach ($resource as $dvName => $dvVal) {
            $ph[$dvName] = $dvVal;
        }

        //If tvs are used add them to the placeholder array
        if ($this->tvList) {
            foreach ($this->tvList as $tvName) {
                $ph[$tvName] = $resource[$tvName];
            }
        }
        //Debug
        if ($this->_config['debug']) {
            $debugDocInfo = array();
            $debugDocInfo['template'] = $usedTemplate;
            foreach ($ph as $k=>$v) {
                $k = sprintf('[+%s+]', $k);
                $debugDocInfo[$k] = $v;
            }
            if(count($resource)%2!=0) {
                $resource['-'] = '';
            }
            $this->addDebugInfo(
                'row'
                , sprintf(
                    '%s:%s'
                    , $resource['parent']
                    , $resource['id']
                )
                ,"Doc: #{$resource['id']}"
                , 'The following fields were used when processing this document.'
                , $debugDocInfo
            );
            $this->addDebugInfo(
                'rowdata'
                , sprintf(
                    '%s:%s'
                    , $resource['parent']
                    , $resource['id']
                )
                ,"Doc: #{$resource['id']}"
                , 'The following fields were retrieved from the database for this document.'
                ,$resource
            );
        }
        //Process the row

        $output = $modx->parseText($this->_templates[$usedTemplate],$ph);

        return $output . $this->_config['nl'];
    }

    //determine style class for current item being processed
    function setItemClass($classType, $docId = 0, $first = 0, $last = 0, $level = 0, $isFolder = 0, $type = 'document') {
        $classNames = array();
        $class  = &$this->_css;
        $config = &$this->_config;

        switch($classType) {
            case 'outercls':
                if($class['outer']) {
                    $classNames[] = $class['outer'];
                }             //Set outer class
                break;
            case 'innercls':
                if($class['inner']) {
                    $classNames[] = $class['inner'];
                }             //Set inner class
                if($class['outerLevel']) {
                    $classNames[] = $class['outerLevel'] . $level;
                } //Set level class
                break;
            case 'rowcls':
                if($class['row']) {
                    $classNames[] = $class['row'];
                }               //Set row class
                if($first && $class['first']) {
                    $classNames[] = $class['first'];
                }             //Set first class
                if($last && $class['last']) {
                    $classNames[] = $class['last'];
                }              //Set last class
                if($class['level']) {
                    $classNames[] = $class['level'] . $level;
                }      //Set level class

                if($class['here'] && $this->isHere($docId)) {
                    $classNames[] = $class['here'];
                }              //Set here class
                if($class['self'] && $docId==$config['hereId']) {
                    $classNames[] = $class['self'];
                }              //Set self class
                if($class['weblink'] && $type === 'reference') {
                    $classNames[] = $class['weblink'];
                }           //Set class for weblink

                if($isFolder && $class['parent']) {
                  if ($level < $config['level'] || $config['level']==0) {
                    if ($this->isHere($docId) || !$config['hideSubMenus']) {
                        $classNames[] = $class['parent'];
                    }            // Set parentFolder class
                  }
                }
                break;
        }

        if($classNames) {
            return join(' ', $classNames);
        }
        return '';
    }

    //determine "you are here"
    function isHere($did) {
        return in_array($did,$this->parentTree);
    }

    //Add the specified css & javascript chunks to the page
    function regJsCss() {
        global $modx;
        //Debug
        if ($this->_config['debug']) {
            $jsCssDebug = array('js' => 'None Specified.', 'css' => 'None Specified.');
        }
        //Check and load the CSS
        if ($this->_config['cssTpl']) {
            $cssChunk = $this->fetch($this->_config['cssTpl']);
            if ($cssChunk) {
                $modx->regClientCSS($cssChunk);
                if ($this->_config['debug']) {
                    $jsCssDebug['css'] = sprintf('The CSS in %s was registered.', $this->_config['cssTpl']);
                }
            }
            elseif ($this->_config['debug']) {
                $jsCssDebug['css'] = sprintf('The CSS in %s was not found.', $this->_config['cssTpl']);
            }
        }
        //Check and load the Javascript
        if ($this->_config['jsTpl']) {
            $jsChunk = $this->fetch($this->_config['jsTpl']);
            if ($jsChunk) {
                $modx->regClientStartupScript($jsChunk);
                if ($this->_config['debug']) {
                    $jsCssDebug['js'] = sprintf('The Javascript in %s was registered.', $this->_config['jsTpl']);
                }
            }
            elseif ($this->_config['debug']) {
                $jsCssDebug['js'] = sprintf('The Javascript in %s was not found.', $this->_config['jsTpl']);
            }
        }
        //Debug
        if ($this->_config['debug']) {
            $this->addDebugInfo('settings', 'JSCSS', 'JS/CSS Includes', 'Results of CSS & Javascript includes.', $jsCssDebug);
        }
    }

    //Get all of the documents from the database
    private function getData() {
        global $modx;

        $ids = array();

        if (!$this->_config['hideSubMenus']) {
            $ids = $modx->getChildIds(
                $this->_config['id']
                , $this->_config['level'] ?: 10
            );
        } else {
            $ids = $modx->getChildIds($this->_config['id'], 1, $ids);
            // if startId not in parents, only show children of startId
            $parents = array($this->_config['hereId']) + $modx->getParentIds($this->_config['hereId']);
            if ($this->_config['id'] == 0 || in_array($this->_config['id'], $parents)) {
                //remove parents higher than startId(including startId)
                $startId_parents = array($this->_config['id']) + $modx->getParentIds($this->_config['id']);
                $parents = array_diff($parents, $startId_parents);

                //remove parents lower than level of startId + level depth
                $parents = array_slice(
                    array_reverse($parents)
                    , 0
                    , ($this->_config['level'] ?: 10) -1);

                foreach($parents as $p) {
                    $ids += $modx->getChildIds($p, 1, $ids);
                }
            }
        }
        //Get all of the ids for processing
        if ($this->_config['displayStart'] && $this->_config['id'] !== 0) {
            $ids[] = $this->_config['id'];
        }

        if (!$ids) {
            return array();
        }

        $from = array();
        $from[] = '[+prefix+]site_content sc';
        $from[] = 'LEFT JOIN [+prefix+]document_groups dg ON dg.document=sc.id';

        $where = array();
        $where[] = 'sc.published=1';
        $where[] = 'AND sc.deleted=0';
        if($modx->isFrontend()) {
            if(!$this->_config['showPrivate']) {
                $where[] = 'AND sc.privateweb=0';
            }
        }
        elseif($_SESSION['mgrRole']!=1) {
            if($modx->getUserDocGroups()) {
                $where[] = sprintf(
                    'AND (sc.privatemgr=0 OR dg.document_group IN (%s))'
                    , implode(',', $modx->getUserDocGroups()
                    )
                );
            } else {
                $where[] = 'AND sc.privatemgr=0';
            }
        }

        //Add the ignore hidden option to the where clause
        if (!$this->_config['ignoreHidden']) {
            $where[] = 'AND sc.hidemenu=0';
        }

        //add the include docs to the where clause
        if ($this->_config['includeDocs']) {
            $where[] = sprintf(
                'AND sc.id IN (%s)'
                , $this->_config['includeDocs']
            );
        }

        //add the exclude docs to the where clause
        if ($this->_config['excludeDocs']) {
            $where[] = sprintf(
                'AND (sc.id NOT IN (%s))'
                , $this->_config['excludeDocs']
            );
        }

        //add custom where conditions
        if (!empty($this->_config['where'])) {
            $where[] = sprintf(
                'AND (%s)'
                , $this->_config['where']
            );
        }

        $where[]  = sprintf(
            'AND sc.id IN (%s)'
            , implode(',',$ids)
        );
        $where[] = 'GROUP BY sc.id';

        //Determine sorting
        if (strtolower($this->_config['sortBy']) === 'random') {
            $sort = 'rand()';
        }
        else {
            // modify field names to use sc. table reference
            $_ = explode(',', $this->_config['sortBy']);
            foreach($_ as $i=>$v) {
                $_[$i] = 'sc.' . trim($v);
            }
            $sort = implode(',', $_);
            $sort = "{$sort} {$this->_config['sortOrder']}";
        }

        //run the query
        $fields = explode(',','id,menutitle,pagetitle,introtext,menuindex,published,hidemenu,parent,isfolder,description,alias,longtitle,type,content,template,link_attributes');
        foreach($fields as $i=>$v) {
            if    ($v === 'alias')   $fields[$i] = "IF(sc.alias='', sc.id, sc.alias) AS alias";
            elseif($v === 'content') $fields[$i] = "IF(sc.type='reference',sc.content,'') AS content";
            else                     $fields[$i] = 'sc.'.$v;
        }

        $result = $modx->db->select(
            'DISTINCT ' . join(',', $fields)
            , $from
            , $where
            , $sort
            , $this->_config['limit'] ? $this->_config['limit'] : ''
        );

        $level = 1;
        $prevParent = -1;
        //Setup startlevel for determining each items level
        if ($this->_config['id'] == 0) {
            $startLevel = 0;
        } else {
            $startLevel = count($modx->getParentIds($this->_config['id']));
            $startLevel = $startLevel ? $startLevel+1 : 1;
        }
        $resultIds = array();
        $resourceArray = array();
        //loop through the results
        while($row = $modx->db->getRow($result)) {
            $resultIds[] = $row['id'];
            //Create the link
            $linkScheme = $this->_config['fullLink'] ? 'full' : '';
            if ($this->_config['useWeblinkUrl'] && $row['type'] === 'reference') {
                if (preg_match('@^[1-9][0-9]*$@',$row['content'])) {
                    $row['link'] = $modx->makeUrl((int)$row['content'], '', '', $linkScheme);
                }
                else {
                    $row['link'] = $row['content'];
                }
            }
            elseif ($row['id'] == $modx->config['site_start']) {
                $row['link'] = $modx->config['site_url'];
            }
            else {
                $row['link'] = $modx->makeUrl($row['id'], '', '', $linkScheme);
            }

            //determine the level, if parent has changed
            if ($prevParent !== $row['parent']) {
                $level = count($modx->getParentIds($row['id'])) + 1 - $startLevel;
            }
            //add parent to hasChildren array for later processing
            if (($level > 1 || $this->_config['displayStart']) && !in_array($row['parent'],$this->hasChildren)) {
                $this->hasChildren[] = $row['parent'];
            }
            //set the level
            $row['level'] = $level;
            $prevParent = $row['parent'];
            //determine other output options
            if(strpos($this->_config['textOfLinks'],',')!==false) {
                $_ = explode(',', $this->_config['textOfLinks']);
                foreach($_ as $v) {
                    $v = trim($v);
                    if($row[$v]) {
                        $useTextField = $v;
                        break;
                    }
                }
                if(empty($useTextField)) {
                    $useTextField = 'pagetitle';
                }
            }
            elseif($row[$this->_config['textOfLinks']]) {
                $useTextField = $this->_config['textOfLinks'];
            }
            else {
                $useTextField = 'pagetitle';
            }

            $row['linktext'] = $row[$useTextField];

            $useTitleField = null;

            if(strpos($this->_config['titleOfLinks'],',')!==false) {
                $_ = explode(',', $this->_config['titleOfLinks']);
                foreach($_ as $v) {
                    $v = trim($v);
                    if($row[$v]) {
                        $useTitleField = $v;
                        break;
                    }
                }
                if(empty($useTitleField)) {
                    $useTitleField = $this->_config['titleOfLinks'];
                }
            }
            else {
                $useTitleField = $this->_config['titleOfLinks'];
            }

            $row['title'] = $row[$useTitleField];

            //If tvs were specified keep array flat otherwise array becomes level->parent->doc
            if ($this->tvList) {
                $tempResults[] = $row;
            }
            else {
                $resourceArray[$row['level']][$row['parent']][] = $row;
            }
        }
        //Process the tvs
        if ($this->tvList && !empty($resultIds)) {
            $tvValues = array();
            //loop through all tvs and get their values for each document
            foreach ($this->tvList as $tvName) {
                $tvValues = $this->array_merge_recursive($this->appendTV($tvName,$resultIds),$tvValues);
            }
            //loop through the document array and add the tvar values to each document
            foreach ($tempResults as $tempDocInfo) {
                if (isset($tvValues["#{$tempDocInfo['id']}"])) {
                    foreach ($tvValues["#{$tempDocInfo['id']}"] as $tvName => $tvValue) {
                        $tempDocInfo[$tvName] = $tvValue;
                    }
                }
                $resourceArray[$tempDocInfo['level']][$tempDocInfo['parent']][] = $tempDocInfo;
            }
        }
        //return final docs
        return $resourceArray;
    }

    // ---------------------------------------------------
    // Function: appendTV taken from Ditto (thanks Mark)
    // Apeend a TV to the documents array
    // ---------------------------------------------------

    function appendTV($tvname,$docIDs){
        global $modx;

        $resourceArray = array();
        foreach($docIDs as $id) {
            $tv = $modx->getTemplateVarOutput($tvname, $id);
            $resourceArray["#{$id}"][$tvname] = $tv[$tvname];
        }
        return $resourceArray;
    }

    // ---------------------------------------------------
    // Get a list of all available TVs
    // ---------------------------------------------------

    function getTVList() {
        global $modx;
        $tvs = $modx->db->select('name', '[+prefix+]site_tmplvars');
            // TODO: make it so that it only pulls those that apply to the current template
        return $modx->db->getColumn('name', $tvs);
    }

    //debugging to check for valid chunks
    function checkTemplates() {
        $default['outerTpl'] = '<ul[+wf.classes+]>[+wf.wrapper+]</ul>';
        $default['rowTpl'] = '<li[+wf.id+][+wf.classes+]><a href="[+wf.link+]" title="[+wf.title+]" [+wf.attributes+]>[+wf.linktext+]</a>[+wf.wrapper+]</li>';
        $default['startItemTpl'] = '<h2[+wf.id+][+wf.classes+]>[+wf.linktext+]</h2>[+wf.wrapper+]';

        $find_fields = array();

        foreach ($this->_templates as $name => $string) {
            $fetched_tpl = $string ? $this->fetch($string) : false;
            if (!$string || !$fetched_tpl) {
                $this->_templates[$name] = isset($default[$name]) ? $default[$name] : false;
                if ($this->_config['debug']) {
                    $this->addDebugInfo(
                        'template'
                        , $name
                        , $name
                        , 'No template found, using default.'
                        , array($name => $this->_templates[$name])
                    );
                }
                continue;
            }

            $this->_templates[$name] = $fetched_tpl;
            $tv = $this->findTemplateVars($fetched_tpl);
            foreach ($tv as $k) {
                $find_fields[] = $k;
            }

            if ($this->_config['debug']) {
                $this->addDebugInfo(
                    'template'
                    , $name
                    , $name
                    , 'Template Found.'
                    , array($name => $this->_templates[$name])
                );
            }
        }

        if ($find_fields) {
            $find_fields = array_unique($find_fields);
            $allTvars = $this->getTVList();

            foreach ($find_fields as $field) {
                if (in_array($field, $allTvars, true)) {
                    $this->tvList[] = $field;
                }
            }
            if ($this->_config['debug']) {
                $this->addDebugInfo(
                    'tvars'
                    , 'tvs'
                    , 'Template Variables'
                    , 'The following template variables were found in your templates.'
                    , $this->tvList
                );
            }
        }
    }

    private function array_merge_recursive($arr1, $arr2) {
        foreach($arr2 as $key=>$value) {
            if(!is_array($arr1)) {
                $arr1 = array($arr1);
            }
            if(is_array($value)) {
                $arr1[$key] = $this->array_merge_recursive($arr1[$key], $value);
            } else {
                $arr1[$key] = $value;
            }
        }
        return $arr1;
    }

    function fetch($tpl){
        global $modx;

        if(strpos($tpl, '@FILE') === 0) {
            return file_get_contents(substr($tpl, 6));
        }
        if(strpos($tpl, '@CODE') === 0) {
            return substr($tpl, 6);
        }
        if($modx->getChunk($tpl) != '') {
            return $modx->getChunk($tpl);
        }
        return $tpl;
    }


    function findTemplateVars($tpl) {
        preg_match_all('~\[\+([^:]*?)(:|\+\])~', $tpl, $matches);
        $tvnames = array();
        foreach($matches[1] as $tv) {
            if (strpos(strtolower($tv), 'phx')===0) continue;
            if (strpos(strtolower($tv), 'wf.')===0) continue;
            $tvnames[] = $tv;
        }

        if ($tvnames) {
            return array_unique($tvnames);
        }

        return array();
    }

    function addDebugInfo($group,$groupkey,$header,$message,$info) {
        $infoString = '<table class="wfdebug" style="margin-bottom:1em;">';
        $total = count($info);
        if($total!=1 && $total%2 != 0) {
            $info['-'] = '';
        }
        $count = 0;

        foreach ($info as $key => $value) {
            $key = $this->modxPrep($key);
            if ($value === true || $value === false) {
                $value = $value ? 'true' : 'false';
                $value = sprintf('<span class="bool %s">%s</span>',$value,$value);
            } else {
                if($key === 'nl') {
                    $key = 'removeNewLines';
                    $value = str_replace("\n",'\\n',$value);
                }
                elseif($key === 'id') {
                    $key = 'startId';
                }
                $value = $this->modxPrep($value);
                $value = str_replace(array(' ',"\n"),array('&nbsp;',"<br />\n"),$value);
            }
            if ($count == 2) {
                $infoString .= '</tr>'; $count = 0;
            }
            if ($count == 0) {
                $infoString .= '<tr>';
            }
            if ($value=='') {
                $value = '&nbsp;';
            }
            if($key!='-' && ($group === 'settings'||$group === 'template')) {
                $key = '&' . $key;
            }
            $infoString .= sprintf('<th>%s</th><td>%s</td>', $key, $value);
            $count++;
        }
        $infoString .= '</tr></table>';

        $message = $this->modxPrep($message);
        if($group === 'row') {
            $message = sprintf('<div>%s</div>', $message);
        }
        $this->debugInfo[$group][$groupkey] = array(
            'header' => $this->modxPrep($header),
            'message' =>  $message,
            'info' => $infoString,
        );
    }

    function renderDebugOutput() {
        global $modx;

        $output = '<style>table.wfdebug {font-family:verdana,sans-serif;background-color:#fff;margin:1em;border-collapse:collapse !important;box-sizing: border-box;} table.wfdebug * {box-sizing: border-box;} table.wfdebug td,table.wfdebug th {padding:3px;border:1px solid #ccc;}table.wfdebug th {background-color:#eee;color:#333;font-weight:normal;} table.wfdebug td span.bool{border-radius: 4px;font-size: 90%;padding: 2px 4px;}table.wfdebug td span.true{background-color: #dff0d8;color: #3c763d;}table.wfdebug td span.false{background-color: #f9f2f4;color: #c7254e;}</style>';
        $output .= '<table class="wfdebug">';
        foreach ($this->debugInfo as $group => $item) {
            switch ($group) {
                case 'template':
                    $output .= '<tr><th style="background:#C3D9FF;font-size:200%;">Template Processing</th></tr>';
                    foreach ($item as $parentId => $info) {
                        $output .= $modx->parseText('
                            <tr style="background:#336699;color:#fff;"><th>[+header+] - <span style="font-weight:normal;">[+message+]</span></th></tr>
                            <tr><td>[+info+]</td></tr>', $info);
                    }
                    break;
                case 'wrapper':
                    $output .= '<tr><th style="background:#C3D9FF;font-size:200%;">Document Processing</th></tr>';

                    foreach ($item as $parentId => $info) {
                        $output .= $modx->parseText('<tr><table class="wfdebug">
                                    <tr style="background:#336699;color:#fff;"><th>[+header+] - <span style="font-weight:normal;">[+message+]</span></th></tr>
                                    <tr><td>[+info+]</td></tr>
                                    <tr style="background:#336699;color:#fff;"><th>Documents included in this wrapper:</th></tr>',$info);

                        foreach ($this->debugInfo['row'] as $key => $value) {
                            $value['message_v'] = $this->debugInfo['rowdata'][$key]['message'];
                            $value['info_v']    = $this->debugInfo['rowdata'][$key]['info'];
                            $keyParts = explode(':',$key);
                            if ($parentId == $keyParts[0]) {
                                $output .= $modx->parseText('<tr style="background:#eee;"><th>[+header+]</th></tr>
                                    <tr><td>
                                    <div>[+message+] [+message_v+]</div>
                                    <div>[+info+] [+info_v+]</div></td></tr>',$value);
                            }
                        }

                        $output .= '</table></tr>';
                    }

                    break;
                case 'settings':
                    $output .= '<tr><th style="background:#C3D9FF;font-size:200%;">Settings</th></tr>';
                    foreach ($item as $parentId => $info) {
                        $output .= $modx->parseText('
                            <tr style="background:#336699;color:#fff;"><th>[+header+] - <span style="font-weight:normal;">[+message+]</span></th></tr>
                            <tr><td>[+info+]</td></tr>',$info);
                    }
                    break;
                default:

                    break;
            }
        }
        $output .= '</table>';
        return $output;
    }

    function modxPrep($value) {
        global $modx;
        if (strpos($value, '<') !== false) {
            $value = htmlentities($value, ENT_NOQUOTES, $modx->config['modx_charset']);
        }
        $s = array('[', ']', '{', '}');
        $r = array('&#091;', '&#093;', '&#123;', '&#125;');
        $value = str_replace($s, $r, $value);
        return $value;
    }

    function getIndexID($id)
    {
        global $modx;

        if($modx->documentObject['parent']==0) {
            return $id;
        }

        $rs = $modx->db->select('*','[+prefix+]site_content',"parent='{$id}' AND hidemenu=0");

        if($modx->db->getRecordCount($rs)) {
            return $id;
        }

        return $modx->documentObject['parent'];
    }

    function getParentID($id) {
        global $modx;

        if($modx->documentObject['parent']==0) {
            return $id;
        }

        return $modx->documentObject['parent'];
    }
}
