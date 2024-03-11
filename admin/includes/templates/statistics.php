<?php
/**
 * Statistics page template
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}

	// wp_ulike_stats class instance
	$wp_ulike_stats = WP_Ulike_Pro_Stats::get_instance();
	// get tables info
	$get_tables     = $wp_ulike_stats->get_tables();

	if( ! WP_Ulike_Pro_API::has_permission() ) {
?>
<div class="wrap wp-ulike-container">
	<div class="wp-ulike-row wp-ulike-empty-stats">
		<div class="col-12">
			<div class="wp-ulike-icon">
				<i class="wp-ulike-icons-key"></i>
			</div>
			<div class="wp-ulike-info">
				<?php echo sprintf( '<p>%s</p><a class="button" href="%s">%s</a>', esc_html__( 'Features of the Pro version are only available once you have registered your license. If you don\'t yet have a license key, get WP ULike Pro now.' , WP_ULIKE_PRO_DOMAIN ), self_admin_url( 'admin.php?page=wp-ulike-pro-license' ), esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ) ); ?>
			</div>
		</div>
	</div>
</div>
<?php
		exit;
	}

	if( empty( $get_tables ) ) {
?>
<div class="wrap wp-ulike-container">
	<div class="wp-ulike-row wp-ulike-empty-stats">
		<div class="col-12">
			<div class="wp-ulike-icon">
				<i class="wp-ulike-icons-hourglass"></i>
			</div>
			<div class="wp-ulike-info">
				<?php echo esc_html__( 'No data found! This is because there is still no data in your database.', WP_ULIKE_PRO_DOMAIN ); ?>
			</div>
		</div>
	</div>
</div>
<?php
		exit;
	}
?>
<div id="wp-ulike-stats-app" class="wrap wp-ulike-container wp-ulike-stats-panel">
	<div class="wp-ulike-row">
		<div class="col-12">
			<h2 class="wp-ulike-page-title"><?php esc_html_e( 'WP ULike Statistics', WP_ULIKE_PRO_DOMAIN ); ?></h2>
		</div>
	</div>
    <div class="wp-ulike-row wp-ulike-logs-count">
        <div class="col-4">
			<div class="wp-ulike-inner">
				<div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
					<div class="col-5">
						<div class="wp-ulike-icon">
							<i class="wp-ulike-icons-linegraph"></i>
						</div>
					</div>
					<div class="col-7">
						<get-var dataset="count_all_logs_all" inline-template>
							<span class="wp-ulike-var" v-html="output"></span>
						</get-var>
						<span class="wp-ulike-text"><?php esc_html_e( 'Total', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</div>
				</div>
			</div>
        </div>
        <div class="col-4">
			<div class="wp-ulike-inner">
				<div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
					<div class="col-5">
						<div class="wp-ulike-icon">
							<i class="wp-ulike-icons-hourglass"></i>
						</div>
					</div>
					<div class="col-7">
						<get-var dataset="count_all_logs_today" inline-template>
							<span class="wp-ulike-var" v-html="output"></span>
						</get-var>
						<span class="wp-ulike-text"><?php esc_html_e( 'Today', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</div>
				</div>
			</div>
        </div>
        <div class="col-4">
			<div class="wp-ulike-inner">
				<div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
					<div class="col-5">
						<div class="wp-ulike-icon">
							<i class="wp-ulike-icons-bargraph"></i>
						</div>
					</div>
					<div class="col-7">
						<get-var dataset="count_all_logs_yesterday" inline-template>
							<span class="wp-ulike-var" v-html="output"></span>
						</get-var>
						<span class="wp-ulike-text"><?php esc_html_e( 'Yesterday', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</div>
				</div>
			</div>
        </div>
    </div>
<?php
	foreach ( $get_tables as $type => $table):
?>
	<div class="wp-ulike-row wp-ulike-summary-charts">
	    <div class="col-12">
	        <div class="wp-ulike-inner">
		    	<div class="wp-ulike-header">
		    		<h3 class="wp-ulike-widget-title">
						<?php echo $type . ' ' .  esc_html__( 'Statistics', WP_ULIKE_PRO_DOMAIN ); ?>
		    		</h3>
		    		<a target="_blank" href="admin.php?page=wp-ulike-<?php echo $type; ?>-logs" class="wp-ulike-button">
		    			<?php esc_html_e( 'View All Logs', WP_ULIKE_PRO_DOMAIN ); ?>
		    		</a>
		    	</div>
	            <div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
	                <div class="col-8">
						<chart-options option-type="chart" identify="wp-ulike-<?php echo $type; ?>-chart" dataset="dataset_<?php echo $table; ?>" inline-template>
							<div class="wp-ulike-chart-options wp-ulike-row">
								<div class="col-6 wp-ulike-flex wp-ulike-flex-start">
									<div class="wp-ulike-datarange">
										<?php echo sprintf( '<div class="wp-ulike-control-label">%s</div>', esc_html__( 'Select Date Range:', WP_ULIKE_PRO_DOMAIN ) ); ?>
										<vue-rangedate-picker @selected="onDateSelected" :preset-ranges="presetRanges" :init-range="selectedDate" i18n="EN"  format="DD-MMM-YYYY"></vue-rangedate-picker>
									</div>
								</div>
								<div class="col-6 wp-ulike-flex wp-ulike-flex-end">
									<div class="wp-ulike-selected">
									<?php echo sprintf( '<div class="wp-ulike-control-label">%s</div>', esc_html__( 'Select Status:', WP_ULIKE_PRO_DOMAIN ) ); ?>
										<select-2 :options="<?php echo htmlspecialchars(json_encode( $wp_ulike_stats->get_table_status_list( $table ) ), ENT_QUOTES, 'UTF-8'); ?>" ref="chart-options" name="selectStatus" v-model="selected" multiple=true></select-2>
									</div>
								</div>
							</div>
						</chart-options>
						<get-chart type="line" identify="wp-ulike-<?php echo $type; ?>-chart" dataset="dataset_<?php echo $table; ?>" inline-template>
							<canvas id="wp-ulike-<?php echo $type; ?>-chart"></canvas>
						</get-chart>
	                </div>
	                <div class="col-4">
	                    <div class="wp-ulike-flex">
	                        <div class="wp-ulike-icon">
								<i class="wp-ulike-icons-magnifying-glass"></i>
							</div>
							<div class="wp-ulike-info">
		                        <get-var dataset="count_logs_<?php echo $table; ?>_week" inline-template>
									<span class="wp-ulike-var" v-html="output"></span>
		                        </get-var>
		                        <span class="wp-ulike-text"><?php esc_html_e( 'Weekly', WP_ULIKE_PRO_DOMAIN ); ?></span>
	                       	</div>
	                    </div>
	                    <div class="wp-ulike-flex">
	                        <div class="wp-ulike-icon">
								<i class="wp-ulike-icons-bargraph"></i>
							</div>
							<div class="wp-ulike-info">
		                        <get-var dataset="count_logs_<?php echo $table; ?>_month" inline-template>
									<span class="wp-ulike-var" v-html="output"></span>
		                        </get-var>
		                        <span class="wp-ulike-text"><?php esc_html_e( 'Monthly', WP_ULIKE_PRO_DOMAIN ); ?></span>
	                       	</div>
	                    </div>
	                    <div class="wp-ulike-flex">
	                        <div class="wp-ulike-icon">
								<i class="wp-ulike-icons-linegraph"></i>
							</div>
							<div class="wp-ulike-info">
		                        <get-var dataset="count_logs_<?php echo $table; ?>_year" inline-template>
									<span class="wp-ulike-var" v-html="output"></span>
		                        </get-var>
		                        <span class="wp-ulike-text"><?php esc_html_e( 'Yearly', WP_ULIKE_PRO_DOMAIN ); ?></span>
	                       	</div>
	                    </div>
	                    <div class="wp-ulike-flex">
	                        <div class="wp-ulike-icon">
								<i class="wp-ulike-icons-global"></i>
							</div>
							<div class="wp-ulike-info">
		                        <get-var dataset="count_logs_<?php echo $table; ?>_all" inline-template>
									<span class="wp-ulike-var" v-html="output"></span>
		                        </get-var>
		                        <span class="wp-ulike-text"><?php esc_html_e( 'Totally',WP_ULIKE_PRO_DOMAIN ); ?></span>
	                       	</div>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
<?php
	endforeach;
?>
	<div class="wp-ulike-row wp-ulike-percent-charts wp-ulike-flex">
	    <div class="col-6">
	        <div class="wp-ulike-inner wp-ulike-match-height">
				<div class="wp-ulike-header">
		    		<h3 class="wp-ulike-widget-title">
						<?php esc_html_e( 'Allocation Statistics', WP_ULIKE_PRO_DOMAIN ); ?>
		    		</h3>
				</div>
            	<div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
                	<div class="col-12">
	                    <div class="wp-ulike-draw-chart">
							<get-chart type="pie" identify="wp-ulike-percent-chart" dataset="" inline-template>
								<canvas id="wp-ulike-percent-chart"></canvas>
							</get-chart>
	                    </div>
                	</div>
                </div>
	        </div>
	    </div>
	    <div class="col-6">
	        <div class="wp-ulike-inner wp-ulike-match-height">
				<div class="wp-ulike-header">
					<chart-options option-type="list" identify="wp-ulike-top-likers" dataset="get_top_likers" inline-template>
						<div class="wp-ulike-chart-options wp-ulike-flex wp-ulike-row">
							<div class="col-6 wp-ulike-flex  wp-ulike-flex-start">
								<h3 class="wp-ulike-widget-title">
									<?php echo sprintf( '%s <span class="wp-ulike-peroid-info">%s: "%s"</span>', esc_html__( 'Top Likers', WP_ULIKE_PRO_DOMAIN ), esc_html__( 'In', WP_ULIKE_PRO_DOMAIN ), wp_ulike_pro_get_daterange_of_tops( 'likers' ) ); ?>
								</h3>
							</div>
							<div class="col-6 wp-ulike-flex wp-ulike-flex-end">
								<div class="wp-ulike-datarange">
									<?php echo sprintf( '<div class="wp-ulike-control-label">%s</div>', esc_html__( 'Select Date Range:', WP_ULIKE_PRO_DOMAIN ) ); ?>
									<vue-rangedate-picker @selected="onDateSelected" :preset-ranges="presetRanges" :init-range="selectedDate" i18n="EN"  righttoleft="true"  format="DD-MMM-YYYY"></vue-rangedate-picker>
								</div>
							</div>
						</div>
					</chart-options>
				</div>
            	<div class="wp-ulike-row wp-ulike-flex wp-ulike-is-loading">
                	<div class="col-12">
						<div class="wp-ulike-top-likers">
							<get-var dataset="get_top_likers" inline-template>
								<div class="wp-ulike-var" v-html="output"></div>
							</get-var>
	                	</div>
                	</div>
                </div>
	        </div>
	    </div>
	</div>
<?php
	foreach ( $get_tables as $type => $table):
?>
	<div class="wp-ulike-row wp-ulike-get-tops">
	    <div class="col-12">
	        <div class="wp-ulike-inner">
				<div class="wp-ulike-header">
					<chart-options option-type="list" identify="wp-ulike-top-<?php echo $type; ?>" dataset="get_top_<?php echo $type; ?>" inline-template>
						<div class="wp-ulike-chart-options wp-ulike-flex wp-ulike-row">
							<div class="col-6 wp-ulike-flex  wp-ulike-flex-start">
								<h3 class="wp-ulike-widget-title">
									<?php echo sprintf( '%s %s <span class="wp-ulike-peroid-info">%s: "%s"</span>', esc_html__( 'Top', WP_ULIKE_PRO_DOMAIN ), $type, esc_html__( 'In', WP_ULIKE_PRO_DOMAIN ), wp_ulike_pro_get_daterange_of_tops( $type ) ); ?>
								</h3>
							</div>
							<div class="col-6 wp-ulike-flex wp-ulike-flex-end">
								<div class="wp-ulike-row">
									<div class="col-<?php echo $type === 'posts' ? '6' : '12' ?>">
										<div class="wp-ulike-datarange">
											<?php echo sprintf( '<div class="wp-ulike-control-label">%s</div>', esc_html__( 'Select Date Range:', WP_ULIKE_PRO_DOMAIN ) ); ?>
											<vue-rangedate-picker @selected="onDateSelected" :preset-ranges="presetRanges" :init-range="selectedDate" i18n="EN"  righttoleft="true"  format="DD-MMM-YYYY"></vue-rangedate-picker>
										</div>
									</div>
									<?php if( $type === 'posts' ): ?>
									<div class="col-6">
										<div class="wp-ulike-selected">
											<?php echo sprintf( '<div class="wp-ulike-control-label">%s</div>', esc_html__( 'Select Post Type:', WP_ULIKE_PRO_DOMAIN ) ); ?>
											<select-2 :options="<?php echo htmlspecialchars(json_encode( wp_ulike_pro_get_public_post_types() ), ENT_QUOTES, 'UTF-8'); ?>" ref="chart-options" name="filterPostType" v-model="selected" multiple=true></select-2>
										</div>
									</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</chart-options>
				</div>
				<div class="wp-ulike-row wp-ulike-is-loading">
					<div class="col-12">
						<div class="wp-ulike-tops-list wp-ulike-top-<?php echo $type; ?>">
							<get-var dataset="get_top_<?php echo $type; ?>" inline-template>
								<ul class="wp-ulike-var" v-html="output"></ul>
							</get-var>
						</div>
					</div>
            	</div>
	        </div>
	    </div>
	</div>
<?php
	endforeach;
?>
</div>