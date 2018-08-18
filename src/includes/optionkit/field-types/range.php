<?php
namespace OptionKit\FieldTypes;

class Range {
	
	protected $atts = array();

	public function __construct( $args ) {
		$this->atts = $args;
	}

	public function display() {
		do_action('optionkit-before-field');
	
		$value = get_option( $this->atts['id'], $this->atts['default'] );
		?>
		<style>
			
			.slider {
		    -webkit-appearance: none;
		    width: 100%;
		    max-width: 250px;
		    height: 5px;
		    border-radius: 5px;   
		    background: #d3d3d3;
		    outline: none;
		    opacity: 0.7;
		    -webkit-transition: .2s;
		    transition: opacity .2s;
		}

		.slider::-webkit-slider-thumb {
		    -webkit-appearance: none;
		    appearance: none;
		    width: 25px;
		    height: 25px;
		    border-radius: 50%; 
		    background: #4CAF50;
		    cursor: pointer;
		}

		.slider::-moz-range-thumb {
		    width: 25px;
		    height: 25px;
		    border-radius: 50%;
		    background: #4CAF50;
		    cursor: pointer;
		}
		</style>
		<div class="slidecontainer">
			<table style="width: 100%; max-width: 250px;">
				<tr>
					<td style="padding:0;">
						<input readonly type="text" class="optionkit-range-io" id="<?php echo esc_attr( $this->atts['id'] ); ?>-range-io" size="4" value="<?php echo esc_attr( $value ); ?>">
					</td>
					<td style="padding:0;">
						<input name="<?php echo esc_attr($this->atts['id']); ?>" data-range-io-id="#<?php echo esc_attr( $this->atts['id'] ); ?>-range-io" <?php echo $this->atts['attributes']; ?> value="<?php echo esc_attr( $value ); ?>" type="range" class="slider" id="<?php echo esc_attr( $this->atts['id'] ); ?>">
					</td>
				</tr>
			</table>
		</div>
		<p class="description">
			<?php echo wp_kses_post( $this->atts['description'] ); ?>
		</p>
		<script>
			jQuery(document).ready(function($){
				
					$('.slider').on('change input', function(){
						$( $( this ).attr( 'data-range-io-id' ) ).val( $(this).val() );
					});
			
			});
		</script>
		<?php
		do_action('optionkit-after-field');
		return;
	}
}