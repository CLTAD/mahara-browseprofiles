<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2008 Catalyst IT Ltd (http://www.catalyst.net.nz)
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
 * @author     Mike Kelly UAL m.f.kelly@arts.ac.uk / Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

defined('INTERNAL') || die();
require_once('view.php');

class PluginArtefactBrowseProfiles extends PluginArtefact {

    public static function get_artefact_types() {
        return array('browseprofiles');
    }

    public static function get_block_types() {
        return array();
    }

    public static function get_plugin_name() {
        return 'browseprofiles';
    }

    public static function menu_items() {
        return array(
            'dashboard/browseprofiles' => array(
                'path' => 'dashboard/browseprofiles',
                'url'  => 'artefact/browseprofiles',
                'title' => get_string('browseprofiles', 'artefact.browseprofiles'),
                'weight' => 25,
            ),
        );
    }
}

class ArtefactTypeBrowseProfiles extends ArtefactType {

    public function __construct($id = 0, $data = null) {
        parent::__construct($id, $data);
    }

    public static function get_links($id) {
        return array();
    }

    public function delete() {
        return;
    }

    public static function get_icon($options=null) {
    }

    public static function is_singular() {
        return true;
    }

    /**
     * This function returns a list of browsable items.
     *
     * @param limit how many items to display per page
     * @param offset current page to display
     * @return array (count: integer, data: array)
     */

    public static function get_browsable_items($filters, $offset=0, $limit=20) {
        global $USER;

        $contents = array();
        $texttitletrim = 20;
        $onetimeclause = false;
        $count = 0;

        if (is_postgres()) {
            $selectclause =  'SELECT *
                              FROM (
                                    SELECT DISTINCT ON (va.view) va.view, a.id, v.mtime, v.owner';
            $grouporderclause = ') p
                                  ORDER BY mtime DESC';
        }
        else if (is_mysql()) {
            $selectclause =  'SELECT va.view, a.id, v.mtime, v.owner';
            $grouporderclause = 'GROUP BY va.view
                                 ORDER BY a.mtime DESC';
        }
        $fromclause =       ' FROM {view_access} va';
        $joinclause =       " INNER JOIN {view} v ON (va.view = v.id AND v.type = 'profile')";
        $join2clause =      " JOIN {view_artefact} var ON v.id = var.view";
        $join3clause =      " JOIN {artefact} a ON (a.artefacttype = 'image' AND a.id = var.artefact)";
        $join4clause =      '';
        $whereclause =      ' WHERE (v.owner > 0)';
        $andclause =        " AND (v.startdate IS NULL OR v.startdate < current_timestamp)
        AND (v.stopdate IS NULL OR v.stopdate > current_timestamp)
        AND (va.startdate IS NULL OR va.startdate < current_timestamp)
        AND (va.stopdate IS NULL OR va.stopdate > current_timestamp)";

        foreach ($filters as $filterkey => $filterval) {

            switch ($filterkey) {

                case 'keyword':
                    $join4clause = ' LEFT JOIN {usr} u ON u.id = v.owner';
                    // replace spaces with commas so that we can search on each term separately
                    $filterval = str_replace(' ', ',', $filterval);
                    $keywords = explode(",", $filterval);
                    if (count($keywords) == 1 ) {
                        $andclause .= " AND (
                        LOWER(u.firstname) LIKE LOWER('%$filterval%')
                        OR LOWER(u.lastname) LIKE LOWER('%$filterval%')
                        OR LOWER(u.preferredname) LIKE LOWER('%$filterval%')
                        )";
                    } else {
                        foreach($keywords as $key => $word) {
                            if ($key == 0) {
                                $andclause .= " AND ((
                                LOWER(u.firstname) LIKE LOWER('%$word%')
                                OR LOWER(u.lastname) LIKE LOWER('%$word%')
                                OR LOWER(u.preferredname) LIKE LOWER('%$word%')
                                )";
                            } else {
                                $andclause .= " AND (
                                LOWER(u.firstname) LIKE LOWER('%$word%')
                                OR LOWER(u.lastname) LIKE LOWER('%$word%')
                                OR LOWER(u.preferredname) LIKE LOWER('%$word%')
                                )";
                            }
                            if ($key == count($keywords)-1) {
                                $andclause .= ')';
                            }
                        }
                    }
                    break;
                case 'college' :
                    if (!empty($filterval) && !$onetimeclause) {
                        $join4clause .= ' JOIN {usr_enrolment} e ON e.usr = v.owner';
                        $selectclause .= ', e.college, e.course';
                        $ontimeclause = true;
                    }
                    $andclause .= " AND e.college IN ($filterval)";
                    break;
                case 'course' :
                     if (!empty($filterval) && !$onetimeclause) {
                        $join4clause .= ' JOIN {usr_enrolment} e ON e.usr = v.owner';
                        $selectclause .= ', e.college, e.course';
                        $ontimeclause = true;
                    }
                    $courseidgroups = explode(";", $filterval);
                    if (count($courseidgroups) == 1) {
                        // one course submitted, could have multiple csv ids if selected by name
                        $courseids = explode(",", $courseidgroups[0]);
                        if (count($courseids) == 1 ) {
                            $andclause .= " AND (e.course LIKE '%$courseids[0]%' AND e.usr = v.owner)";
                        } else if (count($courseids) > 1 ) {
                            foreach($courseids as $key => $id) {
                                if ($key == 0) {
                                    $andclause .= " AND (e.course LIKE '%$id%'";
                                } else {
                                    $andclause .= " OR e.course LIKE '%$id%'";
                                }
                                if ($key == count($courseids)-1) {
                                    $andclause .= ')';
                                }
                            }
                        }
                    } else if (count($courseidgroups) > 1) {
                        // more than one course submitted
                        foreach($courseidgroups as $key => $coursegroup) {

                            if ($key == 0) {
                                $courseids = explode(",", $coursegroup);
                                if (count($courseids) == 1 ) {
                                    $andclause .= " AND ((e.course LIKE '%$courseids[0]%')";
                                } else if (count($courseids) > 1 ) {
                                    foreach($courseids as $key => $id) {
                                        if ($key == 0) {
                                            $andclause .= " AND ((e.course LIKE '%$id%'";
                                        } else {
                                            $andclause .= " OR e.course LIKE '%$id%'";
                                        }
                                        if ($key == count($courseids)-1) {
                                            $andclause .= ')';
                                        }
                                    }
                                }

                            } else {
                                // key != 0
                                $courseids = explode(",", $coursegroup);
                                if (count($courseids) == 1 ) {
                                    $andclause .= " AND (e.course LIKE '%$courseids[0]%')";
                                } else if (count($courseids) > 1 ) {
                                    foreach($courseids as $key => $id) {
                                        if ($key == 0) {
                                            $andclause .= " AND (e.course LIKE '%$id%'";
                                        } else {
                                            $andclause .= " OR e.course LIKE '%$id%'";
                                        }
                                        if ($key == count($courseids)-1) {
                                            $andclause .= ')';
                                        }
                                    }
                                }
                            }
                        }
                    }
                    break;
            }
        }

        /**
        * The query checks for profile page images.
        */
        $profileimagesids = get_records_sql_array("
                $selectclause
                $fromclause
                $joinclause
                $join2clause
                $join3clause
                $join4clause
                $whereclause
                $andclause
                $grouporderclause
                ", array(), $offset, $limit);

        if ($profileimagesids) {
            foreach ($profileimagesids as $key => $profileimageid) {
                $view = new View($profileimageid->view);
                $view->set('dirty', false);
                $fullurl = $view->get_url();
                $avatarurl = profile_icon_url($profileimageid->owner,50,50);
                $pagetitle = str_shorten_text(display_name($profileimageid->owner), $texttitletrim, true);
                $contents['profiles'][] = array("id" => "p" . $profileimageid->id,
                                    "type" => "profile",
                                    "image" => array(
                                                "id" => $profileimageid->id,
                                                "view" => $profileimageid->view
                                            ),
                                    "page" => array(
                                                "url" => $fullurl,
                                                "title" => $pagetitle
                                    ),
                                    "owner" => array(
                                            "avatarurl" => $avatarurl
                                    )
                                );
            } // foreach
        }

        if ($profileimagesids) {
            $allprofileswithimages = get_records_sql_array("
                    SELECT va.view
                    $fromclause
                    $joinclause
                    $join2clause
                    $join3clause
                    $join4clause
                    $whereclause
                    $andclause
                    GROUP BY va.view
                    ", array());
            $count = count($allprofileswithimages);
        }

        $items = array(
                'count'  => $count,
                'data'   => $contents,
                'offset' => $offset,
                'limit'  => $limit,
        );
        return $items;
    }

    /**
     * Builds the browse display
     *
     * @param items (reference)
     */
    public static function build_browse_list_html(&$items) {
        $smarty = smarty_core();
        $smarty->assign_by_ref('items', $items);
        // compensate for table padding, which has no effect on bg images
        $padding = 8;
        $smarty->assign('padding', $padding);
        $smarty->assign('wwwroot', get_config('wwwroot'));
        $items['tablerows'] = $smarty->fetch('artefact:browseprofiles:browselist.tpl'); // the 'tablerows' naming is required for pagination script
        $pagination = build_browseprofile_pagination(array(
            'id' => 'browseprofileslist_pagination',
            'url' => get_config('wwwroot') . 'artefact/browseprofiles/index.php',
            'jsonscript' => 'artefact/browseprofiles/browseprofiles.json.php',
            'datatable' => 'browselist', // the pagination script expects a table with this id
            'count' => $items['count'],
            'limit' => $items['limit'],
            'offset' => $items['offset'],
            'firsttext' => '',
            'previoustext' => '',
            'nexttext' => '',
            'lasttext' => '',
            'numbersincludefirstlast' => false,
            'resultcounttextsingular' => 'Item',
            'resultcounttextplural' => 'Items',
        ));
        $items['pagination'] = $pagination['html'];
        $items['pagination_js'] = $pagination['javascript'];
    }
}

/**
* Builds pagination links for HTML display.
*
* @param array $params Options for the pagination
*/
function build_browseprofile_pagination($params) {
    // Bail if the required attributes are not present
    $required = array('url', 'count', 'limit', 'offset');
    foreach ($required as $option) {
        if (!isset($params[$option])) {
            throw new ParameterException('You must supply option "' . $option . '" to build_browseprofile_pagination');
        }
    }

    // Work out default values for parameters
    if (!isset($params['id'])) {
        $params['id'] = substr(md5(microtime()), 0, 4);
    }

    $params['offsetname'] = (isset($params['offsetname'])) ? $params['offsetname'] : 'offset';
    if (isset($params['forceoffset']) && !is_null($params['forceoffset'])) {
        $params['offset'] = (int) $params['forceoffset'];
    }
    else if (!isset($params['offset'])) {
        $params['offset'] = param_integer($params['offsetname'], 0);
    }

    // Correct for odd offsets
    $params['offset'] -= $params['offset'] % $params['limit'];

    $params['firsttext'] = (isset($params['firsttext'])) ? $params['firsttext'] : get_string('first');
    $params['previoustext'] = (isset($params['previoustext'])) ? $params['previoustext'] : get_string('previous');
    $params['nexttext']  = (isset($params['nexttext']))  ? $params['nexttext'] : get_string('next');
    $params['resultcounttextsingular'] = (isset($params['resultcounttextsingular'])) ? $params['resultcounttextsingular'] : get_string('result');
    $params['resultcounttextplural'] = (isset($params['resultcounttextplural'])) ? $params['resultcounttextplural'] : get_string('results');

    if (!isset($params['numbersincludefirstlast'])) {
        $params['numbersincludefirstlast'] = true;
    }
    if (!isset($params['numbersincludeprevnext'])) {
        $params['numbersincludeprevnext'] = true;
    }

    if (!isset($params['extradata'])) {
        $params['extradata'] = null;
    }

    // Begin building the output
    $output = '<div id="' . $params['id'] . '" class="pagination';
    if (isset($params['class'])) {
        $output .= ' ' . hsc($params['class']);
    }
    $output .= '">';

    if ($params['limit'] <= $params['count']) {
        $pages = ceil($params['count'] / $params['limit']);
        $page = $params['offset'] / $params['limit'];

        $last = $pages - 1;
        if (!empty($params['lastpage'])) {
            $page = $last;
        }
        $next = min($last, $page + 1);
        $prev = max(0, $page - 1);

        // Build a list of what pagenumbers will be put between the previous/next links
        $pagenumbers = array();
        if ($params['numbersincludefirstlast']) {
            $pagenumbers[] = 0;
        }
        if ($params['numbersincludeprevnext']) {
            $pagenumbers[] = $prev;
        }
        $pagenumbers[] = $page;
        if ($params['numbersincludeprevnext']) {
            $pagenumbers[] = $next;
        }
        if ($params['numbersincludefirstlast']) {
            $pagenumbers[] = $last;
        }
        $pagenumbers = array_unique($pagenumbers);

        // Build the first/previous links
        $isfirst = $page == 0;
        $setlimit = true;
        $output .= build_browseprofile_pagination_pagelink('first', $params['url'], $setlimit, $params['limit'], 0, '&laquo; ' . $params['firsttext'], get_string('firstpage'), $isfirst, $params['offsetname']);
        $output .= build_browseprofile_pagination_pagelink('prev', $params['url'], $setlimit, $params['limit'], $params['limit'] * $prev, $params['offset'], '&larr; ' . $params['previoustext'], get_string('prevpage'), $isfirst, $params['offsetname']);

        // Build the pagenumbers in the middle
        foreach ($pagenumbers as $k => $i) {
            if ($k != 0 && $prevpagenum < $i - 1) {
                $output .= '…';
            }
            if ($i == $page) {
                $output .= '<span class="selected">' . ($i + 1) . '</span>';
            }
            else {
                $output .= build_browseprofile_pagination_pagelink('', $params['url'], $setlimit, $params['limit'],
                    $params['limit'] * $i, $i + 1, '', false, $params['offsetname']);
            }
            $prevpagenum = $i;
        }

        // Build the next/last links
        $islast = $page == $last;
        $output .= build_browseprofile_pagination_pagelink('next', $params['url'], $setlimit, $params['limit'], $params['limit'] * $next,
            $params['nexttext'] . ' &rarr;', get_string('nextpage'), $islast, $params['offsetname']);
    }

    $js = '';
    // Close the container div
    $output .= '</div>';

    return array('html' => $output, 'javascript' => $js);

}

function build_browseprofile_pagination_pagelink($class, $url, $setlimit, $limit, $offset, $text, $title, $disabled=false, $offsetname='offset') {
    $return = '<span class="pagination';
    $return .= ($class) ? " $class" : '';
    $url = "javascript:BrowseProfiles.filtercontent('profiles'," . $limit . "," . $offset . ");";

    if ($disabled) {
        $return .= ' disabled">' . $text . '</span>';
    }
    else {
        $return .= '">'
        . '<a href="' . $url . '" title="' . $title
        . '">' . $text . '</a></span>';
    }

    return $return;
}