<?php
namespace OptionKit\FieldTypes;

class WYSIWYG {
	
	protected $atts = array();

	public function __construct( $args ) {
		$this->atts = $args;
	}

	public function display() {
		
		do_action('optionkit-before-field');
		
		$content = get_option( $this->atts['id'], $this->atts['default']);

		$editor_id = $this->atts['id'];

		$default = array(
				'wpautop' => true,
				'media_buttons' => true,
				'textarea_rows' => '',
				'tabindex' => '',
				'editor_css' => '',
				'editor_class' => '',
				'editor_height' => '',
				'teeny' => true,
				'dfw' => false,
				'tinymce' => true,
				'quicktags' => true,
				'drag_drop_upload' => false
			);
		$settings = array();
		
		if ( isset( $this->atts['wysiwyg_attributes'] ) && is_array( $this->atts['wysiwyg_attributes'] ) )
		{
			$settings = wp_parse_args($this->atts['wysiwyg_attributes'], $default);
		}

		wp_editor( $content, $editor_id, $settings ); ?> 
		
		<p class="description">
			<?php echo wp_kses_post( $this->atts['description'] ); ?>
		</p>
		
		<?php
		
		do_action('optionkit-after-field');

		return;
	}
}