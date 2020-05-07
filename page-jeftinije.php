<?php 

    $config = array(
        'id' => 'id',                   // 'id' or 'sku'
        'delivery-price' => 30,         // delivery price for all products
        'free-delivery-limit' => 300,   // free delivery over 300
        'global-warranty' => '',        // e.g. '1 godina', if set it applies to all products
        'currency' => 'HRK',            // 'HRK', 'EUR', ...
        'primary-cat' => '',            // e.g. Telefoni (optional)
        'delivery-time-min' => '1',     // min days for delivery
        'delivery-time-max' => '2',     // max days for delivery
        'display-attributes' => false,
        'attributes-to-skip' => array( 'naziv-na-deklaraciji' ),
        'manage-stock' => false,        // if false, all products will be set to “in stock”
        'available-now-text' => 'Raspoloživo odmah',
        'coming-soon-text' => 'Dolazi uskoro',
    );

    /*
     *
     *
     * STOP EDITING HERE
     *
     *
     */

    // FETCH ALL PRODUCTS
    $args = array(
        'posts_per_page' => -1,
        'post_type' => 'product'
    );

    $products = new WP_Query( $args );

    // SET XML CONTENT TXPE
    header("Content-type: text/xml");

    // START WITH OUTPUT
    echo "<?xml version='1.0' encoding='UTF-8' ?>\n";

    echo "<CNJExport>\n";

    if( $products->have_posts() ): while ( $products->have_posts() ): $products->the_post(); 

        $id = get_the_id();

        $_product = wc_get_product( $id );

        if( $product->get_sku() && $config['id'] == 'sku' ) {
            $id = $product->get_sku();
        }

        // PREPARE OTHER IMAGES
        $product_gallery = array();
    
        $attachment_ids = $_product->get_gallery_image_ids();
    
        foreach( $attachment_ids as $attachment_id ) {
            $product_gallery[] = wp_get_attachment_url( $attachment_id );
        }

        // PREPARE QUANTITY AND STOCK INFORMATION
        $qty = $_product->get_stock_quantity();
        if( $config['manage-stock'] ) {
            $stockText = $qty > 0 ? $config['available-now-text'] : $config['coming-soon-text'];
            $stock = $qty > 0 ? 'in stock' : 'out of stock';
        } else {
            $stockText = $config['available-now-text'];
            $stock = 'in stock';
        }

        // PREPARE CATEGORIES
        $categories = get_the_terms( $post, 'product_cat' );
        $levelTwoCat = isset($categories[0]) ? $categories[0]->name : '';

        // PREPARE BRAND IF TAXONOMY EXISTS
        $brand = '';
        $brands = get_the_terms( $post, 'brand' );
        if( !is_wp_error( $brands ) ) {
            $brand = isset($brands[0]) ? $brands[0]->name : '';
        }

        // DEFINE DELIVERY PRICE
        $delivery = $config['delivery-price'];

        // FREE DELIVERY IF AMOUNT LARGER THAN XX
        if($_product->get_price() > $config['free-delivery-limit']) {
            $delivery = 0;
        }

        // PREPRARE ATTRIBS, SKIP SOME

        $attribs = array();

        if( $config['display-attributes'] ) {

            foreach( $_product->get_attributes() as $attr_name => $attr ){
        
                if ( in_array( wc_attribute_label( $attr_name ), $config['attributes-to-skip'] ) ) {
                    continue;
                }
    
                $attribs[$attr_name]['name'] = wc_attribute_label( $attr_name );
        
                foreach( $attr->get_terms() as $term ){
        
                    $attribs[$attr_name]['values'][] = $term->name;
                }
            }
        }

        // PREPARE DESCRIPTION
        $desc = str_replace(array("\n", "\r"), '', get_the_excerpt());
        $desc .= ' ' . str_replace(array("\n", "\r"), '', get_the_content());
        // $desc = str_replace("<strong>Uputstva za uporabu</strong>", " Uputstva za uporabu: ", $desc);
        // $desc = str_replace("<strong>Upozorenje</strong>", " Upozorenje: ", $desc);
        $desc = strip_tags($desc);

        // ECHO ITEM
        echo "\t<Item>\n";
        echo "\t\t<ID><![CDATA[" . $id . "]]></ID>\n";
        echo "\t\t<name><![CDATA[" . str_replace('&#8211;', '–', get_the_title()) . "]]></name>\n";
        echo "\t\t<description><![CDATA[" . $desc . "]]></description>\n";
        echo "\t\t<link><![CDATA[" . get_the_permalink() . "]]></link>\n";
        echo "\t\t<mainImage><![CDATA[" . get_the_post_thumbnail_url($post, 'full') . "]]></mainImage>\n";
        echo "\t\t<moreImages><![CDATA[" . implode(',', $product_gallery) . "]]></moreImages>\n";
        echo "\t\t<price>" . number_format($_product->get_price(), 2) . "</price>\n";
        echo "\t\t<regularPrice>" . number_format($_product->get_regular_price(), 2) . "</regularPrice>\n";
        echo "\t\t<curCode>" . $config['currency'] . "</curCode>\n";
        echo "\t\t<stockText><![CDATA[" . $stockText . "]]></stockText>\n";
        echo "\t\t<stock>" . $stock . "</stock>\n";
        echo "\t\t<quantity>" . $qty . "</quantity>\n";
        echo "\t\t<fileUnder><![CDATA[" . ( $config['primary-cat'] ? $config['primary-cat'] . " &gt; " : "" ) . $levelTwoCat . "]]></fileUnder>\n";
        echo "\t\t<brand><![CDATA[" . $brand . "]]></brand>\n";
        echo "\t\t<EAN></EAN>\n";
        echo "\t\t<productCode><![CDATA[" . $product->get_sku() . "]]></productCode>\n";
        echo "\t\t<warranty><![CDATA[" . $config['global-warranty'] . "]]></warranty>\n";
        echo "\t\t<deliveryCost>" . number_format($delivery, 2) . "</deliveryCost>\n";
        echo "\t\t<deliveryTimeMin>" . $config['delivery-time-min'] . "</deliveryTimeMin>\n";
        echo "\t\t<deliveryTimeMax>" . $config['delivery-time-max'] . "</deliveryTimeMax>\n";

        // ECHO ATTRIBS
        if(count($attribs)) {
    
            echo "\t\t<attributes>\n";
    
            foreach($attribs as $attrib) {

                echo "\t\t\t<attribute>\n";
                echo "\t\t\t\t<name><![CDATA[" . $attrib['name'] . "]]></name>\n";
                echo "\t\t\t\t<values>\n";

                foreach($attrib['values'] as $val) {
                    echo "\t\t\t\t\t<value><![CDATA[" . $val . "]]></value>\n";
                }

                echo "\t\t\t\t</values>\n";
                echo "\t\t\t</attribute>\n";

            }
    
            echo "\t\t</attributes>\n";

        }

        echo "\t</Item>\n";

    endwhile; endif; 

    echo "</CNJExport>";
