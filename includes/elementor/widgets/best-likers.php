<?php
namespace WpUlikePro\Includes\Elementor\Elements;

use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Utils;
use Elementor\Control_Media;
use Elementor\Group_Control_Border;


if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Elementor 'BestLikers' widget.
 *
 * Elementor widget that displays an 'BestLikers' with lightbox.
 *
 * @since 1.0.0
 */
class BestLikers extends Widget_Base {

    /**
     * Get widget name.
     *
     * Retrieve 'BestLikers' widget name.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'wp_ulike_best_likers';
    }

    /**
     * Get widget title.
     *
     * Retrieve 'BestLikers' widget title.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__('Best Likers', WP_ULIKE_PRO_DOMAIN );
    }

    /**
     * Get widget icon.
     *
     * Retrieve 'BestLikers' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-person';
    }

    /**
     * Get widget categories.
     *
     * Retrieve 'BestLikers' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_categories() {
        return array( WP_ULIKE_PRO_DOMAIN );
    }

    /**
     * Register 'BestLikers' widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function register_controls() {

        /*-----------------------------------------------------------------------------------*/
        /*  Content TAB
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'general_section',
            array(
                'label'      => esc_html__('General', WP_ULIKE_PRO_DOMAIN ),
            )
        );

        $this->add_control(
            'number',
            array(
                'label'       => esc_html__('Number of users', WP_ULIKE_PRO_DOMAIN),
                'label_block' => false,
                'type'        => Controls_Manager::NUMBER,
                'default'     => '8',
                'min'         => 1,
                'step'        => 1
            )
        );

        $this->add_control(
            'peroid_limit',
            array(
                'label'   => esc_html__( 'Peroid Limit', WP_ULIKE_PRO_DOMAIN ),
                'type'    => Controls_Manager::SELECT,
                'options' => array(
					'all'       => esc_html__( 'All', WP_ULIKE_PRO_DOMAIN ),
					'today'     => esc_html__( 'Today', WP_ULIKE_PRO_DOMAIN ),
					'yesterday' => esc_html__( 'Yesterday', WP_ULIKE_PRO_DOMAIN ),
					'week'      => esc_html__( 'Week', WP_ULIKE_PRO_DOMAIN ),
					'month'     => esc_html__( 'Month', WP_ULIKE_PRO_DOMAIN ),
					'year'      => esc_html__( 'Year', WP_ULIKE_PRO_DOMAIN ),
					'past_days' => esc_html__( 'Last X Days', WP_ULIKE_PRO_DOMAIN )
                ),
                'default'   => 'all',
            )
        );

		$this->add_control(
			'past_days_num',
			array(
				'label' => esc_html__( 'Past Days Number', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::NUMBER,
				'default' => 30,
                'condition' => array(
                    'peroid_limit' => 'past_days'
                )
            )
        );

        $this->add_control(
            'display_avatar',
            array(
                'label'        => esc_html__('Display avatar',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->add_control(
            'display_name',
            array(
                'label'        => esc_html__('Display name',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->add_control(
            'display_meta',
            array(
                'label'        => esc_html__('Display meta',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->add_control(
            'display_user_profile',
            array(
                'label'        => esc_html__('Display user profile',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->add_control(
            'profile_url',
            array(
                'label'   => esc_html__( 'Profile URL type', WP_ULIKE_PRO_DOMAIN ),
                'type'    => Controls_Manager::SELECT,
                'options' => array(
					'default'    => esc_html__( 'Default ', WP_ULIKE_PRO_DOMAIN ),
					'author'     => esc_html__( 'Author Page', WP_ULIKE_PRO_DOMAIN ),
					'buddypress' => esc_html__( 'BuddyPress', WP_ULIKE_PRO_DOMAIN ),
					'um'         => esc_html__( 'Ultimate Member', WP_ULIKE_PRO_DOMAIN )
                ),
                'default'   => 'default',
                'condition' => array(
                    'display_user_profile' => 'yes'
                )
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  custom_text_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'custom_text_section',
            array(
                'label'      => esc_html__('Custom Text', WP_ULIKE_PRO_DOMAIN ),
            )
        );

		$this->add_control(
			'not_found_text',
			[
				'label'       => esc_html__( 'Not Found Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'No user found!',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			]
        );

		$this->add_control(
			'ago_text',
			[
				'label'       => esc_html__( 'Ago Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Ago',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			]
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  item_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'item_style_section',
            array(
                'label'     => esc_html__( 'Items', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE
            )
        );

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'item_border',
				'label' => esc_html__( '', WP_ULIKE_PRO_DOMAIN ),
				'selector' => '{{WRAPPER}} .wp-ulike-user',
			]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'      => 'item_shadow',
                'selector'  => '{{WRAPPER}} .wp-ulike-user'
            )
        );

        $this->add_responsive_control(
            'item_padding',
            array(
                'label'      => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-user' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

        $this->add_responsive_control(
            'item_margin',
            array(
                'label'      => esc_html__( 'Margin', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-user' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );


        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  avatar_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'avatar_style_section',
            array(
                'label'     => esc_html__( 'Avatar', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_avatar' => 'yes'
                )
            )
        );

        $this->add_control(
            'avatar_size',
            array(
                'label' => esc_html__( 'Avatar size', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::SLIDER,
                'default' => array(
                    'size' => 128,
                    'unit' => 'px'
                ),
                'range' => array(
                    'px' => array(
                        'max' => 512,
                    )
                )
            )
        );

        $this->add_responsive_control(
            'avatar_radius',
            array(
                'label'      => esc_html__( 'Avatar radius', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-user-avatar .avatar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow:hidden;',
                ),
                'separator' => 'after'
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'      => 'avatar_shadow',
                'selector'  => '{{WRAPPER}} .wp-ulike-user-avatar .avatar'
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  title_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'title_style_section',
            array(
                'label'     => esc_html__( 'Title', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_name' => 'yes'
                )
            )
        );

        $this->start_controls_tabs( 'title_colors' );

        $this->start_controls_tab(
            'title_color_normal',
            array(
                'label' => esc_html__( 'Normal' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-user-name a, {{WRAPPER}} .wp-ulike-user-name' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'title_color_hover',
            array(
                'label' => esc_html__( 'Hover' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'title_hover_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-user-name a:hover, {{WRAPPER}} .wp-ulike-user-name:hover' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'scheme' => '1',
                'selector' => '{{WRAPPER}} .wp-ulike-user-name'
            )
        );

        $this->add_responsive_control(
            'title_margin_bottom',
            array(
                'label' => esc_html__( 'Bottom space', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-user-name' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                )
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  meta_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'meta_style_section',
            array(
                'label'     => esc_html__( 'Meta Info', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_meta' => 'yes'
                )
            )
        );

        $this->start_controls_tabs( 'meta_colors' );

        $this->start_controls_tab(
            'meta_color_normal',
            array(
                'label' => esc_html__( 'Normal' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'meta_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-user-meta a, {{WRAPPER}} .wp-ulike-user-meta' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'meta_color_hover',
            array(
                'label' => esc_html__( 'Hover' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'meta_hover_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-user-meta a:hover' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'meta_typography',
                'scheme' => '1',
                'selector' => '{{WRAPPER}} .wp-ulike-user-meta, {{WRAPPER}} .wp-ulike-user-meta a'
            )
        );

        $this->add_responsive_control(
            'meta_spacing_between',
            array(
                'label' => esc_html__( 'Space between metas', WP_ULIKE_PRO_DOMAIN ),
                'type'  => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 30
                    )
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-user-meta [class^="wp-ulike-user-"]:after' =>
                    'margin-right: {{SIZE}}{{UNIT}}; margin-left: {{SIZE}}{{UNIT}};'
                )
            )
        );

        $this->end_controls_section();

    }

    /**
    * Render recentcomments widget output on the frontend.
    *
    * Written in PHP and used to generate the final HTML.
    *
    * @since 1.0.0
    * @access protected
    */
    protected function render() {

        $settings  = $this->get_settings_for_display();

        // Update peroid limit value
        if( $settings['peroid_limit'] === 'past_days' ){
            $settings['peroid_limit'] = array(
                'interval_value' => $settings['past_days_num'],
                'interval_unit'  => 'DAY'
            );
        }

        $get_users = wp_ulike_get_best_likers_info( $settings['number'], $settings['peroid_limit'] );
        $this->add_render_attribute( 'wrapper', 'class', 'wp-ulike-best-likers-wrapper' );

        if( empty( $get_users ) ){
            $output = sprintf( '<p>%s<p>', $settings['not_found_text'] );
        } else {
            // widget output -----------------------
            ob_start();
            foreach ( $get_users as $user ):
            $user_info   = get_user_by( 'id', $user->user_id );
            $profile_url = '';

            if( empty( $user_info ) ){
                continue;
            }

            switch ($settings['profile_url']) {
                case 'default':
                    $profile_url = wp_ulike_pro_get_user_profile_permalink( $user->user_id );
                    break;

               case 'author':
                    $profile_url = get_author_posts_url( $user->user_id );
                    break;

                case 'um':
                    if( function_exists('um_fetch_user') ){
                        um_fetch_user( $user->user_id );
                        $profile_url = um_user_profile_url();
                    }
                    break;

                case 'buddypress':
                    $profile_url = function_exists('bp_members_get_user_url') ? bp_members_get_user_url( $user->user_id ) : '';
                    break;
            }

            if ( ! empty( $profile_url  ) ) {
                $profile_url = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $profile_url), esc_attr( $user_info->display_name )  );
            } else {
                $profile_url = esc_attr( $user_info->display_name ) ;
            }
        ?>
<div id="wp-ulike-user-<?php echo esc_attr( $user->user_id ); ?>" class="wp-ulike-user">
    <?php
                if ( wp_ulike_is_true( $settings['display_avatar'] ) ) :
                ?>
    <div class="wp-ulike-user-avatar">
        <?php echo get_avatar( $user_info->user_email, $settings['avatar_size']['size'], '' , 'avatar'); ?>
    </div>
    <?php endif; ?>
    <div class="wp-ulike-user-info">
        <div class="wp-ulike-user-name"><?php echo $profile_url; ?></div>
        <div class="wp-ulike-user-meta">
            <span class="wp-ulike-user-registered-date">
                <i aria-hidden="true" class="fas fa-clock"></i>
                <?php echo human_time_diff( strtotime( $user_info->user_registered ) ) . ' ' . $settings['ago_text']; ?>
            </span>
            <span class="wp-ulike-user-vote-counter">
                <i aria-hidden="true" class="fas fa-heart"></i>
                <?php echo $user->SumUser; ?>
            </span>
        </div>
    </div>
</div>
<?php
            endforeach;
            $output = ob_get_clean();
        }

        echo sprintf( '<div %s>%s</div>', $this->get_render_attribute_string( 'wrapper' ), $output );
    }

}