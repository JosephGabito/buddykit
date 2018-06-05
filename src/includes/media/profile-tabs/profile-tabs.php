<?php
namespace BuddyKit\Media;

class Setup_Profile_Tabs {

	var $view_image_slug = 'media-files';
	var $tab_parent = 'profile';

	public function __construct() {
		add_action( 'bp_setup_nav', array( $this, 'setup_tabs'), 100 );
	}

	public function setup_tabs () {

		bp_core_new_subnav_item( array(
			'name'              => esc_html__('Media', 'buddykit'),
			'slug'              => $this->view_image_slug,
			'parent_url'        => trailingslashit( bp_displayed_user_domain() . $this->tab_parent ),
			'parent_slug'       => $this->tab_parent,
			'screen_function'   => array( $this, 'screen_function_callback'),
			'position'          => 10,
			'user_has_access'   => true
		));		

	}

	public function screen_function_callback() {
		add_action( 'bp_template_title', array($this, 'view_photos_title' ) );
		add_action( 'bp_template_content', array($this, 'view_photos_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	public function view_photos_content() { 
		$this->setup_content_tabs();
		$this->setup_content_body();
	}

	public function view_photos_title() {
		esc_html_e('Media Files', 'buddykit');
	}

	public function setup_content_tabs() {
		$current_tab = filter_input(INPUT_GET, 'show', FILTER_SANITIZE_STRING );
		?>
		<ul id="buddykit-profile-tabs-media-tab">
			<li <?php echo 'videos' !== $current_tab ? 'class="active"': ''; ?> >
				<a href="<?php echo esc_url(trailingslashit( bp_displayed_user_domain() . $this->tab_parent  ) . $this->view_image_slug ); ?>#item-body" title="<?php esc_attr_e('Photos', 'buddykit'); ?>">
					<?php esc_html_e('Photos', 'buddykit'); ?>
				</a>
			</li>
			<li <?php echo 'videos' === $current_tab ? 'class="active"': ''; ?> >
				<a href="<?php echo esc_url(trailingslashit( bp_displayed_user_domain() . $this->tab_parent  ) . $this->view_image_slug ); ?>?show=videos#item-body" title="<?php esc_attr_e('Videos', 'buddykit'); ?>">
					<?php esc_html_e('Videos', 'buddykit'); ?>
				</a>
			</li>
		</ul>
		<?php
	}

	public function setup_content_body() {
		$current_tab = filter_input(INPUT_GET, 'show', FILTER_SANITIZE_STRING );
		if ( 'videos' === $current_tab ) {
			$videos = buddykit_get_user_activity_videos( bp_displayed_user_id() );
			if ( ! empty ( $videos ) ) { ?>
				<div id="buddykit-media-count">
					<p>
						<span>
							<?php $message = _n_noop('Found %d video', 'Found %d videos', 'buddykit' ); ?>
							<?php printf( translate_nooped_plural( $message, absint(count($videos)), 'buddykit' ), number_format_i18n( absint(count($videos)) ) ); ?>
						</span>
					</p>
				</div>
				<ul id="buddykit-profile-tab-list-videos">
				<?php 
				foreach( $videos as $video ) { 
					?>
					<li class="buddykit-profile-tab-list-videos-item">
						<div class="buddykit-profile-tab-list-videos-item-wrap buddykit-media-wrap">
							<div class="buddykit-media-button-play-wrap">
								<div class="buddykit-media-button-play"></div>
							</div>
							<p>
							<video width="100%" height="150" id="player">
						    	<source src="<?php echo esc_url( $video['video_src'] ); ?>" type="video/mp4">
							</video>
							</p>
						</div>
					</li>
					<?php
				}?>
				</ul>
			<?php }  else {
				?>
				<aside class="bp-feedback bp-messages bp-template-notice info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p>
						<?php esc_html_e('There are no videos to show', 'buddykit'); ?>
					</p>
				</aside>
				<?php
			}
		} else {
			$photos = buddykit_get_user_activity_photos( bp_displayed_user_id() );
			if ( ! empty( $photos ) ) { ?>
				<div id="buddykit-media-count">
					<p>
						<?php $message = _n_noop('Found %d photo', 'Found %d photos', 'buddykit' ); ?>
						<?php printf( translate_nooped_plural( $message, absint(count($photos)), 'buddykit' ), number_format_i18n( absint(count($photos)) ) ); ?>
					</p>
				</div>
				<ul id="buddykit-profile-tab-list-photos">
					<?php foreach( $photos as $photo ) { ?>
						<?php $image_src_full = $photo['image_src_full']; ?>
						<?php $image_src = $photo['image_src']; ?>
						<?php $image_alt = $photo['image_alt']; ?>
							<li class="buddykit-profile-tab-list-photos-item">
								<a href="#" data-file-id="<?php echo esc_attr( absint( $photo['image_id']) ); ?>" class="buddykit-profile-tabs-media-delete">
									<?php esc_html_e('Delete', 'buddykit'); ?>
								</a>
								<a href="<?php echo esc_url($image_src_full); ?>" class="buddykit-profile-tabs-image-item" 
									title="<?php echo esc_attr($image_alt); ?>">
									<img src="<?php echo esc_url($image_src); ?>" alt="<?php echo esc_attr($image_alt); ?>" />
								</a>
							</li>
					<?php } ?>
				</ul>
			<?php
			} else {
				?>
				<aside class="bp-feedback bp-messages bp-template-notice info">
					<span class="bp-icon" aria-hidden="true"></span>
					<p>
						<?php esc_html_e('There are no photos to show', 'buddykit'); ?>
					</p>
				</aside>
				<?php
			}
		}
	}

}
// You can also do Setup_Profile_Tabs(). ok.
new \Buddykit\Media\Setup_Profile_Tabs();

