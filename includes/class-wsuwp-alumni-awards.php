<?php

class WSUWP_Alumni_Awards {
	/**
	 * @since 0.0.1
	 *
	 * @var WSUWP_Alumni_Awards
	 */
	private static $instance;

	/**
	 * The slug used to register the awardee post type.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	var $post_type_slug = 'awardee';

	/**
	 * The slug used to register the award taxonomy.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	var $taxonomy_slug = 'award';

	/**
	 * A list of post meta keys associated with awardees.
	 *
	 * @since 0.0.1
	 *
	 * @var array
	 */
	var $post_meta_keys = array(
		'name_first' => array(
			'description' => 'Given name',
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'name_last' => array(
			'description' => 'Surname',
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'class' => array(
			'description' => 'Class of',
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'awarded' => array(
			'description' => 'Year Awarded/Inducted',
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
		'sport' => array(
			'description' => 'Sport(s)',
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
		),
	);

	/**
	 * Maintain and return the one instance. Initiate hooks when
	 * called the first time.
	 *
	 * @since 0.0.1
	 *
	 * @return \WSUWP_Alumni_Awards
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WSUWP_Alumni_Awards();
			self::$instance->setup_hooks();
		}
		return self::$instance;
	}

	/**
	 * Setup hooks to include.
	 *
	 * @since 0.0.1
	 */
	public function setup_hooks() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( "add_meta_boxes_{$this->post_type_slug}", array( $this, 'add_meta_boxes' ) );
		add_action( "save_post_{$this->post_type_slug}", array( $this, 'save_awardee' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10 );
	}

	/**
	 * Register the alumni award post type.
	 *
	 * @since 0.0.1
	 */
	public function register_post_type() {
		$labels = array(
			'name' => 'Awardees',
			'singular_name' => 'Awardee',
			'all_items' => 'All Awardees',
			'add_new_item' => 'Add Awardee',
			'add_new' => 'Add Awardee',
			'new_item' => 'New Awardee',
			'edit_item' => 'Edit Awardee',
			'update_item' => 'Update Awardee',
			'view_item' => 'View Awardee',
			'search_items' => 'Search Awardees',
			'not_found' => 'Not found',
			'not_found_in_trash' => 'Not found in Trash',
			'menu_name' => 'Alumni Awards',
		);

		$args = array(
			'label' => 'Alumni Awards',
			'labels' => $labels,
			'description' => 'WSU Alumni Association award recipient profiles.',
			'public' => true,
			'show_in_admin_bar' => false,
			'show_in_nav_menus' => false,
			'hierarchical' => false,
			'menu_icon' => 'dashicons-awards',
			'menu_position' => 25,
			'supports' => array(
				'title',
				'editor',
			),
			'has_archive' => true,
			'show_in_rest' => true,
			'rest_base' => 'alumni_awards',
		);

		register_post_type( $this->post_type_slug, $args );
	}

	/**
	 * Register the taxonomy that will track the type of award recieved by the awardee.
	 *
	 * @since 0.0.1
	 */
	public function register_taxonomy() {
		$labels = array(
			'name' => 'Awards',
			'singular_name' => 'Award',
			'search_items' => 'Search awards',
			'all_items' => 'All Awards',
			'edit_item' => 'Edit Award',
			'update_item' => 'Update Award',
			'add_new_item' => 'Add New Award',
			'new_item_name' => 'New Award',
			'menu_name' => 'Awards',
		);

		$args = array(
			'labels' => $labels,
			'description' => 'Types of WSU Alumni Association awards.',
			'public' => true,
			'hierarchical' => true,
			'rewrite' => false,
			'show_admin_column' => true,
			'show_in_rest' => true,
		);

		register_taxonomy( $this->taxonomy_slug, array( $this->post_type_slug ), $args );
	}

	/**
	 * Register the meta keys used to store awardee data.
	 *
	 * @since 0.0.1
	 */
	public function register_meta() {
		foreach ( $this->post_meta_keys as $key => $args ) {
			$args['show_in_rest'] = true;
			$args['single'] = true;
			register_meta( 'post', $key, $args );
		}
	}

	/**
	 * Add the meta boxes used to capture information about an awardee.
	 *
	 * @since 0.0.1
	 *
	 * @param string $post_type the current post type.
	 */
	public function add_meta_boxes( $post_type ) {
		add_meta_box(
			'awardee-data',
			'Awardee Information',
			array( $this, 'display_awardee_meta_box' ),
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Capture the main set of data about an awardee.
	 *
	 * @since 0.0.1
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function display_awardee_meta_box( $post ) {
		$data = get_registered_metadata( 'post', $post->ID );

		wp_nonce_field( 'save-awardee-data', '_awardee_data_nonce' );

		?>
		<div class="awardee-fields">
		<?php

		foreach ( $this->post_meta_keys as $key => $meta ) {
			$value = ( isset( $data[ $key ][0] ) ) ? $data[ $key ][0] : '';
			?>
			<p>
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $meta['description'] ); ?>:</label>
				<input type="text" id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			</p>
			<?php
		}

		?>
		</div>
		<?php
	}

	/**
	 * Save additional data associated with an awardee.
	 *
	 * @since 0.0.1
	 *
	 * @param int     $post_id The current post ID.
	 * @param WP_Post $post    The current post object.
	 */
	public function save_awardee( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( ! isset( $_POST['_awardee_data_nonce'] ) || ! wp_verify_nonce( $_POST['_awardee_data_nonce'], 'save-awardee-data' ) ) {
			return;
		}

		$keys = get_registered_meta_keys( 'post' );

		foreach ( $this->post_meta_keys as $key => $meta ) {
			if ( isset( $_POST[ $key ] ) && isset( $keys[ $key ] ) && isset( $keys[ $key ]['sanitize_callback'] ) ) {
				// Each piece of meta is registered with sanitization.
				update_post_meta( $post_id, $key, $_POST[ $key ] );
			}
		}
	}

	/**
	 * Enqueue the styles used in the admin.
	 *
	 * @since 0.0.1
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) && get_current_screen()->id === $this->post_type_slug ) {
			wp_enqueue_style( 'alumni-awards-admin', plugins_url( 'css/admin.css', dirname( __FILE__ ) ) );
		}
	}
}
