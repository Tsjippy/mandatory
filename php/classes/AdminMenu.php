<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

use function TSJIPPY\addRawHtml;

if (! defined('ABSPATH')) {
    exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu
{

    /**
     * AdminMenu constructor.
     *
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name)
    {
        parent::__construct($settings, $name);
    }

    public function settings($parent)
    {

        $this->recurrenceSelector('reminder-freq', $this->settings['reminder-freq'] ?? '', 'How often should people be reminded of remaining content to read', $parent);

        return true;
    }

    public function emails($parent)
    {
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

    public function data($parent = '')
    {
        wp_enqueue_script('tsjippy_mandatory_admin', TSJIPPY\pathToUrl(PLUGINPATH . 'js/admin.min.js'), array(), PLUGINVERSION, true);

        //Get all the pages with an audience meta key
        $pages = get_posts(
            array(
                'orderby'       => 'post_name',
                'order'         => 'asc',
                'post_type'     => 'any',
                'post_status'   => 'publish',
                'meta_query'    => [
                    [
                        'key'     => "tsjippy_audience",
                        'compare' => 'EXISTS'
                    ],
                    [
                        'key'     => "tsjippy_audience",
                        'value'   => 'a:0:{}',
                        'compare' => '!='
                    ],
                    [
                        'key'     => "tsjippy_audience",
                        'value'   => '',
                        'compare' => '!='
                    ]
                ],
                'numberposts'     => -1                // all posts
            )
        );

        if (empty($pages)) {
            return false;
        }

        $keys    = getAudienceOptions(['empty'], 1);
        unset($keys['everyone']);

        // get all users 
        $users    = get_users(
            array(
                'orderby'       => 'display_name',
                'fields'        => ['display_name', 'ID']
            )
        );

        ob_start();

        ?>
        <table class='mandatory-pages-overview'>
            <thead>
                <tr>
                    <th>
                        Page
                    </th>
                    <th>
                        Users
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($pages as $page) {
                    $audience   = get_post_meta($page->ID, 'tsjippy_audience', true);
                    if (!is_array($audience) && !empty($audience)) {
                        $audience  = json_decode($audience, true);
                    }

                    ?>
                    <tr>
                        <td>
                            <a href='$url'>
                                <?php echo esc_attr($page->post_title); ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            $count  = 0;
                            $list   = [];
                            foreach($users as $user){
                                $readPages        = get_user_meta($user->ID, 'tsjippy_read_pages');

                                // already read
                                if(in_array($page->ID, $readPages)){
                                    continue;
                                }

                                // Post has not been read, check if it should be read
                                $mustRead    = true;

                                /**
                                 * Filter if this post should be read
                                 * 
                                 * @param   bool        $mustRead   
                                 * @param   array       $audience   The audience targets
                                 * @param   int         $userId     The WP_User id
                                 * @param   \WP_Post    $post       The current post
                                 * @param   \DOMElement $parent     The top node element
                                */ 
                                $mustRead    = apply_filters('tsjippy-mandatory-should-read-mandatory-page', $mustRead, $audience, $user->ID, $page);

                                if($mustRead){
                                    $count++;
                                }

                                $list[$user->ID] = $user->display_name;
                            }

                            if ($count) {
                                ?>
                                <div id='wrapper-<?php echo esc_attr($page->ID); ?>' class='hidden'>
                                    <?php echo esc_html($count); ?> users still have to read this.
                                    <?php
                                    foreach ($list as $userId => $name) {
                                        $url    = get_edit_profile_url($userId);
                                        ?>
                                        <a href='<?php echo esc_url($url);?>?user-id=<?php echo esc_attr($userId);?>'>
                                            <?php echo esc_html($name);?>
                                        </a>
                                         <br>
                                         <?php
                                    } ?>
                                </div>
                                <?php
                            }else{
                                ?>
                                Read by everyone
                                <?php
                            }
                            ?>
                        </td>

                        <td>
                            <button class='small show-user-list' onclick='showUserList(<?php echo esc_attr($page->ID); ?>, this)'>
                                Show who
                            </button>
                        </td>

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

    public function functions($parent)
    {

        return false;
    }

    /**
     * Schedules the tasks for this plugin
     *
     */
    public function postSettingsSave($request)
    {
        $roleSet = get_role('contributor')->capabilities;

        // Only add the new role if it does not exist
        if (!wp_roles()->is_role('no_man_docs')) {
            add_role(
                'no_man_docs',
                'No mandatory documents',
                $roleSet
            );

            return "Added the 'No mandatory documents' role";
        }
    }
}
