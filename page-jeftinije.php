<?php 

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

        $_product = wc_get_product( get_the_id() );

        // PREPARE OTHER IMAGES
        $product_gallery = array();
    
        $attachment_ids = $_product->get_gallery_image_ids();
    
        foreach( $attachment_ids as $attachment_id ) {
            $product_gallery[] = wp_get_attachment_url( $attachment_id );
        }

        // PREPARE QUANTITY AND STOCK INFORMATION
        $qty = $_product->get_stock_quantity();
        $stockText = $qty > 0 ? 'Raspoloživo odmah' : 'Dolazi uskoro';
        $stock = $qty > 0 ? 'in stock' : 'out of stock';

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
        $delivery = '30.00';

        // FREE DELIVERY IF AMOUNT LARGER THAN 300
        if($_product->get_price() > 300) {
            $delivery = '0.00';
        }

        // PREPRARE ATTRIBS, SKIP SOME

        $attribs = array();

        foreach( $_product->get_attributes() as $attr_name => $attr ){
    
            if ( wc_attribute_label( $attr_name ) == 'naziv-na-deklaraciji' ) {
                continue;
            }
    
            $attribs[$attr_name]['name'] = wc_attribute_label( $attr_name );
    
            foreach( $attr->get_terms() as $term ){
    
                $attribs[$attr_name]['values'][] = $term->name;
            }
        }

        // PREPARE DESCRIPTION
        $desc = str_replace(array("\n", "\r"), '', get_the_excerpt());
        $desc .= ' ' . str_replace(array("\n", "\r"), '', get_the_content());
        $desc = str_replace("<strong>Uputstva za uporabu</strong>", " Uputstva za uporabu: ", $desc);
        $desc = str_replace("<strong>Upozorenje</strong>", " Upozorenje: ", $desc);
        $desc = strip_tags($desc);

        // ECHO ITEM
        echo "\t<Item>\n";
        echo "\t\t<ID><![CDATA[" . $product->get_sku() . "]]></ID>\n";
        echo "\t\t<name><![CDATA[" . str_replace('&#8211;', '–', get_the_title()) . "]]></name>\n";
        echo "\t\t<description><![CDATA[" . $desc . "]]></description>\n";
        echo "\t\t<link><![CDATA[" . get_the_permalink() . "]]></link>\n";
        echo "\t\t<mainImage><![CDATA[" . get_the_post_thumbnail_url($post, 'full') . "]]></mainImage>\n";
        echo "\t\t<moreImages><![CDATA[" . implode(',', $product_gallery) . "]]></moreImages>\n";
        echo "\t\t<price>" . number_format($_product->get_price(), 2) . "</price>\n";
        echo "\t\t<regularPrice>" . number_format($_product->get_regular_price(), 2) . "</regularPrice>\n";
        echo "\t\t<curCode>HRK</curCode>\n";
        echo "\t\t<stockText><![CDATA[" . $stockText . "]]></stockText>\n";
        echo "\t\t<stock>" . $stock . "</stock>\n";
        echo "\t\t<quantity>" . $qty . "</quantity>\n";
        echo "\t\t<fileUnder><![CDATA[Telefoni &gt; " . $levelTwoCat . "]]></fileUnder>\n";
        echo "\t\t<brand><![CDATA[" . $brand . "]]></brand>\n";
        echo "\t\t<EAN></EAN>\n";
        echo "\t\t<productCode><![CDATA[" . $product->get_sku() . "]]></productCode>\n";
        echo "\t\t<warranty><![CDATA[1 godina]]></warranty>\n";
        echo "\t\t<deliveryCost>" . $delivery . "</deliveryCost>\n";
        echo "\t\t<deliveryTimeMin>1</deliveryTimeMin>\n";
        echo "\t\t<deliveryTimeMax>2</deliveryTimeMax>\n";

        // ECHO ATTRIBS
        if(count($attribs)) {
    
            echo "\t\t<attributes>";
    
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
