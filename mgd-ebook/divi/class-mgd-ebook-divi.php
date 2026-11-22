<?php
defined( 'ABSPATH' ) || exit;

/**
 * Divi-5-Module "MGD eBook" + "MGD Bücherregal". Ohne Build-Schritt: das Builder-Skript ist reines JS,
 * die Modul-Beschreibung (module.json) nutzen PHP und JS gemeinsam. Ausgabe = dieselbe wie die Shortcodes.
 * Läuft nur, wenn Divi 5 aktiv ist - sonst passiert hier gar nichts.
 */
final class MGD_Ebook_Divi {
    private const SCRIPT = 'mgd-ebook-divi';
    private const REST_NS = 'mgd-ebook/v1';

    public static function boot(): void {
        add_action( 'divi_module_library_modules_dependency_tree', array( self::class, 'modules' ) );
        add_action( 'divi_visual_builder_assets_before_enqueue_scripts', array( self::class, 'builder_assets' ) );
        add_action( 'rest_api_init', array( self::class, 'rest' ) );
    }

    // Module registrieren (Divi ruft das nur mit Divi 5 auf)
    public static function modules( $tree ): void {
        if ( ! class_exists( '\ET\Builder\Packages\ModuleLibrary\ModuleRegistration' ) ) { return; }
        add_action( 'init', static function (): void {
            try {
                \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::register_module( MGD_EBOOK_DIR . 'divi/modules-json/ebook/', array( 'render_callback' => array( self::class, 'render_ebook' ) ) );
                \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::register_module( MGD_EBOOK_DIR . 'divi/modules-json/regal/', array( 'render_callback' => array( self::class, 'render_regal' ) ) );
            } catch ( \Throwable $e ) {
                unset( $e ); // Divi-API anders als erwartet > Shortcodes gehen trotzdem
            }
        } );
    }

    // Builder-Skript + eBook-Liste ins Builder-Fenster
    public static function builder_assets(): void {
        if ( ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' ) || ! current_user_can( 'edit_posts' ) ) { return; }
        \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build( array(
            'name' => self::SCRIPT,
            'version' => MGD_EBOOK_VERSION,
            'script' => array(
                'src' => MGD_EBOOK_URL . 'divi/assets/divi-vb.js',
                'deps' => array( 'divi-module-library', 'divi-vendor-wp-hooks' ),
                'enqueue_top_window' => false,
                'enqueue_app_window' => true,
                'data_app_window' => array(
                    'modules' => array( self::metadata( 'ebook' ), self::metadata( 'regal' ) ),
                    'preview' => esc_url_raw( rest_url( self::REST_NS . '/preview' ) ),
                    'nonce' => wp_create_nonce( 'wp_rest' ),
                ),
            ),
            'style' => array(
                'src' => MGD_EBOOK_URL . 'assets/ebook.css',
                'deps' => array(),
                'enqueue_top_window' => false,
                'enqueue_app_window' => true,
            ),
        ) );
    }

    /** module.json + Auswahllisten (eBooks, Reihen) */
    public static function metadata( string $which ): array {
        $raw = file_get_contents( MGD_EBOOK_DIR . 'divi/modules-json/' . ( 'regal' === $which ? 'regal' : 'ebook' ) . '/module.json' );
        $meta = is_string( $raw ) ? json_decode( $raw, true ) : null;
        if ( ! is_array( $meta ) ) { return array(); }
        $items = &$meta['attributes']['ebook']['settings']['innerContent']['items'];
        if ( 'regal' === $which ) {
            $items['reihe']['component']['props']['options'] += self::series_options();
        } else {
            $items['id']['component']['props']['options'] += self::book_options();
        }
        return $meta;
    }

    /** @return array<string,array{label:string}> */
    public static function book_options(): array {
        $out = array();
        $query = new WP_Query( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => MGD_Ebook_Library::MIME, 'posts_per_page' => 300, 'fields' => 'ids', 'no_found_rows' => true, 'orderby' => 'date', 'order' => 'DESC' ) );
        foreach ( $query->posts as $id ) {
            $out[ (string) $id ] = array( 'label' => MGD_Ebook_Library::title( (int) $id ) . ' (ID ' . (int) $id . ')' );
        }
        return $out;
    }

    /** @return array<string,array{label:string}> */
    public static function series_options(): array {
        $out = array();
        $query = new WP_Query( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => MGD_Ebook_Library::MIME, 'posts_per_page' => 300, 'fields' => 'ids', 'no_found_rows' => true ) );
        foreach ( $query->posts as $id ) {
            $series = trim( MGD_Ebook_Library::book( (int) $id )['series'] );
            if ( '' !== $series ) { $out[ $series ] = array( 'label' => $series ); }
        }
        ksort( $out, SORT_NATURAL | SORT_FLAG_CASE );
        return $out;
    }

    // Vorschau im Builder: gleiche Ausgabe wie auf der Seite
    public static function rest(): void {
        register_rest_route( self::REST_NS, '/preview', array(
            'methods' => 'GET',
            'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
            'args' => array(
                'type' => array( 'type' => 'string', 'enum' => array( 'ebook', 'regal' ), 'default' => 'ebook' ),
                'id' => array( 'type' => 'string', 'default' => '' ),
                'ids' => array( 'type' => 'string', 'default' => '' ),
                'reihe' => array( 'type' => 'string', 'default' => '' ),
                'width' => array( 'type' => 'string', 'default' => '' ),
                'caption' => array( 'type' => 'string', 'default' => 'yes' ),
                'button' => array( 'type' => 'string', 'default' => '' ),
            ),
            'callback' => static function ( WP_REST_Request $request ) {
                $values = array();
                foreach ( array( 'id', 'ids', 'reihe', 'width', 'caption', 'button' ) as $key ) { $values[ $key ] = (string) $request->get_param( $key ); }
                $html = self::html( (string) $request->get_param( 'type' ), $values );
                return rest_ensure_response( array( 'html' => '' !== $html ? $html : '<p class="mgd-ebook-hint">' . esc_html( 'regal' === $request->get_param( 'type' ) ? 'Bitte eine Reihe wählen oder IDs eintragen.' : 'Bitte ein eBook wählen.' ) . '</p>' ) );
            },
        ) );
    }

    /** HTML wie die Shortcodes, aus den Modulwerten */
    public static function html( string $type, array $v ): string {
        $renderer = new MGD_Ebook_Renderer();
        $common = array(
            'width' => '' !== trim( (string) ( $v['width'] ?? '' ) ) ? (string) $v['width'] : ( 'regal' === $type ? '180' : '220' ),
            'caption' => 'no' === ( $v['caption'] ?? 'yes' ) ? 'no' : 'yes',
            'button' => (string) ( $v['button'] ?? '' ),
        );
        if ( 'regal' === $type ) {
            if ( '' === trim( (string) ( $v['ids'] ?? '' ) ) && '' === trim( (string) ( $v['reihe'] ?? '' ) ) ) { return ''; }
            return $renderer->shelf( $common + array( 'ids' => (string) ( $v['ids'] ?? '' ), 'reihe' => (string) ( $v['reihe'] ?? '' ) ) );
        }
        if ( ! absint( $v['id'] ?? 0 ) ) { return ''; }
        return $renderer->shortcode( $common + array( 'id' => (string) $v['id'] ) );
    }

    public static function render_ebook( $attrs, $content, $block, $elements ): string {
        return self::render( 'ebook', $attrs, $content, $block, $elements );
    }

    public static function render_regal( $attrs, $content, $block, $elements ): string {
        return self::render( 'regal', $attrs, $content, $block, $elements );
    }

    // Divi-Hülle (Abstände, Rahmen, Animation...) + unser HTML
    private static function render( string $type, $attrs, $content, $block, $elements ): string {
        $values = (array) ( $attrs['ebook']['innerContent']['desktop']['value'] ?? array() );
        $html = self::html( $type, $values );
        if ( ! class_exists( '\ET\Builder\Packages\Module\Module' ) ) { return $html; }
        $parent = \ET\Builder\FrontEnd\BlockParser\BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
        return \ET\Builder\Packages\Module\Module::render( array(
            'orderIndex' => $block->parsed_block['orderIndex'],
            'storeInstance' => $block->parsed_block['storeInstance'],
            'id' => $block->parsed_block['id'],
            'name' => $block->block_type->name,
            'moduleCategory' => $block->block_type->category,
            'attrs' => $attrs,
            'elements' => $elements,
            'stylesComponent' => array( self::class, 'styles' ),
            'parentAttrs' => $parent->attrs ?? array(),
            'parentId' => $parent->id ?? '',
            'parentName' => $parent->blockName ?? '',
            'children' => array( $elements->style_components( array( 'attrName' => 'module' ) ), $html ),
        ) );
    }

    public static function styles( $args ): void {
        \ET\Builder\FrontEnd\Module\Style::add( array(
            'id' => $args['id'],
            'name' => $args['name'],
            'orderIndex' => $args['orderIndex'],
            'storeInstance' => $args['storeInstance'],
            'styles' => array(
                $args['elements']->style( array(
                    'attrName' => 'module',
                    'styleProps' => array(
                        'defaultPrintedStyleAttrs' => $args['defaultPrintedStyleAttrs']['module']['decoration'] ?? array(),
                        'disabledOn' => array( 'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null ),
                    ),
                ) ),
            ),
        ) );
    }
}
