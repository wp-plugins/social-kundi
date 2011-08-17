<?php
/*
Plugin Name: Social Kundi
Plugin URI: http://blog.kundi.si
Description: Add simple social sharing features for your blog, by enabling the plugin and setting the embed option or adding the code social_kundi() in the template file/s
Author: Aljaž Fajmut
Version: 1.0
Author URI: http://kundi.si/
*/


add_action('init', create_function('', 'new SocialKundi();'));


class SocialKundi {
  public static $options;
  
  
  public function __construct() {
    //read settings
    self::$options = $this->read_settings();
    add_action('wp_head', array($this, 'og_headers'));
    add_action('admin_menu', array($this, 'admin_actions'));
    
    //var_dump(get_option('embed_social'));
    if (get_option('embed_social')) {
      add_filter('the_content', array($this, 'social_content'));
    }
  }
  
  
  public function admin_actions() {
  	add_options_page("Social Kundi", "Social Kundi", 'manage_options', 'social_kundi', "social_kundi_admin");
  }
  
  public function social_content($content) {
    return $content . social_kundi(FALSE);
  }
  
  
  
  static function get_options() {
    return self::$options;
  }
  
  private function read_settings() {
    $options = $this->get_defaults();
    foreach($options as $key => $value) {
      if ($option = get_option($key)) {
        $options[$key] = $option;
      }
    }
    return $options;
  }
  
  private function get_defaults() {
    return array(
      'fb_admins' => '',
      'fb_appid' => '',
      'twitter_name' => '',
      'logo_img' => '',
      'fb_color' => 'light',
      'embed_social' => FALSE
    );
  }
  
  
  static function clean_code($content) {
    return preg_replace('/\s+/', ' ', strip_tags($content));
  }
  
  private function get_excerpt() {
    global $post;

    $excerpt = $post->post_excerpt ? $post->post_excerpt : $post->post_content;
    $excerpt = self::clean_code($excerpt);

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
  
  private function get_image() {
    global $post;

    if (  (function_exists('get_post_thumbnail_id')) && ( $image = wp_get_attachment_thumb_url( get_post_thumbnail_id( $post->ID ) ) )  )
      return $image;

    return self::get_first_image($post->post_content);
  }
  
  static function get_first_image($html) {
    $matches = array();
    if (!preg_match('/<img[^>]*src=([\'"])(.*?)\\1/i', $html, $matches))
        return FALSE;

    return $matches[2];
  }
  
  public function og_headers() {  
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
      $description = $this->get_excerpt();
      $image = $this->get_image();

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
  
  
}







function social_kundi($echo = TRUE)
{
  global $post;
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

  if ($echo)  
    echo $string;
    
  return $string;
}



/*
admin page
*/

function social_kundi_admin() {  
  $options = SocialKundi::get_options();

  if (isset($_POST['update_settings']) && isset($_POST['form_token']) && ($_POST['form_token'] == 'social_kundi')) {
    foreach($options as $key => $val) {

      if (isset($_POST[$key])) {

        $value = $_POST[$key];
        $options[$key] = $value;        
      }
      else {
        $options[$key] = FALSE;
      }
      
      update_option($key, $options[$key]);
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
  
  <input type="hidden" name="form_token" value="social_kundi" />
  
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
      
      <tr valign="top">
        <th scope="row">
          <?php _e("Embed social bar", 'menu-test' ); ?>
        </th>
        <td>
          <fieldset>
            <legend class="screen-reader-text">
              <span>Embed social bar</span>
            </legend>
            <label for="embed_social">
              <input type="checkbox" id="embed_social" name="embed_social" value="1"<?php echo ($options['embed_social']) ? ' checked' : '' ?> />
              <?php _e("Embed after content", 'menu-test'); ?>
            </label>
          </fieldset>
        </td>
      </tr>

    </tbody>
  </table>

  <p class="submit">
    <input type="submit" name="update_settings" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
  </p>

  </form>
</div>
<?php
}



?>
