<?php
namespace TSJIPPY\MANDATORY;
use TSJIPPY;

use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu{

    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        
        $this->recurrenceSelector('reminder-freq', $this->settings['reminder-freq'], 'How often should people be reminded of remaining content to read', $parent);

        return true;
    }

    public function emails($parent){
        ob_start();
        ?>
        <h4>E-mail with read reminders</h4>
        <label>Define the e-mail people get when they shour read some mandatory content.</label>
        <?php
        $readReminder    = new ReadReminder(wp_get_current_user());
        $readReminder->printPlaceholders();
        $readReminder->printInputs();

        addRawHtml(ob_get_clean(), $parent);

        return true;
    }

    public function data($parent=''){
        //Get all the pages with an audience meta key
        $pages = get_posts(
            array(
                'orderby' 		=> 'post_name',
                'order' 		=> 'asc',
                'post_type' 	=> 'any',
                'post_status' 	=> 'publish',
                'meta_query'	=> [
                    [
                        'key' 		=> "audience",
                        'compare'	=> 'EXISTS'
                    ],
                    [
                        'key' 		=> "audience",
                        'value'		=> 'a:0:{}',
                        'compare'	=> '!='
                    ],
                    [
                        'key' 		=> "audience",
                        'value'		=> '',
                        'compare'	=> '!='
                    ]
                ],
                'numberposts'	=> -1				// all posts
            )
        );
        
        if(empty($pages)){
            return false;
        }

        $keys	= getAudienceOptions(['empty'], 1);
        unset($keys['everyone']);

        ob_start();

        ?>
        <script>
            function showUserList(pageId, button){
                document.querySelector(`#wrapper-\${pageId}`).classList.toggle('hidden'); 
                if(button.textContent.includes('Show')){
                    button.textContent	= button.textContent.replace('Show', 'Hide') 
                }else{
                    button.textContent	= button.textContent.replace('Hide', 'Show') 
                }
            }
        </script>

        <table class='mandatory-pages-overview'>
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Users</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($pages as $page){
                    $audience   = get_post_meta($page->ID, 'audience', true);
                    if(!is_array($audience) && !empty($audience)){
                        $audience  = json_decode($audience, true);
                    }

                    $url	= get_permalink($page->ID);

                    $users	= [];

                    // Evryone should read this
                    if(isset($audience['everyone']) || ( isset($audience['beforearrival']) && isset($audience['afterarrival']))){
                        $metaQuery	= array(
                            array(
                                'key' 		=> 'read_pages',
                                'value' 	=> $page->ID,
                                'compare' 	=> 'NOT LIKE'
                            )
                        );
                    }elseif(isset($audience['beforearrival'])){
                        $metaQuery	=  array(
                            'relation' => 'AND',
                            array(
                                'key' 		=> 'read_pages',
                                'value' 	=> $page->ID,
                                'compare' 	=> 'NOT LIKE'
                            ),
                            array(
                                'key' 		=> 'arrival_date',
                                'value' 	=> Date('Y-m-d'),
                                'compare' 	=> '>'
                            ),
                        );
                    }elseif(isset($audience['afterarrival'])){
                        $metaQuery	=  array(
                            'relation' => 'AND',
                            array(
                                'key' 		=> 'read_pages',
                                'value' 	=> $page->ID,
                                'compare' 	=> 'NOT LIKE'
                            ),
                            array(
                                'key' 		=> 'arrival_date',
                                'value' 	=> Date('Y-m-d'),
                                'compare' 	=> '<'
                            ),
                        );
                    }else{
                        $metaQuery	= '';
                    }

                    // get all users who have not read this page/post
                    $users	= get_users(
                        array(
                            'orderby'		=> 'display_name',
                            'count_total'	=> false,
                            'fields'		=> ['display_name', 'ID'],
                            'meta_query' 	=> $metaQuery
                        )
                    );

                    ?>
                    <tr>
                        <td><a href='$url'><?php echo esc_attr($page->post_title);?></a></td>
                        <td>
                            <?php
                            if(!empty($users)){
                                $count			= count($users);
                                $userEditPage	= TSJIPPY\getValidPageLink(TSJIPPY\USERMANAGEMENT\SETTINGS['user-edit-page'] ?? '');
                                ?>
                                <div id='wrapper-<?php echo esc_attr($page->ID);?>' class='hidden'>
                                    <?php echo esc_html($count);?> users still have to read this.
                                    <?php
                                    foreach($users as $user){
                                        echo "<a href='$userEditPage?user-id=$user->ID'>$user->display_name<br>";
                                    }?>
                                </div>
                                <?php
                            }else{
                                ?>
                                Read by everyone
                                <?php
                            }
                            ?>
                        </td>

                        <td><button class='small show-user-list' onclick='showUserList(<?php echo esc_attr($page->ID);?>, this)'>Show who</button></td>
                        
                    </tr>
                    <?php
                }
                ?>			
            </tbody>
        </table>
        <?php 
        
        addRawHtml(ob_get_clean(), $parent);
    
        return true;
    }

    public function functions($parent){

        return false;
    }

    /**
     * Function to do extra actions from $_POST data. Overwrite if needed
     */
    public function postActions(){
        return '';
    }

    /**
     * Schedules the tasks for this plugin
     *
    */
    public function postSettingsSave(){
        scheduleTasks();

        $roleSet = get_role( 'contributor' )->capabilities;

        // Only add the new role if it does not exist
        if(!wp_roles()->is_role( 'no_man_docs' )){
            add_role(
                'no_man_docs',
                'No mandatory documents',
                $roleSet
            );

            return "Added the 'No mandatory documents' role";
        }
    }
}