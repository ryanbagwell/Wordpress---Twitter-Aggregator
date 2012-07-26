<?php
/*
Plugin Name: HZ Twitter Aggregator
Plugin URI: http://www.hzdg.com
Description: Shows the most recent Tweets from the feeds you specify. 
Version: 1.0
Author: Ryan Bagwell (ryan@ryanbagwell.com)
Author URI: http://www.ryanbagwell.com
*/

class HZTwitterfeed extends WP_Widget {

	public $feed_urls; //an array of the feed urls
	public $feed_content; //an array of the content of each feed in string form;

	function HZTwitterfeed() {
  	parent::WP_Widget(false, $name = 'Latest Tweets');	
	
		add_action('wp_enqueue_scripts', array( $this, 'add_js' ) );
	
		$this->feed_urls = array();	
		$this->feed_content = array();

	}

	function widget($args, $instance) { ?>
		
		<li class='widget hz-twitter-feed-widget'>
			<h2><?php echo $instance['title']; ?></h2>
			<ul class="tweets">
				
				<?php if ( $instance['tweet_to'] != '' ): ?>
				<li class="button">
					<a href="https://twitter.com/<?php echo $instance['tweet_to']; ?>" class="twitter-follow-button" data-show-count="false">Follow @<?php echo $instance['tweet_to']; ?></a>
				</li>
				<?php endif; ?>
			</ul>
			<div class='extra-1'></div>
			<div class='extra-2'></div>
			<div class='extra-3'></div>
		</li>
		<script type="text/javascript">
			jQuery(document).ready(function() {
				$('ul.tweets').hzTwitter({
					maxItems: '<?php echo $instance['max_items']; ?>',
			  		tweetTo: '<?php echo ( array_key_exists('tweet_to', $instance ) ) ? $instance['tweet_to'] : ''; ?>',
					feeds: <?php echo json_encode($instance['feeds']); ?>
				});
			});
		</script>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
	
	<?php	
	
	}

	function get_feed_items_html() {
		
		$this->update_feed_file( $_POST['feeds'] );
		
		return file_get_contents(dirname(__FILE__).'/hz-twitter-feed.xml');
			
	}

	//grabs the xml content from each feed and stores it in an array
	function set_feed_content( $feeds = array()) {
		foreach($feeds as $feed) {
			$this->feed_content[] = file_get_contents(str_replace(array('.rss', '.json'), '.xml', $feed));	
		}	
	}

	
	function update_feed_file( $feeds ) {

		$this->feed_urls = $feeds;
		
		//if it's been less than 15 minutes since we last update the file, don't update it.
		if ( time() - filemtime(dirname(__FILE__) . '/hz-twitter-feed.xml' )  < 900)
			return false;
		
		//grab the latest feed urls
		$this->set_feed_content( $feeds );
		
		//set an array to sort the feed items by timeline
		$sort_array = array();
		
		//loop through the list of feeds and grab the content of each feed
		foreach($this->feed_content as $feed_no => $feed) {
			
			$xml = simplexml_load_string( $feed );
			
			foreach($xml->status as $item) {
				$user = $item->user->screen_name;
				$status_id = $item->id;
				$url = "http://www.twitter.com/$user/statuses/$status_id";
				$item->addChild('sourceURL', $this->get_tiny_url($url));
				$sort_array[strtotime($item->created_at)] = $item;
			}
			
		}
		//sort them based on the timestamp value
		krsort($sort_array);
		
		//a placeholder for the xml string to add to the new xmldoc later
		$xml_str = '';
		
		foreach($sort_array as $timestamp => $item) {
			$xml_str .= $item->asXML();
		}
	
		//put our xml string together and add  it to the 
		$xml = "<statusUpdates><lastUpdate>".time()."</lastUpdate>$xml_str</statusUpdates>";
				
		//write the new content to the file
		$handle = fopen(dirname(__FILE__) . '/hz-twitter-feed.xml','w');
		fwrite( $handle, $xml );
		fclose( $handle );
		
	}


	function form($args) { ?>

		<div class="hz-field-wrapper">
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
			<input id="<?php echo $this->get_field_id('title'); ?>" type="text" name="<?php echo $this->get_field_name('title'); ?>"value="<?php echo $args['title']; ?>" />
		</div>
		
		<div class="hz-field-wrapper tweet-to">
			<label>Tweet To:</label>
			<input type="text" class="tweet-to" name="<?php echo $this->get_field_name("tweet_to"); ?>" value="<?php echo ( array_key_exists('tweet_to', $args ) ) ? $args['tweet_to'] : ''; ?>" />
		</div>		
		
		<div class="hz-feed-fields hzclearfix">
			
			<?php 
			
			if ($args['feeds'] == '')
				$args['feeds'] = array('');
			
			$i = 1;
			foreach($args['feeds'] as $value): ?>

			<div class="hz-field-wrapper">
				
				<label for="<?php echo $this->get_field_id("feed$i"); ?>">Feed <?php echo $i; ?></label>
				<input type="text" id="<?php echo $this->get_field_id("feed$i"); ?>" class="hz-feed-<?php echo $i; ?>" name=" <?php echo $this->get_field_name('feeds'); ?>[]" value="<?php echo $value; ?>" />
				
			</div>
			
			<?php 
			$i++;
			endforeach; ?>
			
		</div>
		
		<span class="hz-add-feed">Add another feed</span>
		
		<div class="hz-field-wrapper max-feeds">
			<label>Items to show:</label>
			<input type="text" class="max-feeds" name="<?php echo $this->get_field_name("max_items"); ?>" value="<?php echo $args['max_items']; ?>" size="5" />
		</div>
		

		
		<span class="field-name-prefix" style="display: none"><?php echo $this->get_field_name(''); ?></span>
		
	<?php }


	function update($new_instance, $old_instance) {

		foreach($new_instance as $key => $value) {
			
			if ( $value == '' )
				unset($new_instance[$key]);
			
			if ( is_array($value) )
				foreach( $value as $item => $feed) {
					if ($feed == '')
						unset($new_instance[$key][$item]);
				}
				
		}
		
		return $new_instance;
  }

	//turns a url into a tiny url
	function get_tiny_url($url = null) {
		if (is_null($url))
			return;
			
		$tiny_url = file_get_contents("http://tinyurl.com/api-create.php?url=$url");
	
		return $tiny_url;
	
	}
	
	function add_js() {

		if ( is_admin() ) {
			wp_enqueue_script('hz-feed',plugins_url('hz-twitter.js',__FILE__),'jquery');
			wp_enqueue_style('hz-feed',plugins_url('hz-twitter-admin.css',__FILE__));
		}
	
		if ( !is_admin() )
			wp_enqueue_script( 'hzTwitter', plugins_url('hzTwitter.jquery.js', __FILE__ ), 'jquery' );		
		
	}
	
	function hz_twitter_ajax() {
		echo $this->get_feed_items_html();
		die();
	}

}

add_action('wp_ajax_nopriv_hz_twitter_ajax',create_function('', '$t = new HZTwitterfeed(); return $t->hz_twitter_ajax();'));	
add_action('wp_ajax_hz_twitter_ajax',create_function('', '$t = new HZTwitterfeed(); return $t->hz_twitter_ajax();'));			
add_action('widgets_init', create_function('', 'return register_widget("HZTwitterfeed");'));

