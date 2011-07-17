<?php
/*
Plugin Name: Social Kundi
Plugin URI: http://blog.kundi.si
Description: Add simple social sharing features for your blog, by enabling the plugin and adding the code social_kundi() andwhere in the code
Author: Aljaž Fajmut
Version: 0.5
Author URI: http://kundi.si/
*/


function clean_code($content) {
  return preg_replace('/\s+/', ' ', strip_tags($content));
}

function social_kundi_get_excerpt() {
  global $post;
  
  $excerpt = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
  $excerpt = clean_code($excerpt);
  
  $max_length = 300;  //og max length
  
  //shorten
  if (strlen($excerpt) > $max_length) {
    
    $excerpt = substr($excerpt, 0, $max_length - 3);
    //cut it to the last space
    $pos = strrpos($excerpt, ' ');
    if ($pos) $excerpt = substr($excerpt, 0, $pos);
    
    $excerpt .= '...';
  }
  
  return $excerpt;
}

function social_kundi_get_image() {
  global $post;
  
  if ($image = wp_get_attachment_thumb_url( get_post_thumbnail_id( $post->ID ) ))
    return $image;
  
  return get_first_image($post->post_content);
}


function get_first_image($html) {
  $matches = array();
  if (!preg_match('/<img[^>]*src=([\'"])(.*?)\\1/i', $html, $matches))
      return FALSE;
      
  return $matches[2];
}


function social_kundi_og_headers() {  
  global $post;
  
  $string = '';
  $image = FALSE;
  $permalink = get_permalink();
  $fb_admins = get_option('fb_admins'); //"680101214";
  $fb_appid = get_option('fb_appid');
  
  $tags = array(
    'og:url' => $permalink
  );
  
  if ($fb_admins)
    $tags['fb:admins'] = $fb_admins;
  
  if ($fb_appid)
    $tags['fb:app_id'] = $fb_appid;
    
  if (is_single()) {
    $title = $post->post_title; //get_the_title($post->ID);
    $description = social_kundi_get_excerpt();
    $image = social_kundi_get_image();
    
    //$image = wp_get_attachment_thumb_url( get_post_thumbnail_id( $post->ID ) );
      
    $tags['og:title'] = $title;
    $tags['og:description'] = $description;
    $tags['og:type'] = 'article';
      
  } else {
    $site_name = get_bloginfo('name');
    $description = get_bloginfo('description');

    $tags['og:url'] = get_bloginfo('url');
    $tags['og:site_name'] = $site_name;
    $tags['og:description'] = $description;
    $tags['og:type'] = 'website';
    
  }
  
  //backup image (site image)
  if (!$image) {
    $logo_img = get_option('logo_img');

    if (!empty($logo_img)) {
      if ( (strlen($logo_img) > 7) && (substr($logo_img, 0, 7) == 'http://' ) ) {
        $image = $logo_img;
      }
      else
        $image = get_bloginfo('template_url') . $logo_img; //"/path/to-your/logo.jpg";
    }
  }
  
  if ($image)
    $tags['og:image'] = $image;

    
  //render tags
  $tags_html = '';
  foreach($tags as $name => $content) {
    $tags_html .= <<<EOF
<meta property="$name" content="$content" />

EOF;
  }

  echo "\n" . $tags_html . "\n";  
}

add_action('wp_head', 'social_kundi_og_headers');

function social_kundi()
{
  $permalink = urlencode(get_permalink($post->ID));

  $twitter_name = get_option('twitter_name');
  $twitter = (!empty($twitter_name)) ? " data-via=\"$twitter_name\"" : '';

  $fb_color = get_option('fb_color');
  $facebook = ($fb_color !== 'light') ? " colorscheme=\"dark\"" : '';
  

  $string = <<<EOF

<!-- Social Kundi start -->
<div class="social-bar">

  <div class="line social-top">

  <a href="http://twitter.com/share" class="twitter-share-button" data-count="horizontal"$twitter>Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>

  <!-- Place this tag in your head or just before your close body tag -->
  <script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
  <!-- Place this tag where you want the +1 button to render -->
  <g:plusone size="medium"></g:plusone>


  </div>

  <div class="line social-bottom">
  <div id="fb-root"></div><script src="http://connect.facebook.net/en_US/all.js#appId=159638537442983&amp;xfbml=1"></script><fb:like href="$permalink" send="true" width="450" show_faces="true" font="arial"$facebook></fb:like>
  </div>

</div>
<!-- Social Kundi end -->

EOF;

  echo $string;
}


/*
admin page
*/

function social_kundi_admin() {  
  $options = array(
    'fb_admins' => '',
    'fb_appid' => '',
    'twitter_name' => '',
    'logo_img' => '',
    'fb_color' => 'light'
  );
  
  
  foreach($options as $key => $val) {
    if ($option = get_option($key)) {
      $options[$key] = $option;
    }
    
    if (isset($_POST[$key])) {
      
      $value = $_POST[$key];
      $options[$key] = $value;
      //echo "je set: $key = $value <br/>";
      update_option($key, $value);
    }
  }
  
?>
<div class="wrap">

  <div id="icon-options-general" class="icon32">
    <br/>
  </div>
  
  <h2>Social Kundi Configuration</h2>
  
  <p>
    Social Kundi is the most powerful, simple and clean Wordpress plugin, which adds common social sharing functions to your blog (Facebook, Twitter and Google+1).<br/>
    If you want to set up loads of other functions such as which social sharing options to use and so on, then this plugin is not suitable for you.<br/>
    It is intended for effective social sharing and it was carefully designed to be plain and simple without tons of options.
    <br/>
    <br/>
    Simply set the options below so that Social Kundi can use data for generating Open Graph headers on your wordpress.<br/>
    
    <h3>Usage</h3>
    To add a sharing toolbar, call the function <b>social_kundi();</b> from anywhere in the template files.<br/>
    Usually it is placed in the <b>single-post.php</b> file under or above the content() function.
  </p>
  
  <form name="social_kundi" method="post" action="">
  
  <table class="form-table">
    <tbody>
      <tr valign="top">
        <th scope="row">
          <label for="fb_admins"><?php _e("Facebook admins:", 'menu-test' ); ?> </label>
        </th>
        <td>
          <input type="text" id="fb_admins" class="regular-text" name="fb_admins" value="<?php echo $options['fb_admins'] ?>" />
        </td>
      </tr>

      <tr valign="top">
        <th scope="row">
          <label for="fb_appid"><?php _e("Facebook app id:", 'menu-test' ); ?> </label>
        </th>
        <td>
          <input type="text" id="fb_appid" class="regular-text" name="fb_appid" value="<?php echo $options['fb_appid'] ?>" />
        </td>
      </tr>
      
      <tr valign="top">
        <th scope="row">
          <label for="fb_color"><?php _e("Facebook Like color scheme:", 'menu-test' ); ?> </label>
        </th>
        <td>
          <select id="fb_color" name="fb_color">
            <option value="light"<?php echo ($options['fb_color'] == 'light') ? ' selected="selected"' : '' ?>>Light</option>
            <option value="dark"<?php echo ($options['fb_color'] == 'dark') ? ' selected="selected"' : '' ?>>Dark</option>
          </select>

<!--
          <input type="text" id="fb_color" class="regular-text" name="fb_color" value="<?php echo $options['fb_color'] ?>" />
-->
        </td>
      </tr>
      
      <tr valign="top">
        <th scope="row">
          <label for="twitter_name"><?php _e("Twitter username:", 'menu-test' ); ?> </label>
        </th>
        <td>
          <input type="text" id="twitter_name" class="regular-text" name="twitter_name" value="<?php echo $options['twitter_name'] ?>" />
        </td>
      </tr>
      
      <tr valign="top">
        <th scope="row">
          <label for="logo_img"><?php _e("Logo image (URL):", 'menu-test' ); ?> </label>
        </th>
        <td>
          <input type="text" id="logo_img" class="regular-text" name="logo_img" value="<?php echo $options['logo_img'] ?>" />
        </td>
      </tr>

    </tbody>
  </table>

  <p class="submit">
    <input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
  </p>

  </form>
</div>
<?php
}

function social_kundi_admin_actions() {
	add_options_page("Social Kundi", "Social Kundi", 'manage_options', 'social_kundi', "social_kundi_admin");
}

add_action('admin_menu', 'social_kundi_admin_actions');


?>
