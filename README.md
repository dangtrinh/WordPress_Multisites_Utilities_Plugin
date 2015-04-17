# WordPress Multisite Utilities Plugin


## DESCRIPTION

A plugin to add some functionalities to your WordPress Multisite instance. This 
provides you some functions you can use in your plugin or template.

* Get recent posts from all of the blogs in your network:

```
get_recent_network_posts
```

## USAGE

* Install the WordPress Multisite Utilities Plugin:

```
$ cd /var/www/your_wordpress_installation/wp-content/plugins

$ git clone https://github.com/dangtrinh/WordPress_Multisites_Utilities_Plugin
```

* Activate the plugin for your network (network only)
* In your home page template file (for example page-home.php), call the get_recent_network_posts function to get latest posts (6 posts by default) from your network. For example:

```html
<?php
 $lastposts = get_recent_network_posts();
 foreach( $lastposts as $post):
?>
  <div class="col-1-3">
   <div class="blog-post">
    <div class="blog-post-thumbnail" style="background-image: url('<?php echo $post['thumb_url'];?>')"></div>
    <a href="<?php echo $post['permalink']; ?>">
    <div class="blog-post-content">
     <div class="content-container">
      <p class="date"><?php echo strftime("%m/%d/%Y", strtotime($post['the_post']->post_date)); ?></p>
      <h4><?php echo $post['the_post']->post_title; ?></h4>
      <p><?php $content = $post['the_post']->post_content; $trimmed_content = wp_trim_words( $content, 15, '...' ); echo $trimmed_content; ?></p>
     </div>
    </div>
    </a>
   </div>
  </div>
<?php 
 endforeach;
?>
```

Notes: the return data is an array of objects:

$posts = Array(

			[0] => Array(
					['the_post']	=> the_post_object,
					['thumb_url']	=> url_of_the_featured_image,
					['permalink']	=> permanent_link_of_the_post
				),

			[1] => Array(...),
...
		)


## LICENSE

GPLv2 or later
