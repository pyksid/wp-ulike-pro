<?php
namespace WpUlikePro\Includes\Elementor\Modules\QueryControl\Controls;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Base;
use WpUlikePro\Includes\Elementor\Modules\QueryControl\Module as Query_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Group_Control_Query extends Group_Control_Base {

	protected static $presets;
	protected static $fields;

	public static function get_type() {
		return 'wp-ulike-query-group';
	}

	protected function init_args( $args ) {
		parent::init_args( $args );
		$args = $this->get_args();
		static::$fields = $this->init_fields_by_name( $args['name'] );
	}

	protected function init_fields() {
		$args = $this->get_args();

		return $this->init_fields_by_name( $args['name'] );
	}

	/**
	 * Build the group-controls array
	 * Note: this method completely overrides any settings done in Group_Control_Posts
	 * @param string $name
	 *
	 * @return array
	 */
	protected function init_fields_by_name( $name ) {
		$fields = [];

		$name .= '_';

		$fields['post_type'] = [
			'label' => esc_html__( 'Source', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::SELECT,
			'options' => [
				'by_id' => esc_html__( 'Manual Selection', WP_ULIKE_PRO_DOMAIN ),
				'current_query' => esc_html__( 'Current Query', WP_ULIKE_PRO_DOMAIN ),
			],
		];

		$fields['query_args'] = [
			'type' => Controls_Manager::TABS,
		];

		$tabs_wrapper = $name . 'query_args';
		$include_wrapper = $name . 'query_include';
		$exclude_wrapper = $name . 'query_exclude';

		$fields['query_include'] = [
			'type' => Controls_Manager::TAB,
			'label' => esc_html__( 'Include', WP_ULIKE_PRO_DOMAIN ),
			'tabs_wrapper' => $tabs_wrapper,
			'condition' => [
				'post_type!' => [
					'current_query',
					'by_id',
				],
			],
		];

		$fields['posts_ids'] = [
			'label' => esc_html__( 'Search & Select', WP_ULIKE_PRO_DOMAIN ),
			'type' => Query_Module::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'multiple' => true,
			'autocomplete' => [
				'object' => Query_Module::QUERY_OBJECT_POST,
			],
			'condition' => [
				'post_type' => 'by_id',
			],
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $include_wrapper,
			'export' => false,
		];

		$fields['include'] = [
			'label' => esc_html__( 'Include By', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::SELECT2,
			'multiple' => true,
			'options' => [
				'terms' => esc_html__( 'Term', WP_ULIKE_PRO_DOMAIN ),
				'authors' => esc_html__( 'Author', WP_ULIKE_PRO_DOMAIN ),
			],
			'condition' => [
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'label_block' => true,
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $include_wrapper,
		];

		$fields['include_term_ids'] = [
			'label' => esc_html__( 'Term', WP_ULIKE_PRO_DOMAIN ),
			'description' => esc_html__( 'Terms are items in a taxonomy. The available taxonomies are: Categories, Tags, Formats and custom taxonomies.', WP_ULIKE_PRO_DOMAIN ),
			'type' => Query_Module::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'multiple' => true,
			'autocomplete' => [
				'object' => Query_Module::QUERY_OBJECT_CPT_TAX,
				'display' => 'detailed',
			],
			'group_prefix' => $name,
			'condition' => [
				'include' => 'terms',
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $include_wrapper,
		];

		$fields['include_authors'] = [
			'label' => esc_html__( 'Author', WP_ULIKE_PRO_DOMAIN ),
			'label_block' => true,
			'type' => Query_Module::QUERY_CONTROL_ID,
			'multiple' => true,
			'default' => [],
			'options' => [],
			'autocomplete' => [
				'object' => Query_Module::QUERY_OBJECT_AUTHOR,
			],
			'condition' => [
				'include' => 'authors',
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $include_wrapper,
			'export' => false,
		];

		$fields['query_exclude'] = [
			'type' => Controls_Manager::TAB,
			'label' => esc_html__( 'Exclude', WP_ULIKE_PRO_DOMAIN ),
			'tabs_wrapper' => $tabs_wrapper,
			'condition' => [
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
		];

		$fields['exclude'] = [
			'label' => esc_html__( 'Exclude By', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::SELECT2,
			'multiple' => true,
			'options' => [
				'current_post' => esc_html__( 'Current Post', WP_ULIKE_PRO_DOMAIN ),
				'manual_selection' => esc_html__( 'Manual Selection', WP_ULIKE_PRO_DOMAIN ),
				'terms' => esc_html__( 'Term', WP_ULIKE_PRO_DOMAIN ),
				'authors' => esc_html__( 'Author', WP_ULIKE_PRO_DOMAIN ),
			],
			'condition' => [
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'label_block' => true,
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $exclude_wrapper,
		];

		$fields['exclude_ids'] = [
			'label' => esc_html__( 'Search & Select', WP_ULIKE_PRO_DOMAIN ),
			'type' => Query_Module::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'multiple' => true,
			'autocomplete' => [
				'object' => Query_Module::QUERY_OBJECT_POST,
			],
			'condition' => [
				'exclude' => 'manual_selection',
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $exclude_wrapper,
			'export' => false,
		];

		$fields['exclude_term_ids'] = [
			'label' => esc_html__( 'Term', WP_ULIKE_PRO_DOMAIN ),
			'type' => Query_Module::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'multiple' => true,
			'autocomplete' => [
				'object' => Query_Module::QUERY_OBJECT_CPT_TAX,
				'display' => 'detailed',
			],
			'group_prefix' => $name,
			'condition' => [
				'exclude' => 'terms',
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $exclude_wrapper,
			'export' => false,
		];

		$fields['exclude_authors'] = [
			'label' => esc_html__( 'Author', WP_ULIKE_PRO_DOMAIN ),
			'type' => Query_Module::QUERY_CONTROL_ID,
			'options' => [],
			'label_block' => true,
			'multiple' => true,
			'autocomplete' => [
				'object' => Query_Module::QUERY_OBJECT_AUTHOR,
				'display' => 'detailed',
			],
			'condition' => [
				'exclude' => 'authors',
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $exclude_wrapper,
			'export' => false,
		];

		$fields['avoid_duplicates'] = [
			'label' => esc_html__( 'Avoid Duplicates', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::SWITCHER,
			'default' => '',
			'description' => esc_html__( 'Set to Yes to avoid duplicate posts from showing up. This only effects the frontend.', WP_ULIKE_PRO_DOMAIN ),
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $exclude_wrapper,
			'condition' => [
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
		];

		$fields['offset'] = [
			'label' => esc_html__( 'Offset', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::NUMBER,
			'default' => 0,
			'condition' => [
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'description' => esc_html__( 'Use this setting to skip over posts (e.g. \'2\' to skip over 2 posts).', WP_ULIKE_PRO_DOMAIN ),
			'tabs_wrapper' => $tabs_wrapper,
			'inner_tab' => $exclude_wrapper,
		];

		$fields['select_date'] = [
			'label' => esc_html__( 'Date', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::SELECT,
			'post_type' => '',
			'options' => [
				'anytime' => esc_html__( 'All', WP_ULIKE_PRO_DOMAIN ),
				'today' => esc_html__( 'Past Day', WP_ULIKE_PRO_DOMAIN ),
				'week' => esc_html__( 'Past Week', WP_ULIKE_PRO_DOMAIN ),
				'month'  => esc_html__( 'Past Month', WP_ULIKE_PRO_DOMAIN ),
				'quarter' => esc_html__( 'Past Quarter', WP_ULIKE_PRO_DOMAIN ),
				'year' => esc_html__( 'Past Year', WP_ULIKE_PRO_DOMAIN ),
				'exact' => esc_html__( 'Custom', WP_ULIKE_PRO_DOMAIN ),
			],
			'default' => 'anytime',
			'multiple' => false,
			'condition' => [
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'separator' => 'before',
		];

		$fields['date_before'] = [
			'label' => esc_html__( 'Before', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::DATE_TIME,
			'post_type' => '',
			'label_block' => false,
			'multiple' => false,
			'placeholder' => esc_html__( 'Choose', WP_ULIKE_PRO_DOMAIN ),
			'condition' => [
				'select_date' => 'exact',
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'description' => esc_html__( 'Setting a ‘Before’ date will show all the posts published until the chosen date (inclusive).', WP_ULIKE_PRO_DOMAIN ),
		];

		$fields['date_after'] = [
			'label' => esc_html__( 'After', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::DATE_TIME,
			'post_type' => '',
			'label_block' => false,
			'multiple' => false,
			'placeholder' => esc_html__( 'Choose', WP_ULIKE_PRO_DOMAIN ),
			'condition' => [
				'select_date' => 'exact',
				'post_type!' => [
					'by_id',
					'current_query',
				],
			],
			'description' => esc_html__( 'Setting an ‘After’ date will show all the posts published since the chosen date (inclusive).', WP_ULIKE_PRO_DOMAIN ),
		];

		$fields['orderby'] = [
			'label' => esc_html__( 'Order By', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::SELECT,
			'default' => 'post_date',
			'options' => [
				'post_date' => esc_html__( 'Date', WP_ULIKE_PRO_DOMAIN ),
				'post_title' => esc_html__( 'Title', WP_ULIKE_PRO_DOMAIN ),
				'menu_order' => esc_html__( 'Menu Order', WP_ULIKE_PRO_DOMAIN ),
				'rand' => esc_html__( 'Random', WP_ULIKE_PRO_DOMAIN ),
			],
			'condition' => [
				'post_type!' => 'current_query',
			],
		];

		$fields['order'] = [
			'label' => esc_html__( 'Order', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::SELECT,
			'default' => 'desc',
			'options' => [
				'asc' => esc_html__( 'ASC', WP_ULIKE_PRO_DOMAIN ),
				'desc' => esc_html__( 'DESC', WP_ULIKE_PRO_DOMAIN ),
			],
			'condition' => [
				'post_type!' => 'current_query',
			],
		];

		$fields['posts_per_page'] = [
			'label' => esc_html__( 'Posts Per Page', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::NUMBER,
			'default' => 3,
			'condition' => [
				'post_type!' => 'current_query',
			],
		];

		$fields['ignore_sticky_posts'] = [
			'label' => esc_html__( 'Ignore Sticky Posts', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::SWITCHER,
			'default' => 'yes',
			'condition' => [
				'post_type' => 'post',
			],
			'description' => esc_html__( 'Sticky-posts ordering is visible on frontend only', WP_ULIKE_PRO_DOMAIN ),
		];

		$fields['query_id'] = [
			'label' => esc_html__( 'Query ID', WP_ULIKE_PRO_DOMAIN ),
			'type' => Controls_Manager::TEXT,
			'default' => '',
			'description' => esc_html__( 'Give your Query a custom unique id to allow server side filtering', WP_ULIKE_PRO_DOMAIN ),
			'separator' => 'before',
			'dynamic' => [
				'active' => true,
			]
		];

		static::init_presets();

		return $fields;
	}

	/**
	 * Presets: filter controls subsets to be be used by the specific Group_Control_Query instance.
	 *
	 * Possible values:
	 * 'full' : (default) all presets
	 * 'include' : the 'include' tab - by id, by taxonomy, by author
	 * 'exclude': the 'exclude' tab - by id, by taxonomy, by author
	 * 'advanced_exclude': extend the 'exclude' preset with 'avoid-duplicates' & 'offset'
	 * 'date': date query controls
	 * 'pagination': posts per-page
	 * 'order': sort & ordering controls
	 * 'query_id': allow saving a specific query for future usage.
	 *
	 * Usage:
	 * full: build a Group_Controls_Query with all possible controls,
	 * when 'full' is passed, the Group_Controls_Query will ignore all other preset values.
	 * $this->add_group_control(
	 * Group_Control_Query::get_type(),
	 * [
	 * ...
	 * 'presets' => [ 'full' ],
	 *  ...
	 *  ] );
	 *
	 * Subset: build a Group_Controls_Query with subset of the controls,
	 * in the following example, the Query controls will set only the 'include' & 'date' query args.
	 * $this->add_group_control(
	 * Group_Control_Query::get_type(),
	 * [
	 * ...
	 * 'presets' => [ 'include', 'date' ],
	 *  ...
	 *  ] );
	 */
	protected static function init_presets() {

		$tabs = [
			'query_args',
			'query_include',
			'query_exclude',
		];

		static::$presets['include'] = array_merge( $tabs, [
			'include',
			'include_ids',
			'include_term_ids',
			'include_authors',
		] );

		static::$presets['exclude'] = array_merge( $tabs, [
			'exclude',
			'exclude_ids',
			'exclude_term_ids',
			'exclude_authors',
		] );

		static::$presets['advanced_exclude'] = array_merge( static::$presets['exclude'], [
			'avoid_duplicates',
			'offset',
		] );

		static::$presets['date'] = [
			'select_date',
			'date_before',
			'date_after',
		];

		static::$presets['pagination'] = [
			'posts_per_page',
			'ignore_sticky_posts',
		];

		static::$presets['order'] = [
			'orderby',
			'order',
		];

		static::$presets['query_id'] = [
			'query_id',
		];
	}

	private function filter_by_presets( $presets, $fields ) {

		if ( in_array( 'full', $presets, true ) ) {
			return $fields;
		}

		$control_ids = [];
		foreach ( static::$presets as $key => $preset ) {
			$control_ids = array_merge( $control_ids, $preset );
		}

		foreach ( $presets as $preset ) {
			if ( array_key_exists( $preset, static::$presets ) ) {
				$control_ids = array_diff( $control_ids, static::$presets[ $preset ] );
			}
		}

		foreach ( $control_ids as $remove ) {
			unset( $fields[ $remove ] );
		}

		return $fields;

	}

	protected function prepare_fields( $fields ) {

		$args = $this->get_args();

		if ( ! empty( $args['presets'] ) ) {
			$fields = $this->filter_by_presets( $args['presets'], $fields );
		}

		$post_type_args = [];
		if ( ! empty( $args['post_type'] ) ) {
			$post_type_args['post_type'] = $args['post_type'];
		}

		$post_types = wp_ulike_pro_get_public_post_types( $post_type_args );

		$fields['post_type']['options'] = array_merge( $post_types, $fields['post_type']['options'] );
		$fields['post_type']['default'] = key( $post_types );
		$fields['posts_ids']['object_type'] = array_keys( $post_types );

		//skip parent, go directly to grandparent
		return Group_Control_Base::prepare_fields( $fields );
	}

	protected function get_child_default_args() {
		$args = parent::get_child_default_args();
		$args['presets'] = [ 'full' ];

		return $args;
	}

	protected function get_default_options() {
		return [
			'popover' => false,
		];
	}
}
