<?php
/*
Plugin Name: NonverBlaster:hover
PluginURI: https://github.com/jacobbuck/wp-nonverblaster-hover
Description: Play video and audio files using the NonverBlaster:hover flash player, or HTML5 fallback for mobile.
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
Version: 1.3
*/

class WPNonverBlasterHover {
	
	public $options;
	private $default_options = array(
		"control_color" => "#000000",
		"control_back_color" => "#3fd2a3",
		"audio_width" => "230",
		"video_width" => "",
		"video_height" => "",
		"video_crop" => false,
		"video_default_hd" => false	);
	private $fallback_width;
	private $fallback_height;
	private $version = "1.3";
	
	public function __construct () {
		$this->activate();
		// Actions
		add_action("init", array($this, "init"));
		add_action("admin_init", array($this, "admin_init"));
		add_action("admin_enqueue_scripts", array($this, "admin_enqueue_scripts"));
		add_action("admin_menu", array($this, "admin_menu"));
		// Filters
		add_filter("attachment_fields_to_edit", array($this, "insert_shortcode_button_field"), 10, 2);
		add_filter("attachment_fields_to_save", array($this, "send_shortcode_to_editor"), 10, 2);
		add_filter("plugin_action_links", array($this, "add_settings_link"), 10, 2);
		// Shortcodes
		add_shortcode("audio", array($this, "audio_shortcode_func"));
		add_shortcode("video", array($this, "video_shortcode_func"));
		// Shortcodes (legacy)
		add_shortcode("audioplayer", array($this, "audio_shortcode_func"));
		add_shortcode("videoplayer", array($this, "video_shortcode_func"));
	}
	
	/* Install */
	
	public function activate () {
		$this->options = get_option("wpnbh_options");
		if (empty($this->options) || ! is_array($this->options)) {
			// Get default options
			$this->options = $this->default_options;
			// Set defualt options
			delete_option("wpnbh_options");
			add_option("wpnbh_options", $this->options);
		}
	}
		
	/* Plugin Scripts & Styles */
		
	public function init () {
		// Get default sizes
		$wp_embed_defaults = wp_embed_defaults();
		$this->fallback_width = empty($this->options["video_width"]) ? $wp_embed_defaults["width"] : $this->options["video_width"];
		$this->fallback_height = empty($this->options["video_height"]) ? round($this->fallback_width * .5625) : $this->options["video_height"];
	}
		
	/* Insert shortcode in media library */
	
	public function insert_shortcode_button_field ($form_fields, $attachment) {
		switch (reset(explode("/", $attachment->post_mime_type))) {
			case "audio" :
				$form_fields["wpnbh"] = array(
					"label" => "",
					"input" => "html",
					"html" => get_submit_button(__("Insert Audio Player into Post"), "button", "wpnbhsend[".$attachment->ID."]", false )
				);
				break;
			case "video" :
				$form_fields["wpnbh"] = array(
					"label" => "",
					"input" => "html",
					"html" => get_submit_button(__("Insert Video Player into Post"), "button", "wpnbhsend[".$attachment->ID."]", false )
				);
				break;
		}
		return $form_fields;
	}
	
	public function send_shortcode_to_editor ($post, $attachment) {
		if (isset($_POST["wpnbhsend"])) {
			$src = wp_get_attachment_url($post["ID"]);
			$title = htmlspecialchars($attachment["post_title"]);
			switch (reset(explode("/", $post["post_mime_type"]))) {
				case "audio":
					media_send_to_editor("[audio src=\"$src\" title=\"$title\"]");
					break;
				case "video":
					media_send_to_editor("[video src=\"$src\" title=\"$title\"]");
					break;
				default:
					return $post;
			}
		}
		return $post;
	}
	
	/* Shortcode Functions */
	
	public function audio_shortcode_func ($atts) {
		$player_swf = plugins_url("NonverBlaster.swf", __FILE__);
		
		extract(shortcode_atts(array(
			"src" => "",
			"title" => "",
			"autoplay" => false,
			"loop" => false,
			"width" => $this->options["audio_width"]
		), $atts));
		
		$flashvars = array(
			"mediaURL" => $src,
			"autoPlay" => $autoplay,
			"loop" => $loop,
			"controlColor" => str_replace("#", "0x", $this->options["control_color"]),
			"controlBackColor" => str_replace("#", "0x", $this->options["control_back_color"]),
			"playerBackColor" => str_replace("#", "0x", $this->options["control_back_color"]),
			"defaultVolume" => 100,
			"treatAsAudio" => true
		);
		
		$output  = "<object type=\"application/x-shockwave-flash\" data=\"$player_swf\" width=\"$width\" height=\"17\" class=\"nonverblaster nonverblaster-audio\">";
		$output .= "<param name=\"movie\" value=\"$player_swf\" /><param name=\"menu\" value=\"false\" /><param name=\"wmode\" value=\"transparent\" />";
		$output .= "<param name=\"allowfullscreen\" value=\"false\" /><param name=\"allowscriptaccess\" value=\"always\" />";
		$output .= "<param name=\"flashvars\" value=\"" . $this->array_to_flashvars($flashvars) . "\" />";
		$output .= "<audio src=\"$src\" preload=\"none\" title=\"$title\" style=\"width:" . $width . (substr($this->options["audio_width"], -1) == "%" ? "" : "px") . "\"";
		$output .= ($autoplay ? " autoplay" : "") . ($loop ? " loop" : "") . ">";
		$output .= "<p>To listen to this you'll need the latest <a href=\"http://get.adobe.com/flashplayer\" target=\"_blank\">Adobe Flash Player</a>, or a browser with HTML5 video support.</p>";
		$output .= "</audio></object>";
		
		return $output;
	}
	
	public function video_shortcode_func ($atts) {
		$player_swf = plugins_url("NonverBlaster.swf", __FILE__);
		
		extract(shortcode_atts(array(
			"src" => "",
			"hdsrc" => "",
			"title" => "",
			"poster" => "",
			"autoplay" => false,
			"loop" => false,
			"width" => $this->fallback_width,
			"height" => $this->fallback_height
		), $atts));
		
		$flashvars = array(
			"mediaURL" => $src,
			"showTimecode" => true,
			"autoPlay" => $autoplay,
			"loop" => $loop,
			"controlColor" => str_replace("#", "0x", $this->options["control_color"]),
			"controlBackColor" => str_replace("#", "0x", $this->options["control_back_color"]),
			"playerBackColor" => "0x000000",
			"crop" => $this->options["video_crop"],
			"defaultVolume" => 100,
			"allowSmoothing" => true
		);
		if (! empty($hdsrc)) {
			$flashvars["hdURL"] = $hdsrc;
			$flashvars["defaultHD"] = $this->options["video_default_hd"];
		}
		if (! empty($poster)) {
			$flashvars["teaserURL"] = $poster;
		}
		
		$output  = "<object type=\"application/x-shockwave-flash\" data=\"$player_swf\" width=\"$width\" height=\"$height\" class=\"nonverblaster nonverblaster-video\">";
		$output .= "<param name=\"movie\" value=\"$player_swf\" /><param name=\"menu\" value=\"false\" /><param name=\"wmode\" value=\"transparent\" />";
		$output .= "<param name=\"allowfullscreen\" value=\"true\" /><param name=\"allowscriptaccess\" value=\"always\" />";
		$output .= "<param name=\"flashvars\" value=\"" . $this->array_to_flashvars($flashvars) . "\" />";
		$output .= "<video src=\"$src\" width=\"$width\" height=\"$height\" title=\"$title\" preload=\"none\" controls";
		$output .= ($autoplay ? " autoplay" : "" ) . ($loop ? " loop" : "") . ($poster ? " poster=\"$poster\"" : "") . ">";
		$output .= "<p>To watch this you'll need the latest <a href=\"http://get.adobe.com/flashplayer\" target=\"_blank\">Adobe Flash Player</a>, or a browser with HTML5 video support.</p>";
		$output .= "</video></object>";
		
		return $output;
	}
	
	/* Plugin Options Page */
	
	public function admin_init () {
		if (empty($_POST["wpnbh_nonce"]) || ! wp_verify_nonce($_POST["wpnbh_nonce"], plugin_basename( __FILE__ ))) 
			return;
		$posted = $_POST["wpnbh"];
		/* Filter Hex Colors */
		foreach (array("control_color", "control_back_color") as $name)
			$posted[$name] = strtolower(preg_replace('/(^#[a-fA-F0-9]{6})/', '$1', $posted[$name]));
		/* Filter Sizes */
		foreach (array("audio_width", "video_width", "video_height") as $name)
			$posted[$name] = strtolower(preg_replace('/(^[0-9]+%?)/', '$1', $posted[$name]));
		/* Filter Checkboxes */
		foreach (array("video_crop", "video_default_hd") as $name)
			$posted[$name] = ! empty($posted[$name]);
		/* Revert Empty Options To Default */
		foreach (array("control_color", "control_back_color", "audio_width", "video_width", "video_height") as $name)
			$posted[$name] = empty($posted[$name]) ? $posted[$name] : $this->default_options[$name];
		/* Update Option */
		update_option("wpnbh_options", $posted);
		wp_redirect(admin_url("options-general.php?page=wpnbh&settings-updated=true"));
	}
	
	public function admin_menu () {
		add_options_page("NonvernBlaster:hover Settings", "NonvernBlaster:hover", "manage_options", "wpnbh", array($this, "plugin_options"));
	}
	
	public function admin_enqueue_scripts ($hook) {
		if ($hook != "settings_page_options-wpnbh")
			return;	
		wp_enqueue_style("farbtastic");
		wp_enqueue_script("farbtastic");
	}
	
	public function add_settings_link ($links, $file) {
		if (strstr(__FILE__, $file)) 
			$links[] = "<a href=\"".admin_url("options-general.php?page=wpnbh")."\">".__("Settings")."</a>";
		return $links;
	}
	
	public function plugin_options () {
		?>
		<style media="screen">
			td {position: relative;}
			input.color {width: 65px;}
			.colorpicker {display: none; position: absolute; top: 8px; left: 100px; background: white; border: 1px solid #BBB; padding: 3px; border-radius: 3px; z-index: 100;}
		</style>
		<div class="wrap options-wpnbh">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>NonvernBlaster<span style="font-style:italic;font-weight:lighter">:hover</span> Settings</h2>
						
			<form action="" method="post">
				<input type="hidden" name="option_page" value="wpnbh">
				<input type="hidden" name="action" value="update">
				<?php wp_nonce_field(plugin_basename( __FILE__ ), "wpnbh_nonce"); ?>
				<h3>Player colors</h3>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row">Control color</th>
							<td><fieldset><legend class="screen-reader-text"><span>Default audio size</span></legend>
							<input name="wpnbh[control_color]" type="text" id="wpnbh_control_color" value="<?php echo $this->options["control_color"]; ?>" class="small-text color">
							<div class="colorpicker" id="wpnbh_control_color-farbtastic"></div>
							</fieldset></td>
						</tr>
						<tr valign="top">
							<th scope="row">Control back colour</th>
							<td><fieldset><legend class="screen-reader-text"><span>Default audio size</span></legend>
							<input name="wpnbh[control_back_color]" type="text" id="wpnbh_control_back_color" value="<?php echo $this->options["control_back_color"]; ?>" class="small-text color">
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
							<input name="wpnbh[audio_width]" type="text" id="wpnbh_audio_width" value="<?php echo $this->options["audio_width"]; ?>" class="small-text">
							</fieldset></td>
						</tr>
						<tr valign="top">
							<th scope="row">Video player default size</th>
							<td><fieldset><legend class="screen-reader-text"><span>Video player default size</span></legend>
							<label for="wpnbh_video_width">Width</label>
							<input name="wpnbh[video_width]" type="text" id="wpnbh_video_width" value="<?php echo $this->options["video_width"]; ?>" class="small-text">
							<label for="wpnbh_video_height">Height</label>
							<input name="wpnbh[video_height]" type="text" id="wpnbh_video_height" value="<?php echo $this->options["video_height"]; ?>" class="small-text">
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
							<th scope="row" colspan="2" class="th-full"><label for="wpnbh_video_crop"><input name="wpnbh[video_crop]" type="checkbox" id="wpnbh_video_crop" value="true" <?php echo $this->options["video_crop"] ? " checked=\"checked\" " : ""; ?>> Crop video to fit player.</label></th>
						</tr>
						<tr valign="top">
							<th scope="row" colspan="2" class="th-full"><label for="wpnbh_video_default_hd"><input name="wpnbh[video_default_hd]" type="checkbox" id="wpnbh_video_default_hd" value="true" <?php echo $this->options["video_default_hd"] ? " checked=\"checked\" " : ""; ?>> Enable HD on default, when HD video is available.</label></th>
						</tr>
					</tbody>
				</table>
						
				<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
			</form>
		</div>
		<script>
		jQuery(function ($) {
			$("input.color").each(function () {
				var $input = $(this),
					$picker = $(".colorpicker:first", $(this).parent());
				$picker.farbtastic($input).click(function (event) {
					event.stopPropagation();
				});
				$input.click(function (event) {
					event.stopPropagation();
					$(".colorpicker").not($picker.show()).hide();
				});
			});
			$("#wpwrap").click(function () {
				$(".colorpicker").hide();
			});
		});
		</script>
		<?php
	}
	
	private function array_to_flashvars ($old_array) {
		$new_array = array();
		foreach ($old_array as $key => $value)
			array_push($new_array, $key."=".strval($value));
		return htmlspecialchars(implode("&", $new_array));
	}
	
}

$wpnbh = new WPNonverBlasterHover;