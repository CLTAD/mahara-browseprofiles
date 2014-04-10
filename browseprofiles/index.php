<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2009 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage artefact-browseprofiles
 * @author     Mike Kelly / Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2009 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', 1);
define('MENUITEM', 'dashboard/browseprofiles');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'browseprofiles');
define('SECTION_PAGE', 'index');

require(dirname(dirname(dirname(__FILE__))) . '/init.php');
safe_require('artefact', 'browseprofiles');

define('TITLE', get_string('browseprofiles','artefact.browseprofiles'));

// offset and limit for pagination
$offset = param_integer('offset', 0);
$limit  = param_integer('limit', 20);

$filters = array();

if ($keyword = param_variable('keyword', '')) {
    $filters['keyword'] = $keyword;
}
if ($college = param_variable('college', '')) {
    $filters['college'] = $college;
}
if ($yearofstudy = param_variable('yearofstudy', '')) {
    $filters['yearofstudy'] = $yearofstudy;
}
if ($course = param_variable('course', '')) {
    $filters['course'] = $course;
}
/*
$colleges = get_records_assoc('mis_college');
foreach ($colleges as $key => $college ) {
    if ($college->displaytousers == 1) {
        // there are some duplicate entries in the table - don't load them
        $optionscolleges[$key] = $college->abbrev;
    }
}
*/
$items = ArtefactTypeBrowseProfiles::get_browsable_items($filters, $offset, $limit);
ArtefactTypeBrowseProfiles::build_browse_list_html($items);

$js = <<< EOF
addLoadEvent(function () {
    {$items['pagination_js']}
});
EOF;

$smarty = smarty(array('artefact/browseprofiles/js/jquery-ui/js/jquery-ui-1.8.19.custom.min.js','artefact/browseprofiles/js/chosen.jquery.js','artefact/browseprofiles/js/browseprofiles.js'), array('<link href="' . get_config('wwwroot') . 'artefact/browseprofiles/js/jquery-ui/css/custom-theme/jquery-ui-1.8.20.custom.css" type="text/css" rel="stylesheet">','<link href="' . get_config('wwwroot') . 'artefact/browseprofiles/theme/raw/static/style/chosen.css" type="text/css" rel="stylesheet">'));
$smarty->assign_by_ref('items', $items);
$smarty->assign('PAGEHEADING', hsc(get_string("browseprofiles", "artefact.browseprofiles")));
$smarty->assign('INLINEJAVASCRIPT', $js);
//$smarty->assign('colleges', $optionscolleges);
$smarty->display('artefact:browseprofiles:index.tpl');
