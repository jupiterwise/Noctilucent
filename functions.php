<?php

/**
 * Load includes
 */
if ( ! function_exists( 'noctilucent_load_modules' ) ) {
	function noctilucent_load_modules() {

		$defaults = array(
			'admin',
			'homepage',
			'pagination'
		);
		$modules = apply_filters( 'noctilucent_modules', $defaults );

		foreach ( $modules as $module ) {
			if ( $module == 'admin' ) {
				if ( is_admin() )
					get_template_part( 'inc/class', $module );
			} else {
				get_template_part( 'inc/class', $module );
			}
		}

	}
	add_action( 'after_setup_theme', 'noctilucent_load_modules', 1 );
}

/**
 * Determine HTTP protocol
 */
function noctilucent_get_protocol() {
    $protocol = ( is_ssl() ? 'https:' : 'http:' );
    return $protocol;
}


/**
 * Return the URL of a theme file. If overrideable, will check child theme
 * directory first, then parent theme.
 *
 * from https://core.trac.wordpress.org/attachment/ticket/18302/18302.9.diff
 */
function noctilucent_theme_url( $file = '', $overrideable = true ) {
	$file = ltrim( $file, '/' ); 
	
	if ( empty( $file ) || ( false !== strpos( $file, '..' ) ) ) {
		$url = get_stylesheet_directory_uri(); 
	} elseif ( $overrideable && is_child_theme() && file_exists( trailingslashit( get_stylesheet_directory() ) . $file ) ) { 
		$url = trailingslashit( get_stylesheet_directory_uri() ) . $file; 
	} else { 
		$url = trailingslashit( get_template_directory_uri() ) . $file; 
	} 
	
	return apply_filters( 'theme_url', $url, $file, $overrideable ); 
}
	

/**
 * Enqueue stylesheets
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_enqueue_styles' ) ) {
    function noctilucent_enqueue_styles() {
		
		// Main stylesheet
		wp_register_style( 'style', noctilucent_theme_url( 'css/style.css' ), array() );
		wp_enqueue_style( 'style' );
		
    }
    add_action( 'wp_enqueue_scripts', 'noctilucent_enqueue_styles' );
}


/**
 * Re-register jQuery
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_load_jquery' ) ) {
    function noctilucent_load_jquery() {
		
		$jquery_version = apply_filters( 'noctilucent_jquery_version', '1.9.1' );
		
		// Only load on front end. The latest jQuery version may not be compatible
		// with WordPress admin scripts.
		if ( ! is_admin() ) {
			wp_deregister_script( 'jquery' );
			wp_register_script( 'jquery', noctilucent_get_protocol() . '//ajax.googleapis.com/ajax/libs/jquery/' . $jquery_version . '/jquery.min.js', array(), $jquery_version, apply_filters( 'noctilucent_jquery_in_footer', true ) );
			wp_enqueue_script( 'jquery' );
		}
		
    }
    add_action( 'wp_enqueue_scripts', 'noctilucent_load_jquery' );
}


/**
 * Enqueue script files
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_enqueue_scripts' ) ) {
    function noctilucent_enqueue_scripts() {
		
		// Modernizr
		$modernizr_version = '2.6.2';
		wp_register_script( 'modernizr', noctilucent_theme_url( 'js/modernizr-' . $modernizr_version . '.min.js' ), array(), $modernizr_version );
		wp_enqueue_script( 'modernizr' );
		
		// Comment reply functions
		if ( ! is_admin() && is_singular() && comments_open() && get_option( 'thread_comments' ) )
			wp_enqueue_script( 'comment-reply' );
		
		// Plugins
		wp_register_script( 'noctilucent-plugins', noctilucent_theme_url( 'js/plugins.js' ), apply_filters( 'noctilucent_js_dependencies', array( 'jquery' ) ), '', true );
		wp_enqueue_script( 'noctilucent-plugins' );
		wp_localize_script( 'noctilucent-plugins', 'noctilucent', apply_filters( 'noctilucent_js_vars', array(
			'ajax'      => admin_url( 'admin-ajax.php', noctilucent_get_protocol() . '//' ),
			'home_url'  => home_url(),
			'jquery'    => trailingslashit( home_url() ) . WPINC . '/js/jquery/jquery.js',
			'theme_url' => get_stylesheet_directory_uri()
		) ) );
		
		// Custom scripts
		wp_register_script( 'noctilucent-main', noctilucent_theme_url( 'js/main.js' ), array( 'plugins' ), '', true );
		wp_enqueue_script( 'noctilucent-main' );

    }
    add_action( 'wp_enqueue_scripts', 'noctilucent_enqueue_scripts' );
}


/**
 * Clean up <head>
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_head_cleanup' ) ) {
    function noctilucent_head_cleanup() {
		
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'start_post_rel_link' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link' );
		//remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		add_action( 'wp_head', 'noctilucent_add_pingback', 3 );
		
    }
    add_action( 'init', 'noctilucent_head_cleanup' );
}

	/**
	 * Add pingback link
	 */
	function noctilucent_add_pingback() { ?>
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<?php }


/**
 * Disable RSS Feeds
 * Pluggable
 *
 * To disable a feed:
 * add_action( 'do_feed', 'noctilucent_disable_feed', 1 );
 * add_action( 'do_feed_rdf', 'noctilucent_disable_feed', 1 );
 * add_action( 'do_feed_rss', 'noctilucent_disable_feed', 1 );
 * add_action( 'do_feed_rss2', 'noctilucent_disable_feed', 1 );
 * add_action( 'do_feed_atom', 'noctilucent_disable_feed', 1 );
 *
 * From https://github.com/wycks/WP-Skeleton-Theme/
 */
if ( ! function_exists( 'noctilucent_disable_feed' ) ) {
	function noctilucent_disable_feed() {
		wp_die( 'No feed available, please visit our <a href="' . home_url() . '">home page</a>.' );
	}
}


/**
 * Theme support
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_theme_support' ) ) {
    function noctilucent_theme_support() {
		add_theme_support( 'automatic-feed-links' );
	    if ( $post_thumbnails = apply_filters( 'noctilucent_post_thumbnails', array( 'post', 'page' ) ) )
			add_theme_support( 'post-thumbnails', $post_thumbnails );
		if ( $post_formats = apply_filters( 'noctilucent_post_formats', false ) )
	        add_theme_support( 'post-formats', $post_formats );
    }
    add_action( 'after_setup_theme', 'noctilucent_theme_support' );
}


/**
 * Menus
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_menus' ) ) {
    function noctilucent_menus() {
		register_nav_menus( array(
			'primary' => 'Primary Menu'
		) );
    }
    add_action( 'after_setup_theme', 'noctilucent_menus' );
}


/**
 * Sidebars
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_sidebars' ) ) {
    function noctilucent_sidebars() {
		register_sidebar( array(
			'id'            => 'sidebar-default',
			'name'          => 'Default Widget Area',
			'description'   => 'Widgets placed here will appear in the side column.',
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget'  => '</li>',
			'before_title'  => '<h3 class="widgettitle">',
			'after_title'   => '</h3>'
		) );
		register_sidebar( array(
			'id'            => 'sidebar-footer',
			'name'          => 'Footer Widget Area',
			'description'   => 'Widgets placed here will appear in the footer.',
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget'  => '</li>',
			'before_title'  => '<h3 class="widgettitle">',
			'after_title'   => '</h3>'
		) );
    }
    add_action( 'widgets_init', 'noctilucent_sidebars' );
}


/**
 * Content width
 */
if ( ! isset( $content_width ) )
	$content_width = 640;


/**
 * Theme setup wrapper to allow child themes to change/remove actions and filters
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_theme_setup' ) ) {
	function noctilucent_theme_setup() {

		// Modify contents of title tag
		add_filter( 'wp_title', 'noctilucent_title_tag', 10, 3 );
		
		// Body classes
		add_filter( 'body_class', 'noctilucent_body_classes' );
		
		// Chromeframe
		add_action( 'noctilucent_before_header', 'noctilucent_chromeframe' );
		
		// Insert primary nav after header
		add_action( 'noctilucent_after_header', 'noctilucent_insert_primary_nav' );

		// Pre-content insertions
		add_action( 'noctilucent_prepend_to_content', 'noctilucent_insert_section_header' );
		add_action( 'noctilucent_prepend_to_content', 'noctilucent_insert_search_header' );

		// Conditionals for loading content templates
		add_filter( 'noctilucent_content_template', 'noctilucent_load_page', 10, 1 );
		add_filter( 'noctilucent_content_template', 'noctilucent_load_archive', 10, 1 );
		add_filter( 'noctilucent_content_template', 'noctilucent_load_cpt', 15, 1 );

		// Excerpt filters
		add_filter( 'excerpt_more', 'noctilucent_excerpt_more' );
		add_filter( 'get_the_excerpt', 'noctilucent_excerpt_plus' );

		// Post-content insertions
		add_action( 'noctilucent_append_to_content', 'noctilucent_load_comments' );
		add_action( 'noctilucent_append_to_content', 'noctilucent_insert_archive_pagination' );

		// Sidebars
		add_action( 'noctilucent_after_loop', 'noctilucent_insert_default_sidebar' );
		
		// Insert copyright string and credit string after footer
		add_action( 'noctilucent_after_footer', 'noctilucent_insert_copyright' );
		add_action( 'noctilucent_after_footer', 'noctilucent_insert_credit' );
		
		// Unsuck gallery styling
		add_filter( 'gallery_style', 'noctilucent_edit_gallery_style' );
		
	}
	add_action( 'after_setup_theme', 'noctilucent_theme_setup', 10 );
}

	/**
	 * Filter the title tag to add site info
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_title_tag' ) ) {
		function noctilucent_title_tag( $title, $sep, $seplocation ) {
			
			global $page, $paged;
			$site_label = get_bloginfo( 'name' );
			
			$site_description = get_bloginfo( 'description', 'display' );
			if ( $site_description && ( is_home() || is_front_page() ) )
				$site_label .= " $sep $site_description";
			
			if ( ! $title )
				return $site_label;
			
			if ( $seplocation == 'right' ) {
				$output = "$title $site_label";
			} else {
				$output = "$site_label $title";
			}

			if ( $paged >= 2 || $page >= 2 )
				$output .= " $sep " . sprintf( 'Page %s', max( $paged, $page ) );
			
			return $output;
		
		}
	}

	/**
	 * Custom body classes
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_body_classes' ) ) {
		function noctilucent_body_classes( $classes ) {
			if ( is_singular() )
				$classes[] = 'singular';
			if ( is_page() ) {
				global $post;
				$slug = $post->post_name;
				$classes[] = 'page-' . $slug;
			}
			return $classes;
		}
	}

	/**
	 * Insert notice for outdated IE browsers
	 */
	if ( ! function_exists( 'noctilucent_chromeframe' ) ) {
		function noctilucent_chromeframe() { ?>
    <!--[if lt IE 7]>
	<p class="chromeframe">You are using an outdated browser. <a href="http://browsehappy.com/">Upgrade your browser today</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to better experience this site.</p>
	<![endif]-->
		<?php }
	}

	/**
	 * Insert primary nav
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_insert_primary_nav' ) ) {
		function noctilucent_insert_primary_nav() {
			wp_nav_menu( array(
				'container'         => 'nav',
				'container_class'   => 'site-menu',
				'theme_location'    => 'primary',
				'fallback_cb'       => 'noctilucent_page_menu'
			) );
		}
	}

	/**
	 * Insert section header
	 */
	if ( ! function_exists( 'noctilucent_insert_section_header' ) ) {
		function noctilucent_insert_section_header() {
			$archive_title = noctilucent_section_header();
			if ( $archive_title != '' ) : ?>
	            <header class="content-header">
	                <h3><?php echo $archive_title; ?></h3>
	            </header>
			<?php endif;
		}
	}

	/**
	 * Insert search page header
	 */
	if ( ! function_exists( 'noctilucent_insert_search_header' ) ) {
		function noctilucent_insert_search_header() {
			if ( is_search() ) : ?>
				<header class="content-header">
                    <?php get_search_form(); ?>
				</header>
			<?php endif;
		}
	}

	/**
	 * Insert archive pagination
	 */
	if ( ! function_exists( 'noctilucent_insert_archive_pagination' ) ) {
		function noctilucent_insert_archive_pagination() {

			if ( ! class_exists( 'Noctilucent_Pagination' ) )
				return;

			if ( ! is_singular() && 1 != noctilucent_pagination( 'count', false ) ) : ?>
	            <footer class="content-footer">
					<?php noctilucent_pagination( 'archive' ); ?>
	            </footer>
			<?php endif;

		}
	}

	/**
	 * Page content templates
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_load_page' ) ) {
		function noctilucent_load_page( $name ) {
			global $post;
			if ( is_page() ) {
				if ( locate_template( array( "content-page_{$post->post_name}.php" ) ) ) {
					$name = 'page_' . $post->post_name;
				} else {
					$name = 'page';
				}
			}
			return $name;
		}
	}
	
	/**
	 * Archive content templates
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_load_archive' ) ) {
		function noctilucent_load_archive( $name ) {
			if ( is_search() && locate_template( array( 'content-search.php' ) ) ) {
				$name = 'search';
			} else if ( is_archive() || is_search() ) {
				$name = 'archive';
			}
			return $name;
		}
	}
	
	/**
	 * Custom post type content templates
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_load_cpt' ) ) {
		function noctilucent_load_cpt( $name ) {
			$post_type = get_post_type();

			if ( ! in_array( $post_type, noctilucent_custom_post_types() ) )
				return $name;

			$name = $post_type;
			if ( is_post_type_archive( $post_type ) )
				$name .= '_archive';

			return $name;
		}
	}

	/**
	 * Custom excerpt link
	 */
	function noctilucent_excerpt_more( $more ) {

		global $post;

		$url = get_permalink( $post->ID );
		$title = get_the_title( $post->ID );
		$more = " &hellip; <a class=\"excerpt-link\" href=\"$url\" title=\"Continue reading &ldquo;$title&rdquo;\">Continue reading &ldquo;$title&rdquo; &raquo;</a>";

		return $more;

	}

	/**
	 * Add excerpt_more even when there is an excerpt
	 */
	function noctilucent_excerpt_plus( $excerpt ) {

		global $post;

		if ( $post->post_excerpt ) {
			$url = get_permalink( $post->ID );
			$excerpt .= apply_filters( 'excerpt_more', '' );
		}
		return $excerpt;

	}

	/**
	 * When to enable comments
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_load_comments' ) ) {
		function noctilucent_load_comments() {
			if ( is_singular( 'post' ) || is_attachment() )
				comments_template();
		}
	}

	/**
	 * Insert default sidebar
	 */
	if ( ! function_exists( 'noctilucent_insert_default_sidebar' ) ) {
		function noctilucent_insert_default_sidebar() {
			if ( apply_filters( 'noctilucent_sidebar_switch', true ) )
				get_sidebar( apply_filters( 'noctilucent_sidebar_template', null ) );
		}
	}

	/**
	 * Insert copyright string
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_insert_copyright' ) ) {
		function noctilucent_insert_copyright() { ?>
			<p class="site-copyright">&copy; <?php echo noctilucent_copyright_date(); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
		<?php }
	}
	
	/**
	 * Insert credit string
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_insert_credit' ) ) {
		function noctilucent_insert_credit() { ?>
			<p class="site-credit">Site by <a href="http://jupiterwise.com">Jupiterwise Design</a>.</p>
		<?php }
	}

	/**
	 * Pare down the inline gallery CSS to allow for more flexibility in the
	 * stylesheets
	 *
	 * Note: this may not work well with Jetpack
	 *
	 * Pluggable
	 */
	if ( ! function_exists( 'noctilucent_edit_gallery_style' ) ) {
		function noctilucent_edit_gallery_style( $input ) {
			$match = preg_match( '/\#(.*) {\n/', $input, $selector );
			$match = preg_match( '/float: (.*);\n/', $input, $float );
			$match = preg_match( '/width: (.*)%;\n/', $input, $itemwidth );
			$match = preg_match( '/\n\t\t<div(.*)$/', $input, $div );
			$output = "
			<style type='text/css'>
				#{$selector[1]} .gallery-item {
					float: {$float[1]};
					width: {$itemwidth[1]}%;
				}
			</style>
			";
			$output .= '<div' . $div[1];
			echo $output;
		}
	}


/**
 * Link to author archive 
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_author_link' ) ) {
    function noctilucent_author_link() {
		
		$current_author_url = get_author_posts_url( get_the_author_meta( 'ID' ), get_the_author_meta( 'user_nicename' ) );
		$current_author_link = '<a href="' . $current_author_url . '" title="Posts written by ' . get_the_author_meta( 'display_name' ) . '">' . get_the_author_meta( 'display_name' ) . '</a>';
		
		return $current_author_link;
	
    }
}


/**
 * Generates a year or a range of years
 * Pluggable
 *
 * $firstyear must be a valid date string, such as 2012-01-01
 */
if ( ! function_exists( 'noctilucent_copyright_date' ) ) {
	function noctilucent_copyright_date( $firstyear = '', $sep = ' &ndash; ' ) {
		
		try {
			$firstyear = new DateTime( $firstyear );
		} catch ( Exception $e ) {
			$firstyear = new DateTime();
		}
		
		if ( ! is_string( $sep ) )
			$sep = ' &ndash; ';
		
		( $firstyear->format( 'Y' ) == date( 'Y' ) ) ? $copyright = date( 'Y' ) : $copyright = $firstyear->format( 'Y' ) . $sep . date( 'Y' );
		
		return $copyright;
		
	}
}


/**
 * Custom comment markup
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_comment_markup' ) ) {
    function noctilucent_comment_markup( $comment, $args, $depth ) {
		$GLOBALS['comment'] = $comment;
		switch( $comment->comment_type ) :
			case '' :
			case 'comment' :
			default :
	?>

	<li>
	    <article <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
			<header>
				<?php echo get_avatar( $comment, $size = '48', $default = '<path_to_url>' ); ?>
		
				<time datetime="<?php comment_time( 'c' ); ?>"><a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ); ?>"><?php comment_date(); ?> at <?php comment_time(); ?></a></time><?php edit_comment_link( 'Edit', ' | ', '' ) ?><br />
				<cite class="comment-author"><?php comment_author_link(); ?></cite> <span class="says">says:</span>
			</header>
			
			<?php if ( $comment->comment_approved == '0' ) : ?>
			<p class="comment-moderation">Your comment is awaiting moderation.</p>
			<?php endif; ?>
		
			<?php comment_text() ?>
		
			<footer>
				<?php comment_reply_link( array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
			</footer>
	    </article>
    <?php
				break;

			case 'pingback' :
			case 'trackback' : 
	?>

	<li>
		<article <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
			<?php echo get_avatar( $comment, $size = '48', $default = '<path_to_url>' ); ?>
	
			<time datetime="<?php comment_time( 'c' ); ?>"><?php comment_date(); ?> at <?php comment_time(); ?></time><?php edit_comment_link( 'Edit', ' | ', '' ) ?><br />
			<cite class="comment-author"><?php comment_author_link(); ?></cite>
		</article>
	<?php
				break;
		endswitch;
    // </li> is added by WordPress automatically
    }
}


/**
 * Modification of wp_page_menu to allow for custom container.
 * Emulates wp_nav_menu.
 * Added arguments:
 * - container
 * - container_id
 * - container_class
 *
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_page_menu' ) ) {
	function noctilucent_page_menu( $args = array() ) {
		$defaults = array(
			'sort_column'     => 'menu_order, post_title',
			'menu_class'      => 'menu',
			'echo'            => true,
			'link_before'     => '',
			'link_after'      => '',
			'container'       => 'div',
			'container_id'    => '',
			'container_class' => ''
		);
		$args = wp_parse_args( $args, $defaults );
		$args = apply_filters( 'wp_page_menu_args', $args );

		$menu = '';

		$list_args = $args;

		// Show Home in the menu
		if ( ! empty($args['show_home']) ) {
				if ( true === $args['show_home'] || '1' === $args['show_home'] || 1 === $args['show_home'] )
						$text = __('Home');
				else
						$text = $args['show_home'];
				$class = '';
				if ( is_front_page() && !is_paged() )
						$class = 'class="current_page_item"';
				$menu .= '<li ' . $class . '><a href="' . home_url( '/' ) . '" title="' . esc_attr($text) . '">' . $args['link_before'] . $text . $args['link_after'] . '</a></li>';
				// If the front page is a page, add it to the exclude list
				if (get_option('show_on_front') == 'page') {
						if ( !empty( $list_args['exclude'] ) ) {
								$list_args['exclude'] .= ',';
						} else {
								$list_args['exclude'] = '';
						}
						$list_args['exclude'] .= get_option('page_on_front');
				}
		}

		$list_args['echo'] = false;
		$list_args['title_li'] = '';
		$menu .= str_replace( array( "\r", "\n", "\t" ), '', wp_list_pages($list_args) );

		$menu_class = '';
		if ( $args['menu_class'] )
			$menu_class = ' class="' . esc_attr( $args['menu_class'] ) . '"';

		if ( $menu )
			$menu = "<ul{$menu_class}>{$menu}</ul>";

		$container = '';
		if ( $args['container'] )
			$container = ( $args['container'] == 'nav' ) ? 'nav' : 'div';

		$container_id = '';
		if ( $args['container_id'] )
			$container_id = ' id="' . esc_attr( $args['container_id'] ) . '"';

		$container_class = '';
		if ( $args['container_class'] )
			$container_class = ' class="' . esc_attr( $args['container_class'] ) . '"';

		if ( $container && $menu )
			$menu = "<{$container}{$container_id}{$container_class}>{$menu}</{$container}>\n";

		$menu = apply_filters( 'wp_page_menu', $menu, $args );
		if ( $args['echo'] )
			echo $menu;
		else
			return $menu;
	}
}


/**
 * Generate section header based on the type of archive page
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_section_header' ) ) {
	function noctilucent_section_header() {
		
		global $wp_query;
		
		$section_header = '';
		
		if ( is_category() ) {
			$section_header = 'Archive for &ldquo;' . single_cat_title( '', false ) . '&rdquo;';
		} elseif ( is_tag() ) {
			$section_header = 'Posts tagged &ldquo;' . single_tag_title( '', false ) . '&rdquo;';
		} elseif ( is_day() ) {
			$section_header = 'Archive for ' . get_the_time( 'F jS, Y' );
		} elseif ( is_month() ) {
			$section_header = 'Archive for ' . get_the_time( 'F, Y' );
		} elseif ( is_year() ) {
			$section_header = 'Archive for ' . get_the_time( 'Y' );
		} elseif ( is_author() ) {
			$curauth = get_user_by( 'slug', get_query_var( 'author_name' ) );
			$section_header = 'Posts by ' . $curauth->display_name;
		} elseif ( is_search() ) {
			$search_count = $wp_query->found_posts;
			$search_label = ( $search_count == 1 ) ? 'result' : 'results';
			$section_header = $search_count . ' search ' . $search_label . ' for &ldquo;' . get_search_query() . '&rdquo;';
		} elseif ( is_post_type_archive( noctilucent_custom_post_types() ) ) {
			$section_header = post_type_archive_title( null, false );
		}
		
		return apply_filters( 'noctilucent_section_header', $section_header );
		
	}
}


/**
* Returns true if a blog has more than 1 category
*
* From https://github.com/Automattic/_s/
*/
function noctilucent_categorized_blog() {
	if ( false === ( $all_the_cool_cats = get_transient( 'all_the_cool_cats' ) ) ) {
		// Create an array of all the categories that are attached to posts
		$all_the_cool_cats = get_categories( array(
			'hide_empty' => 1,
		) );
		
		// Count the number of categories that are attached to the posts
		$all_the_cool_cats = count( $all_the_cool_cats );
		
		set_transient( 'all_the_cool_cats', $all_the_cool_cats );
	}
	
	if ( '1' != $all_the_cool_cats ) {
		// This blog has more than 1 category so _s_categorized_blog should return true
		return true;
	} else {
		// This blog has only 1 category so _s_categorized_blog should return false
		return false;
	}
}

/**
* Flush out the transients used in noctilucent_categorized_blog
*
* From https://github.com/Automattic/_s/
*/
function noctilucent_category_transient_flusher() {
	// Like, beat it. Dig?
	delete_transient( 'all_the_cool_cats' );
}
add_action( 'edit_category', 'noctilucent_category_transient_flusher' );
add_action( 'save_post', 'noctilucent_category_transient_flusher' );


/**
 * Returns an array of custom post type names. If $obj is set to true, returns
 * an associative array of objects.
 *
 * Pluggable
 */
if ( ! function_exists( 'noctilucent_custom_post_types' ) ) {
	function noctilucent_custom_post_types( $obj = false ) {
		
		$args = array(
			'public'   => true,
			'_builtin' => false
		);
		$output = ( $obj ) ? 'objects' : 'names';
		$operator = 'and';
		
		$post_types = get_post_types( $args, $output, $operator );
		
		return $post_types;
		
	}
}