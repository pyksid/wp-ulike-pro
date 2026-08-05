<?php
/**
 * Schema Generator tab template.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$schema_types   = WP_Ulike_Pro_Schema_Generator_Tool::get_schema_types();
$app_categories = WP_Ulike_Pro_Schema_Generator_Tool::get_application_categories();
$days_of_week   = WP_Ulike_Pro_Schema_Generator_Tool::get_days_of_week();
$selected_id    = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
$selected_post  = $selected_id ? get_post( $selected_id ) : null;
$schema_data    = $selected_id ? WP_Ulike_Pro_Schema_Generator_Tool::get_post_schema_data( $selected_id ) : array();
$post_types     = get_post_types( array( 'public' => true ), 'objects' );

$val = function ( $key, $default = '' ) use ( $schema_data ) {
	return isset( $schema_data[ $key ] ) ? $schema_data[ $key ] : $default;
};

$is_checked = function ( $key ) use ( $val ) {
	return wp_ulike_is_true( $val( $key, 'false' ) );
};

?>

<div class="wp-ulike-pro-schema-tool" data-initial-post-id="<?php echo esc_attr( (string) $selected_id ); ?>">
	<div class="wp-ulike-pro-schema-tool__layout">
		<aside class="wp-ulike-pro-schema-tool__sidebar wp-ulike-pro-tools-card">
			<div class="wp-ulike-pro-tools-card-header">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Find Content', WP_ULIKE_PRO_DOMAIN ); ?></h2>
			</div>
			<div class="wp-ulike-pro-tools-card-content">
				<p class="wp-ulike-tools-panel__intro"><?php esc_html_e( 'Enable schema markup so search engines can read aggregate ratings. WP ULike uses star votes when the Star Rating template is active, or estimates from likes and dislikes otherwise. Emoji reactions are not included. Pick a post below to configure ratings, optional FAQ, and advanced schema fields.', WP_ULIKE_PRO_DOMAIN ); ?></p>

				<div class="wp-ulike-pro-schema-filters">
					<div class="wp-ulike-pro-schema-field">
						<label for="wp-ulike-schema-post-type" class="wp-ulike-pro-schema-field__label"><?php esc_html_e( 'Post Type', WP_ULIKE_PRO_DOMAIN ); ?></label>
						<select id="wp-ulike-schema-post-type" class="widefat">
							<option value=""><?php esc_html_e( 'All Types', WP_ULIKE_PRO_DOMAIN ); ?></option>
							<?php foreach ( $post_types as $type ) : ?>
								<option value="<?php echo esc_attr( $type->name ); ?>"><?php echo esc_html( $type->label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="wp-ulike-pro-schema-field">
						<label for="wp-ulike-schema-search" class="wp-ulike-pro-schema-field__label"><?php esc_html_e( 'Search', WP_ULIKE_PRO_DOMAIN ); ?></label>
						<input type="search" id="wp-ulike-schema-search" class="widefat" placeholder="<?php esc_attr_e( 'Search by title…', WP_ULIKE_PRO_DOMAIN ); ?>">
					</div>
					<div class="wp-ulike-pro-schema-field wp-ulike-pro-schema-field--checkbox">
						<label class="wp-ulike-pro-schema-filter-checkbox">
							<input type="checkbox" id="wp-ulike-schema-only" value="1" checked>
							<span><?php esc_html_e( 'Only show items with schema or FAQ data', WP_ULIKE_PRO_DOMAIN ); ?></span>
						</label>
					</div>
					<div class="wp-ulike-pro-schema-field wp-ulike-pro-schema-field--action">
						<button type="button" class="button button-primary button-large" id="wp-ulike-schema-search-btn">
							<span class="wp-ulike-pro-schema-search-btn__text"><?php esc_html_e( 'Search', WP_ULIKE_PRO_DOMAIN ); ?></span>
							<span class="spinner wp-ulike-pro-schema-search-spinner" aria-hidden="true"></span>
						</button>
					</div>
				</div>

				<p id="wp-ulike-schema-results-summary" class="wp-ulike-pro-schema-results__summary description" hidden></p>

				<div id="wp-ulike-schema-results" class="wp-ulike-pro-schema-results" aria-live="polite">
					<p class="wp-ulike-pro-schema-results__placeholder description"><?php esc_html_e( 'Run a search to see matching content.', WP_ULIKE_PRO_DOMAIN ); ?></p>
				</div>

				<div id="wp-ulike-schema-results-footer" class="wp-ulike-pro-schema-results__footer" hidden>
					<button type="button" class="button button-secondary button-large" id="wp-ulike-schema-load-more">
						<span class="wp-ulike-pro-schema-load-more__text"><?php esc_html_e( 'Load more', WP_ULIKE_PRO_DOMAIN ); ?></span>
						<span class="spinner wp-ulike-pro-schema-load-more-spinner" aria-hidden="true"></span>
					</button>
				</div>
			</div>
		</aside>

		<div class="wp-ulike-pro-schema-tool__editor">
			<form method="post" action="" id="wp-ulike-schema-form" class="wp-ulike-pro-schema-form">
				<?php wp_nonce_field( 'wp_ulike_schema_generator', 'wp_ulike_schema_generator_nonce' ); ?>
				<input type="hidden" name="wp_ulike_schema_generator_save" value="1">
				<input type="hidden" name="schema_post_id" id="wp-ulike-schema-post-id" value="<?php echo esc_attr( $selected_id ); ?>">

				<div class="wp-ulike-pro-tools-card" id="wp-ulike-schema-editor-card">
					<div class="wp-ulike-pro-tools-card-header wp-ulike-pro-schema-editor-header">
						<div>
							<h2 class="wp-ulike-about-card__title wp-ulike-pro-schema-editor-title-row">
								<span id="wp-ulike-schema-editor-title">
								<?php
								if ( $selected_post ) {
									echo esc_html( get_the_title( $selected_post ) );
								} else {
									esc_html_e( 'Schema Editor', WP_ULIKE_PRO_DOMAIN );
								}
								?>
								</span>
								<span class="spinner wp-ulike-pro-schema-editor-spinner" id="wp-ulike-schema-editor-spinner" aria-hidden="true"></span>
							</h2>
							<p class="description wp-ulike-pro-schema-editor-subtitle" id="wp-ulike-schema-editor-subtitle">
								<?php
								if ( $selected_post ) {
									printf(
										/* translators: 1: post type label, 2: post ID */
										esc_html__( '%1$s · ID %2$d', WP_ULIKE_PRO_DOMAIN ),
										esc_html( get_post_type_object( $selected_post->post_type )->labels->singular_name ),
										(int) $selected_id
									);
								} else {
									esc_html_e( 'Select a post from the list to begin.', WP_ULIKE_PRO_DOMAIN );
								}
								?>
							</p>
						</div>
						<?php
						$view_url         = $selected_post ? get_permalink( $selected_id ) : '';
						$rich_results_url = $view_url ? 'https://search.google.com/test/rich-results?url=' . rawurlencode( $view_url ) : '';
						?>
						<div class="wp-ulike-pro-schema-editor-actions">
							<?php if ( $selected_post ) : ?>
								<a href="<?php echo esc_url( get_edit_post_link( $selected_id, 'raw' ) ); ?>" class="button button-secondary" id="wp-ulike-schema-edit-post-link" target="_blank" rel="noopener">
									<?php esc_html_e( 'Edit Post', WP_ULIKE_PRO_DOMAIN ); ?>
								</a>
								<a href="<?php echo esc_url( $view_url ); ?>" class="button button-secondary" id="wp-ulike-schema-view-post-link" target="_blank" rel="noopener">
									<?php esc_html_e( 'View Post', WP_ULIKE_PRO_DOMAIN ); ?>
								</a>
								<a href="<?php echo esc_url( $rich_results_url ); ?>" class="button button-secondary" id="wp-ulike-schema-rich-results-link" target="_blank" rel="noopener">
									<?php esc_html_e( 'Test Rich Results', WP_ULIKE_PRO_DOMAIN ); ?>
								</a>
							<?php else : ?>
								<a href="#" class="button button-secondary" id="wp-ulike-schema-edit-post-link" style="display:none;" target="_blank" rel="noopener">
									<?php esc_html_e( 'Edit Post', WP_ULIKE_PRO_DOMAIN ); ?>
								</a>
								<a href="#" class="button button-secondary" id="wp-ulike-schema-view-post-link" style="display:none;" target="_blank" rel="noopener">
									<?php esc_html_e( 'View Post', WP_ULIKE_PRO_DOMAIN ); ?>
								</a>
								<a href="#" class="button button-secondary" id="wp-ulike-schema-rich-results-link" style="display:none;" target="_blank" rel="noopener">
									<?php esc_html_e( 'Test Rich Results', WP_ULIKE_PRO_DOMAIN ); ?>
								</a>
							<?php endif; ?>
						</div>
					</div>

					<div class="wp-ulike-pro-tools-card-content" id="wp-ulike-schema-fields-wrap" <?php echo $selected_id ? '' : 'hidden'; ?>>
						<div class="wp-ulike-pro-schema-section wp-ulike-pro-schema-section--core" data-field-group="basics">
							<h3><?php esc_html_e( 'Schema', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<p class="description"><?php esc_html_e( 'Turn on schema markup so search engines can read your content type and WP ULike star ratings.', WP_ULIKE_PRO_DOMAIN ); ?> <a href="https://schema.org/docs/gs.html" target="_blank" rel="noopener"><?php esc_html_e( 'Learn more about schema', WP_ULIKE_PRO_DOMAIN ); ?></a></p>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row"><?php esc_html_e( 'Enable Schema', WP_ULIKE_PRO_DOMAIN ); ?></th>
										<td>
											<label>
												<input type="hidden" name="schema[enable_schema]" value="false">
												<input type="checkbox" name="schema[enable_schema]" value="true" data-schema-field="enable_schema" <?php checked( $is_checked( 'enable_schema' ) ); ?>>
												<?php esc_html_e( 'Output schema markup for this post', WP_ULIKE_PRO_DOMAIN ); ?>
											</label>
										</td>
									</tr>
									<tr data-schema-field-wrap="schema_type">
										<th scope="row"><label for="schema_schema_type"><?php esc_html_e( 'Schema Type', WP_ULIKE_PRO_DOMAIN ); ?> <span class="required wp-ulike-pro-schema-required" data-required-field="schema_type" hidden aria-hidden="true">*</span></label></th>
										<td>
											<select name="schema[schema_type]" id="schema_schema_type" data-schema-field="schema_type" class="regular-text">
												<option value=""><?php esc_html_e( 'Select type…', WP_ULIKE_PRO_DOMAIN ); ?></option>
												<?php foreach ( $schema_types as $type_key => $type_label ) : ?>
													<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $val( 'schema_type' ), $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
												<?php endforeach; ?>
											</select>
											<p class="description"><?php esc_html_e( 'Choose the type that best matches this content. Product and Software App are common for review-style pages; Event, Local Business, and How-to work for specialized content.', WP_ULIKE_PRO_DOMAIN ); ?></p>
										</td>
									</tr>
									<tr data-schema-field-wrap="title">
										<th scope="row"><label for="schema_title"><?php esc_html_e( 'Title', WP_ULIKE_PRO_DOMAIN ); ?></label></th>
										<td>
											<input type="text" class="large-text" name="schema[title]" id="schema_title" data-schema-field="title" value="<?php echo esc_attr( $val( 'title' ) ); ?>">
											<p class="description"><?php esc_html_e( 'Leave empty to use the post title.', WP_ULIKE_PRO_DOMAIN ); ?></p>
										</td>
									</tr>
									<tr data-schema-field-wrap="description">
										<th scope="row"><label for="schema_description"><?php esc_html_e( 'Description', WP_ULIKE_PRO_DOMAIN ); ?></label></th>
										<td><textarea name="schema[description]" id="schema_description" data-schema-field="description" class="large-text" rows="3"><?php echo esc_textarea( $val( 'description' ) ); ?></textarea></td>
									</tr>
								</tbody>
							</table>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="rating_section">
							<h3><?php esc_html_e( 'Star Ratings', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<p class="description"><?php esc_html_e( 'Aggregate ratings use real star votes when the Star Rating template is enabled for posts; otherwise WP ULike estimates from likes and dislikes (like = 5, dislike = 1). Emoji reactions are not converted to schema ratings. Manual values below override automatic calculation.', WP_ULIKE_PRO_DOMAIN ); ?></p>

							<div id="wp-ulike-schema-rating-preview" class="wp-ulike-pro-schema-rating-preview" aria-live="polite">
								<p class="wp-ulike-pro-schema-rating-preview__text description"><?php esc_html_e( 'Select a post to preview star ratings.', WP_ULIKE_PRO_DOMAIN ); ?></p>
							</div>

							<table class="form-table" role="presentation"><tbody>
								<tr><th scope="row"><?php esc_html_e( 'Disable Star Ratings', WP_ULIKE_PRO_DOMAIN ); ?></th><td><label><input type="hidden" name="schema[disable_star_ratings]" value="false"><input type="checkbox" name="schema[disable_star_ratings]" value="true" data-schema-field="disable_star_ratings" <?php checked( $is_checked( 'disable_star_ratings' ) ); ?>> <?php esc_html_e( 'Do not include aggregate rating in schema', WP_ULIKE_PRO_DOMAIN ); ?></label></td></tr>
								<tr data-schema-field-wrap="enable_time_factor_rating_row"><th scope="row"><?php esc_html_e( 'Time Factor Rating', WP_ULIKE_PRO_DOMAIN ); ?></th><td><label><input type="hidden" name="schema[enable_time_factor_rating]" value="false"><input type="checkbox" name="schema[enable_time_factor_rating]" value="true" data-schema-field="enable_time_factor_rating" <?php checked( $is_checked( 'enable_time_factor_rating' ) ); ?>> <?php esc_html_e( 'Calculate rating using time-weighted likes', WP_ULIKE_PRO_DOMAIN ); ?></label><p class="description"><?php esc_html_e( 'When the dislike button is disabled, the star rating value can be calculated by considering the time factor on the number of likes.', WP_ULIKE_PRO_DOMAIN ); ?></p></td></tr>
								<tr data-schema-field-wrap="enable_custom_rating_row"><th scope="row"><?php esc_html_e( 'Custom Star Rating', WP_ULIKE_PRO_DOMAIN ); ?></th><td><label><input type="hidden" name="schema[enable_custom_rating]" value="false"><input type="checkbox" name="schema[enable_custom_rating]" value="true" data-schema-field="enable_custom_rating" <?php checked( $is_checked( 'enable_custom_rating' ) ); ?>> <?php esc_html_e( 'Use manual rating values below', WP_ULIKE_PRO_DOMAIN ); ?></label><p class="description"><?php esc_html_e( 'Manually rate the post instead of using dynamic algorithms.', WP_ULIKE_PRO_DOMAIN ); ?></p></td></tr>
							</tbody></table>
							<div data-schema-field-wrap="custom_rating_section">
								<table class="form-table" role="presentation"><tbody>
									<tr><th scope="row"><label for="schema_rating_value"><?php esc_html_e( 'Rating Value', WP_ULIKE_PRO_DOMAIN ); ?> <span class="required wp-ulike-pro-schema-required" data-required-field="rating_value" hidden aria-hidden="true">*</span></label></th><td><input type="text" class="small-text" name="schema[rating_value]" id="schema_rating_value" data-schema-field="rating_value" value="<?php echo esc_attr( $val( 'rating_value' ) ); ?>"></td></tr>
									<tr><th scope="row"><label for="schema_rating_count"><?php esc_html_e( 'Rating Count', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="number" class="small-text" min="0" name="schema[rating_count]" id="schema_rating_count" data-schema-field="rating_count" value="<?php echo esc_attr( $val( 'rating_count' ) ); ?>"></td></tr>
									<tr><th scope="row"><label for="schema_review_count"><?php esc_html_e( 'Review Count', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="number" class="small-text" min="0" name="schema[review_count]" id="schema_review_count" data-schema-field="review_count" value="<?php echo esc_attr( $val( 'review_count' ) ); ?>"></td></tr>
									<tr><th scope="row"><label for="schema_worst_rating"><?php esc_html_e( 'Worst Rating', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="number" class="small-text" min="0" name="schema[worst_rating]" id="schema_worst_rating" data-schema-field="worst_rating" value="<?php echo esc_attr( $val( 'worst_rating', 1 ) ); ?>"></td></tr>
									<tr><th scope="row"><label for="schema_best_rating"><?php esc_html_e( 'Best Rating', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="number" class="small-text" min="0" name="schema[best_rating]" id="schema_best_rating" data-schema-field="best_rating" value="<?php echo esc_attr( $val( 'best_rating', 5 ) ); ?>"></td></tr>
								</tbody></table>
							</div>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="name">
							<h3><?php esc_html_e( 'People & Names', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<table class="form-table" role="presentation"><tbody>
								<tr><th scope="row"><label for="schema_name"><?php esc_html_e( 'Name / Brand / Actor', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="large-text" name="schema[name]" id="schema_name" data-schema-field="name" value="<?php echo esc_attr( $val( 'name' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="author"><th scope="row"><label for="schema_author"><?php esc_html_e( 'Author / Performer / Director / Brand', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="large-text" name="schema[author]" id="schema_author" data-schema-field="author" value="<?php echo esc_attr( $val( 'author' ) ); ?>"><p class="description"><?php esc_html_e( 'For Product schema, this value is used as the brand name.', WP_ULIKE_PRO_DOMAIN ); ?></p></td></tr>
							</tbody></table>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="address_group">
							<h3><?php esc_html_e( 'Location & Address', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<table class="form-table" role="presentation"><tbody>
								<tr data-schema-field-wrap="location"><th scope="row"><label for="schema_location"><?php esc_html_e( 'Location Name', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="large-text" name="schema[location]" id="schema_location" data-schema-field="location" value="<?php echo esc_attr( $val( 'location' ) ); ?>"></td></tr>
								<tr><th scope="row"><label for="schema_street_address"><?php esc_html_e( 'Street Address', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="large-text" name="schema[street_address]" id="schema_street_address" data-schema-field="street_address" value="<?php echo esc_attr( $val( 'street_address' ) ); ?>"></td></tr>
								<tr><th scope="row"><label for="schema_address_locality"><?php esc_html_e( 'City / Locality', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[address_locality]" id="schema_address_locality" data-schema-field="address_locality" value="<?php echo esc_attr( $val( 'address_locality' ) ); ?>"></td></tr>
								<tr><th scope="row"><label for="schema_address_region"><?php esc_html_e( 'Region / State', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[address_region]" id="schema_address_region" data-schema-field="address_region" value="<?php echo esc_attr( $val( 'address_region' ) ); ?>"></td></tr>
								<tr><th scope="row"><label for="schema_postal_code"><?php esc_html_e( 'Postal Code', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[postal_code]" id="schema_postal_code" data-schema-field="postal_code" value="<?php echo esc_attr( $val( 'postal_code' ) ); ?>"></td></tr>
								<tr><th scope="row"><label for="schema_address_country"><?php esc_html_e( 'Country', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[address_country]" id="schema_address_country" data-schema-field="address_country" value="<?php echo esc_attr( $val( 'address_country' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="telephone"><th scope="row"><label for="schema_telephone"><?php esc_html_e( 'Telephone', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[telephone]" id="schema_telephone" data-schema-field="telephone" value="<?php echo esc_attr( $val( 'telephone' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="price_range"><th scope="row"><label for="schema_price_range"><?php esc_html_e( 'Price Range', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[price_range]" id="schema_price_range" data-schema-field="price_range" value="<?php echo esc_attr( $val( 'price_range' ) ); ?>" placeholder="$$"></td></tr>
							</tbody></table>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="start_date">
							<h3><?php esc_html_e( 'Dates & Hours', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<table class="form-table" role="presentation"><tbody>
								<tr><th scope="row"><label for="schema_start_date"><?php esc_html_e( 'Start Date', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="date" name="schema[start_date]" id="schema_start_date" data-schema-field="start_date" value="<?php echo esc_attr( $val( 'start_date' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="end_date"><th scope="row"><label for="schema_end_date"><?php esc_html_e( 'End Date', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="date" name="schema[end_date]" id="schema_end_date" data-schema-field="end_date" value="<?php echo esc_attr( $val( 'end_date' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="created_date"><th scope="row"><label for="schema_created_date"><?php esc_html_e( 'Created / Published Date', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="date" name="schema[created_date]" id="schema_created_date" data-schema-field="created_date" value="<?php echo esc_attr( $val( 'created_date' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="day_of_week"><th scope="row"><label for="schema_day_of_week"><?php esc_html_e( 'Days of Week', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td>
									<?php
									$selected_days = $val( 'day_of_week', array() );
									if ( ! is_array( $selected_days ) ) {
										$selected_days = array();
									}
									?>
									<select name="schema[day_of_week][]" id="schema_day_of_week" data-schema-field="day_of_week" class="regular-text" multiple size="7">
										<?php foreach ( $days_of_week as $day_key => $day_label ) : ?>
											<option value="<?php echo esc_attr( $day_key ); ?>" <?php selected( in_array( $day_key, $selected_days, true ) ); ?>><?php echo esc_html( $day_label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Hold Ctrl (Windows) or Command (Mac) to select multiple days.', WP_ULIKE_PRO_DOMAIN ); ?></p>
								</td></tr>
								<tr data-schema-field-wrap="opens"><th scope="row"><label for="schema_opens"><?php esc_html_e( 'Opens', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="time" name="schema[opens]" id="schema_opens" data-schema-field="opens" value="<?php echo esc_attr( $val( 'opens' ) ); ?>"><p class="description"><?php esc_html_e( 'The time the business location opens.', WP_ULIKE_PRO_DOMAIN ); ?></p></td></tr>
								<tr data-schema-field-wrap="closes"><th scope="row"><label for="schema_closes"><?php esc_html_e( 'Closes', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="time" name="schema[closes]" id="schema_closes" data-schema-field="closes" value="<?php echo esc_attr( $val( 'closes' ) ); ?>"><p class="description"><?php esc_html_e( 'The time the business location closes.', WP_ULIKE_PRO_DOMAIN ); ?></p></td></tr>
								<tr data-schema-field-wrap="valid_date"><th scope="row"><label for="schema_valid_date"><?php esc_html_e( 'Valid Until', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="date" name="schema[valid_date]" id="schema_valid_date" data-schema-field="valid_date" value="<?php echo esc_attr( $val( 'valid_date' ) ); ?>"></td></tr>
							</tbody></table>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="price_group">
							<h3><?php esc_html_e( 'Commerce', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<table class="form-table" role="presentation"><tbody>
								<tr><th scope="row"><label for="schema_price"><?php esc_html_e( 'Price', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="small-text" name="schema[price]" id="schema_price" data-schema-field="price" value="<?php echo esc_attr( $val( 'price' ) ); ?>"></td></tr>
								<tr><th scope="row"><label for="schema_price_currency"><?php esc_html_e( 'Currency', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="small-text" name="schema[price_currency]" id="schema_price_currency" data-schema-field="price_currency" value="<?php echo esc_attr( $val( 'price_currency', 'USD' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="availability"><th scope="row"><label for="schema_availability"><?php esc_html_e( 'Availability', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td>
									<select name="schema[availability]" id="schema_availability" data-schema-field="availability">
										<option value=""><?php esc_html_e( 'Select…', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<?php foreach ( array( 'InStock', 'PreOrder', 'SoldOut' ) as $opt ) : ?>
											<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $val( 'availability' ), $opt ); ?>><?php echo esc_html( $opt ); ?></option>
										<?php endforeach; ?>
									</select>
								</td></tr>
								<tr data-schema-field-wrap="url"><th scope="row"><label for="schema_url"><?php esc_html_e( 'URL', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="url" class="large-text" name="schema[url]" id="schema_url" data-schema-field="url" value="<?php echo esc_url( $val( 'url' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="sku"><th scope="row"><label for="schema_sku"><?php esc_html_e( 'SKU', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[sku]" id="schema_sku" data-schema-field="sku" value="<?php echo esc_attr( $val( 'sku' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="mpn"><th scope="row"><label for="schema_mpn"><?php esc_html_e( 'MPN', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[mpn]" id="schema_mpn" data-schema-field="mpn" value="<?php echo esc_attr( $val( 'mpn' ) ); ?>"></td></tr>
							</tbody></table>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="application_category">
							<h3><?php esc_html_e( 'Software Application', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<table class="form-table" role="presentation"><tbody>
								<tr><th scope="row"><label for="schema_application_category"><?php esc_html_e( 'Application Category', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td>
									<select name="schema[application_category]" id="schema_application_category" data-schema-field="application_category">
										<option value=""><?php esc_html_e( 'Select…', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<?php foreach ( $app_categories as $cat_key => $cat_label ) : ?>
											<option value="<?php echo esc_attr( $cat_key ); ?>" <?php selected( $val( 'application_category' ), $cat_key ); ?>><?php echo esc_html( $cat_label ); ?></option>
										<?php endforeach; ?>
									</select>
								</td></tr>
								<tr data-schema-field-wrap="operating_system"><th scope="row"><label for="schema_operating_system"><?php esc_html_e( 'Operating System', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[operating_system]" id="schema_operating_system" data-schema-field="operating_system" value="<?php echo esc_attr( $val( 'operating_system' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="software_version"><th scope="row"><label for="schema_software_version"><?php esc_html_e( 'Software Version', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[software_version]" id="schema_software_version" data-schema-field="software_version" value="<?php echo esc_attr( $val( 'software_version' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="is_accessible_for_free"><th scope="row"><?php esc_html_e( 'Accessible For Free', WP_ULIKE_PRO_DOMAIN ); ?></th><td><label><input type="hidden" name="schema[is_accessible_for_free]" value="false"><input type="checkbox" name="schema[is_accessible_for_free]" value="true" data-schema-field="is_accessible_for_free" <?php checked( $is_checked( 'is_accessible_for_free' ) ); ?>> <?php esc_html_e( 'Yes', WP_ULIKE_PRO_DOMAIN ); ?></label></td></tr>
							</tbody></table>
						</div>

						<div class="wp-ulike-pro-schema-section wp-ulike-pro-schema-section--collapsible" data-schema-section="media">
							<h3><?php esc_html_e( 'Media & Technical', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<table class="form-table" role="presentation"><tbody>
								<tr data-schema-field-wrap="issn"><th scope="row"><label for="schema_issn"><?php esc_html_e( 'ISSN', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[issn]" id="schema_issn" data-schema-field="issn" value="<?php echo esc_attr( $val( 'issn' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="duration"><th scope="row"><label for="schema_duration"><?php esc_html_e( 'Duration (ISO 8601)', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[duration]" id="schema_duration" data-schema-field="duration" value="<?php echo esc_attr( $val( 'duration' ) ); ?>" placeholder="PT30M"></td></tr>
								<tr data-schema-field-wrap="encoding_format"><th scope="row"><label for="schema_encoding_format"><?php esc_html_e( 'Encoding Format', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="text" class="regular-text" name="schema[encoding_format]" id="schema_encoding_format" data-schema-field="encoding_format" value="<?php echo esc_attr( $val( 'encoding_format' ) ); ?>" placeholder="audio/mpeg"></td></tr>
								<tr data-schema-field-wrap="num_tracks"><th scope="row"><label for="schema_num_tracks"><?php esc_html_e( 'Tracks Number', WP_ULIKE_PRO_DOMAIN ); ?></label></th><td><input type="number" class="small-text" min="0" name="schema[num_tracks]" id="schema_num_tracks" data-schema-field="num_tracks" value="<?php echo esc_attr( $val( 'num_tracks' ) ); ?>"></td></tr>
								<tr data-schema-field-wrap="image"><th scope="row"><?php esc_html_e( 'Logo / Image', WP_ULIKE_PRO_DOMAIN ); ?></th><td>
									<div class="wp-ulike-pro-schema-media-field" data-media-field="image">
										<input type="hidden" name="schema[image]" id="schema_image" data-schema-field="image" value="<?php echo esc_url( $val( 'image' ) ); ?>">
										<div class="wp-ulike-pro-schema-media-field__preview" id="wp-ulike-schema-image-preview">
											<?php if ( $val( 'image' ) ) : ?>
												<img src="<?php echo esc_url( $val( 'image' ) ); ?>" alt="">
											<?php endif; ?>
										</div>
										<p class="wp-ulike-pro-schema-media-field__actions">
											<button type="button" class="button wp-ulike-schema-media-upload" data-media-target="schema_image"><?php esc_html_e( 'Select Image', WP_ULIKE_PRO_DOMAIN ); ?></button>
											<button type="button" class="button-link wp-ulike-schema-media-remove" data-media-target="schema_image" <?php echo $val( 'image' ) ? '' : 'hidden'; ?>><?php esc_html_e( 'Remove', WP_ULIKE_PRO_DOMAIN ); ?></button>
										</p>
									</div>
								</td></tr>
								<tr data-schema-field-wrap="image_list"><th scope="row"><?php esc_html_e( 'Image(s) List', WP_ULIKE_PRO_DOMAIN ); ?></th><td>
									<?php
									$image_list_urls = $val( 'image_list', array() );
									if ( ! is_array( $image_list_urls ) ) {
										$image_list_urls = array();
									}
									?>
									<div class="wp-ulike-pro-schema-gallery-field">
										<div id="wp-ulike-schema-image-list" class="wp-ulike-pro-schema-gallery">
											<?php foreach ( $image_list_urls as $gallery_url ) : ?>
												<?php if ( $gallery_url ) : ?>
													<div class="wp-ulike-pro-schema-gallery__item">
														<img src="<?php echo esc_url( $gallery_url ); ?>" alt="">
														<input type="hidden" name="schema[image_list][]" value="<?php echo esc_url( $gallery_url ); ?>">
														<button type="button" class="button-link button-link-delete wp-ulike-schema-gallery-remove" aria-label="<?php esc_attr_e( 'Remove image', WP_ULIKE_PRO_DOMAIN ); ?>">&times;</button>
													</div>
												<?php endif; ?>
											<?php endforeach; ?>
										</div>
										<p class="wp-ulike-pro-schema-section-actions"><button type="button" class="button button-secondary wp-ulike-schema-gallery-add" id="wp-ulike-schema-add-images"><?php esc_html_e( 'Add Images', WP_ULIKE_PRO_DOMAIN ); ?></button></p>
									</div>
								</td></tr>
							</tbody></table>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="tracks_section">
							<h3><?php esc_html_e( 'Playlist Tracks', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<div id="wp-ulike-schema-tracks" class="wp-ulike-pro-schema-repeater" data-repeater="tracks"></div>
							<p class="wp-ulike-pro-schema-section-actions"><button type="button" class="button button-secondary" id="wp-ulike-schema-add-track"><?php esc_html_e( 'Add Track', WP_ULIKE_PRO_DOMAIN ); ?></button></p>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="supply_section">
							<h3><?php esc_html_e( 'How-to Supplies', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<div id="wp-ulike-schema-supply" class="wp-ulike-pro-schema-repeater" data-repeater="supply"></div>
							<p class="wp-ulike-pro-schema-section-actions"><button type="button" class="button button-secondary" id="wp-ulike-schema-add-supply"><?php esc_html_e( 'Add Supply', WP_ULIKE_PRO_DOMAIN ); ?></button></p>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="tool_section">
							<h3><?php esc_html_e( 'How-to Tools', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<div id="wp-ulike-schema-tool" class="wp-ulike-pro-schema-repeater" data-repeater="tool"></div>
							<p class="wp-ulike-pro-schema-section-actions"><button type="button" class="button button-secondary" id="wp-ulike-schema-add-tool"><?php esc_html_e( 'Add Tool', WP_ULIKE_PRO_DOMAIN ); ?></button></p>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="step_section">
							<h3><?php esc_html_e( 'How-to Steps', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<div id="wp-ulike-schema-step" class="wp-ulike-pro-schema-repeater" data-repeater="step"></div>
							<p class="wp-ulike-pro-schema-section-actions"><button type="button" class="button button-secondary" id="wp-ulike-schema-add-step"><?php esc_html_e( 'Add Step', WP_ULIKE_PRO_DOMAIN ); ?></button></p>
						</div>

						<div class="wp-ulike-pro-schema-section" data-schema-field-wrap="custom_reviews_section">
							<h3><?php esc_html_e( 'Custom Reviews', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<p><label><input type="hidden" name="schema[enable_custom_reviews]" value="false"><input type="checkbox" name="schema[enable_custom_reviews]" value="true" data-schema-field="enable_custom_reviews" <?php checked( $is_checked( 'enable_custom_reviews' ) ); ?>> <?php esc_html_e( 'Include custom reviews in schema', WP_ULIKE_PRO_DOMAIN ); ?></label></p>
							<div data-schema-field-wrap="reviews_repeater">
								<div id="wp-ulike-schema-reviews" class="wp-ulike-pro-schema-repeater" data-repeater="reviews"></div>
								<p class="wp-ulike-pro-schema-section-actions"><button type="button" class="button button-secondary" id="wp-ulike-schema-add-review"><?php esc_html_e( 'Add Review', WP_ULIKE_PRO_DOMAIN ); ?></button></p>
							</div>
						</div>

						<div class="wp-ulike-pro-schema-section wp-ulike-pro-schema-section--core">
							<h3><?php esc_html_e( 'FAQ', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<p><label><input type="hidden" name="schema[enable_faq]" value="false"><input type="checkbox" name="schema[enable_faq]" value="true" data-schema-field="enable_faq" <?php checked( $is_checked( 'enable_faq' ) ); ?>> <?php esc_html_e( 'Enable FAQ structured data', WP_ULIKE_PRO_DOMAIN ); ?></label></p>
							<div data-schema-field-wrap="faq_section">
								<div id="wp-ulike-schema-faq" class="wp-ulike-pro-schema-repeater" data-repeater="faq"></div>
								<p class="wp-ulike-pro-schema-section-actions"><button type="button" class="button button-secondary" id="wp-ulike-schema-add-faq"><?php esc_html_e( 'Add FAQ Item', WP_ULIKE_PRO_DOMAIN ); ?></button></p>
							</div>
						</div>

						<div class="wp-ulike-pro-schema-editor-footer">
							<div id="wp-ulike-schema-notice" class="notice wp-ulike-pro-tools-notice wp-ulike-pro-schema-notice" hidden aria-live="polite"></div>

							<div class="wp-ulike-pro-schema-save">
								<button type="button" class="button button-primary" <?php echo $selected_id ? '' : 'disabled'; ?> id="wp-ulike-schema-save-btn">
									<?php esc_html_e( 'Save Schema', WP_ULIKE_PRO_DOMAIN ); ?>
								</button>
								<span class="spinner wp-ulike-pro-schema-save-spinner" id="wp-ulike-schema-save-spinner" aria-hidden="true"></span>
							</div>
						</div>
					</div>

					<div class="wp-ulike-pro-tools-card-content wp-ulike-pro-schema-empty" id="wp-ulike-schema-empty" <?php echo $selected_id ? 'hidden' : ''; ?>>
						<div class="wp-ulike-pro-display-empty">
							<div class="wp-ulike-pro-display-empty-icon" aria-hidden="true"><span class="dashicons dashicons-media-code"></span></div>
							<h3><?php esc_html_e( 'No content selected', WP_ULIKE_PRO_DOMAIN ); ?></h3>
							<p><?php esc_html_e( 'Search on the left, then click a post to edit its schema and FAQ settings.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>

<script type="application/json" id="wp-ulike-schema-config"><?php echo wp_json_encode( WP_Ulike_Pro_Schema_Generator_Tool::get_js_config() ); ?></script>
<script type="application/json" id="wp-ulike-schema-initial-data"><?php echo wp_json_encode( $schema_data ); ?></script>

