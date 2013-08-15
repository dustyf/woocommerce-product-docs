<?php 
/**
 *
 * Adds a shortcode [product_docs] to display a documentation selector
 * on a page or post.
 *
 **/

function docrepo_shortcode() {
	// Get the current URL
	$url = 'http://' . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI']; ?>
	<form id="doc-select" method="get" action ="<?php echo $url ?>">
		<select multiple name="documenttype[]" form="doc-select">
			<option selected="selected" value="all-documents">All Documents</option>
			<?php $doc_types = get_terms( 'docrepo_document_types' );
			foreach ($doc_types as $doc_type) { 
				$docslug = $doc_type->slug;
				$docname = $doc_type->name;
				echo '<option value="' . $docslug . '">' . $docname . '</option>';
			} ?>
		</select>
		<select multiple name="productselect[]" form="doc-select">
			<option selected="selected" value="all-products">All Products</option>
			<?php $args = array(
				'post_type' => 'product',
				'posts_per_page' => -1
				);
			$prods = new WP_Query( $args );
			if ( $prods->have_posts() ) {
				while ( $prods->have_posts() ) : $prods->the_post();
					echo '<option value="' . get_the_ID() . '">' . get_the_title() . '</option>';
				endwhile;
			}
			wp_reset_postdata(); ?>
		</select>
		<input type="submit">
	</form>

	<?php if ( !empty( $_GET['documenttype'] ) && !empty( $_GET['productselect'] ) ) { 

		// Set up variables for the query strings
		$documenttypes = $_GET['documenttype'];
		$productselect = $_GET['productselect'];

		// Begin by checking if All Documents was selected
		if ( 'all-documents' === $documenttypes[0] ) {

			// Display documents when All Products and All Documents are selected
			if ( 'all-products' === $productselect[0] ) {
				foreach ( $doc_types as $doc_type ) {
					echo '<h2>' . $doc_type->name . '</h2>';
					$args = array(
						'post_type' => 'product',
						'posts_per_page' => -1
						);
					$loop = new WP_Query( $args );
					echo '<table class="table">';
					while ( $loop->have_posts() ) : $loop->the_post();
						echo '<tr><th>' . get_the_title() . '</th></tr>';
						$connected = new WP_Query( array(
							'connected_type' => 'related_documents',
							'connected_items' => get_the_ID(),
							'nopaging' => true,
							'taxonomy' => 'docrepo_document_types',
							'term' => $doc_type->slug
						) );
						if ( $connected->have_posts() ) {
							while ( $connected->have_posts() ) : $connected->the_post();
								echo '<tr>';
								echo '<td>' . get_the_title() . '</td>';
								echo '</tr>';
							endwhile;
						} else {
							echo '<tr><td>No ' . $doc_type->name . ' available.</td></tr>';
						}
						wp_reset_postdata();
					endwhile;
					echo '</table>';
					wp_reset_postdata();
				}
			} else {
				// If all Documents are selected and one or multiple products are selected
				foreach ( $doc_types as $doc_type ) {
					echo '<h2>' . $doc_type->name . '</h2>';
					echo '<table class="table">';
					foreach ( $productselect as $productsingle ) {
						echo '<tr><th>' . get_the_title( $productsingle ) . '</th></tr>';
						$connected = new WP_Query( array(
							'connected_type' => 'related_documents',
							'connected_items' => $productsingle,
							'nopaging' => true,
							'taxonomy' => 'docrepo_document_types',
							'term' => $doc_type->slug
						) );
						if ( $connected->have_posts() ) {
							while ( $connected->have_posts() ) : $connected->the_post();
								echo '<tr>';
								echo '<td>' . get_the_title() . '</td>';
								echo '</tr>';
							endwhile;
						} else {
							echo '<tr><td>No ' . $doc_type->name . ' available.</td></tr>';
						}
						wp_reset_postdata();
					}
					echo '</table>';
				}
			}

		} else {
			// If All Products are selected and one or more categories are selected
			if ( 'all-products' === $productselect[0] ) {
				foreach ( $documenttypes as $documenttype ) {
					$doc_cat_name = get_term_by( 'slug', $documenttype, 'docrepo_document_types' );
					echo '<h2>' . $doc_cat_name->name . '</h2>';
					$args = array(
						'post_type' => 'product',
						'posts_per_page' => -1
						);
					$loop = new WP_Query( $args );
					echo '<table class="table">';
					while ( $loop->have_posts() ) : $loop->the_post();
						echo '<tr><th>' . get_the_title() . '</th></tr>';
						$connected = new WP_Query( array(
							'connected_type' => 'related_documents',
							'connected_items' => get_the_ID(),
							'nopaging' => true,
							'taxonomy' => 'docrepo_document_types',
							'term' => $documenttype
						) );
						if ( $connected->have_posts() ) {
							while ( $connected->have_posts() ) : $connected->the_post();
								echo '<tr>';
								echo '<td>' . get_the_title() . '</td>';
								echo '</tr>';
							endwhile;
						} else {
							echo '<tr><td>No ' . $doc_type->name . ' available.</td></tr>';
						}
						wp_reset_postdata();
					endwhile;
					echo '</table>';
					wp_reset_postdata();
				}
			} else {
				// If one or more categories are selected and one or more products are selected
				foreach ( $documenttypes as $documenttype ) {
					$doc_cat_name = get_term_by( 'slug', $documenttype, 'docrepo_document_types' );
					echo '<h2>' . $doc_cat_name->name . '</h2>';
					echo '<table class="table">';
					foreach ( $productselect as $productsingle ) {
						echo '<tr><th>' . get_the_title( $productsingle ) . '</th></tr>';
						$connected = new WP_Query( array(
							'connected_type' => 'related_documents',
							'connected_items' => $productsingle,
							'nopaging' => true,
							'taxonomy' => 'docrepo_document_types',
							'term' => $documenttype
						) );
						if ( $connected->have_posts() ) {
							while ( $connected->have_posts() ) : $connected->the_post();
								echo '<tr>';
								echo '<td>' . get_the_title() . '</td>';
								echo '</tr>';
							endwhile;
						} else {
							echo '<tr><td>No ' . $doc_type->name . ' available.</td></tr>';
						}
						wp_reset_postdata();
					}
					echo '</table>';
				}
			}
			
		}
	} else {
		echo 'Please select both a Document Type and a Product from above.  To see all documents, select All Documents and All Products.';
	}
}
add_shortcode( 'product_docs', 'docrepo_shortcode' );