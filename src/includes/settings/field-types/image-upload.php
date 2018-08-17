<?php
namespace OptionKit\FieldTypes;

class MediaUpload {
	
	protected $atts = array();

	public function __construct( $args ) {
		$this->atts = $args;
	}

	public function display() {
		$upload_class = '';
		$remove_class = 'hidden';
		do_action('optionkit-before-field');

		wp_enqueue_media();
		?>
		
		<style>
			.optionkit-preview {
				max-width: 250px;
				height: auto;
			}
		</style>
		<p id="<?php echo esc_attr( $this->atts['id'] ); ?>-preview">
			<?php $image_id = get_option( $this->atts['id'] ); ?>
			<?php if ( ! empty ( $image_id ) ): ?>
				<?php $upload_class = 'hidden'; ?>
				<?php $remove_class = ''; ?>
				<?php echo wp_get_attachment_image( $image_id, 'full', false, array('class'=>'optionkit-preview')); ?>
			<?php endif; ?>
		</p>

		<p>

			<input size="50" type="hidden"  id="<?php echo esc_attr( $this->atts['id'] ); ?>" name="<?php echo esc_attr( $this->atts['name'] ); ?>" value="<?php echo sanitize_text_field( get_option( $this->atts['id'], $this->atts['default'] ) ); ?>" 
			<?php echo $this->atts['attributes'] ; ?> />
			
			<input data-preview-id="#<?php echo esc_attr( $this->atts['id'] ); ?>-preview" data-input-id="#<?php echo esc_attr( $this->atts['id'] ); ?>" data-media-upload-button="yes" class="button button-secondary <?php echo esc_attr( $upload_class ); ?>" type="button" value="<?php echo apply_filters('optionkit-upload-label-'.$this->atts['id'], esc_attr__('Choose Image', 'optionkit')); ?>"/>
			
			<!--Delete Media Button-->
			<input data-preview-id="#<?php echo esc_attr( $this->atts['id'] ); ?>-preview" data-input-id="#<?php echo esc_attr( $this->atts['id'] ); ?>" class="button button-secondary optionkit-image-upload-delete <?php echo esc_attr( $remove_class ); ?>" type="button" value="<?php echo apply_filters('optionkit-upload-label-'.$this->atts['id'], esc_attr__('Remove Image', 'optionkit')); ?>"/>

		</p>
		<p class="description">
			<?php echo wp_kses_post( $this->atts['description'] ); ?>
		</p>
		<?php
		do_action('optionkit-after-field');
		return;
	}
}