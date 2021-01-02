<?php 

    $config = array(
        'id'                    => 'id',       // 'id' or 'sku'
        'delivery-price'        => 30,          // delivery price for all products
        'free-delivery-limit'   => 300,         // free delivery over 300
        'global-warranty'       => '2 godine',  // e.g. '1 godina', if set it applies to all products
        'currency'              => 'HRK',       // 'HRK', 'EUR', ...
        'primary-cat'           => '',          // e.g. Telefoni (optional)
        'delivery-time-min'     => '1',         // min days for delivery
        'delivery-time-max'     => '2',         // max days for delivery
        'display-attributes'    => false,
        'attributes-to-skip'    => array( 'naziv-na-deklaraciji' ),
        'manage-stock'          => false,        // if false, quantity doesn't matter
        'available-now-text'    => 'Raspoloživo odmah',
        'coming-soon-text'      => 'Dolazi uskoro',
        'brand-attribute'       => 'brand',     // leave empty to use Brand from custom taxonomy (https://github.com/ikovacic/woocommerce-brand)
        'ean-attribute'         => 'ean',       // leave empty to use EAN from custom meta field (https://github.com/ikovacic/woocommerce-ean)
    );

    /*
     *
     *
     * STOP EDITING HERE
     *
     *
     */

    // PREPARE STOCK 
    function applause_prepare_quantity_stock( $entity ) {

        global $config;

        $qty = $entity->get_stock_quantity();

        $stock_arr = array(
            'stockText' => '<![CDATA[' . $config['available-now-text'] . ']]>',
            'stock' => 'in stock',
            'qty' => $qty,
        );

        if( $config['manage-stock'] ) {

            $stock_arr['stockText'] = $qty > 0 ? ('<![CDATA[' . $config['available-now-text'] . ']]>') : '';
            $stock_arr['stock'] = $qty > 0 ? 'in stock' : 'out of stock';

        } elseif ( !$entity->is_in_stock() ) {

            $stock_arr['stockText'] = '<![CDATA[' . $config['coming-soon-text'] . ']]>';
            $stock_arr['stock'] = 'out of stock';

        }

        return $stock_arr;

    }

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

        // PREPARE OTHER IMAGES
        $product_gallery = array();
    
        $attachment_ids = $product->get_gallery_image_ids();
    
        foreach( $attachment_ids as $attachment_id ) {
            $product_gallery[] = wp_get_attachment_url( $attachment_id );
        }

        // PREPARE CATEGORIES
        $categories = get_the_terms( $post, 'product_cat' );
        $levelTwoCat = isset($categories[0]) ? $categories[0]->name : '';

        // DEFINE DELIVERY PRICE
        $delivery = $config['delivery-price'];

        // PREPRARE ATTRIBS, SKIP SOME

        $attribs = array();

        $ean = '';

        $brand = '';

        if( $config['display-attributes'] || $config['brand-attribute'] || $config['display-attributes'] ) {
    
            foreach( $product->get_attributes() as $attr_name => $attr ){
        
                $attribute_label = wc_attribute_label( $attr_name );
        
                if ( in_array( $attribute_label, $config['attributes-to-skip'] ) ) {
                    continue;
                }

                if ( $config['brand-attribute'] && $attribute_label == $config['brand-attribute'] ) {
                    $brand = $attr['value'];
                    continue;
                }
    
                if ( $config['ean-attribute'] && $attribute_label == $config['ean-attribute'] ) {
                    $temp = $attr->get_terms();
                    $ean = $attr['value'];
                    continue;
                }
    
                if( $config['display-attributes'] ) {
        
                    $attribs[$attr_name]['name'] = $attribute_label;

                    if( $attr->get_terms() ) {

                        foreach( $attr->get_terms() as $term ){
                
                            $attribs[$attr_name]['values'][] = $term->name;
                        }

                    } else {

                        $attribs[$attr_name]['values'][] = $attr['value'];

                    }
                }
            }

        }

        // PREPARE BRAND IF TAXONOMY EXISTS
        if( !$config['brand-attribute'] ) {
            $brands = get_the_terms( $post, 'brand' );
            if( !is_wp_error( $brands ) ) {
                $brand = isset($brands[0]) ? $brands[0]->name : '';
            }
        }

        // PREPARE DESCRIPTION
        $desc = str_replace(array("\n", "\r"), '', get_the_excerpt());
        $desc .= ' ' . str_replace(array("\n", "\r"), '', get_the_content());
        // $desc = str_replace("<strong>Uputstva za uporabu</strong>", " Uputstva za uporabu: ", $desc);
        // $desc = str_replace("<strong>Upozorenje</strong>", " Upozorenje: ", $desc);
        $desc = strip_tags($desc);

        if( $product->get_type() == 'variable' ) {

            $handle = new WC_Product_Variable( get_the_ID() );
            $variations = $handle->get_children();

            foreach ($variations as $variation) {
                $single_variation = new WC_Product_Variation($variation);

                $id = $single_variation->get_id();

                if( $single_variation->get_sku() && $config['id'] == 'sku' ) {
                    $id = $single_variation->get_sku();
                }

                $stock_arr = applause_prepare_quantity_stock( $single_variation );

                if($single_variation->get_price() > $config['free-delivery-limit']) {
                    $delivery = '0.00';
                }

                if( !$config['ean-attribute'] && get_post_meta( $single_variation->get_id(), '_ean', true ) ) {
                    $ean = get_post_meta( $single_variation->get_id(), '_ean', true );
                }

                // ECHO ITEM
                echo "\t<Item>\n";
                echo "\t\t<ID><![CDATA[" . $id . "]]></ID>\n";
                echo "\t\t<name><![CDATA[" . str_replace('&#8211;', '–', $single_variation->get_name()) . "]]></name>\n";
                echo "\t\t<description><![CDATA[" . $desc . "]]></description>\n";
                echo "\t\t<link><![CDATA[" . get_the_permalink() . "]]></link>\n";
                echo "\t\t<mainImage><![CDATA[" . wp_get_attachment_url( $single_variation->get_image_id() ) . "]]></mainImage>\n";
                echo "\t\t<moreImages><![CDATA[" . implode(',', $product_gallery) . "]]></moreImages>\n";
                echo "\t\t<price>" . number_format($single_variation->get_price(), 2) . "</price>\n";
                echo "\t\t<regularPrice>" . number_format($single_variation->get_regular_price(), 2) . "</regularPrice>\n";
                echo "\t\t<curCode>" . $config['currency'] . "</curCode>\n";
                echo "\t\t<stockText>" . $stock_arr['stockText'] . "</stockText>\n";
                echo "\t\t<stock>" . $stock_arr['stock'] . "</stock>\n";
                echo "\t\t<quantity>" . $stock_arr['qty'] . "</quantity>\n";
                echo "\t\t<fileUnder><![CDATA[" . ( $config['primary-cat'] ? $config['primary-cat'] . " &gt; " : "" ) . $levelTwoCat . "]]></fileUnder>\n";
                echo "\t\t<brand>" . ( $brand ? "<![CDATA[" . $brand . "]]>" : "" ) . "</brand>\n";
                echo "\t\t<EAN>" . $ean . "</EAN>\n";
                echo "\t\t<productCode><![CDATA[" . $single_variation->get_sku() . "]]></productCode>\n";
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


            }
        } else {

            $id = get_the_id();
    
            if( $product->get_sku() && $config['id'] == 'sku' ) {
                $id = $product->get_sku();
            }

            $stock_arr = applause_prepare_quantity_stock( $product );

            if($product->get_price() >= $config['free-delivery-limit']) {
                $delivery = 0;
            }

            if( !$config['ean-attribute'] && get_post_meta( get_the_id(), '_ean', true ) ) {
                $ean = get_post_meta( get_the_id(), '_ean', true );
            }

            // ECHO ITEM
            echo "\t<Item>\n";
            echo "\t\t<ID><![CDATA[" . $id . "]]></ID>\n";
            echo "\t\t<name><![CDATA[" . str_replace('&#8211;', '–', get_the_title()) . "]]></name>\n";
            echo "\t\t<description><![CDATA[" . $desc . "]]></description>\n";
            echo "\t\t<link><![CDATA[" . get_the_permalink() . "]]></link>\n";
            echo "\t\t<mainImage><![CDATA[" . get_the_post_thumbnail_url($post, 'full') . "]]></mainImage>\n";
            echo "\t\t<moreImages><![CDATA[" . implode(',', $product_gallery) . "]]></moreImages>\n";
            echo "\t\t<price>" . number_format($product->get_price(), 2) . "</price>\n";
            echo "\t\t<regularPrice>" . number_format(($product->get_regular_price() ? $product->get_regular_price() : $product->get_price()), 2) . "</regularPrice>\n";
            echo "\t\t<curCode>" . $config['currency'] . "</curCode>\n";
            echo "\t\t<stockText>" . $stock_arr['stockText'] . "</stockText>\n";
            echo "\t\t<stock>" . $stock_arr['stock'] . "</stock>\n";
            echo "\t\t<quantity>" . $stock_arr['qty'] . "</quantity>\n";
            echo "\t\t<fileUnder><![CDATA[" . ( $config['primary-cat'] ? $config['primary-cat'] . " &gt; " : "" ) . $levelTwoCat . "]]></fileUnder>\n";
            echo "\t\t<brand>" . ( $brand ? "<![CDATA[" . $brand . "]]>" : "" ) . "</brand>\n";
            echo "\t\t<EAN>" . $ean . "</EAN>\n";
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

        }

    endwhile; endif; 

    echo "</CNJExport>";
