<?php if ( $settings['show_projects_filter'] ) : ?>

    <?php
        $wrapper_classes = '';
        switch ( $settings['filter_align'] ) {
            case 'center':
                $wrapper_classes .= ' -center';
                break;
            case 'right':
                $wrapper_classes .= ' -right';
                break;
        }

        if ( $settings['filter_layout'] == 'button' ) {
            $wrapper_classes .= ' -filter-button';
        }

        $selected_categories = [];
        if ( !empty( $settings['portfolio_category'] ) ) {
            $selected_categories = $settings['portfolio_category'];
        }

        $cat_ids = get_terms( array( 'taxonomy' => 'ohio_portfolio_category' ) );

        if ( !empty( $cat_ids ) && is_array( $cat_ids ) ) :
    ?>

    <div class="portfolio-filter<?php echo esc_attr( $wrapper_classes ); ?>"
        data-filter="portfolio"
        <?php if ( $filter_is_paged ) { echo 'data-filter-paged="true"'; } ?>>
        <ul class="-unlist">
            <li><?php esc_html_e( 'Filter by', 'ohio-extra' ) ?></li>
            <li>
                <a class="active<?php if ( $settings['filter_layout'] == 'button' ) { echo esc_attr( ' tag -unlink'); } ?>" href="#all" data-isotope-filter="*" data-category-count="<?php echo str_pad( (string)count( $projects_data ), 2, '0', STR_PAD_LEFT ); ?>">
                    <span class="name"><?php esc_html_e( 'All', 'ohio-extra' ); ?></span>
                    <span class="num"><?php echo str_pad( (string)count( $projects_data ), 2, '0', STR_PAD_LEFT ); ?></span>
                </a>
            </li>
            <?php foreach ( $cat_ids as $cat_obj ) : ?>
                <?php
                    if ( !$settings['show_empty_categories']
                        && !in_array( $cat_obj->term_id, $category_id_allowlist )
                    ) continue;
                ?>
                <?php if ( !empty( $selected_categories ) ): ?>
                    <?php if ( !in_array( $cat_obj->slug, $selected_categories ) ) { continue; } ?>
                <?php endif; ?>
                <li>
                    <a class="<?php if ( $settings['filter_layout'] == 'button' ) { echo esc_attr( 'tag -unlink'); } ?>" href="#<?php echo esc_attr( $cat_obj->slug ); ?>" data-isotope-filter=".ohio-filter-project-<?php echo hash( 'md4', $cat_obj->slug, false ); ?>" data-category-count="<?php echo esc_attr($cat_obj->count);?>">
                        <span class="name"><?php echo esc_html( $cat_obj->name ); ?></span>
                        <span class="num"><?php echo str_pad( (string)esc_html( $cat_obj->count ), 2, '0', STR_PAD_LEFT ); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <?php endif; ?>

<?php endif; ?>