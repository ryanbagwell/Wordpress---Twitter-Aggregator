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
	
		if (is_admin()) {
			wp_enqueue_script('hz-feed',plugins_url('hz-twitter.js',__FILE__),'jquery');
			wp_enqueue_style('hz-feed',plugins_url('hz-twitter-admin.css',__FILE__));
		}
	
		$this->feed_urls = array();	
		$this->feed_content = array();

	}

	function widget($args, $instance) {
		
		//default settings		
		$before_widget = "<li class='widget hz-twitter-feed-widget'>";
		$after_widget = "</li>";
		$before_title = "<h2>";
		$after_title = "</h2>";
		
		$this->set_feed_urls($instance);
				
		echo $before_widget;
		
    	echo $before_title . $instance['title'] . $after_title;
		
		$xml = simplexml_load_string($this->get_combined_feed());
      
		$items = $xml->item;
	
		$max_items = $instance['max_items'];
		
		//remove the twitter name prefix from the tweet that for some reason Twitter started inserting into the rss feeds
		$parts = explode(':',$items[$i]->title);
		$tweet = ltrim($items[$i]->title,$parts[0].":");
						
		$i = 0;		
		while($i<$max_items) { 
					
			?>
			<div class="tweet-wrapper">
				<span class="tweet"><?php echo $tweet; ?></span>
				<a class="url" rel="external" href="<?php echo $items[$i]->tinyUrl;?>"><?php echo $items[$i]->tinyUrl;?></a>
				<div class="date-wrapper">
					<span class="date"><?php echo date(get_site_option('date_format'),strtotime($items[$i]->pubDate));?></span>
					<span class="time"> at <?php echo date(get_site_option('time_format'),strtotime($items[$i]->pubDate));?></span>
					<span class="via">via <?php echo $items[$i]->children('http://api.twitter.com')->source; ?></span>
				</div>
			</div>
		
		<?php
		$i++;	
		}
		
		//empty divs for extra images or whatever
		echo "<div class='extra-1'></div>";
		echo "<div class='extra-2'></div>";
		echo "<div class='extra-3'></div>";
		
		echo $after_widget;
		
	}

	//puts all the urls from the widget settings into an array
	function set_feed_urls($instance) {
		
		//get all the feeds and stick them in an array
		foreach($instance as $key=>$value) {
		
			if (strpos($key,'tfeed-') !== false && $value != '') {
				$feed_no = substr($key,8);			
				
				$this->feed_urls['tfeed-' . $feed_no] = $value;
		
			}
		
		}
		
	}

	//grabs the xml content from each feed and stores it in an array
	function set_feed_content() {
		foreach($this->feed_urls as $key => $url) {
			$this->feed_content[$key] = file_get_contents($url);	
		}	
	}


	function get_combined_feed() {
	
		//first, check if it's time to update the file
		$feed = simplexml_load_file(dirname(__FILE__) . '/hz-twitter-feed.xml');
		
		//if it's been less than 15 minutes since we last update the file, don't update it.
		if (time() - $feed->lastUpdate > 900)
			$this->update_feed_file();

		return file_get_contents(dirname(__FILE__). '/hz-twitter-feed.xml');
		
	}
	
	function update_feed_file() {

		//grab the latest feed urls
		$this->set_feed_content();
		
		//set an array to sort the feed items by timeline
		$sort_array = array();
		
		//loop through the list of feeds and grab the content of each feed
		foreach($this->feed_content as $feed_no => $feed) {
			
			$xml = simplexml_load_string($feed);
			$items = $xml->channel->item;
			
			$i=0;
			foreach($items as $item_no => $item) {
					
				$sort_array[$feed_no . '-' . $i] = strtotime($item->pubDate);
			$i++;
			}
			
		}
				
		//sort them based on the timestamp value
		arsort($sort_array,SORT_NUMERIC);

		//a placeholder for our namespaces so we can add them in later
		$namespaces = array();
		
		//a placeholder for the xml string to add to the new xmldoc later
		$xml_str = '';
		
		foreach($sort_array as $item => $timestamp) {
			
			$item = explode('-',$item);
			$feed_no = $item[1];
			$feed_item_no = (int)$item[2];
						
			$content = $this->feed_content['tfeed-' . $feed_no];
					
			$xml = simplexml_load_string($content);
			
			//we need to define all of the namespaces in our combined document so it doesn't throw an error
			foreach($xml->getDocNamespaces() as $key => $ns) {
				$namespaces[$key] = $ns;
			};
						
			//for rss feed formats
			$rss_items = $xml->channel->item;
			if (count($rss_items) > 0)
					$xml_str .= $rss_items[$feed_item_no]->asXML();
	

			//for atom formats
			$atom_items = $xml->feed->entry[$feed_item_no];
			if (count($atom_items) > 0)
				$xml_str .= $atom_items->asXML();
	
		}

		//construct a string for namespaces
		$ns_string = '';
		foreach($namespaces as $key=>$value) {
			$ns_string .= "xmlns:$key='$value' ";
		}


		//put our xml string together and add  it to the 
		$xml = "<xml $ns_string>";
		$xml .= "<lastUpdate>" . time() . "</lastUpdate>";
		$xml .= "$xml_str</xml>";


		//add a tiny url to items
		$xml = simplexml_load_string($xml);
		$items = $xml->item;
		
		$i = 0;
		foreach($items as $item) {
			$item->addChild('tinyUrl',$this->get_tiny_url($item->link));
			$xml->items[$i] = $item;
		
		$i++;	
		}
		
		//write the new content to the file
		$handle = fopen(dirname(__FILE__) . '/hz-twitter-feed.xml','w');
		fwrite($handle,$xml->asXML());
		fclose($handle);
		
	}


	function form($args) {
				
		?>

		<div class="hz-field-wrapper">
			<label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
			<input id="<?php echo $this->get_field_id('title'); ?>" type="text" name="<?php echo $this->get_field_name('title'); ?>"value="<?php echo $args['title']; ?>" />
		</div>
		
		<div class="hz-feed-fields hzclearfix">

			<div class="hz-field-wrapper">
				
				<label for="<?php echo $this->get_field_id('tfeed-1'); ?>">Feed 1:</label>
				<input type="text" id="<?php echo $this->get_field_id('tfeed-1'); ?>" class="hz-feed-1" name="<?php echo $this->get_field_name('tfeed-1'); ?>" value="<?php echo $args['tfeed-1']; ?>" />
			</div>
			
			<?php 
			
			$i = 1;
			foreach($args as $key => $value) {
				
				if (strpos($key,'tfeed-1'))
					continue;
					
				if (strpos($key,'tfeed-')) { ?>
					 
					<div class="hz-field-wrapper">
						<label for="<?php echo $this->get_field_id("tfeed-$i"); ?>">Feed <?php echo $i; ?>:</label>
						<input type="text" id="<?php echo $this->get_field_id("tfeed-$i"); ?>" class="hz-feed-<?php echo $i; ?>" name="<?php echo $this->get_field_name("tfeed-$i"); ?>" value="<?php echo $value; ?>" />
					</div>
					
				<?php
				}
				$i++;
			}
			
			?>
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
			
			if($value == '') {
			//if (strpos($key,'hz_feed_') >= 0 && $value == '') {
				unset($new_instance[$key]);
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

}
			
add_action('widgets_init', create_function('', 'return register_widget("HZTwitterfeed");'));

