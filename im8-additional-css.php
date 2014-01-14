<?php
/**
 * Plugin Name: IM8 Additional CSS
 * Plugin URI: http://wordpress.org/plugins/im8-additional-css/
 * Description: Add an additional CSS file and/or CSS styles for each page or (custom) post.
 * Version: 2.5
 * Author: intermedi8
 * Author URI: http://intermedi8.de
 * License: MIT
 * License URI: http://opensource.org/licenses/MIT
 * Text Domain: im8-additional-css
 * Domain Path: /languages
 */


// Exit on direct access
if (! defined('ABSPATH'))
	exit;


if (! class_exists('IM8AdditionalCSS')) :


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
	protected $version = '2.5';


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
	 * Plugin repository.
	 *
	 * @type	string
	 */
	protected $repository = 'im8-additional-css';


	/**
	 * Constructor. Register activation routine.
	 *
	 * @see		get_instance()
	 * @return	void
	 */
	public function __construct() {
		register_activation_hook(__FILE__, array(__CLASS__, 'activation'));
	} // function __construct


	/**
	 * Get plugin instance.
	 *
	 * @hook	plugins_loaded
	 * @return	object IM8AdditionalCSS
	 */
	public static function get_instance() {
		if (null === self::$instance)
			self::$instance = new self;

		return self::$instance;
	} // function get_instance


	/**
	 * Register uninstall routine.
	 *
	 * @hook	activation
	 * @return	void
	 */
	public static function activation() {
		register_uninstall_hook(__FILE__, array(__CLASS__, 'uninstall'));
	} // function activation


	/**
	 * Check if the plugin has to be initialized.
	 *
	 * @hook	plugins_loaded
	 * @return	boolean
	 */
	public static function init_on_demand() {
		global $pagenow;

		if (empty($pagenow))
			return;

		self::$page_base = basename($pagenow, '.php');
		$admin_pages = array(
			'post',
			'post-new',
			'plugins',
		);

		if (is_admin() && ! in_array(self::$page_base, $admin_pages))
			return;

		add_action('wp_loaded', array(self::$instance, 'init'));
	} // function init_on_demand


	/**
	 * Register plugin actions.
	 *
	 * @hook	wp_loaded
	 * @return	void
	 */
	public function init() {
		if (is_admin()) {
			add_action('admin_init', array($this, 'autoupdate'));

			$pages = array(
				'post',
				'post-new',
			);
			if (in_array(self::$page_base, $pages)) {
				add_action('add_meta_boxes', array($this, 'add_meta_box'));
				add_action('save_post', array($this, 'save_meta_data'));
			}

			if ('plugins' === self::$page_base)
				add_action('in_plugin_update_message-'.basename(dirname(__FILE__)).'/'.basename(__FILE__), array($this, 'update_message'), 10, 2);
		} else {
			add_action('wp_enqueue_scripts', array($this, 'enqueue_additional_css_file'));
			add_action('wp_print_styles', array($this, 'print_additional_css'));
		}
	} // function init


	/**
	 * Check for and perform necessary updates.
	 *
	 * @hook	admin_init
	 * @return	void
	 */
	public function autoupdate() {
		$options = $this->get_option();
		$update_successful = true;

		if (version_compare($options['version'], '2.0', '<')) {
			global $wpdb;

			$sql = "
				UPDATE $wpdb->postmeta
				SET meta_key='im8_additional_css_file'
				WHERE meta_key='additional_css_file'";
			$update_successful &= $wpdb->query($sql) != -1;

			$sql = "
				UPDATE $wpdb->postmeta
				SET meta_key='im8_additional_css'
				WHERE meta_key='additional_css'";
			$update_successful &= $wpdb->query($sql) != -1;

			$options['version'] = '2.0';
			if ($update_successful)
				update_option($this->option_name, $options);
		}

		if ($update_successful) {
			$options['version'] = $this->version;
			update_option($this->option_name, $options);
		}
	} // function autoupdate


	/**
	 * Wrapper for get_option().
	 *
	 * @param	string $key Option name.
	 * @param	mixed $default Default return value for missing key.
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
	 * Add plugin meta box for all public post type posts.
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
	 * Save meta data.
	 *
	 * @hook	save_post
	 * @param	int $id ID of the saved post.
	 * @return	int Post ID, in case no meta data was saved.
	 */
	public function save_meta_data($id) {
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
	} // function save_meta_data


	/**
	 * Print update message based on current plugin version's readme file.
	 *
	 * @hook	in_plugin_update_message-{$file}
	 * @param	array $plugin_data Plugin metadata.
	 * @param	array $r Metadata about the available plugin update.
	 * @return	void
	 */
	public function update_message($plugin_data, $r) {
		if ($plugin_data['update']) {
			$readme = wp_remote_fopen('http://plugins.svn.wordpress.org/'.$this->repository.'/trunk/readme.txt');
			if (! $readme)
				return;

			$pattern = '/==\s*Changelog\s*==(.*)=\s*'.preg_quote($this->version).'\s*=/s';
			if (
				false === preg_match($pattern, $readme, $matches)
				|| ! isset($matches[1])
			)
				return;

			$changelog = (array) preg_split('/[\r\n]+/', trim($matches[1]));
			if (empty($changelog))
				return;

			$output = '<div style="margin: 8px 0 0 26px;">';
			$output .= '<ul style="margin-left: 14px; line-height: 1.5; list-style: disc outside none;">';

			$item_pattern = '/^\s*\*\s*/';
			foreach ($changelog as $line)
				if (preg_match($item_pattern, $line))
					$output .= '<li>'.preg_replace('/`([^`]*)`/', '<code>$1</code>', htmlspecialchars(preg_replace($item_pattern, '', trim($line)))).'</li>';

			$output .= '</ul>';
			$output .= '</div>';

			echo $output;
		}
	} // function update_message


	/**
	 * Get relevant post types.
	 *
	 * @see		add_meta_box()
	 * @return	array Post types.
	 */
	protected function get_post_types() {
		$args = array(
			'show_ui' => true,
		);
		if (! is_array($types = get_post_types($args)))
			return array();

		unset($types['attachment']);
		return $types;
	} // function get_post_types


	/**
	 * Load plugin textdomain.
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
	 * Enqueue the additional CSS file for the current post.
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
	 * Print the additional CSS style fo the current post.
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
	 * Delete plugin data on uninstall.
	 *
	 * @hook	uninstall
	 * @return	void
	 */
	public static function uninstall() {
		global $wpdb;

		$sql = "
			DELETE
			FROM $wpdb->postmeta
			WHERE meta_key LIKE 'im8_additional_css%'";
		$wpdb->query($sql);

		delete_option(self::get_instance()->option_name);
	} // function uninstall

} // class IM8AdditionalCSS


add_action('plugins_loaded', array(IM8AdditionalCSS::get_instance(), 'init_on_demand'));


endif; // if (! class_exists('IM8AdditionalCSS'))