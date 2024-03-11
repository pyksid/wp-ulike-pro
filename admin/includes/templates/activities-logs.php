<?php
/**
 * Activities Logs template
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}
?>

<div id="wp-ulike-logs-app" data-table-name="ulike_activities" class="wrap wp-ulike-container wp-ulike-stats-panel">
	<div class="wp-ulike-row">
		<div class="col-12">
			<h2 class="wp-ulike-page-title"><?php esc_html_e( 'Activities Vote Logs', WP_ULIKE_PRO_DOMAIN ); ?></h2>
		</div>
	</div>
	<div class="wp-ulike-row">
		<div class="col-12">
			<vue-good-table
			mode="remote"
			@on-page-change="onPageChange"
			@on-sort-change="onSortChange"
			@on-per-page-change="onPerPageChange"
			@on-search="onSearch"
			@on-select-all="selectionChanged"
			@on-selected-rows-change="selectionChanged"
			:rtl="<?php echo is_rtl() ? 'true' : 'false'; ?>"
			:total-rows="totalRecords"
			:columns='<?php echo json_encode( wp_ulike_pro_get_admin_logs_columns('activity') ); ?>'
			:rows="rows"
			:pagination-options="{
				enabled: true,
				mode: 'records',
 				perPage: 15,
				position: 'top',
				perPageDropdown: [25, 50, 100],
				dropdownAllowAll: true,
				ofLabel: 'of',
				allLabel: 'All',
			}"
			:select-options="{
				enabled: true,
				selectOnCheckboxOnly: true
			}"
			:search-options="{
				enabled: true
			}">
			<div slot="table-actions">
				<button id="wp-ulike-remove-logs" style="display:none" class="button button-primary"><?php esc_html_e( 'Delete', WP_ULIKE_PRO_DOMAIN ); ?></button>
				<button id="wp-ulike-export-logs" class="button button-secondary"><?php esc_html_e( 'Export CSV', WP_ULIKE_PRO_DOMAIN ); ?></button>
			</div>
			</vue-good-table>
		</div>
	</div>
</div>