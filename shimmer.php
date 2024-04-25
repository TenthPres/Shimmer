<?php

/*
Plugin Name: Shimmer
Plugin URI: https://github.com/TenthPres/Shimmer
Description: A series of basic functions to fill gaps in WordPress functionality. Shims.
Version: 1.0.2
Author: James Kurtz
Author URI: https://github.com/jkrrv
License: MIT
GitHub Plugin URI: https://github.com/TenthPres/Shimmer
*/

use tp\Shimmer\SessionMatters;
use tp\TouchPointWP\TouchPointWP;

require_once __DIR__ . "/shimmer/SessionMatters.php";

function tenth_involvementClasses()
{
    \tp\TouchPointWP\Involvement::$containerClass = "inv-list involvement-list";
}

add_action('tp_init', 'tenth_involvementClasses');

function tenth_setTribeSeriesSlug($args) : array
{
    $args['rewrite']         = $args['rewrite'] ?? [];
    $args['rewrite']['slug'] = 'events/series';

    return $args;
}

function tenth_filterInvolvementActions($existing, $invObj = null)
{
    global $post;

    return $existing;
}

add_filter('tp_involvement_actions', 'tenth_filterInvolvementActions');

add_filter('tribe_events_register_series_type_args', 'tenth_setTribeSeriesSlug');


// Register function to return schedule instead of publishing date
function tenth_filterEventPublishDate($theDate, $format, $post = null): string
{
    if ($post == null)
        $post = get_the_ID();

    if (get_post_type($post) === "tribe_events")
        return tribe_get_start_date($post);

    if (get_post_type($post) === "tribe_event_series") {
        global $wpdb;

        if (is_object($post)) {
            /** @var WP_POST $post */
            $post = $post->ID;
        }

        $result = $wpdb->get_results("SELECT min(start_date) as first, max(start_date) as last
FROM wp_tec_occurrences JOIN wp_tec_series_relationships wtsr on wp_tec_occurrences.event_id = wtsr.event_id
WHERE wtsr.series_post_id = $post");

        if (count($result) < 1) {
            return "";
        }

        $result = $result[0];

        if ($result->first == null) {
            return "";
        }

        try {
            $out = (new DateTime($result->first))->format($format);

            if ($result->first !== $result->last) {
                $out .= " - " . (new DateTime($result->last))->format($format);
            }

            return $out;
        } catch (Exception $e) {
            return "";
        }

    }

    return $theDate;
}
add_filter('get_the_date', 'tenth_filterEventPublishDate', 10, 3);
add_filter('get_the_time', 'tenth_filterEventPublishDate', 10, 3);


/*
 * SMTP Mail sender help you to prevent mail goes to spam folder
 */
if ( !function_exists('sc_smtp_mail_sender') ) :

    add_action( 'phpmailer_init', 'sc_smtp_mail_sender' );

    function sc_smtp_mail_sender( $phpmailer ) {

        $phpmailer->isSMTP();
        $phpmailer->Host       = SMTP_HOST;
        $phpmailer->SMTPAuth   = SMTP_AUTH;
        $phpmailer->Port       = SMTP_PORT;
        $phpmailer->Username   = SMTP_USER;
        $phpmailer->Password   = SMTP_PASS;
        $phpmailer->SMTPSecure = SMTP_SECURE;
        $phpmailer->From       = SMTP_FROM;
        $phpmailer->FromName   = SMTP_NAME;

    }

endif;

/**
 * Automatically add worship services weekly
 */
function tenth_addWorshipServices()
{
    add_action('tenth_weekly', 'tenth_createWorshipServices');
    if ( ! wp_next_scheduled('tenth_weekly')) {
        // Runs at 10pm EST (3am UTC), hypothetically after TouchPoint runs its Morning Batches.
        wp_schedule_event(
            date('U', strtotime('Saturday') + 3600 * 3),
            'weekly',
            'tenth_weekly'
        );
    }
}
add_action('init', 'tenth_addWorshipServices');


function tenth_createWorshipServices()
{
    global $wp_error;
    $wp_error = true;

    $date     = new \DateTime("Next Sunday");
    $date->add(new DateInterval("P21D"));
    $date_str = $date->format('Y m d');
    $date_fmt = $date->format('Y-m-d');

    $authorId = 6;

    // Morning
    $createOptions = [
        'comment_status' => 'closed',
        'ping_status'    => 'open',
        'post_author'    => $authorId,
        'post_title'     => $date_str . " Morning",
        'post_content'   => '',
        'post_status'    => 'publish',
        'post_type'      => 'worship_services'
    ];
    $result        = wp_insert_post($createOptions);
    if ($result instanceof WP_Error) {
        return $result;
    }
    update_field("last_occurrence", $date_fmt . " 11:00:00", $result);
    update_field("livestream", true, $result);
    update_field("sign_language", false, $result);
    wp_set_object_terms($result, "morning", "service_type");




    // Afternoon
    $createOptions = [
        'comment_status' => 'closed',
        'ping_status'    => 'open',
        'post_author'    => $authorId,
        'post_title'     => $date_str . " Afternoon",
        'post_content'   => '',
        'post_status'    => 'publish',
        'post_type'      => 'worship_services'
    ];
    $result        = wp_insert_post($createOptions);
    if ($result instanceof WP_Error) {
        return $result;
    }
    update_field("last_occurrence", $date_fmt . " 14:00:00", $result);
    update_field("livestream", true, $result);
    update_field("sign_language", true, $result);
    wp_set_object_terms($result, "afternoon", "service_type");



    // Evening
    $createOptions = [
        'comment_status' => 'closed',
        'ping_status'    => 'open',
        'post_author'    => $authorId,
        'post_title'     => $date_str . " Evening",
        'post_content'   => '',
        'post_status'    => 'publish',
        'post_type'      => 'worship_services'
    ];
    $result        = wp_insert_post($createOptions);
    if ($result instanceof WP_Error) {
        return $result;
    }
    update_field("last_occurrence", $date_fmt . " 18:30:00", $result);
    update_field("livestream", true, $result);
    update_field("sign_language", false, $result);
    wp_set_object_terms($result, "evening", "service_type");

    $wp_error = false;

    return $result;
}

add_filter('tribe_events_add_no_index_meta', '__return_null', 1);


/**
 * Used for announcement loop
 *
 * @param $data
 * @param $event
 *
 * @return void
 */
function tenth_event_rest_data( $data, $event )
{
    $announcementSlideImage = get_field("announcement_slide", $event);
    if (is_array($announcementSlideImage)) {
        $data['slide'] = $announcementSlideImage['url'];
    } else {
        $data['slide'] = null;
    }

    global $wpdb;
    $tableName = $wpdb->base_prefix . "redirection_items";

    $longPath = str_replace([site_url(), 'https://', 'http://'], "", $data['url']);

    $data['short_url'] = "";

    $q = $wpdb->prepare("SELECT * FROM $tableName as ri WHERE ri.action_data LIKE %s AND ri.status = 'enabled'", $wpdb->esc_like($longPath) . '%');
    $q = str_replace("**", "%", $q);
    $redir = $wpdb->get_row($q);
    if ($redir) {
        $data['short_url'] = "tenth.org" . $redir->url;
    }

    return $data;
}

/**
 * @param $event
 *
 * @return array
 */
//public function tenth_event_rest_properties( $event )
//{
//    $event = tribe_get_event( $event );
//
//    if ( ! $event ) {
//        return [];
//    }
//
//    $meta_virtual = [
//        'is_virtual'           => $event->virtual,
//        'virtual_url'          => $event->virtual_url,
//        'virtual_video_source' => $event->virtual_video_source,
//    ];
//
//    return $meta_virtual;
//}
add_filter( 'tribe_rest_event_data', 'tenth_event_rest_data', 10, 2 );


/**
 * @param       $url
 * @param       $userId
 * @param array $args
 *
 * @return string|null
 */
function tenth_getProfilePhoto($url, $userId, $args = []): ?string {
    if (class_exists('\tp\TouchPointWP\Person')) {
        $person = \tp\TouchPointWP\Person::fromId($userId);

        if ($person === null) {
            return null;
        }

        $dir = WP_CONTENT_DIR;
        $name = str_ireplace(" ", "", $person->display_name);

        if (file_exists($dir . '/themes/firmament-child/people/' . $name . '.jpg')) {
            return '/wp-content/themes/firmament-child/people/' . $name . '.jpg';
        }
        if (file_exists($dir . '/themes/firmament-child/people/' . $name . '.png')) {
            return '/wp-content/themes/firmament-child/people/' . $name . '.png';
        }
    }
    return null;
}
add_filter('get_avatar_url', 'tenth_getProfilePhoto', 100, 3);


function remove_problematic_link_action() {
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
}
add_action('init', 'remove_problematic_link_action');


function remove_obnoxious_redirect()
{
    function ddie() {
        die();
    }
    remove_action('template_redirect', 'redirect_canonical');
    remove_action('template_redirect', 'wp_old_slug_redirect');
//    add_action('template_redirect', 'ddie', 0);
}
add_action('init', 'remove_obnoxious_redirect');

function tenth_setLocale($locale): string
{
    $server_lang = htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? "en-us");
    if (!$server_lang)
        return $locale;

    $server_lang = strtolower(substr($server_lang, 0, 2));

    if ($server_lang == 'es') {
        $locale = "es_ES";
        if (!isset($_COOKIE['locale'])) {
            setcookie("locale", $locale, 0, '/', 'tenth.org');
            $_COOKIE['locale'] = $locale;
        }
    }

    return $locale;
}
add_filter('locale', 'tenth_setLocale');


// Add Twitter links for those who have them, and email addresses for staff members
function tenth_personActions($content, $person, $context): string
{
    $preContent = "";
    $postContent = "";
    if ($person->ExtraValues()->TwitterHandle) {
        $preContent .= "<a href=\"https://twitter.com/{$person->ExtraValues()->TwitterHandle}\" rel=\"noopener\" target=\"_blank\" title=\"Twitter\" class=\"fa fa-twitter btn no-ext-decoration\"></a> ";
    }
//    if (substr($person->user_email, -9) === 'tenth.org') {
//        if ($context === "person-profile") {
//            $postContent .= "&nbsp; <a href=\"mailto:{$person->user_email}\"><i class=\"las la-envelope\"></i>&nbsp;{$person->user_email}</a> ";
//        } else {
//            $preContent .= "<a href=\"mailto:{$person->user_email}\" title=\"Email\" class=\"las la-envelope btn\"></a> ";
//        }
//    }
    return $preContent . $content . $postContent;
}
add_filter('tp_person_actions', 'tenth_personActions', 10, 3);



// Determine whether contact is allowed.
function tenth_allowContact($value): string
{
    if (is_user_logged_in()) {
        return true;
    }

    // Prevent public caching, unfortunately.
    TouchPointWP::doCacheHeaders(TouchPointWP::CACHE_PRIVATE);
    $country = $_SERVER['HTTP_CF-IPCountry'] ?? null;

    if ($country === null) {
        $geoObj = TouchPointWP::instance()->geolocate(true, true);
        $country = $geoObj->raw->country_code ?? "";
    }

    if (!in_array($country, ['US', 'T2'])) {
        return false;
    }

    return $value;
}
add_filter('tp_allow_contact', 'tenth_allowContact');


/**
 * Remove :00 from time strings that have it.
 *
 * @param $string
 * @param $t
 * @return string
 */
function tenth_formatTimeString($string, $t): string
{
    return str_replace($string, ":00", "");
}
add_filter('tp_adjust_time_string', 'tenth_formatTimeString');

SessionMatters::load();


/**
 * Allow admins to upload SVGs.
 *
 * @param $file_types
 * @return array|mixed
 */
function tenth_allowFileUploadTypes($file_types){
    if (TouchPointWP::currentUserIsAdmin()) {
        $new_filetypes = [];
        $new_filetypes['svg'] = 'image/svg+xml';
        return array_merge($file_types, $new_filetypes);
    }
    return $file_types;
}
add_filter('upload_mimes', 'tenth_allowFileUploadTypes');