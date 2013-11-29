<?php
/**
 * Plugin Name: IM8 Additional CSS
 * Description: Add an additional CSS file and/or CSS styles for each page or (custom) post.
 * Plugin URI: http://intermedi8.de
 * Version: 2.0
 * Author: intermedi8
 * Author URI: http://intermedi8.de
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: im8-additional-css
 * Domain Path: /languages
 */


if (IM8AdditionalCSS::has_to_be_loaded())
	add_action('wp_loaded', array(IM8AdditionalCSS::get_instance(), 'init'));




/**
 * Main (and only) class.
 */
class IM8AdditionalCSS {

	/**
	 * Plugin instance.
	 *
	 * @type	object
	 */
	protected static $instance = null;


	/**
	 * Plugin version.
	 *
	 * @type	string
	 */
	protected $version = '2.0';


	/**
	 * basename() of global $pagenow.
	 *
	 * @type	string
	 */
	protected static $page_base;


	/**
	 * Plugin textdomain.
	 *
	 * @type	string
	 */
	protected $textdomain = 'im8-additional-css';


	/**
	 * Plugin nonce.
	 *
	 * @type	string
	 */
	protected $nonce = 'im8_additional_css_nonce';


	/**
	 * Plugin option name.
	 *
	 * @type	string
	 */
	protected $option_name = 'im8_additional_css';


	/**
	 * Constructs class.
	 *
	 * @hook	wp_loaded
	 * @return	void
	 */
	public function __construct() {
		register_activation_hook(__FILE__, array(__CLASS__, 'activation'));
		register_uninstall_hook(__FILE__, array(__CLASS__, 'uninstall'));
	} // function __construct


	/**
	 * Get plugin instance.
	 *
	 * @hook	wp_loaded
	 * @return	object IM8AdditionalCSS
	 */
	public static function get_instance() {
		if (null === self::$instance)
			self::$instance = new self;

		return self::$instance;
	} // function get_instance


	/**
	 * Performs update actions.
	 *
	 * @hook	activation
	 * @return	void
	 */
	public static function activation() {
		self::get_instance()->autoupdate();
	} // function activation


	/**
	 * Checks if the plugin has to be loaded.
	 *
	 * @return	boolean
	 */
	public static function has_to_be_loaded() {
		global $pagenow;

		if (empty($pagenow))
			return false;

		self::$page_base = basename($pagenow, '.php');
		$admin_pages = array(
			'post',
			'post-new',
			'plugins',
		);

		return ! is_admin() || in_array(self::$page_base, $admin_pages);
	} // function has_to_be_loaded


	/**
	 * Registers plugin actions.
	 *
	 * @hook	wp_loaded
	 * @return	void
	 */
	public function init() {
		if (is_admin()) {
			$pages = array(
				'post',
				'post-new',
			);
			if (in_array(self::$page_base, $pages)) {
				add_action('add_meta_boxes', array($this, 'add_meta_box'));
				add_action('save_post', array($this, 'save'));
			}
		} else {
			add_action('wp_enqueue_scripts', array($this, 'enqueue_additional_css_file'));
			add_action('wp_print_styles', array($this, 'print_additional_css'));
		}
	} // function init


	/**
	 * Wrapper for get_option().
	 *
	 * @param	string $key Option name.
	 * @param	mixed $default Return value for missing key.
	 * @return	mixed|$default Option value.
	 */
	protected function get_option($key = null, $default = false) {
		static $option = null;
		if (null === $option) {
			$option = get_option($this->option_name, false);
			if (false === $option)
				$option = array(
					'version' => 0,
				);
		}

		if (null === $key)
			return $option;

		if (! isset($option[$key]))
			return $default;

		return $option[$key];
	} // function get_option


	/**
	 * Checks for and performs necessary updates.
	 *
	 * @see		activation()
	 * @return	void
	 */
	protected function autoupdate() {
		$options = $this->get_option();
		$version = $this->get_option('version', 0);

		if (version_compare($version, '2.0', '<')) {
			global $wpdb;

			$update_successful = true;

			$sql = "
				UPDATE $wpdb->postmeta
				SET meta_key='im8_additional_css_file'
				WHERE meta_key='additional_css_file'";
			$update_successful &= $wpdb->query($wpdb->prepare($sql)) != -1;

			$sql = "
				UPDATE $wpdb->postmeta
				SET meta_key='im8_additional_css'
				WHERE meta_key='additional_css'";
			$update_successful &= $wpdb->query($wpdb->prepare($sql)) != -1;

			$options['version'] = '2.0';
			if ($update_successful)
				update_option($this->option_name, $options);
		}
	} // function autoupdate


	/**
	 * Adds plugin meta box for all public post type posts.
	 *
	 * @hook	add_meta_boxes
	 * @return	void
	 */
	public function add_meta_box() {
		if (count($post_types = $this->get_post_types())) {
			$this->load_textdomain();
			foreach ($post_types as $post_type)
				add_meta_box('im8_additional_css_box', __("Additional CSS", 'im8-additional-css'), array($this, 'print_meta_box'), $post_type, 'normal', 'high');
		}
	} // function add_meta_box


	/**
	 * Callback function for plugin meta box.
	 *
	 * @see		add_meta_box()
	 * @param	object $post Post object of currently displayed post.
	 * @return	void
	 */
	public function print_meta_box($post) {
		wp_nonce_field(basename(__FILE__), $this->nonce);
		?>
		<p>
			<?php _e("Here you can select an additional CSS file or define additional CSS styles which should be loaded for this post or page.<br/><b>In order to show up here, the CSS files have to be located in the \"/css\" sub-folder of your theme.</b>", 'im8-additional-css'); ?>
		</p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="im8_additional_css_file">
							<?php _e("Additional CSS file", 'im8-additional-css'); ?>
						</label>
					</th>
					<td>
						<select id="im8_additional_css_file" name="im8_additional_css_file">
							<option value="-1">
								<?php _e("None", 'im8-additional-css'); ?>
							</option>
							<?php
							if (file_exists($path = get_stylesheet_directory().'/css/')) {
								$additional_css_file = get_post_meta($post->ID, 'im8_additional_css_file', true);
								foreach (glob($path.'*.css') as $file) {
									$file = substr($file, strlen($path));
									?>
									<option value="<?php echo $file; ?>" <?php selected($file, $additional_css_file); ?>>
										<?php echo $file; ?>
									</option>
									<?php
								}
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="im8_additional_css">
							<?php _e("Additional CSS styles", 'im8-additional-css'); ?>
						</label>
					</th>
					<td>
						<textarea id="im8_additional_css" name="im8_additional_css" class="widefat" rows="10"><?php echo get_post_meta($post->ID, 'im8_additional_css', true); ?></textarea>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
		$this->unload_textdomain();
	} // function print_meta_box


	/**
	 * Saves meta data.
	 *
	 * @hook	save_post
	 * @param	int $id ID of the saved post.
	 * @return	int Post ID, in case no meta data was saved.
	 */
	public function save($id) {
		if (
			! isset($_POST[$this->nonce])
			|| ! wp_verify_nonce($_POST[$this->nonce], basename(__FILE__))
		) return $id;

		if (defined('DOING_AUTOSAVE')&& DOING_AUTOSAVE)
			return $id;

		$meta_key = 'im8_additional_css';
		$meta_value = $_POST[$meta_key];
		if ('' != $meta_value) update_post_meta($id, $meta_key, $meta_value);
		else delete_post_meta($id, $meta_key);

		$meta_key = 'im8_additional_css_file';
		$meta_value = $_POST[$meta_key];
		if (-1 != $meta_value) update_post_meta($id, $meta_key, $meta_value);
		else delete_post_meta($id, $meta_key);
	} // function save


	/**
	 * Gets relevant post types.
	 *
	 * @see		add_meta_box()
	 * @return	array Post types.
	 */
	protected function get_post_types() {
		$args = array(
			'public' => true,
		);
		if (! is_array($types = get_post_types($args)))
			return array();

		unset($types['attachment']);
		return $types;
	} // function get_post_types


	/**
	 * Loads plugin textdomain.
	 *
	 * @return	void
	 */
	protected function load_textdomain() {
		load_plugin_textdomain($this->textdomain, false, plugin_basename(dirname(__FILE__)).'/languages');
	} // function load_textdomain


	/**
	 * Remove translations from memory.
	 *
	 * @return	void
	 */
	protected function unload_textdomain() {
		unset($GLOBALS['l10n'][$this->textdomain]);
	} // function unload_textdomain


	/**
	 * Enqueues the additional CSS file for the current post.
	 *
	 * @hook	wp_enqueue_scripts
	 * @return	void
	 */
	public function enqueue_additional_css_file() {
		if (is_page() || is_single()) {
			while (have_posts()) {
				the_post();
				$additional_css_file = get_post_meta(get_the_ID(), 'im8_additional_css_file', true);
				$path = get_stylesheet_directory().'/css/'.$additional_css_file;
				$url = get_stylesheet_directory_uri().'/css/'.$additional_css_file;
				if ($additional_css_file && file_exists($path))
					wp_enqueue_style('im8_additional_css_file', $url, array(), filemtime($path), 'screen, projection');
			}
			rewind_posts();
		}
	} // function enqueue_additional_css_file


	/**
	 * Prints the additional CSS style fo the current post.
	 *
	 * @hook	wp_print_styles
	 * @return	void
	 */
	public function print_additional_css() {
		if (is_page() || is_single()) {
			while (have_posts()) {
				the_post();
				if ($additional_css = get_post_meta(get_the_ID(), 'im8_additional_css', true)) {
					?>
					<style type="text/css">
						<?php echo $additional_css; ?>
					</style>
					<?php
				}
			}
			rewind_posts();
		}
	} // function print_additional_css


	/**
	 * Deletes plugin data on uninstall.
	 *
	 * @hook	uninstall
	 * @return	void
	 */
	public static function uninstall() {
		global $wpdb;

		$sql = "
			DELETE
			FROM $wpdb->postmeta
			WHERE meta_key='im8_additional_css_file' || meta_key='im8_additional_css'";
		$wpdb->query($wpdb->prepare($sql));

		delete_option(self::get_instance()->option_name);
	} // function uninstall

} // class IM8AdditionalCSS
?>