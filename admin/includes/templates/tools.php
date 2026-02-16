<?php
/**
 * Tools page template
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'maintenance';
$tabs = array(
    'maintenance'  => esc_html__( 'Maintenance', WP_ULIKE_PRO_DOMAIN ),
    'bulk-actions' => esc_html__( 'Bulk Actions', WP_ULIKE_PRO_DOMAIN ),
    'gdpr'         => esc_html__( 'GDPR', WP_ULIKE_PRO_DOMAIN ),
    'rest-api'     => esc_html__( 'REST API', WP_ULIKE_PRO_DOMAIN ),
    'debug'        => esc_html__( 'Debug Info', WP_ULIKE_PRO_DOMAIN )
);

// Only load data for the active tab to improve performance
$optimization_tools = array();
$debug_info = '';

if ( $current_tab === 'maintenance' ) {
    $optimization_tools = WP_Ulike_Pro_Tools::get_optimization_tools();
} elseif ( $current_tab === 'debug' ) {
    $debug_info = WP_Ulike_Pro_Tools::get_debug_info();
}
?>

<div class="wrap wp-ulike-pro-admin-page-tools">
    <nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Secondary menu', WP_ULIKE_PRO_DOMAIN ); ?>">
        <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=' . $tab_key ) ); ?>"
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html( $tab_label ); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="wp-ulike-pro-tools-container">
        <?php if ( $current_tab === 'maintenance' ) : ?>
            <?php if ( ! WP_Ulike_Pro_API::has_permission() ) : ?>
                <div class="wp-ulike-pro-tools-card">
                    <div class="wp-ulike-pro-tools-card-header">
                        <h2><?php esc_html_e( 'License Required', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                    </div>
                    <div class="wp-ulike-pro-tools-card-content">
                        <p><?php esc_html_e( 'You need an active license to access maintenance tools.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <?php foreach ( $optimization_tools as $group_key => $group_data ) : ?>
                    <div class="wp-ulike-pro-tools-card">
                        <div class="wp-ulike-pro-tools-card-header">
                            <h2><?php echo esc_html( $group_data['title'] ); ?></h2>
                        </div>
                        <div class="wp-ulike-pro-tools-card-content">
                            <div class="wp-ulike-pro-tools-list">
                                <?php foreach ( $group_data['tools'] as $tool ) : ?>
                                    <div class="wp-ulike-pro-tool-item">
                                        <div class="wp-ulike-pro-tool-content">
                                            <h3 class="wp-ulike-pro-tool-title"><?php echo esc_html( $tool['title'] ); ?></h3>
                                            <p class="wp-ulike-pro-tool-desc"><?php echo wp_kses_post( $tool['desc'] ); ?></p>
                                        </div>
                                        <div class="wp-ulike-pro-tool-action">
                                            <button type="button"
                                                    class="wp-ulike-pro-btn wp-ulike-pro-btn-secondary wp-ulike-pro-ajax-button-field wp-ulike-pro-tools-action-btn"
                                                    data-type="<?php echo esc_attr( $tool['type'] ); ?>"
                                                    data-action="<?php echo esc_attr( $tool['action'] ); ?>"
                                                    data-nonce="<?php echo esc_attr( wp_create_nonce('wp_ulike_pro_ajax_button_field') ); ?>">
                                                <?php echo esc_html( $tool['label'] ); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php elseif ( $current_tab === 'gdpr' ) : ?>
            <?php if ( ! WP_Ulike_Pro_API::has_permission() ) : ?>
                <div class="wp-ulike-pro-tools-card">
                    <div class="wp-ulike-pro-tools-card-header">
                        <h2><?php esc_html_e( 'License Required', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                    </div>
                    <div class="wp-ulike-pro-tools-card-content">
                        <p><?php esc_html_e( 'You need an active license to access GDPR tools.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <div class="wp-ulike-pro-tools-card">
                    <div class="wp-ulike-pro-tools-card-header">
                        <h2><?php esc_html_e( 'Remove User Logs', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                    </div>
                    <div class="wp-ulike-pro-tools-card-content">
                        <p><?php esc_html_e( 'Search and select users to remove all their records. This action will delete all logs (likes and dislikes) from all content types (posts, comments, activities, topics) and sync counters. This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ); ?></p>

                        <div class="wp-ulike-pro-gdpr-user-search">
                            <label for="wp-ulike-pro-user-search">
                                <?php esc_html_e( 'Search Users', WP_ULIKE_PRO_DOMAIN ); ?>
                            </label>
                            <div class="wp-ulike-pro-search-wrapper">
                                <input type="text"
                                       id="wp-ulike-pro-user-search"
                                       class="regular-text wp-ulike-pro-user-search-input"
                                       data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_search_users' ) ); ?>"
                                       placeholder="<?php esc_attr_e( 'Type to search...', WP_ULIKE_PRO_DOMAIN ); ?>">
                                <span class="wp-ulike-pro-search-spinner spinner"></span>
                            </div>
                            <div id="wp-ulike-pro-user-search-results" class="wp-ulike-pro-search-results"></div>
                        </div>

                        <div class="wp-ulike-pro-gdpr-selected-users">
                            <label>
                                <?php esc_html_e( 'Selected Users', WP_ULIKE_PRO_DOMAIN ); ?>
                                <span class="wp-ulike-pro-selected-count">(0)</span>
                            </label>
                            <div id="wp-ulike-pro-selected-users-list" class="wp-ulike-pro-selected-users-list">
                                <p class="wp-ulike-pro-empty-message"><?php esc_html_e( 'No users selected.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                            </div>
                        </div>

                            <p class="submit">
                            <button type="button"
                                    id="wp-ulike-pro-remove-user-votes"
                                    class="button button-primary wp-ulike-pro-remove-votes-btn"
                                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_remove_user_votes' ) ); ?>"
                                    disabled>
                                <?php esc_html_e( 'Remove Logs', WP_ULIKE_PRO_DOMAIN ); ?>
                            </button>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ( $current_tab === 'bulk-actions' ) : ?>
            <?php if ( ! WP_Ulike_Pro_API::has_permission() ) : ?>
                <div class="wp-ulike-pro-tools-card">
                    <div class="wp-ulike-pro-tools-card-header">
                        <h2><?php esc_html_e( 'License Required', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                    </div>
                    <div class="wp-ulike-pro-tools-card-content">
                        <p><?php esc_html_e( 'You need an active license to access bulk actions.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <div class="wp-ulike-pro-tools-card">
                    <div class="wp-ulike-pro-tools-card-header">
                        <h2><?php esc_html_e( 'Bulk Add Likes/Dislikes', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                    </div>
                    <div class="wp-ulike-pro-tools-card-content">
                        <p><?php esc_html_e( 'Filter and select posts to manage like/dislike counts. You can edit counts individually or apply bulk operations with specific or random values.', WP_ULIKE_PRO_DOMAIN ); ?></p>

                        <div class="wp-ulike-pro-bulk-filters">
                            <div class="wp-ulike-pro-filter-tabs">
                                <button type="button" class="wp-ulike-pro-filter-tab active" data-filter-mode="posts">
                                    <?php esc_html_e( 'Search Posts', WP_ULIKE_PRO_DOMAIN ); ?>
                                </button>
                                <button type="button" class="wp-ulike-pro-filter-tab" data-filter-mode="item-id">
                                    <?php esc_html_e( 'Search by Item ID', WP_ULIKE_PRO_DOMAIN ); ?>
                                </button>
                            </div>

                            <div class="wp-ulike-pro-filter-mode-content" data-filter-mode="posts">
                                <div class="wp-ulike-pro-filter-grid" data-filter-mode="posts">
                                    <div class="wp-ulike-pro-filter-group">
                                        <label for="wp-ulike-pro-bulk-post-type" class="wp-ulike-pro-filter-label">
                                            <?php esc_html_e( 'Post Type', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </label>
                                        <select id="wp-ulike-pro-bulk-post-type" class="wp-ulike-pro-filter-select" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_get_categories' ) ); ?>">
                                            <option value=""><?php esc_html_e( 'All Types', WP_ULIKE_PRO_DOMAIN ); ?></option>
                                            <?php
                                            $post_types = get_post_types( array( 'public' => true ), 'objects' );
                                            foreach ( $post_types as $post_type ) {
                                                echo '<option value="' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->label ) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="wp-ulike-pro-filter-group">
                                        <label for="wp-ulike-pro-bulk-taxonomy" class="wp-ulike-pro-filter-label">
                                            <?php esc_html_e( 'Taxonomy', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </label>
                                        <div class="wp-ulike-pro-taxonomy-wrapper">
                                            <select id="wp-ulike-pro-bulk-taxonomy" class="wp-ulike-pro-filter-select">
                                                <option value=""><?php esc_html_e( 'Select Taxonomy', WP_ULIKE_PRO_DOMAIN ); ?></option>
                                            </select>
                                            <span class="spinner wp-ulike-pro-taxonomy-spinner" style="float: none; margin: 0 0 0 8px; visibility: hidden;"></span>
                                        </div>
                                    </div>

                                    <div class="wp-ulike-pro-filter-group">
                                        <label for="wp-ulike-pro-bulk-category" class="wp-ulike-pro-filter-label" id="wp-ulike-pro-category-label">
                                            <?php esc_html_e( 'Category', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </label>
                                        <div class="wp-ulike-pro-category-wrapper">
                                            <select id="wp-ulike-pro-bulk-category" class="wp-ulike-pro-filter-select" disabled>
                                                <option value=""><?php esc_html_e( 'All Categories', WP_ULIKE_PRO_DOMAIN ); ?></option>
                                            </select>
                                            <span class="spinner wp-ulike-pro-category-spinner" style="float: none; margin: 0 0 0 8px; visibility: hidden;"></span>
                                        </div>
                                    </div>

                                    <div class="wp-ulike-pro-filter-group wp-ulike-pro-filter-group-search">
                                        <label for="wp-ulike-pro-bulk-search" class="wp-ulike-pro-filter-label">
                                            <?php esc_html_e( 'Search', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </label>
                                        <input type="text"
                                               id="wp-ulike-pro-bulk-search"
                                               class="wp-ulike-pro-filter-input"
                                               placeholder="<?php esc_attr_e( 'Search by title...', WP_ULIKE_PRO_DOMAIN ); ?>">
                                    </div>

                                    <div class="wp-ulike-pro-filter-group wp-ulike-pro-filter-group-button">
                                        <label class="wp-ulike-pro-filter-label">&nbsp;</label>
                                        <button type="button" id="wp-ulike-pro-bulk-search-btn" class="button button-primary wp-ulike-pro-search-button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_search_posts' ) ); ?>">
                                            <span class="wp-ulike-pro-search-button-text"><?php esc_html_e( 'Search Posts', WP_ULIKE_PRO_DOMAIN ); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="wp-ulike-pro-filter-mode-content" data-filter-mode="item-id" style="display: none;">
                                <div class="wp-ulike-pro-filter-grid" data-filter-mode="item-id">
                                    <div class="wp-ulike-pro-filter-group">
                                        <label for="wp-ulike-pro-bulk-item-type" class="wp-ulike-pro-filter-label">
                                            <?php esc_html_e( 'Type', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </label>
                                        <select id="wp-ulike-pro-bulk-item-type" class="wp-ulike-pro-filter-select">
                                            <option value=""><?php esc_html_e( 'All Types', WP_ULIKE_PRO_DOMAIN ); ?></option>
                                            <option value="post"><?php esc_html_e( 'Post', WP_ULIKE_PRO_DOMAIN ); ?></option>
                                            <option value="comment"><?php esc_html_e( 'Comment', WP_ULIKE_PRO_DOMAIN ); ?></option>
                                            <?php if ( defined( 'BP_VERSION' ) ) : ?>
                                                <option value="activity"><?php esc_html_e( 'Activity', WP_ULIKE_PRO_DOMAIN ); ?></option>
                                            <?php endif; ?>
                                            <?php if ( function_exists( 'is_bbpress' ) ) : ?>
                                                <option value="topic"><?php esc_html_e( 'Topic', WP_ULIKE_PRO_DOMAIN ); ?></option>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="wp-ulike-pro-filter-group wp-ulike-pro-filter-group-search">
                                        <label for="wp-ulike-pro-bulk-item-id" class="wp-ulike-pro-filter-label">
                                            <?php esc_html_e( 'Item ID(s)', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </label>
                                        <input type="text"
                                               id="wp-ulike-pro-bulk-item-id"
                                               class="wp-ulike-pro-filter-input"
                                               placeholder="<?php esc_attr_e( 'Enter item ID(s) separated by comma...', WP_ULIKE_PRO_DOMAIN ); ?>">
                                        <p class="description" style="margin-top: 6px; margin-bottom: 0; font-size: 12px; color: #646970;">
                                            <?php esc_html_e( 'Enter one or more item IDs separated by commas. Leave empty to search all items of selected type.', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </p>
                                    </div>

                                    <div class="wp-ulike-pro-filter-group wp-ulike-pro-filter-group-button">
                                        <label class="wp-ulike-pro-filter-label">&nbsp;</label>
                                        <button type="button" id="wp-ulike-pro-bulk-search-item-id-btn" class="button button-primary wp-ulike-pro-search-button" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_search_by_item_id' ) ); ?>">
                                            <span class="wp-ulike-pro-search-button-text"><?php esc_html_e( 'Search Items', WP_ULIKE_PRO_DOMAIN ); ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="wp-ulike-pro-bulk-results" style="display: none;" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_get_post_counts' ) ); ?>">
                            <div class="wp-ulike-pro-bulk-results-header">
                                <h3><?php esc_html_e( 'Search Results', WP_ULIKE_PRO_DOMAIN ); ?> <span class="wp-ulike-pro-results-count">(0)</span></h3>
                                <div class="wp-ulike-pro-bulk-select-actions">
                                    <button type="button" class="button-link wp-ulike-pro-select-all"><?php esc_html_e( 'Select All', WP_ULIKE_PRO_DOMAIN ); ?></button>
                                    <span class="wp-ulike-pro-separator">|</span>
                                    <button type="button" class="button-link wp-ulike-pro-select-none"><?php esc_html_e( 'Select None', WP_ULIKE_PRO_DOMAIN ); ?></button>
                                </div>
                            </div>
                            <div id="wp-ulike-pro-bulk-posts-list" class="wp-ulike-pro-bulk-posts-list"></div>
                        </div>

                        <div class="wp-ulike-pro-bulk-selected" style="display: none;">
                            <h3><?php esc_html_e( 'Selected Posts', WP_ULIKE_PRO_DOMAIN ); ?> <span class="wp-ulike-pro-selected-count">(0)</span></h3>

                            <div class="wp-ulike-pro-bulk-actions-panel">
                                <div class="wp-ulike-pro-bulk-actions-tabs">
                                    <button type="button" class="wp-ulike-pro-bulk-action-tab active" data-mode="individual">
                                        <?php esc_html_e( 'Individual Edit', WP_ULIKE_PRO_DOMAIN ); ?>
                                    </button>
                                    <button type="button" class="wp-ulike-pro-bulk-action-tab" data-mode="bulk">
                                        <?php esc_html_e( 'Bulk Operations', WP_ULIKE_PRO_DOMAIN ); ?>
                                    </button>
                                </div>

                                <div class="wp-ulike-pro-bulk-mode-content" data-mode="individual">
                                    <div class="wp-ulike-pro-bulk-posts-table-wrapper">
                                        <table class="wp-list-table widefat fixed striped">
                                            <thead>
                                                <tr>
                                                    <th class="column-post" style="width: 40%;"><?php esc_html_e( 'Post', WP_ULIKE_PRO_DOMAIN ); ?></th>
                                                    <th class="column-current-likes" style="width: 15%;"><?php esc_html_e( 'Current Likes', WP_ULIKE_PRO_DOMAIN ); ?></th>
                                                    <th class="column-new-likes" style="width: 15%;"><?php esc_html_e( 'New Likes', WP_ULIKE_PRO_DOMAIN ); ?></th>
                                                    <th class="column-current-dislikes" style="width: 15%;"><?php esc_html_e( 'Current Dislikes', WP_ULIKE_PRO_DOMAIN ); ?></th>
                                                    <th class="column-new-dislikes" style="width: 15%;"><?php esc_html_e( 'New Dislikes', WP_ULIKE_PRO_DOMAIN ); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="wp-ulike-pro-bulk-posts-table-body">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="wp-ulike-pro-bulk-mode-content" data-mode="bulk" style="display: none;">
                                    <div class="wp-ulike-pro-bulk-operations">
                                        <div class="wp-ulike-pro-bulk-operation-type">
                                            <label>
                                                <input type="radio" name="bulk_operation_type" value="add" checked>
                                                <?php esc_html_e( 'Add specific number', WP_ULIKE_PRO_DOMAIN ); ?>
                                            </label>
                                            <label>
                                                <input type="radio" name="bulk_operation_type" value="set">
                                                <?php esc_html_e( 'Set exact number', WP_ULIKE_PRO_DOMAIN ); ?>
                                            </label>
                                            <label>
                                                <input type="radio" name="bulk_operation_type" value="random">
                                                <?php esc_html_e( 'Add random numbers', WP_ULIKE_PRO_DOMAIN ); ?>
                                            </label>
                                        </div>

                                        <div class="wp-ulike-pro-bulk-operation-values" data-operation="add">
                                            <table class="form-table" role="presentation">
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="wp-ulike-pro-bulk-add-likes"><?php esc_html_e( 'Add Likes', WP_ULIKE_PRO_DOMAIN ); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="wp-ulike-pro-bulk-add-likes" class="small-text" min="0" value="0" step="1">
                                                            <p class="description"><?php esc_html_e( 'This number will be added to current likes for each post.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="wp-ulike-pro-bulk-add-dislikes"><?php esc_html_e( 'Add Dislikes', WP_ULIKE_PRO_DOMAIN ); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="wp-ulike-pro-bulk-add-dislikes" class="small-text" min="0" value="0" step="1">
                                                            <p class="description"><?php esc_html_e( 'This number will be added to current dislikes for each post.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="wp-ulike-pro-bulk-operation-values" data-operation="set" style="display: none;">
                                            <table class="form-table" role="presentation">
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="wp-ulike-pro-bulk-set-likes"><?php esc_html_e( 'Set Likes', WP_ULIKE_PRO_DOMAIN ); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="wp-ulike-pro-bulk-set-likes" class="small-text" min="0" value="0" step="1">
                                                            <p class="description"><?php esc_html_e( 'All selected posts will have this exact number of likes.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="wp-ulike-pro-bulk-set-dislikes"><?php esc_html_e( 'Set Dislikes', WP_ULIKE_PRO_DOMAIN ); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="wp-ulike-pro-bulk-set-dislikes" class="small-text" min="0" value="0" step="1">
                                                            <p class="description"><?php esc_html_e( 'All selected posts will have this exact number of dislikes.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="wp-ulike-pro-bulk-operation-values" data-operation="random" style="display: none;">
                                            <table class="form-table" role="presentation">
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="wp-ulike-pro-bulk-random-likes-min"><?php esc_html_e( 'Random Likes Range', WP_ULIKE_PRO_DOMAIN ); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="wp-ulike-pro-bulk-random-likes-min" class="small-text" min="0" value="0" step="1" placeholder="Min">
                                                            <span> - </span>
                                                            <input type="number" id="wp-ulike-pro-bulk-random-likes-max" class="small-text" min="0" value="10" step="1" placeholder="Max">
                                                            <p class="description"><?php esc_html_e( 'Random number of likes will be added to each post within this range.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <label for="wp-ulike-pro-bulk-random-dislikes-min"><?php esc_html_e( 'Random Dislikes Range', WP_ULIKE_PRO_DOMAIN ); ?></label>
                                                        </th>
                                                        <td>
                                                            <input type="number" id="wp-ulike-pro-bulk-random-dislikes-min" class="small-text" min="0" value="0" step="1" placeholder="Min">
                                                            <span> - </span>
                                                            <input type="number" id="wp-ulike-pro-bulk-random-dislikes-max" class="small-text" min="0" value="5" step="1" placeholder="Max">
                                                            <p class="description"><?php esc_html_e( 'Random number of dislikes will be added to each post within this range.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="submit wp-ulike-pro-bulk-apply-submit">
                                <button type="button" id="wp-ulike-pro-bulk-apply" class="button button-primary wp-ulike-pro-bulk-apply-btn" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_bulk_update_likes' ) ); ?>" disabled>
                                    <span class="wp-ulike-pro-apply-button-text"><?php esc_html_e( 'Apply Changes', WP_ULIKE_PRO_DOMAIN ); ?></span>
                                </button>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif ( $current_tab === 'rest-api' ) : ?>
            <?php if ( ! WP_Ulike_Pro_API::has_permission() ) : ?>
                <div class="wp-ulike-pro-tools-card">
                    <div class="wp-ulike-pro-tools-card-header">
                        <h2><?php esc_html_e( 'License Required', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                    </div>
                    <div class="wp-ulike-pro-tools-card-content">
                        <p><?php esc_html_e( 'You need an active license to access REST API settings.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                    </div>
                </div>
            <?php else : ?>
                <?php
                // Get current settings
                $settings = WP_Ulike_Pro_Tools::get_rest_api_settings_data();
                $enable_rest_api = $settings['enable_rest_api'];
                $authentication_type = $settings['authentication_type'];
                $read_permissions = $settings['rest_api_permission_for_readable_routes'];
                $write_permissions = $settings['rest_api_permission_for_writable_routes'];
                $enable_auto_user_id = $settings['enable_auto_user_id'];

                // Get all user roles
                $roles = wp_roles()->get_names();

                // Show success message
                if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
                    echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 20px 0;"><p>' . esc_html__( 'Settings saved successfully.', WP_ULIKE_PRO_DOMAIN ) . '</p></div>';
                }
                ?>
                <form method="post" action="" class="wp-ulike-pro-rest-api-settings-form">
                    <?php wp_nonce_field( 'wp_ulike_rest_api_settings', 'wp_ulike_rest_api_settings_nonce' ); ?>
                    <input type="hidden" name="wp_ulike_rest_api_settings_save" value="1">

                    <div class="wp-ulike-pro-tools-card">
                        <div class="wp-ulike-pro-tools-card-header">
                            <h2><?php esc_html_e( 'REST API Settings', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                        </div>
                        <div class="wp-ulike-pro-tools-card-content">
                            <p><?php esc_html_e( 'Configure REST API access and authentication settings for external applications.', WP_ULIKE_PRO_DOMAIN ); ?></p>

                            <div class="wp-ulike-pro-rest-api-settings">
                                <div class="wp-ulike-pro-rest-api-setting-group">
                                    <label for="enable_rest_api" class="wp-ulike-pro-rest-api-label">
                                        <input type="checkbox" name="enable_rest_api" id="enable_rest_api" value="1" <?php checked( $enable_rest_api, true ); ?>>
                                        <strong><?php esc_html_e( 'Enable REST API', WP_ULIKE_PRO_DOMAIN ); ?></strong>
                                    </label>
                                    <p class="description"><?php esc_html_e( 'Expose WP ULike data through WordPress REST API endpoints, allowing external applications to access like counts, user votes, and statistics.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                </div>

                                <div class="wp-ulike-pro-rest-api-setting-group rest-api-dependent" style="<?php echo $enable_rest_api ? '' : 'display:none;'; ?>">
                                    <label for="authentication_type" class="wp-ulike-pro-rest-api-label">
                                        <?php esc_html_e( 'Authentication Type', WP_ULIKE_PRO_DOMAIN ); ?>
                                    </label>
                                    <div class="wp-ulike-pro-rest-api-radio-group">
                                        <label>
                                            <input type="radio" name="authentication_type" value="login" <?php checked( $authentication_type, 'login' ); ?>>
                                            <?php esc_html_e( 'User Login', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </label>
                                        <label>
                                            <input type="radio" name="authentication_type" value="token" <?php checked( $authentication_type, 'token' ); ?>>
                                            <?php esc_html_e( 'Custom Keys', WP_ULIKE_PRO_DOMAIN ); ?>
                                        </label>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Choose how API requests are authenticated. User Login uses WordPress user credentials, Custom Keys uses API tokens.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                </div>

                                <div class="wp-ulike-pro-rest-api-setting-group rest-api-dependent login-auth-dependent" style="<?php echo ( $enable_rest_api && $authentication_type === 'login' ) ? '' : 'display:none;'; ?>">
                                    <label for="rest_api_permission_for_readable_routes" class="wp-ulike-pro-rest-api-label">
                                        <?php esc_html_e( 'Read-Only Route Access', WP_ULIKE_PRO_DOMAIN ); ?>
                                    </label>
                                    <select name="rest_api_permission_for_readable_routes[]" id="rest_api_permission_for_readable_routes" multiple="multiple" class="wp-ulike-pro-rest-api-select">
                                        <?php foreach ( $roles as $role_key => $role_name ) : ?>
                                            <option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $read_permissions ), true ); ?>>
                                                <?php echo esc_html( $role_name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Choose which user roles can access API endpoints that retrieve data (GET requests).', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                </div>

                                <div class="wp-ulike-pro-rest-api-setting-group rest-api-dependent login-auth-dependent" style="<?php echo ( $enable_rest_api && $authentication_type === 'login' ) ? '' : 'display:none;'; ?>">
                                    <label for="rest_api_permission_for_writable_routes" class="wp-ulike-pro-rest-api-label">
                                        <?php esc_html_e( 'Write Route Access', WP_ULIKE_PRO_DOMAIN ); ?>
                                    </label>
                                    <select name="rest_api_permission_for_writable_routes[]" id="rest_api_permission_for_writable_routes" multiple="multiple" class="wp-ulike-pro-rest-api-select">
                                        <?php foreach ( $roles as $role_key => $role_name ) : ?>
                                            <option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( in_array( $role_key, $write_permissions ), true ); ?>>
                                                <?php echo esc_html( $role_name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Choose which user roles can access API endpoints that modify data (POST/PUT/DELETE requests).', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                </div>

                                <div class="wp-ulike-pro-rest-api-setting-group rest-api-dependent" style="<?php echo $enable_rest_api ? '' : 'display:none;'; ?>">
                                    <label for="enable_auto_user_id" class="wp-ulike-pro-rest-api-label">
                                        <input type="checkbox" name="enable_auto_user_id" id="enable_auto_user_id" value="1" <?php checked( $enable_auto_user_id, true ); ?>>
                                        <strong><?php esc_html_e( 'Enable Auto User ID', WP_ULIKE_PRO_DOMAIN ); ?></strong>
                                    </label>
                                    <p class="description"><?php esc_html_e( 'Automatically use the authenticated user\'s ID for API requests when no user ID is specified.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                                </div>
                            </div>

                            <p class="submit">
                                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', WP_ULIKE_PRO_DOMAIN ); ?>">
                            </p>
                        </div>
                    </div>

                    <div class="wp-ulike-pro-tools-card token-auth-dependent" style="<?php echo ( $enable_rest_api && $authentication_type === 'token' ) ? '' : 'display:none;'; ?>">
                        <div class="wp-ulike-pro-tools-card-header">
                            <h2><?php esc_html_e( 'API Keys Management', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                        </div>
                        <div class="wp-ulike-pro-tools-card-content">
                            <?php WP_Ulike_Pro_Tools::render_api_keys_section(); ?>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

        <?php elseif ( $current_tab === 'debug' ) : ?>
            <div class="wp-ulike-pro-tools-card">
                <div class="wp-ulike-pro-tools-card-header">
                    <h2><?php esc_html_e( 'System Information', WP_ULIKE_PRO_DOMAIN ); ?></h2>
                </div>
                <div class="wp-ulike-pro-tools-card-content">
                    <p><?php esc_html_e( 'Copy the information below and send it to support when requesting help. This information helps us troubleshoot your issue more effectively.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                    <p style="color: #46b450; font-size: 13px; margin-top: 10px;">
                        <strong><?php esc_html_e( 'Privacy Note:', WP_ULIKE_PRO_DOMAIN ); ?></strong>
                        <?php esc_html_e( 'This information is safe to share. It contains no passwords, license keys, or sensitive personal data—only system configuration details needed for technical support.', WP_ULIKE_PRO_DOMAIN ); ?>
                    </p>
                    <textarea readonly="readonly" onclick="this.focus();this.select()" id="wp-ulike-debug-info" class="large-text wp-ulike-pro-debug-textarea" rows="20"><?php echo esc_textarea( $debug_info ); ?></textarea>
                    <p class="submit">
                        <button type="button" class="button button-primary" id="wp-ulike-copy-debug-info">
                            <?php esc_html_e( 'Copy to Clipboard', WP_ULIKE_PRO_DOMAIN ); ?>
                        </button>
                        <button type="button" class="button" id="wp-ulike-download-debug-info">
                            <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 4px;"></span>
                            <?php esc_html_e( 'Download Logs', WP_ULIKE_PRO_DOMAIN ); ?>
                        </button>
                        <span class="wp-ulike-pro-copy-success">
                            <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Copied!', WP_ULIKE_PRO_DOMAIN ); ?>
                        </span>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

