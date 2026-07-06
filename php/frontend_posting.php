<?php

namespace TSJIPPY\MANDATORY;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get the mandatory audience options
 */
function getAudienceOptions($postId=null)
{
    $keys    = [
        'everyone'      => "Everyone must read this no matter how long in the country"
    ];

    if ($postId != null){
        $audience   = get_post_meta($postId, 'audience');
        if( !empty($audience)) {
            $keys['normal'] = "normal";
        }
    }

    return apply_filters('tsjippy-mandatory-audience-param', $keys);
}


add_action('tsjippy-frontend-content-post-after-content', __NAMESPACE__ . '\afterContent', 20);
/**
 * Adds mandatroy post settings
 * 
 * @param object    $object The class instance
 */
function afterContent($object)
{
    $keys    = getAudienceOptions($object->postId);

?>
    <tbody id="recipients" class="frontend-form property post page expand-wrapper <?php if ($object->postType != 'page' && $object->postType != 'post') echo ' hidden'; ?>">
        <tr>
            <td>
                <h4>
                    Audience
                </h4>
            </td>
            <td>
                <button class="button small expand" type='button'>
                    &#9660;
                </button>
            </td>
        </tr>
        <tr>
            <td class="hidden expandable" collspan=2>
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
            </td>
        </tr>
    </tbody>
<?php
}

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
    if (($audiences['normal'] ?? '') == 'normal') {
        delete_post_meta($post->ID, "tsjippy_audience");
        //Store in DB
    } else {
        array_filter($audiences);

        //Only continue if there are audiences defined
        if (!empty($audiences)) {
            update_metadata('post', $post->ID, "tsjippy_audience", json_encode($audiences));

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
