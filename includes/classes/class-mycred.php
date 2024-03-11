<?php
/**
 * myCRED
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( class_exists( 'myCRED_Hook' ) ) :

	class WP_Ulike_Pro_myCRED extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = 'mycred_default' ) {
			parent::__construct( array(
				'id'       => 'wp_ulike_pro',
				'defaults' => array(
					'add_dislike'    => array(
						'creds'  => 1,
						'log'    => '%plural% deduction for disliking a content',
						'limit'  => '0/x'
					),
					'get_dislike'  => array(
						'creds'  => -1,
						'log'    => '%plural% deduction for getting disliked from a content',
						'limit'  => '0/x'
					),
					'add_undislike'  => array(
						'creds'  => -1,
						'log'    => '%plural% for undisliking a content',
						'limit'  => '0/x'
					),
					'get_undislike'  => array(
						'creds'  => 1,
						'log'    => '%plural% for getting undisliked from a content',
						'limit'  => '0/x'
					),
					'limits'   => array(
						'self_reply' => 0
					),
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run Actions
		 *
		 * @since		2.3
		 */
		public function run() {
			// Goto status function
			add_action( 'wp_ulike_after_process', array( $this, 'status' )	, 10, 4 );
		}

		/**
		 * Start functions by status
		 *
		 * @since		2.3
		 */
		public function status( $id , $key, $user_id, $status ) {
			$author_id = $this->get_author_ID( $id, $key );
			// Check status
			if( ! in_array( $status, array( 'dislike', 'undislike' ) ) ){
				return;
			}
			// Call function by user status
			call_user_func( array( $this, $status ), $id , $key, $user_id, $author_id );
		}

		/**
		 * Add Like
		 *
		 * @since		2.3
		 */
		public function dislike( $id , $key, $user_id, $author_id = 0 ) {
			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) || ! is_user_logged_in() ) return;

			if ( $user_id != $author_id || $this->prefs['limits']['self_reply'] ){

				// Award the user liking
				if ( $this->prefs['add_dislike']['creds'] ) {
					// If not over limit
					if ( ! $this->over_hook_limit( 'add_dislike', 'wp_add_dislike', $user_id ) ) {
						// Make sure this is unique event
						if ( ! $this->core->has_entry( 'wp_add_dislike', $id, $user_id ) ) {
							// Execute
							$this->core->add_creds(
								'wp_add_dislike',
								$user_id,
								$this->prefs['add_dislike']['creds'],
								$this->prefs['add_dislike']['log'],
								$id,
								array( 'ref_type' => $key ),
								$this->mycred_type
							);
						}
					}
				}

				// Award post author for being liked
				if ( $this->prefs['get_dislike']['creds'] && $author_id ) {
					// If not over limit
					if ( ! $this->over_hook_limit( 'get_dislike', 'wp_get_dislike', $author_id ) ) {
						// Make sure this is unique event
						if ( ! $this->core->has_entry( 'wp_get_dislike', $id, $author_id, array( 'ref_type' => $key, 'by' => $user_id ) ) ) {
							// Execute
							$this->core->add_creds(
								'wp_get_dislike',
								$author_id,
								$this->prefs['get_dislike']['creds'],
								$this->prefs['get_dislike']['log'],
								$id,
								array( 'ref_type' => $key, 'by' => $user_id ),
								$this->mycred_type
							);
						}
					}
				}

			}

		}

		/**
		 * Remove Like
		 *
		 * @since		2.3
		 */
		public function undislike( $id , $key, $user_id, $author_id = 0 ) {

			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) || ! is_user_logged_in() ) return;

			if ( $user_id != $author_id || $this->prefs['limits']['self_reply'] ){

				// Award the user liking
				if ( $this->prefs['add_undislike']['creds'] ) {
					// If not over limit
					if ( ! $this->over_hook_limit( 'add_undislike', 'wp_add_undislike', $user_id ) ) {
						// Make sure this is unique event
						if ( ! $this->core->has_entry( 'wp_add_undislike', $id, $user_id ) ) {
							// Execute
							$this->core->add_creds(
								'wp_add_undislike',
								$user_id,
								$this->prefs['add_undislike']['creds'],
								$this->prefs['add_undislike']['log'],
								$id,
								array( 'ref_type' => $key ),
								$this->mycred_type
							);
						}
					}
				}

				// Award post author for being liked
				if ( $this->prefs['get_undislike']['creds'] && $author_id ) {
					// If not over limit
					if ( ! $this->over_hook_limit( 'get_undislike', 'wp_get_undislike', $author_id ) ) {
						// Make sure this is unique event
						if ( ! $this->core->has_entry( 'wp_get_undislike', $id, $author_id, array( 'ref_type' => $key, 'by' => $user_id ) ) ) {
							// Execute
							$this->core->add_creds(
								'wp_get_undislike',
								$author_id,
								$this->prefs['get_undislike']['creds'],
								$this->prefs['get_undislike']['log'],
								$id,
								array( 'ref_type' => $key, 'by' => $user_id ),
								$this->mycred_type
							);
						}
					}
				}

			}

		}

		/**
		 * Get buddpress user ID
		 *
		 * @param integer $activity_id
		 * @return integer
		 */
		public function bp_get_auhtor_id($activity_id) {
			$activity = bp_activity_get_specific( array( 'activity_ids' => $activity_id, 'display_comments'  => true ) );
			return $activity['activities'][0]->user_id;
		}

		/**
		 * Get author ID by it's type
		 *
		 * @param string $key
		 * @return integer
		 */
		protected function get_author_ID( $id, $key ){
			// Default value
			$author_id 	= 0;
			// Get author ID by it's type
			switch ( $key ) {
				case '_liked':
					$author_id 	= get_post_field( 'post_author', $id );
					break;
				case '_topicliked':
					$author_id 	= bbp_get_reply_author_id( $id );
					break;
				case '_commentliked':
					$comment_id = get_comment( $id );
					$author_id 	= $comment_id->user_id;
					break;
				case '_activityliked':
					$author_id 	= $this->bp_get_auhtor_id( $id );
					break;
			}
			return $author_id;
		}


		/**
		 * Preference for wp_ulike Hook
		 *
		 * @since		2.3
		 */
		public function preferences() {

			$prefs = $this->prefs;

		?>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points for Disliking content', WP_ULIKE_PRO_DOMAIN ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_dislike' => 'creds' ) ); ?>"><?php esc_html_e( 'Points', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'add_dislike' => 'creds' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'add_dislike' => 'creds' ) ); ?>"
                    value="<?php echo $this->core->number( $prefs['add_dislike']['creds'] ); ?>" class="form-control" />
                <span class="description"><?php esc_html_e( 'Use zero to disable.', WP_ULIKE_PRO_DOMAIN ); ?></span>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_dislike', 'limit' ) ); ?>"><?php esc_html_e( 'Limit', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <?php echo $this->hook_limit_setting( $this->field_name( array( 'add_dislike', 'limit' ) ), $this->field_id( array( 'add_dislike', 'limit' ) ), $prefs['add_dislike']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_dislike' => 'log' ) ); ?>"><?php esc_html_e( 'Log template', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'add_dislike' => 'log' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'add_dislike' => 'log' ) ); ?>"
                    placeholder="<?php esc_html_e( 'required', WP_ULIKE_PRO_DOMAIN ); ?>"
                    value="<?php echo esc_attr( $prefs['add_dislike']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points for Author Who Get Disliked', WP_ULIKE_PRO_DOMAIN ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_dislike' => 'creds' ) ); ?>"><?php esc_html_e( 'Points', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'get_dislike' => 'creds' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'get_dislike' => 'creds' ) ); ?>"
                    value="<?php echo $this->core->number( $prefs['get_dislike']['creds'] ); ?>" class="form-control" />
                <span class="description"><?php esc_html_e( 'Use zero to disable.', WP_ULIKE_PRO_DOMAIN ); ?></span>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_dislike', 'limit' ) ); ?>"><?php esc_html_e( 'Limit', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <?php echo $this->hook_limit_setting( $this->field_name( array( 'get_dislike', 'limit' ) ), $this->field_id( array( 'get_dislike', 'limit' ) ), $prefs['get_dislike']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_dislike' => 'log' ) ); ?>"><?php esc_html_e( 'Log template', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'get_dislike' => 'log' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'get_dislike' => 'log' ) ); ?>"
                    placeholder="<?php esc_html_e( 'required', WP_ULIKE_PRO_DOMAIN ); ?>"
                    value="<?php echo esc_attr( $prefs['get_dislike']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points for Undisliking content', WP_ULIKE_PRO_DOMAIN ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_undislike' => 'creds' ) ); ?>"><?php esc_html_e( 'Points', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'add_undislike' => 'creds' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'add_undislike' => 'creds' ) ); ?>"
                    value="<?php echo $this->core->number( $prefs['add_undislike']['creds'] ); ?>"
                    class="form-control" />
                <span class="description"><?php esc_html_e( 'Use zero to disable.', WP_ULIKE_PRO_DOMAIN ); ?></span>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_undislike', 'limit' ) ); ?>"><?php esc_html_e( 'Limit', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <?php echo $this->hook_limit_setting( $this->field_name( array( 'add_undislike', 'limit' ) ), $this->field_id( array( 'add_undislike', 'limit' ) ), $prefs['add_undislike']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'add_undislike' => 'log' ) ); ?>"><?php esc_html_e( 'Log template', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'add_undislike' => 'log' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'add_undislike' => 'log' ) ); ?>"
                    placeholder="<?php esc_html_e( 'required', WP_ULIKE_PRO_DOMAIN ); ?>"
                    value="<?php echo esc_attr( $prefs['add_undislike']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Points for Author Who Get Undisliked', WP_ULIKE_PRO_DOMAIN ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_undislike' => 'creds' ) ); ?>"><?php esc_html_e( 'Points', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'get_undislike' => 'creds' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'get_undislike' => 'creds' ) ); ?>"
                    value="<?php echo $this->core->number( $prefs['get_undislike']['creds'] ); ?>"
                    class="form-control" />
                <span class="description"><?php esc_html_e( 'Use zero to disable.', WP_ULIKE_PRO_DOMAIN ); ?></span>
            </div>
        </div>
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_undislike', 'limit' ) ); ?>"><?php esc_html_e( 'Limit', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <?php echo $this->hook_limit_setting( $this->field_name( array( 'get_undislike', 'limit' ) ), $this->field_id( array( 'get_undislike', 'limit' ) ), $prefs['get_undislike']['limit'] ); ?>
            </div>
        </div>
        <div class="col-lg-8 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label
                    for="<?php echo $this->field_id( array( 'get_undislike' => 'log' ) ); ?>"><?php esc_html_e( 'Log template', WP_ULIKE_PRO_DOMAIN ); ?></label>
                <input type="text" name="<?php echo $this->field_name( array( 'get_undislike' => 'log' ) ); ?>"
                    id="<?php echo $this->field_id( array( 'get_undislike' => 'log' ) ); ?>"
                    placeholder="<?php esc_html_e( 'required', WP_ULIKE_PRO_DOMAIN ); ?>"
                    value="<?php echo esc_attr( $prefs['get_undislike']['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<div class="hook-instance">
    <h3><?php esc_html_e( 'Limits', WP_ULIKE_PRO_DOMAIN ); ?></h3>
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <div class="form-group">
                <div class="checkbox">
                    <label for="<?php echo $this->field_id( array( 'limits' => 'self_reply' ) ); ?>"><input
                            type="checkbox" name="<?php echo $this->field_name( array( 'limits' => 'self_reply' ) ); ?>"
                            id="<?php echo $this->field_id( array( 'limits' => 'self_reply' ) ); ?>"
                            <?php checked( $prefs['limits']['self_reply'], 1 ); ?> value="1" />
                        <?php echo $this->core->template_tags_general( esc_html__( '%plural% is to be awarded even when item authors Like/Unlike their own item.', WP_ULIKE_PRO_DOMAIN ) ); ?></label>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
		}

		/**
		 * Sanitise Preferences
		 *
		 * @since 		2.3
		 */
		function sanitise_preferences( $data ) {

			if ( isset( $data['add_dislike']['limit'] ) && isset( $data['add_dislike']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['add_dislike']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['add_dislike']['limit'] = $limit . '/' . $data['add_dislike']['limit_by'];
				unset( $data['add_dislike']['limit_by'] );
			}

			if ( isset( $data['get_dislike']['limit'] ) && isset( $data['get_dislike']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['get_dislike']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['get_dislike']['limit'] = $limit . '/' . $data['get_dislike']['limit_by'];
				unset( $data['get_dislike']['limit_by'] );
			}

			if ( isset( $data['add_undislike']['limit'] ) && isset( $data['add_undislike']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['add_undislike']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['add_undislike']['limit'] = $limit . '/' . $data['add_undislike']['limit_by'];
				unset( $data['add_undislike']['limit_by'] );
			}

			if ( isset( $data['get_undislike']['limit'] ) && isset( $data['get_undislike']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['get_undislike']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['get_undislike']['limit'] = $limit . '/' . $data['get_undislike']['limit_by'];
				unset( $data['get_undislike']['limit_by'] );
			}

			return $data;

		}
	}

endif;