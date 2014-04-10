# BrowseProfiles
================================

### A Mahara plugin for browsing through profile pages featuring image content

*See licence below for standard disclaimer - use this plugin at your own risk.*


The BrowseProfiles plugin presents logged-in users with a gallery of images which link through to the pages on which they are published. It was developed to encourage a sense of community among users. 

**It will only find and display profile pages which feature Image content blocks.**


## Requirements
---
BrowseProfiles requires Mahara 1.6 or later.



## Installation
---

* Copy the 'browseprofiles' folder to your Mahara installation, inside the folder htdocs/artefact.
* Visit the Site Administration->Extensions->Plugin administration page and install the artefact/browseprofiles plugin.

**Set up a menu option**.

Here I describe two options for setting where the BrowseProfiles menu option appears.

**Menu option 1. Make BrowseProfiles menu appear under Dashboard.**
    
This is the default setup for the BrowseProfiles plugin, but it requires editing of some additional files.
    
In htdocs/local/lib.php, add the following code:
    
        function local_main_nav_update(&$menu){

            unset($menu['home']);

            $menu['dashboard'] = array(
            'path' => 'dashboard',
            'url' => '',
            'title' => get_string('dashboard', 'view'),
            'weight' => 10,
            'accesskey' => 'h',
            );

            $menu['dashboard/latest'] =  array(
            'path' => 'dashboard/latest',
            'url' => '',
            'title' => get_string('dashboardlatest', 'view'),
            'weight' => 10,
            );
        }
        
Next, add this line to htdocs/lang/en.utf8/view.php, or your language equivalent:
    
        $string['dashboardlatest'] = 'Latest activity';
    
Finally, edit htdocs/index.php by changing:
    
        define('MENUITEM', ''); 
        
    to:
    
        define('MENUITEM', 'dashboard/latest');
        
With this arrangement, the Dashboard menu will have submenus - **Latest Activity**, which is the standard Dashboard page, and **Explore Profiles** which is the BrowseProfiles plugin page.
    
**Menu option 2.    Make BrowseProfiles menu appear under Content.**
    
If you would prefer to have BrowseProfiles appear under the Content menu, edit the menu_items() function in browseprofiles/lib.php as follows. Edit the weight value to change the position of the menu:
    
            public static function menu_items() {
                return array(
                    'content/browseprofiles' => array (
                    'path' => 'content/browseprofiles',
                    'url'  => 'artefact/browseprofiles',
                    'title' => get_string('browseprofiles', 'artefact.browseprofiles'),
                    'weight' => 20,
            ),
        );
    }

Finally change the following line in browseprofiles/index.php:

    define('MENUITEM', 'dashboard/browseprofiles');
    
to this:

    define('MENUITEM', 'content/browseprofiles');    


## Usage
---

####How to use

The gallery of profile pages will be available for logged-in users browsing either under the Mahara Dashboard menu or the Content menu, according to your installation choice (see above).

The gallery can be searched by name - this means users' first and last names, and preferred names, not Mahara usernames.
Multiple search terms can be used at once. Current filter options are displayed and can be deleted individually or collectively.

Multiple search terms are ANDed together. 

In other words, when searching, the user's name must (partially) match ALL the search terms. 

Typing a search of multiple words with spaces between them works the same way as submitting a series of separate single word searches.


####Additional search options
In the original version of the plugin, searching of pages by college and course is enabled in addition to user name searches.
This functionality is extant in the code but has been commented out. Developers may wish to adapt these features to their own institutional context.

(The college and course filters query a custom table in the database, and the course filter implements autocomplete for course titles and/or IDs, through a connection to an external database when searching.)


####Notes and queries

*   Why doesn't Profile Page X show up in the gallery?

There are a few possibilities for this:

1. Page X doesn't have any images on it.
2. Page X's images are in an image gallery content block or embedded in a text block. I removed searches for pages with image galleries due to slow performance. I may look at this again in future.

##Fork Me
___
Please feel free to update or adapt this plugin.

##Licence
---

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program.  If not, see <http://www.gnu.org/licenses/>.
 
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

