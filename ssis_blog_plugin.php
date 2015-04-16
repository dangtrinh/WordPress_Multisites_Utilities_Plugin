<?php
/**
 * Plugin Name: Recent Network Posts
 * Plugin URI: http://me.dangtrinh.com
 * Description: Creates a function that lists recent posts from all sites of the network. Call it in another plugins or themes.
 * Author: Trinh Nguyen
 */

/**
 * Iterates throught all sites of the network and grab the recent posts
 *//**
 * This function allows you to retrieve the wp_get_attachment_image_src()
 * data for any post's featured image on your network. If you are running
 * a multisite network, you can supply another blog's ID to retrieve a post's
 * featured image data from another site on your WordPress multisite network.
 *
 * Does not take care of icon business (at this time).
 *
 * If successful, this function returns an array of the following:
 *  [0] => url
 *  [1] => width
 *  [2] => height
 *  [3] => boolean: true if a resized image, false if it is the original
 *
 * Returns false if no image is available.
 *
 * @param int $post_id - post ID.
 * @param int post_blog_id - blog ID. Defaults to current blog ID.
 * @param string $size - Optional. Only takes size names, e.g. 'thumbnail', 'large', etc.,
 *	and not size arrays, e.g. array( 32, 32 ).
 * @return bool|array - Returns an array (url, width, height, resized image), or false, if no image is available.
 */
function get_post_featured_image_src( $post_id, $post_blog_id = 0, $size = NULL ) {
    global $blog_id, $wpdb;
    
    // If a $post_blog_id was not supplied or we're on the same site
    // or not running a multisite, then use the WP functions.
    if ( ! $post_blog_id || $blog_id == $post_blog_id || ! is_multisite() ) {
    
        // Use the WordPress function.
        if ( $post_thumbnail_id = get_post_thumbnail_id( $post_id ) )
            return wp_get_attachment_image_src( $post_thumbnail_id, $size );
            
    } else {
    
        // Change $wpdb blog ID for queries.
        // Will change back when we're done.
        $wpdb->set_blog_id( $post_blog_id );
        
        // We have to retrieve the post thumbnail ID manually.
        $post_thumbnail_id = $wpdb->get_var( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND post_id = {$post_id}" );
        
        // We have to retrieve the post thumbnail metadata manually.
        // Only runs if we found a $post_thumbnail_id.
        $post_thumbnail_metadata = $post_thumbnail_id ? maybe_unserialize( $wpdb->get_var( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_metadata' AND post_id = {$post_thumbnail_id}" ) ) : NULL;
        
        // Reset the $wpdb blog ID.
        $wpdb->set_blog_id( $blog_id );
        
        // No point in continuing if we didn't find any data
        if ( ! $post_thumbnail_metadata )
            return false;
            
        // We need to figure out base files URL.
        // This is not the best method but I can't find a more
        // definite way of securing the upload directory for another
        // site on the network and wp_upload_dir() is too complicated
        // to re-create and tweak so we'll assume the upload directory
        // structure is the same for all sites and tweak the current
        // site's upload directory.
        $post_thumbnail_base_url = NULL;
        if ( ( $current_site_upload_dir = wp_upload_dir() )
            && isset( $current_site_upload_dir[ 'baseurl' ] ) ) {
            
            // Create preg patterns for current site to replace with other site.
            $current_site_preg = preg_replace( "/([^a-z])/i", '\\\$1', get_site_url() );
            
            // Replace current site URL with other site URL.
            $post_thumbnail_base_url = preg_replace( '/^' . $current_site_preg . '/i', get_site_url( $post_blog_id ), $current_site_upload_dir[ 'baseurl' ] );
            
        }
        
        // No point going forward if we don't have the base URL or default file.
        if ( ! $post_thumbnail_base_url || ! isset( $post_thumbnail_metadata[ 'file' ] ) )
            return false;
            
        // Set default post thumbnail URL.
        $default_post_thumbnail_url = $post_thumbnail_base_url . '/' . $post_thumbnail_metadata[ 'file' ];
        
        // Set default post thumbnail width and height.
        $default_post_thumbnail_width = isset( $post_thumbnail_metadata[ 'width' ] ) ? $post_thumbnail_metadata[ 'width' ] : NULL;
        $default_post_thumbnail_height = isset( $post_thumbnail_metadata[ 'height' ] ) ? $post_thumbnail_metadata[ 'height' ] : NULL;
        
        // If no set size, or they want 'full' size, or size doesn't exist, get default URL and info.
        if ( ( ! $size || ( $size && ( 'full' == $size || ( ! ( isset( $post_thumbnail_metadata[ 'sizes' ] ) && isset( $post_thumbnail_metadata[ 'sizes' ][ $size ] ) ) ) ) ) ) && isset( $post_thumbnail_metadata[ 'file' ] ) ) {
        
            // Return default image size data.
            return array(
                $default_post_thumbnail_url,
                $default_post_thumbnail_width,
                $default_post_thumbnail_height,
                false,
                );
		        
        }
	    
        // Get specific size URL and info.
        else if ( ( $post_thumbnail_size = $post_thumbnail_metadata[ 'sizes' ][ $size ] )
            && isset( $post_thumbnail_size[ 'file' ] ) ) {
            
            // We need the basename of the default URL to create the specific size URL.
            $default_post_thumbnail_url_basename = wp_basename( $default_post_thumbnail_url );
	        
            // Create URL for specific size by merging with default URL.
            $post_thumbnail_size_url = str_replace( $default_post_thumbnail_url_basename, wp_basename( $post_thumbnail_size[ 'file' ] ), $default_post_thumbnail_url );
	        
            // Set post thumbnail width and height for specific size.
            $post_thumbnail_size_width = isset( $post_thumbnail_size[ 'width' ] ) ? $post_thumbnail_size[ 'width' ] : NULL;
            $post_thumbnail_size_height = isset( $post_thumbnail_size[ 'height' ] ) ? $post_thumbnail_size[ 'height' ] : NULL;
	        
            // Return specific image size data.
            return array(
                $post_thumbnail_size_url,
                $post_thumbnail_size_width,
                $post_thumbnail_size_height,
                ( $post_thumbnail_size_width != $default_post_thumbnail_width && $post_thumbnail_size_height != $default_post_thumbnail_height ),
            );
            
        }
        
    }
    
    return false;
    
}


function add_site_path_to_thumb_url($thumb_url, $site_id) {
	$new_thumb_url = $thumb_url;
	if ($site_id > 1) {
		$upload_pos = strpos($new_thumb_url, 'uploads/');
		$new_thumb_url = substr_replace($new_thumb_url, 'sites/' . $site_id . '/', $upload_pos+8, 0);
	}
	return $new_thumb_url;
}


function get_recent_network_posts( $howMany = 6 ) {
 
  global $wpdb;
  global $table_prefix;
 
  // get an array of the table names that our posts will be in
  // we do this by first getting all of our blog ids and then forming the name of the 
  // table and putting it into an array
  $rows = $wpdb->get_results( "SELECT blog_id from $wpdb->blogs WHERE
    public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0';" );
 
  if ( $rows ) : 
    $blogPostTableNames = array();
    foreach ( $rows as $row ) :
 
      $blogPostTableNames[$row->blog_id] = $wpdb->get_blog_prefix( $row->blog_id ) . 'posts';
 
    endforeach;
    # print_r($blogPostTableNames); # debugging code
 
    // now we need to do a query to get all the posts from all our blogs
    // with limits applied
    if ( count( $blogPostTableNames ) > 0 ) :
 
      $query = '';
      $i = 0;
 
      foreach ( $blogPostTableNames as $blogId => $tableName ) :
 
        if ( $i > 0 ) :
        $query.= ' UNION ';
        endif;
 
        $query.= " (SELECT ID, post_date, $blogId as `blog_id` FROM $tableName WHERE post_status = 'publish' AND post_type = 'post')";
        $i++;
 
      endforeach;
 
      $query.= " ORDER BY post_date DESC LIMIT 0,$howMany;";
      # echo $query; # debugging code
      $rows = $wpdb->get_results( $query );
 
      // now we need to get each of our posts into an array and return them
      if ( $rows ) : 
        $posts = array();
        foreach ( $rows as $row ) :
			$thumb_src = get_post_featured_image_src($row->ID, $row->blog_id, 'full');
			$mypost = array();
			$mypost['the_post'] = get_blog_post( $row->blog_id, $row->ID );
			$mypost['thumb_url'] = add_site_path_to_thumb_url($thumb_src[0], (string)$row->blog_id);
			$mypost['permalink'] = get_blog_permalink($row->blog_id, $row->ID);
			$mypost['time'] = get_post_time('F j, Y', true, $row->ID);
	        $posts[] = $mypost;
        endforeach;
        # echo "<pre>"; print_r($posts); echo "</pre>"; exit; # debugging code
        return $posts; 
      else: 
        return "Error: No Posts found"; 
      endif;
    else: 
       return "Error: Could not find blogs in the database"; 
    endif; 
  else: 
    return "Error: Could not find blogs"; 
  endif;
}
?>
