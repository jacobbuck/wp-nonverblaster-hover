<?php
/*
Plugin Name: NonverBlaster:hover
PluginURI: https://github.com/jacobbuck/wp-nonverblaster-hover
Description: Play video and audio files using the NonverBlaster:hover flash player, or HTML5 fallback for mobile.
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
Version: 1.1.5
*/

class WPNonverBlasterHover {
	
	public $options;
	private $default_options = array(
		"player_back_color" => "#000000",
		"control_color" => "#000000",
		"control_back_color" => "#3fd2a3",
		"audio_width" => "230",
		"video_width" => "",
		"video_height" => "",
		"video_crop" => false,
		"video_default_hd" => false	);
	private $version = "1.1.4";
	
	public function __construct () {
		$this->options = json_decode(get_option("wpnbh_options"));
		// Actions
		add_action("init", array($this, "init"));
		add_action("wp_enqueue_scripts", array($this, "wp_enqueue_scripts"));
		add_action("admin_init", array($this, "admin_init"));
		add_action("admin_enqueue_scripts", array($this, "admin_enqueue_scripts"));
		add_action("admin_menu", array($this, "admin_menu"));
		// Filters
		add_filter("audio_send_to_editor_url", array($this, "insert_audioplayer_shortcode"), 20, 3);
		add_filter("video_send_to_editor_url", array($this, "insert_videoplayer_shortcode"), 20, 3);
		add_filter("media_send_to_editor", array($this, "insert_mediaplayer_shortcode"), 20, 3);
		add_filter("plugin_action_links", array($this, "add_settings_link"), 10, 2);
		// Shortcodes
		add_shortcode("audioplayer", array($this, "audioplayer_shortcode_func"));
		add_shortcode("videoplayer", array($this, "videoplayer_shortcode_func"));
	}
	
	/* Install */
	
	public function install () {
		add_option("wpnbh_options", json_encode($this->default_options));
	}
	
	/* Uninstall */
	
	public function uninstall () {
		delete_option("wpnbh_options");
	}
	
	/* Plugin Scripts & Styles */
		
	public function init () {
		// Frontend
		wp_register_script("wp-nonverblaster-hover", plugins_url("/assets/scripts.js", __FILE__), array("swfobject"), $this->version);
		// Settings page
		wp_register_script("wp-nonverblaster-hover-options", plugins_url("/assets/options.js", __FILE__), array("jquery"), $this->version);
		wp_register_style("wp-nonverblaster-hover-options", plugins_url("/assets/options.css", __FILE__), false, $this->version, "screen");
		
	}
	
	public function wp_enqueue_scripts () {
		wp_enqueue_script("wp-nonverblaster-hover");
		wp_localize_script("wp-nonverblaster-hover", "wpnbh", array(
			"url" => plugins_url("/assets/", __FILE__),
			"options" => $this->options
		));
	}
	
	/* Media Library inserts Shortcods */
	
	public function insert_audioplayer_shortcode ($html, $url, $post_title) {
		return "[audioplayer src=\"$url\" title=\"".htmlspecialchars($post_title)."\"]";
	}
	
	public function insert_videoplayer_shortcode ($html, $url, $post_title) {
		return "[videoplayer src=\"$url\" title=\"".htmlspecialchars($post_title)."\"]";
	}
	
	public function insert_mediaplayer_shortcode ($html, $send_id, $attachment) {
		$mime_type = get_post_mime_type($send_id);
		$url = wp_get_attachment_url($send_id);
		$post_title = htmlspecialchars($attachment["post_title"]);
		switch (substr($mime_type, 0, strpos($mime_type, "/"))) {
			case "audio":
				return "[audioplayer src=\"$url\" title=\"$post_title\"]";
				break;
			case "video":
				return "[videoplayer src=\"$url\" title=\"$post_title\"]";
				break;
			default:
				return $html;
		}
	}
	
	/* Shortcode Functions */
	
	public function audioplayer_shortcode_func ($atts) {
		extract(shortcode_atts(array(
			"src" => "",
			"title" => "",
			"autoplay" => "",
			"controls" => "controls",
			"loop" => "",
		), $atts));
		$width = $this->options->audio_width . (substr($this->options->audio_width, -1) == "%" ? "" : "px");
		return "<audio src=\"$src\"" .
			($autoplay ? " autoplay=\"autoplay\" " : "") .
			($controls ? " controls=\"controls\" " : "") .
			($loop ? " loop=\"loop\" " : "") .
			" class=\"nonverblaster nonverblaster-audio\" style=\"width:$width\" title=\"$title\" id=\"nonverblaster_".md5(time().$src)."\"><a href=\"http://get.adobe.com/flashplayer\" target=\"_blank\">Adobe Flash Player</a> is required to listen to audio.</audio>";
	}
	
	public function videoplayer_shortcode_func ($atts) {
		$wp_embed_defaults = wp_embed_defaults();
		extract(shortcode_atts(array(
			"src" => "",
			"hdsrc" => "",
			"title" => "",
			"poster" => "",
			"autoplay" => "",
			"controls" => "controls",
			"loop" => "",
			"width" => $this->options->video_width ? $this->options->video_width : $wp_embed_defaults["width"],
			"height" => $this->options->video_height ? $this->options->video_height : round($wp_embed_defaults["width"] * .5625) /* 16:9 */
		), $atts));
		return "<video src=\"$src\"" .
			($hdsrc ? " data-hdsrc=\"$hdsrc\" " : "") .
			($autoplay ? " autoplay=\"autoplay\" " : "") .
			($controls ? " controls=\"controls\" " : "") .
			($loop ? " loop=\"loop\" " : "") .
			($poster ? " poster=\"$poster\" " : "") .
			" width=\"$width\" height=\"$height\" class=\"nonverblaster nonverblaster-video\" title=\"$title\" id=\"nonverblaster_".md5(time().$src)."\"><a href=\"http://get.adobe.com/flashplayer\" target=\"_blank\">Adobe Flash Player</a> is required to watch video.</video>";
	}
	
	/* Plugin Options Page */
	
	public function admin_init () {
		if (! wp_verify_nonce($_POST["wpnbh_nonce"], plugin_basename( __FILE__ ))) 
			return false;
		$posted = $_POST["wpnbh"];
		/* Filter Hex Colors */
		foreach (array("player_back_color", "control_color", "control_back_color") as $name) {
			$posted[$name] = preg_replace('/(^#[a-fA-F0-9]{6})/', '$1', $posted[$name]);
			$posted[$name] = $posted[$name] ? strtolower($posted[$name]) : $this->default_options[$name];
		}
		/* Filter Sizes */
		foreach (array("audio_width", "video_width", "video_height") as $name) {
			$posted[$name] = preg_replace('/(^[0-9]+%?)/', '$1', $posted[$name]);
			$posted[$name] = $posted[$name] ? strtolower($posted[$name]) : $this->default_options[$name];
		}
		/* Filter Checkboxes */
		foreach (array("video_crop", "video_default_hd") as $name) {
			$posted[$name] = !! $posted[$name];
		}
		/* Update Option */
		update_option("wpnbh_options", json_encode($posted));
		wp_redirect(admin_url("/options-general.php?page=options-wpnbh&settings-updated=true"));
	}
	
	public function admin_menu() {
		add_options_page("NonvernBlaster:hover Settings", "NonvernBlaster:hover", "manage_options", "options-wpnbh", array($this, "plugin_options"));
	}
	
	public function admin_enqueue_scripts ($hook) {
		if ($hook != "settings_page_options-wpnbh")
			return;	
		wp_enqueue_style("farbtastic");
		wp_enqueue_script("farbtastic");
		wp_enqueue_style("wp-nonverblaster-hover-options");
		wp_enqueue_script("wp-nonverblaster-hover-options");
	}
	
	public function add_settings_link ($links, $file) {
		if (strstr(__FILE__, $file)) 
			$links[] = "<a href=\"options-general.php?page=options-wpnbh\">".__("Settings")."</a>";
		return $links;
	}
	
	public function plugin_options () {
		?>
		<div class="wrap options-wpnbh">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>NonvernBlaster<span style="font-style:italic;font-weight:lighter">:hover</span> Settings</h2>
			
			<?php if (isset($_GET["settings-updated"]) && $_GET["settings-updated"] === "true") : ?>
			<div class="updated settings-error" id="setting-error-settings_updated"> 
			<p><strong>Settings saved.</strong></p></div>
			<?php endif; ?>
			
			<form action="" method="post">
				<input type="hidden" name="option_page" value="wpnbh">
				<input type="hidden" name="action" value="update">
				<?php wp_nonce_field(plugin_basename( __FILE__ ), "wpnbh_nonce"); ?>
				<h3>Player colors</h3>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">Background color</th>
							<td><fieldset><legend class="screen-reader-text"><span>Default audio size</span></legend>
							<input name="wpnbh[player_back_color]" type="text" id="wpnbh_player_back_color" value="<?php echo $this->options->player_back_color; ?>" class="small-text color">
							<div class="colorpicker" id="wpnbh_player_back_color-farbtastic"></div>
							</fieldset></td>
						</tr>
						<tr valign="top">
							<th scope="row">Control color</th>
							<td><fieldset><legend class="screen-reader-text"><span>Default audio size</span></legend>
							<input name="wpnbh[control_color]" type="text" id="wpnbh_control_color" value="<?php echo $this->options->control_color; ?>" class="small-text color">
							<div class="colorpicker" id="wpnbh_control_color-farbtastic"></div>
							</fieldset></td>
						</tr>
						<tr valign="top">
							<th scope="row">Control back colour</th>
							<td><fieldset><legend class="screen-reader-text"><span>Default audio size</span></legend>
							<input name="wpnbh[control_back_color]" type="text" id="wpnbh_control_back_color" value="<?php echo $this->options->control_back_color; ?>" class="small-text color">
							<div class="colorpicker" id="wpnbh_control_back_color-farbtastic"></div>
							</fieldset></td>
						</tr>
					</tbody>
				</table>
				
				<h3>Player sizes</h3>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">Audio player size</th>
							<td><fieldset><legend class="screen-reader-text"><span>Audio player size</span></legend>
							<label for="wpnbh_audio_width">Width</label>
							<input name="wpnbh[audio_width]" type="text" id="wpnbh_audio_width" value="<?php echo $this->options->audio_width; ?>" class="small-text">
							</fieldset></td>
						</tr>
						<tr valign="top">
							<th scope="row">Video player default size</th>
							<td><fieldset><legend class="screen-reader-text"><span>Video player default size</span></legend>
							<label for="wpnbh_video_width">Width</label>
							<input name="wpnbh[video_width]" type="text" id="wpnbh_video_width" value="<?php echo $this->options->video_width; ?>" class="small-text">
							<label for="wpnbh_video_height">Height</label>
							<input name="wpnbh[video_height]" type="text" id="wpnbh_video_height" value="<?php echo $this->options->video_height; ?>" class="small-text">
							</fieldset>
							If the width or height value is left blank, the default maximum embed size will be used.
							</td>
						</tr>
					</tbody>
				</table>
				
				<h3>Video Options</h3>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row" colspan="2" class="th-full"><label for="wpnbh_video_crop"><input name="wpnbh[video_crop]" type="checkbox" id="wpnbh_video_crop" value="true" <?php echo $this->options->video_crop ? " checked=\"checked\" " : ""; ?>> Crop video to fit player.</label></th>
						</tr>
						<tr valign="top">
							<th scope="row" colspan="2" class="th-full"><label for="wpnbh_video_default_hd"><input name="wpnbh[video_default_hd]" type="checkbox" id="wpnbh_video_default_hd" value="true" <?php echo $this->options->video_default_hd ? " checked=\"checked\" " : ""; ?>> Enable HD on default, when HD video is available.</label></th>
						</tr>
					</tbody>
				</table>
						
				<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
			</form>
		</div>
		<?php
	}
	
}

$wpnbh = new WPNonverBlasterHover;

register_activation_hook(__FILE__, array($wpnbh, "install"));
register_deactivation_hook(__FILE__, array($wpnbh, "uninstall"));