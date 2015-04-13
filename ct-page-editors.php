<?php
/**
 * Plugin Name: CT Page Editors
 * Plugin URI: https://www.facebook.com/ctpageeditors
 * Description: Add additional WP wysiswg editors to any page 
 * Version: 0.0.1
 * Author: Craig Tran
 * Author URI: https://plus.google.com/u/cntran
 * Text Domain: cttextdomain
 * License: GPL2
 */
 
add_action( 'admin_init', 'ctpe_admin_init' );
add_action( 'save_post', 'ctpe_save_post' );  

function ctpe_admin_init() {
  // add additional editors if exists
  $editors = ctpe_get_page_editors();
  
  $count = 0;
  foreach ( $editors as $editor ) {
    add_meta_box( 'ct-page-editor' . $count, __($editor, 'cttextdomain'), 'ctpe_page_editor_meta_box', 'page', 'normal', 'default' );
    $count++;
  }
  
}

function ctpe_page_editor_meta_box( $post, $metabox ) {
  $editor_id = trim($metabox['title']);
  $editor_id = strtolower(preg_replace('/\s+/', '', $editor_id));
  
  $ctpe_page_editor_content = get_post_meta( $post->ID, '_ctpe_page_editor_content_' . $editor_id, true );
  
  wp_editor($ctpe_page_editor_content, 'textarea-' . $editor_id );
}
 
function ctpe_save_post( $post_id ) {
  
  ctpe_save_page_editors_meta_box( $post_id );
}


function ctpe_save_page_editors_meta_box( $post_id ) {
  $post = get_post( $post_id );

  if ( $post->post_type == 'revision' ) 
    return;
    
  if ( 'page' === $post->post_type ) {
    
    $editors = ctpe_get_page_editors();
    
    foreach ( $editors as $editor ) {
      
      $editor = trim($editor);
      $editor = strtolower(preg_replace('/\s+/', '', $editor));
      $editor_content = $_REQUEST['textarea-' . $editor];
      update_post_meta( $post_id, '_ctpe_page_editor_content_' . $editor, $editor_content );
    }   
  }
}

function ctpe_get_page_editors() {
  $page_template = ctpe_get_page_template();
 
  if ($page_template == 'default') {
    if ( file_exists(  get_stylesheet_directory() . '/page.php' ) )
     $page_template = "page.php";
  }
  if ( file_exists(  get_stylesheet_directory() . '/' . $page_template ) ) {
    $lines = file( get_stylesheet_directory() . '/' . $page_template );
  }
  
  $editors = '';
  if ( count($lines) > 0 ) {
    foreach ($lines as $line_num => $line) {
        if ( preg_match('/ctpe_content\(\'/i', $line) ) {
            
          $editor = trim( preg_replace('/.*ctpe_content\(\'/i', '', $line) );
          
          $editor = trim(substr($editor, 0, strpos($editor, ";")));
          $editor = trim(str_replace(')', '', $editor));
          $editor = trim(str_replace('\'', '', $editor));
          $editor = trim(str_replace('\"', '', $editor));
          
          
          $editors .= $editor . ","; 
      }
    }
  }
  return explode( ',', $editors );
}


function ctpe_get_page_template() {
  
  $post_id;
  
  if ( isset($_REQUEST['post']) )
    $post_id = $_REQUEST['post'];
  else
    $post_id = $_REQUEST['post_id'];  
  
  if ( !isset( $post_id ) )
    $post_id = $_POST['post_ID'] ;
  
  // ensure post_id is an intval
  $post_id = intval($post_id);
  
  // if post_id is valid value
  if ($post_id > 0) {
      
    $template_file = get_post_meta($post_id,'_wp_page_template',TRUE);
    // check for a template type
    return $template_file;
  }
  else {
    return 'default';
  }
}


function ctpe_content( $name, $post_id = 0 ) {
  

  ob_start();
  global $post;
  
  $name = trim($name);
  $name = strtolower(preg_replace('/\s+/', '', $name));
  
  if ($post_id == 0)
    $post_id = $post->ID;
 
  $ctpe_page_editor_content = get_post_meta( $post_id, '_ctpe_page_editor_content_' . $name, true );
  echo wpautop( do_shortcode($ctpe_page_editor_content) );
  
  $output_string = ob_get_contents();
  ob_end_clean();
  
  echo $output_string;
}



