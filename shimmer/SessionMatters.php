<?php

namespace tp\Shimmer;

class SessionMatters {

    public static function load(): void
    {
        add_action('init', [self::class, 'registerPostType']);
    }

    /**
     * Create the post type for Session Matters.
     *
     * @return void
     */
    public static function registerPostType(): void
    {
        register_post_type(
            "tp_session",
            [
                'labels'            => [
                    'name'          => _x("Session Matters", "Singular", "Shimmer"),
                    'singular_name' => _x("Session Matters", "Plural", "Shimmer"),
                ],
                'public'            => true,
                'hierarchical'      => false,
                'show_ui'           => true,
                'show_in_nav_menus' => false,
                'show_in_rest'      => false,
                'supports'          => [
                    'title',
                    'custom-fields',
//                    'thumbnail'
                ],
                'has_archive'       => true,
                'rewrite'           => [
                    'slug'       => "session",
                    'with_front' => false,
                    'feeds'      => false,
                    'pages'      => true
                ],
                'query_var'         => "session",
                'can_export'        => false,
                'delete_with_user'  => false
            ]
        );
    }
}