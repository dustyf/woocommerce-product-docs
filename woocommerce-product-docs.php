<?php
/*
Plugin Name: WooCommerce Product Documentation
Plugin URI: http://www.orionweb.net
Description: Add documents in WordPress and add as a tab on WooCommerce Products.  Currently requires WooCommerce, Posts 2 Posts, and Advanced Custom Fields.
Version: 1.0
Author: Dustin Filippini
Author URI: http://dustyf.com
License: GPL2
*/

// Include Shortcode to display Doc Selector on a page
include_once( 'inc/shortcode.php' );

// Enqueue Plugin Scripts
function docrepo_enqueue() {
    // Include the Chosen JQuery Plugin for cool multiselects
    wp_enqueue_script( 'docrepo-chosen', plugins_url( '/js/chosen.jquery.min.js' , __FILE__ ), array( 'jquery' ), '1.0', false  );
    wp_enqueue_style( 'docrepo-chosen', plugins_url( '/css/chosen.min.css' , __FILE__ ) );
    wp_enqueue_script( 'product-docs', plugins_url( '/js/product-docs.js' , __FILE__ ), array( 'jquery', 'docrepo-chosen' ), '1.0', false  );
}
add_action( 'wp_enqueue_scripts', 'docrepo_enqueue' );


// Add WooCommerce tab to display documentation related to prodcuts
add_filter( 'woocommerce_product_tabs', 'add_related_doc_tab' );
function add_related_doc_tab() {
    $connected = new WP_Query( array(
        'connected_type' => 'related_documents',
        'connected_items' => get_queried_object(),
        'nopaging' => true, 
    ) );
        
    if ( $connected->have_posts() ) : 
        $tabs['related-docs'] = array(
            'title'    => 'Documentation',
            'priority' => 30,
            'callback' => 'related_doc_tab'
        );
        return $tabs;
    wp_reset_postdata();
    endif;
}

function related_doc_tab() {
    echo '<h2>Documentation</h2>';

    $doc_types = get_terms( 'docrepo_document_types' );
    foreach ( $doc_types as $doc_type ) {

        $connected = new WP_Query( array(
            'connected_type' => 'related_documents',
            'connected_items' => get_queried_object(),
            'nopaging' => true, 
            'taxonomy' => 'docrepo_document_types',
            'term' => $doc_type->name
        ) );
            
        if ( $connected->have_posts() ) : 
            echo '<h4>' . $doc_type->name . '</h4>';
            echo '<ul>';
                while ( $connected->have_posts() ) : $connected->the_post();
                    $file = get_field('docrepo_upload_file');
                    echo '<li><a href="' . $file['url'] . '" target="_blank">' . get_the_title() . '</a></li>';
                endwhile;
            echo '</ul>';
        wp_reset_postdata();
        endif;
    }
}

// Changing the Title label on post type
add_filter( 'enter_title_here', 'docrepo_change_enter_title_here', 10, 1 );
function docrepo_change_enter_title_here( $title ) {
    $screen = get_current_screen();
 
     if ( 'docrepo_document' == $screen->post_type ) {
          $title = 'Document Title';
     }
 
     return $title;
}

// Add field for Uploading documents.  Dependant on Advanced Custom Fields.
if ( function_exists( 'register_field_group' ) ) {
    register_field_group( array(
        'id' => 'acf_document-upload',
        'title' => 'Document Upload',
        'fields' => array (
            array (
                'key' => 'field_52092ccdec2c3',
                'label' => 'Upload File',
                'name' => 'docrepo_upload_file',
                'type' => 'file',
                'save_format' => 'object',
                'library' => 'all',
            ),
        ),
        'location' => array (
            array (
                array (
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'docrepo_document',
                    'order_no' => 0,
                    'group_no' => 0,
                ),
            ),
        ),
        'options' => array (
            'position' => 'normal',
            'layout' => 'no_box',
            'hide_on_screen' => array (
            ),
        ),
        'menu_order' => 0,
    ));
}

// Adding a post relationship between documentation and products. Dependant on Posts 2 Posts.
add_action( 'p2p_init', 'docrepo_connect_products' );
function docrepo_connect_products() {
    p2p_register_connection_type( array( 
        'name' => 'related_documents',
        'from' => 'docrepo_document',
        'to' => 'product',
        'reciprocal' => true,
        'title' => 'Product Documentation',
        'admin_column' => 'from',
        'from_labels' => array( 'column_title' => 'Connected Products' )
    ) );
}

/*****
 * Create custom post type
 *****/
add_action( 'init', 'register_cpt_docrepo_document' );

function register_cpt_docrepo_document() {

    $labels = array( 
        'name' => _x( 'Documents', 'docrepo_document' ),
        'singular_name' => _x( 'Document', 'docrepo_document' ),
        'add_new' => _x( 'Add New', 'docrepo_document' ),
        'add_new_item' => _x( 'Add New Document', 'docrepo_document' ),
        'edit_item' => _x( 'Edit Document', 'docrepo_document' ),
        'new_item' => _x( 'New Document', 'docrepo_document' ),
        'view_item' => _x( 'View Document', 'docrepo_document' ),
        'search_items' => _x( 'Search Documents', 'docrepo_document' ),
        'not_found' => _x( 'No documents found', 'docrepo_document' ),
        'not_found_in_trash' => _x( 'No documents found in Trash', 'docrepo_document' ),
        'parent_item_colon' => _x( 'Parent Document:', 'docrepo_document' ),
        'menu_name' => _x( 'Documents', 'docrepo_document' ),
    );

    $args = array( 
        'labels' => $labels,
        'hierarchical' => true,
        'supports' => array( 'title' ),
        'taxonomies' => array( 'docrepo_language', 'docrepo_model', 'docrepo_equip_type', 'docrepo_doc_type' ),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => false,
        'has_archive' => false,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => array( 
            'slug' => 'documentation', 
            'with_front' => true,
            'feeds' => true,
            'pages' => true
        ),
        'capability_type' => 'post'
    );

    register_post_type( 'docrepo_document', $args );
}

/*****
 * Create Custom Taxonomies
 *****/

add_action( 'init', 'register_taxonomy_docrepo_document_types' );

function register_taxonomy_docrepo_document_types() {

    $labels = array( 
        'name' => _x( 'Document Types', 'docrepo_document_types' ),
        'singular_name' => _x( 'Document Type', 'docrepo_document_types' ),
        'search_items' => _x( 'Search Document Types', 'docrepo_document_types' ),
        'popular_items' => _x( 'Popular Document Types', 'docrepo_document_types' ),
        'all_items' => _x( 'All Document Types', 'docrepo_document_types' ),
        'parent_item' => _x( 'Parent Document Type', 'docrepo_document_types' ),
        'parent_item_colon' => _x( 'Parent Document Type:', 'docrepo_document_types' ),
        'edit_item' => _x( 'Edit Document Type', 'docrepo_document_types' ),
        'update_item' => _x( 'Update Document Type', 'docrepo_document_types' ),
        'add_new_item' => _x( 'Add New Document Type', 'docrepo_document_types' ),
        'new_item_name' => _x( 'New Document Type', 'docrepo_document_types' ),
        'separate_items_with_commas' => _x( 'Separate document types with commas', 'docrepo_document_types' ),
        'add_or_remove_items' => _x( 'Add or remove Document Types', 'docrepo_document_types' ),
        'choose_from_most_used' => _x( 'Choose from most used Document Types', 'docrepo_document_types' ),
        'menu_name' => _x( 'Document Types', 'docrepo_document_types' ),
    );

    $args = array( 
        'labels' => $labels,
        'public' => false,
        'show_in_nav_menus' => false,
        'show_ui' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'rewrite' => false,
        'query_var' => true
    );

    register_taxonomy( 'docrepo_document_types', array('docrepo_document'), $args );
}

add_action( 'init', 'register_taxonomy_docrepo_languages' );

function register_taxonomy_docrepo_languages() {

    $labels = array( 
        'name' => _x( 'Languages', 'docrepo_languages' ),
        'singular_name' => _x( 'Language', 'docrepo_languages' ),
        'search_items' => _x( 'Search Languages', 'docrepo_languages' ),
        'popular_items' => _x( 'Popular Languages', 'docrepo_languages' ),
        'all_items' => _x( 'All Languages', 'docrepo_languages' ),
        'parent_item' => _x( 'Parent Language', 'docrepo_languages' ),
        'parent_item_colon' => _x( 'Parent Language:', 'docrepo_languages' ),
        'edit_item' => _x( 'Edit Language', 'docrepo_languages' ),
        'update_item' => _x( 'Update Language', 'docrepo_languages' ),
        'add_new_item' => _x( 'Add New Language', 'docrepo_languages' ),
        'new_item_name' => _x( 'New Language', 'docrepo_languages' ),
        'separate_items_with_commas' => _x( 'Separate languages with commas', 'docrepo_languages' ),
        'add_or_remove_items' => _x( 'Add or remove Languages', 'docrepo_languages' ),
        'choose_from_most_used' => _x( 'Choose from most used Languages', 'docrepo_languages' ),
        'menu_name' => _x( 'Languages', 'docrepo_languages' ),
    );

    $args = array( 
        'labels' => $labels,
        'public' => false,
        'show_in_nav_menus' => false,
        'show_ui' => true,
        'show_tagcloud' => true,
        'show_admin_column' => true,
        'hierarchical' => true,
        'rewrite' => false,
        'query_var' => true
    );

    register_taxonomy( 'docrepo_languages', array('docrepo_document'), $args );
}


