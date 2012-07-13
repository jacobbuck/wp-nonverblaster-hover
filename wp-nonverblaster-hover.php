<?php
/*
Plugin Name: NonverBlaster:hover
PluginURI: https://github.com/jacobbuck/wp-nonverblaster-hover
Description: Play audio and video files using the NonverBlaster:hover flash player, or HTML5 audio/video fallback.
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
Version: 1.4.1
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
	private $version = "1.4.1";
	
	/* Let's do this thang! */
	public function __construct () {
		
		// Get pptions
		$this->options = get_option("wpnbh_options");
		if (empty($this->options) || ! is_array($this->options)) {
			// Use default options
			$this->options = $this->default_options;
			// Set defualt options
			delete_option("wpnbh_options");
			add_option("wpnbh_options", $this->options);
		}
		
		// Actions
		add_action("init", array($this, "init"));
		add_action("admin_init", array($this, "admin_init"));
		add_action("admin_enqueue_scripts", array($this, "admin_enqueue_scripts"));
		add_action("admin_menu", array($this, "admin_menu"));
		
		// Filters
		add_filter("attachment_fields_to_edit", array($this, "add_attachment_fields"), 10, 2);
		add_filter("save_attachment_fields", array($this, "save_attachment_fields"), 10, 2);
		add_filter("media_send_to_editor", array($this, "send_shortcode_to_editor"), 10, 3);
		add_filter("plugin_action_links", array($this, "add_settings_link"), 10, 2);
		
		// Shortcodes
		add_shortcode("audio", array($this, "audio_shortcode_func"));
		add_shortcode("video", array($this, "video_shortcode_func"));
		
	}
		
	/* Init */
	public function init () {
		
		// Get default sizes
		$wp_embed_defaults = wp_embed_defaults();
		$this->fallback_width = empty($this->options["video_width"]) ? $wp_embed_defaults["width"] : $this->options["video_width"];
		$this->fallback_height = empty($this->options["video_height"]) ? round($this->fallback_width * .5625) : $this->options["video_height"];
		
	}
	
	
	/* BEGIN Media Administration */
		
	/* Add custom attachment fields */
	public function add_attachment_fields ($form_fields, $attachment) {
		
		$type = reset(explode("/", $attachment->post_mime_type));
				
		if ($type == "audio") {
			
			if (! empty($_GET["post_id"])) {
				
				$form_fields["wpnbh_send"] = array(
					"label" => "",
					"input" => "html",
					"html" => get_submit_button(__("Insert Audio Player into Post"), "button", "send[".$attachment->ID."][wpnbh]", false )
				);
				
			}
			
		} else if ($type == "video") {
			
			$form_fields["wpnbh_poster"] = array(
				"label" => __("Poster Image URL"),
				"input" => "text",
				"value" => get_post_meta($attachment->ID, "_wpnbh_poster", true)
			);
			$form_fields["wpnbh_hd"] = array(
				"label" => __("HD Video URL"),
				"input" => "text",
				"value" => get_post_meta($attachment->ID, "_wpnbh_hd", true)
			);
			
			if (! empty($_GET["post_id"])) {
								
				$form_fields["wpnbh_send"] = array(
					"label" => "",
					"input" => "html",
					"html" => get_submit_button(__("Insert Video Player into Post"), "button", "send[".$attachment->ID."][wpnbh]", false )
				);
				
			}
			
		}
		
		return $form_fields;
		
	}
	
	/* Save custom attachment fields */
	public function save_attachment_fields ($post, $attachment) {
		
		$type = reset(explode("/", $post["post_mime_type"]));
		
		if ($type == "video") {
			
			update_post_meta($post["ID"], "_wpnbh_poster", $attachment["wpnbh_poster"]);
			update_post_meta($post["ID"], "_wpnbh_hd", $attachment["wpnbh_hd"]);
			
		}
		
		return $post;
		
	}
	
	/* Insert Shortcode */
	public function send_shortcode_to_editor ($html, $send_id, $attachment) {
				
		if (! is_array($_POST["send"][$send_id]) || empty($_POST["send"][$send_id]["wpnbh"]))
			return $html;
				
		$type = reset(explode("/", get_post_mime_type($send_id)));
		
		if ($type != "audio" && $type != "video")
			return $html;
						
		return "[$type id=$send_id]";
		
	}	
	
	/* END Media Administration */
	
	
	/* BEGIN Shortcodes */
	
	/* Audio shortcode */
	public function audio_shortcode_func ($atts) {
		
		$player_swf = plugins_url("NonverBlaster.swf", __FILE__);
		
		extract(shortcode_atts(array(
			"id" => 0,
			"src" => "",
			"title" => "",
			"autoplay" => false,
			"loop" => false,
			"width" => $this->options["audio_width"]
		), $atts));
				
		$attachment = get_post($id);
		
		if (! empty($id) && ! empty($attachment)) {
			$src = wp_get_attachment_url($attachment->ID);
			$title = apply_filters("the_title", $attachment->post_title);
		} else if (empty($src)) {
			return;
		}
		
		$flashvars = array(
			"mediaurl" => $src,
			"autoplay" => $autoplay,
			"loop" => $loop,
			"controlcolor" => str_replace("#", "0x", $this->options["control_color"]),
			"controlbackcolor" => str_replace("#", "0x", $this->options["control_back_color"]),
			"playerbackcolor" => str_replace("#", "0x", $this->options["control_back_color"]),
			"defaultvolume" => 100,
			"treatasaudio" => true
		);
		
		$output  = "<object type=\"application/x-shockwave-flash\" data=\"$player_swf\" width=\"$width\" height=\"17\" title=\"$title\" class=\"nonverblaster nonverblaster-audio\">";
		$output .= "<param name=\"movie\" value=\"$player_swf\" /><param name=\"menu\" value=\"false\" /><param name=\"wmode\" value=\"transparent\" />";
		$output .= "<param name=\"allowfullscreen\" value=\"false\" /><param name=\"allowscriptaccess\" value=\"always\" />";
		$output .= "<param name=\"flashvars\" value=\"" . $this->array_to_flashvars($flashvars) . "\" />";
		$output .= "<audio src=\"$src\" preload=\"none\" title=\"$title\" style=\"width:" . $width . (substr($this->options["audio_width"], -1) == "%" ? "" : "px") . "\"";
		$output .= ($autoplay ? " autoplay" : "") . ($loop ? " loop" : "") . ">";
		$output .= "<p>To listen to this you'll need the latest <a href=\"http://get.adobe.com/flashplayer\" target=\"_blank\">Adobe Flash Player</a>, or a browser with HTML5 video support.</p>";
		$output .= "</audio></object>";
		
		return $output;
		
	}
	
	/* Video shortcode */
	public function video_shortcode_func ($atts) {
		
		$player_swf = plugins_url("NonverBlaster.swf", __FILE__);
		
		extract(shortcode_atts(array(
			"id" => false,
			"src" => "",
			"title" => "",
			"autoplay" => false,
			"loop" => false,
			"width" => $this->fallback_width,
			"height" => $this->fallback_height
		), $atts));
		
		$attachment = get_post($id);
		
		if (! empty($id) && ! empty($attachment)) {
			$src = wp_get_attachment_url($attachment->ID);
			$title = apply_filters("the_title", $attachment->post_title);
			$poster = get_post_meta($attachment->ID, "_wpnbh_poster", true);
			$hd_src = get_post_meta($attachment->ID, "_wpnbh_hd", true);
		} else if (empty($src)) {
			return;
		}
				
		$flashvars = array(
			"mediaurl" => $src,
			"showtimecode" => true,
			"autoplay" => $autoplay,
			"loop" => $loop,
			"controlcolor" => str_replace("#", "0x", $this->options["control_color"]),
			"controlbackcolor" => str_replace("#", "0x", $this->options["control_back_color"]),
			"playerbackcolor" => "0x000000",
			"crop" => $this->options["video_crop"],
			"defaultvolume" => 100,
			"allowsmoothing" => true
		);
		if (! empty($hd_src)) {
			$flashvars["hdURL"] = $hd_src;
			$flashvars["defaultHD"] = $this->options["video_default_hd"];
		}
		if (! empty($poster)) {
			$flashvars["teaserURL"] = $poster;
		}
		
		$output  = "<object type=\"application/x-shockwave-flash\" data=\"$player_swf\" width=\"$width\" height=\"$height\" title=\"$title\" class=\"nonverblaster nonverblaster-video\">";
		$output .= "<param name=\"movie\" value=\"$player_swf\" /><param name=\"menu\" value=\"false\" /><param name=\"wmode\" value=\"transparent\" />";
		$output .= "<param name=\"allowfullscreen\" value=\"true\" /><param name=\"allowscriptaccess\" value=\"always\" />";
		$output .= "<param name=\"flashvars\" value=\"" . $this->array_to_flashvars($flashvars) . "\" />";
		$output .= "<video src=\"$src\" width=\"$width\" height=\"$height\" title=\"$title\" preload=\"none\" controls";
		$output .= ($autoplay ? " autoplay" : "" ) . ($loop ? " loop" : "") . (! empty($poster) ? " poster=\"$poster\"" : "") . ">";
		$output .= "<p>To watch this you'll need the latest <a href=\"http://get.adobe.com/flashplayer\" target=\"_blank\">Adobe Flash Player</a>, or a browser with HTML5 video support.</p>";
		$output .= "</video></object>";
		
		return $output;
		
	}
	
	/* END Shortcodes */
	
	
	/* BEGIN Plugin Options Page */
	
	public function admin_init () {
		
		if (empty($_POST["wpnbh_nonce"]) || ! wp_verify_nonce($_POST["wpnbh_nonce"], plugin_basename( __FILE__ )) || empty($_POST["wpnbh"])) 
			return;
		
		$posted = $_POST["wpnbh"];
		$options = array();
		
		// Filter Hex Colors
		foreach (array("control_color", "control_back_color") as $name)
			$options[$name] = strtolower(preg_replace('/(^#[a-fA-F0-9]{6})/', '$1', $posted[$name]));
		
		// Filter Sizes 
		foreach (array("audio_width", "video_width", "video_height") as $name)
			$options[$name] = strtolower(preg_replace('/(^[0-9]+%?)/', '$1', $posted[$name]));
		
		// Filter Checkboxes 
		foreach (array("video_crop", "video_default_hd") as $name)
			$options[$name] = ! empty($posted[$name]);
		
		// Revert Empty Options To Default 
		foreach (array("control_color", "control_back_color", "audio_width", "video_width", "video_height") as $name)
			$options[$name] = empty($options[$name]) ? $options[$name] : $this->default_options[$name];
		
		// Update Option
		update_option("wpnbh_options", $options);
		wp_redirect(admin_url("options-general.php?page=wpnbh&settings-updated=true"));
		
	}
	
	public function admin_menu () {
		add_options_page("NonvernBlaster:hover Settings", "NonvernBlaster:hover", "manage_options", "wpnbh", array($this, "plugin_options"));
	}
	
	public function admin_enqueue_scripts ($hook) {
		
		if ($hook != "settings_page_wpnbh")
			return;	
		
		// Enqueue colour picker
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
			<h2><?php _e("NonvernBlaster<span style=\"font-style:italic;font-weight:lighter\">:hover</span> Settings"); ?></h2>
			
			<form action="" method="post">
				<input type="hidden" name="option_page" value="wpnbh">
				<input type="hidden" name="action" value="update">
				<?php wp_nonce_field(plugin_basename( __FILE__ ), "wpnbh_nonce"); ?>
				<h3><?php _e("Player colors"); ?></h3>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e("Control color"); ?></th>
							<td><fieldset><legend class="screen-reader-text"><span><?php _e("Control color"); ?></span></legend>
							<input name="wpnbh[control_color]" type="text" id="wpnbh_control_color" value="<?php echo $this->options["control_color"]; ?>" class="small-text color">
							<div class="colorpicker" id="wpnbh_control_color-farbtastic"></div>
							</fieldset></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Control back colour"); ?></th>
							<td><fieldset><legend class="screen-reader-text"><span><?php _e("Control back colour"); ?></span></legend>
							<input name="wpnbh[control_back_color]" type="text" id="wpnbh_control_back_color" value="<?php echo $this->options["control_back_color"]; ?>" class="small-text color">
							<div class="colorpicker" id="wpnbh_control_back_color-farbtastic"></div>
							</fieldset></td>
						</tr>
					</tbody>
				</table>
				
				<h3><?php _e("Player sizes"); ?></h3>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e("Audio player size"); ?></th>
							<td><fieldset><legend class="screen-reader-text"><span><?php _e("Audio player size"); ?></span></legend>
							<label for="wpnbh_audio_width"><?php _e("Width"); ?></label>
							<input name="wpnbh[audio_width]" type="text" id="wpnbh_audio_width" value="<?php echo $this->options["audio_width"]; ?>" class="small-text">
							</fieldset></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("Video player default size"); ?></th>
							<td><fieldset><legend class="screen-reader-text"><span><?php _e("Video player default size"); ?></span></legend>
							<label for="wpnbh_video_width"><?php _e("Width"); ?></label>
							<input name="wpnbh[video_width]" type="text" id="wpnbh_video_width" value="<?php echo $this->options["video_width"]; ?>" class="small-text">
							<label for="wpnbh_video_height"><?php _e("Height"); ?></label>
							<input name="wpnbh[video_height]" type="text" id="wpnbh_video_height" value="<?php echo $this->options["video_height"]; ?>" class="small-text">
							</fieldset>
							<p class="description"><?php _e("If the width or height value is left blank, the default maximum embed size will be used."); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				
				<h3><?php _e("Player options"); ?></h3>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row" colspan="2" class="th-full"><label for="wpnbh_video_crop"><input name="wpnbh[video_crop]" type="checkbox" id="wpnbh_video_crop" value="true" <?php echo ! empty($this->options["video_crop"]) ? " checked=\"checked\" " : ""; ?>> <?php _e("Crop video to fit player."); ?></label></th>
						</tr>
						<tr valign="top">
							<th scope="row" colspan="2" class="th-full"><label for="wpnbh_video_default_hd"><input name="wpnbh[video_default_hd]" type="checkbox" id="wpnbh_video_default_hd" value="true" <?php echo ! empty($this->options["video_default_hd"]) ? " checked=\"checked\" " : ""; ?>> <?php _e("Enable HD on default, when HD video is available."); ?></label></th>
						</tr>
					</tbody>
				</table>
						
				<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e("Save Changes"); ?>"></p>
			</form>
		</div>
		<script>
		jQuery(function ($) {
			$("input.color").each(function () {
				var input = $(this),
					picker = $(".colorpicker:first", input.parent());
				picker.farbtastic(input).click(function (event) {
					event.stopPropagation();
				});
				input.click(function (event) {
					event.stopPropagation();
					$(".colorpicker").not(picker.show()).hide();
				});
			});
			$("#wpwrap").click(function () {
				$(".colorpicker").hide();
			});
		});
		</script>
		<?php
		
	}
	
	/* END Plugin Options Page */
	
	
	/* BEGIN Private Functions */
	
	private function array_to_flashvars ($old_array) {
		
		$new_array = array();
		
		foreach ($old_array as $key => $value)
			array_push($new_array, $key."=".$this->to_string($value));
		
		return htmlspecialchars(implode("&", $new_array));
		
	}
	
	private function to_string ($from) {
		
		if (is_bool($from))
			return empty($from) ? "false" : "true";
		
		return (string) $from;
		
	}
	
	/* END Plugin Options Page */
		
}

$wpnbh = new WPNonverBlasterHover;