<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get the mandatory audience options
 */
function getAudienceOptions($audience, $postId)
{
    $keys    = [
        'everyone'      => "Everyone must read this no matter how long in the country"
    ];

    if ($postId != null && is_array($audience) && !empty($audience)) {
        $keys['normal'] = "normal";
    }

    return apply_filters('tsjippy-mandatory-audience-param', $keys);
}

/**
 * Adding fields to the frontend posting screen
 * @param  object $frontendContend     frontendContend instance
 */
add_action('tsjippy-frontend-content-post-after-content', __NAMESPACE__ . '\afterContent', 20);
function afterContent($frontendContend)
{
    $audience   = $frontendContend->getPostMeta('audience');
    if (!is_array($audience) && !empty($audience)) {
        $audience  = json_decode($audience, true);
    }

    $keys    = getAudienceOptions($audience, $frontendContend->postId);

?>
    <div
        id="recipients"
        class="frontend-form property post page expand-wrapper
        <?php if ($frontendContend->postType != 'page' && $frontendContend->postType != 'post') echo ' hidden'; ?>">
        <h4>
            Audience
            <button class="button small expand" type='button'>&#9660;</button>
        </h4>

        <div class="hidden expandable">
            <?php
            foreach ($keys as $key => $label) {
            ?>
                <label>
                    <input
                        type='checkbox'
                        name='audience[<?php echo esc_attr($key); ?>]'
                        value='<?php echo esc_attr($key); ?>'
                        <?php if (isset($audience[$key])) echo 'checked'; ?>>
                    <?php echo wp_kses_post($label); ?>
                </label><br>
            <?php
            }
            ?>
        </div>
    </div>
<?php
}

/**
 * Save the mandatory options
/**
 * Allow comments
 * 
 * @param   \WP_Post    $post       The new or updated post
 * @param   object      $object     FrontEndContent Instance
 * @param   array       $request    The sanitized request data
 */
add_action('tsjippy-frontend-content-after-post-save', __NAMESPACE__ . '\afterPostSave', 10, 3);
function afterPostSave($post, $object, $request)
{
    //store audience
    if (empty($request['audience']) || !is_array($request['audience'])) {
        delete_post_meta($post->ID, "tsjippy_audience");

        return;
    }
    $audiences = $request['audience'];

    //Reset to normal if that box is ticked
    if (isset($audiences['normal']) && $audiences['normal'] == 'normal') {
        delete_post_meta($post->ID, "tsjippy_audience");
        //Store in DB
    } else {
        array_filter($audiences);

        //Only continue if there are audiences defined
        if (!empty($audiences)) {
            update_metadata('post', $post->ID, "tsjippy_audience", json_encode($audiences));

            //Mark existing users as if they have read the page if this pages should be read by new people after arrival
            if (isset($audiences['afterarrival']) && !isset($audiences['everyone'])) {
                //Get all users who are longer than 1 month in the country
                $users = get_users(array(
                    'meta_query' => array(
                        array(
                            'key'     => 'tsjippy_arrival_date',
                            'value'   => gmdate('Y-m-d', strtotime("-1 months")),
                            'type'    => 'date',
                            'compare' => '<='
                        )
                    ),
                ));

                //Loop over the users
                foreach ($users as $user) {
                    //add current page
                    add_user_meta($user->ID, 'tsjippy_read_pages', $post->ID);
                }
            }

            do_action('tsjippy-mandatory-save-audience-param', $audiences, $post);
        }
    }
}

/**
 * Adds a message to the Signal message send about the content being mandatory
 * @param  string $message     Signal message
 * @return string            The message
 */
add_filter('tsjippy-signal-post-notification-message', __NAMESPACE__ . '\postNotification', 10, 2);
function postNotification($message, $post)
{
    $audience   = get_post_meta($post->ID, 'tsjippy_audience', true);
    if (!is_array($audience) && !empty($audience)) {
        $audience  = json_decode($audience, true);
    }
    if (is_array($audience) && !empty($audience['everyone'])) {
        $message    .= "\n\nThis is a mandatory message, please read it straight away. ";
    }

    return $message;
}
