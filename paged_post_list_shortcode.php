<?php
/*
Plugin Name: Paged Post List Shortcode
Plugin URI: https://troosoft.com/paged-post-list-shortcode
Description: Display a list of items (posts or pages) with pagination. Use shortcode: [list_posts_paged] 
Version: 1.0.0
Author: Pete Bofs
Author URI: https://troosoft.com/
License: GPL v. 3 or later.
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Add Shortcode
add_shortcode( 'list_posts_paged', 'list_posts_paged_shortcode_func' );
//Shortcode function 
function list_posts_paged_shortcode_func( $atts ) {
    $param_normalization_func = function( $atts ) {
        $string_to_validated_array_func = function( $str, $reference_arr, $default ) {
            $arr = array_filter( array_map( 'sanitize_key', explode( ",", $str ) ), function( $needle ) use ($reference_arr) {
                return in_array( $needle, $reference_arr );
            } );
            return count( $arr ) > 0 ? $arr : $default;
        };
        $defaults = array(
             'posts_per_page' => 10,
            'post_list_html_tag' => 'div',
            'container_div_class' => 'list-posts-paged',
            'container_div_id' => null,
            'previous_page_label' => '&laquo;',
            'next_page_label' => '&raquo;',
            'message_if_empty' => 'No matches.',
            //query posts
            'query_post_type' => 'post',
            'query_post_status' => 'publish',
            'query_author' => null,
            'query_author_id' => null,
            'query_category' => null,
            'query_category_id' => null,
            'query_from_date' => null,
            'query_to_date' => null,
            'query_tag' => null,
            'query_tag_id' => array( ),
            //order
            'order_of_results' => array(
                 'date' => 'desc' 
            ),
            'move_sticky_posts_to_the_top' => false,
            //visibility
            'author_visible' => false,
            'author_label' => ' by',
            'category_visible' => false,
            'uncategorized_visible' => true,
            'category_label' => 'In',
            'date_visible' => true,
            'date_label' => null,
            'date_format' => 'F j, Y',
            'excerpt_visible' => false,
            'override_manual_excerpts' => false,
            'image_visible' => true,
            'image_size' => null,
            'title_visible' => true 
        );
        $atts = (array) $atts;
        $out = array( );
        foreach ( $defaults as $name => $default ) {
            if ( array_key_exists( $name, $atts ) ) {
                $input = $atts[$name];
                switch ( $name ) {
                case 'query_post_type':
                    $all_post_types = array(
                         'post',
                        'page',
                        'revision',
                        'attachment',
                        'nav_menu_item',
                        'any' 
                    );
                    $out[$name] = $string_to_validated_array_func( $input, $all_post_types, $default );
                    break;
                case 'query_post_status':
                    $all_post_statuses = array(
                         'publish',
                        'pending',
                        'draft',
                        'auto-draft',
                        'future',
                        'private',
                        'inherit',
                        'trash',
                        'any' 
                    );
                    $out[$name] = $string_to_validated_array_func( $input, $all_post_statuses, $default );
                    break;
                case 'order_of_results':
                    $all_order_fields = array(
                         'asc',
                        'desc',
                        'id',
                        'author',
                        'title',
                        'type',
                        'date',
                        'modified',
                        'comment_count',
                        'menu_order',
                        'none' 
                    );
                    $result = $string_to_validated_array_func( $input, $all_order_fields, $default );
                    $scalar_result = array( );
                    $count = count( $result );
                    $ok = true;
                    if ( $count < 2 || $count % 2 != 0 ) {
                        $ok = false;
                    } else {
                        for ( $i = 0; $i < $count; $i++ ) {
                            if ( $i % 2 == 0 ) {
                                if ( $result[$i] === 'asc' || $result[$i] === 'desc' ) {
                                    $ok = false;
                                    break;
                                }
                            } else { //odd 
                                if ( $result[$i] !== 'asc' && $result[$i] !== 'desc' ) {
                                    $ok = false;
                                    break;
                                } else {
                                    $scalar_result[$result[$i - 1]] = $result[$i];
                                }
                            }
                        }
                    }
                    $out[$name] = ( $ok === true ? $scalar_result : $default );
                    break;
                case 'image_size':
                    if ( strpos( $input, ',' ) > 0 ) {
                        $image_size = array_filter( array_map( 'sanitize_key', explode( ",", $input ) ), 'intval' );
                        if ( count( $image_size ) === 2 && $image_size[0] > 0 && $image_size[1] > 0 ) {
                            $out[$name] = $image_size;
                        } else {
                            $out[$name] = $return;
                        }
                    } else {
                        $std_image_sizes = array(
                             'thumbnail',
                            'medium',
                            'large',
                            'full' 
                        );
                        if ( in_array( $input, $std_image_sizes ) ) {
                            $out[$name] = $input;
                        } else {
                            $out[$name] = $return;
                        }
                    }
                    break;
                case 'posts_per_page':
                    if ( $input == 'all' ) {
                        $out[$name] = -1;
                    } else {
                        $i = intval( $input );
                        $out[$name] = ( $i > 0 ) ? $i : $default;
                    }
                    break;
                case 'container_div_class':
                    //support multiple classes
                    $out[$name] = implode( " ", array_filter( array_map( 'sanitize_html_class', explode( " ", $input ) ), 'strlen' ) );
                    break;
                case 'container_div_id':
                    $out[$name] = sanitize_html_class( $input );
                    break;
                case 'post_list_html_tag':
                case 'query_author':
                case 'author_label':
                case 'category_label':
                case 'query_category':
                case 'date_label':
                case 'date_format':
                case 'query_from_date':
                case 'query_to_date':
                case 'image_size':
                case 'query_tag':
                case 'previous_page_label':
                case 'next_page_label':
                case 'query_author_id':
                case 'query_category_id':
                case 'message_if_empty':
                    $out[$name] = sanitize_text_field( $input );
                    break;
                case 'author_visible':
                case 'category_visible':
                case 'uncategorized_visible':
                case 'date_visible':
                case 'excerpt_visible':
                case 'override_manual_excerpts':
                case 'image_visible':
                case 'title_visible':
                case 'move_sticky_posts_to_the_top':
                    $out[$name] = wp_validate_boolean( $input );
                    break;
                case 'query_tag_id':
                    $out[$name] = array_filter( array_map( 'sanitize_key', explode( ",", $input ) ), 'strlen' );
                }
            } else {
                $out[$name] = $default;
            }
        }
        return $out;
    };
    $data_query_func = function( $atts, $paged ) {
        $args = array(
             'post_type' => $atts['query_post_type'],
            'post_status' => $atts['query_post_status'],
            'orderby' => $atts['order_of_results'],
            'ignore_sticky_posts' => !$atts['move_sticky_posts_to_the_top'],
            'posts_per_page' => $atts['posts_per_page'],
            'paged' => $paged 
        );
        if ( strlen( $atts['query_category'] ) > 0 ) {
            $args['category_name'] = $atts['query_category'];
        }
        if ( strlen( $atts['query_category_id'] ) > 0 ) {
            $args['cat'] = $atts['query_category_id'];
        }
        if ( strlen( $atts['query_author'] ) > 0 ) {
            $args['author_name'] = $atts['query_author'];
        }
        if ( strlen( $atts['query_author_id'] ) > 0 ) {
            $args['author'] = $atts['query_author_id'];
        }
        if ( strlen( $atts['query_tag'] ) > 0 ) {
            $args['tag'] = $atts['query_tag'];
        }
        if ( count( $atts['query_tag_id'] ) > 0 ) {
            $args['tag__in'] = $atts['query_tag_id'];
        }
        $date_predicate = array( );
        if ( strlen( $atts['query_from_date'] ) > 0 ) {
            $date_predicate['after'] = $atts['query_from_date'];
            $date_predicate['inclusive'] = true;
        }
        if ( strlen( $atts['query_to_date'] ) > 0 ) {
            $date_predicate['before'] = $atts['query_to_date'];
            if ( count( $date_predicate ) == 1 ) {
                $date_predicate['inclusive'] = true;
            }
        }
        if ( count( $date_predicate ) > 0 ) {
            $args['date_query'] = array(
                 $date_predicate 
            );
        }
        return new WP_Query( $args );
    };
    $content_html_func = function( $atts ) {
        $opening = '<div';
        if ( strlen( $atts['container_div_class'] ) > 0 ) {
            $opening .= ' class="' . $atts['container_div_class'] . '" ';
        }
        if ( strlen( $atts['container_div_id'] ) > 0 ) {
            $opening .= ' id="' . $atts['container_div_id'] . '"';
        }
        $opening .= ">";
        $closing = '</div>';
        return array(
             'o' => $opening,
            'c' => $closing 
        );
    };
    $item_list_html_func = function( $atts, $paged ) {
        $tag = $atts['post_list_html_tag'];
        $tag_attr = '';
        if ( $tag === 'ol' ) {
            $start = ( $paged - 1 ) * $atts['posts_per_page'] + 1;
            $tag_attr = ' start="' . $start . '" ';
        }
        $opening = '<' . $tag . $tag_attr . ' class="post-list">';
        $closing = '</' . $tag . '>';
        return array(
             'o' => $opening,
            'c' => $closing 
        );
    };
    $item_html_func = function( $q, $atts, $item_tag ) {
        $html = '<' . $item_tag . ' class="post">';
        if ( $atts['image_visible'] === true ) {
            $html .= '<a href="' . get_the_permalink() . '" class="post-image">' . get_the_post_thumbnail( null, $atts['image_size'], array(
                 'alt' => $q->post->post_title 
            ) ) . '</a>';
        }
        if ( $atts['title_visible'] === true ) {
            $html .= '<a href="' . get_the_permalink() . '" class="post-title">' . $q->post->post_title . '</a>';
        }
        if ( $atts['date_visible'] === true ) {
            $date_prefix = ' <span class="post-date">';
            $date_prefix .= strlen( $atts['date_label'] ) > 0 ? '<span class="date-label">' . $atts['date_label'] . ' </span>' : '';
            $html .= $date_prefix . '<span class="the-date">' . get_the_date( $atts['date_format'] ) . "</span></span>";
        }
        if ( $atts['author_visible'] === true ) {
            $author_prefix = '<span class="post-author"> ';
            $author_prefix .= strlen( $atts['author_label'] ) > 0 ? '<span class="author-label">' . $atts['author_label'] . ' </span>' : '';
            $html .= $author_prefix . '<span class="the-author">' . get_the_author() . "</span></span>";
        } 
        if ( $atts['excerpt_visible'] === true ) {
            if ( $atts['override_manual_excerpts'] ) {
                $html .= '<span class="post-excerpt">' . str_replace( '&nbsp;' , '' ,  wp_trim_words( strip_shortcodes( $q->post->post_content ) ) ) . '</span>';
            } else {
                $html .= '<span class="post-excerpt">' . get_the_excerpt() . '</span>';
            }
        }
        if ( $atts['category_visible'] === true && is_object_in_taxonomy( get_post_type(), 'category' ) ) {
            $categories = get_the_terms( get_the_ID(), 'category' );
            if ( $atts['uncategorized_visible'] === false ) {
                $filter_uncategorized_func = function( $c ) {
                    return $c->term_id !== 1;
                };
                $categories = array_filter( $categories, $filter_uncategorized_func );
            }
            if ( count( $categories ) > 0 ) {
                $category_prefix = ' <span class="post-categories">';
                $category_prefix .= ( strlen( $atts['category_label'] ) > 0 ? '<span class="category-label">' . $atts['category_label'] . ' </span>' : '' );
                $category_to_html_func = function( $c ) {
                    return '<a href="' . get_term_link( $c, 'category' ) . '" class="post-category">' . $c->name . '</a> ';
                };
                $categories_html = $html .= $category_prefix . implode( array_map( $category_to_html_func, $categories ) ) . '</span>'; // $category_prefix . '<span class="category">' . get_the_category_list() . "</span>";
            }
        }
        return $html . '</' . $item_tag . '>';
    };
    $navigation_html_func = function( $q, $atts ) {
        $html = '<div class="post-navigation"><span class="previous-posts" aria-label="previous posts">' . get_previous_posts_link( $atts['previous_page_label'] ) . '</span> <span class="next-posts" aria-label="next posts">' . get_next_posts_link( $atts['next_page_label'], $q->max_num_pages ) . '</span></div>'; //TODO
        return $html;
    };
    $atts = $param_normalization_func( $atts );
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $posts_query = $data_query_func( $atts, $paged );
    $content_html = $content_html_func( $atts );
    if ( !$posts_query->have_posts() ) {
        return $content_html['o'] . '<span class="no-matches">' . $atts['message_if_empty'] . '</span>' . $content_html['c'];
    }
    $item_list_html = $item_list_html_func( $atts, $paged );
    $html_output = $content_html['o'] . $item_list_html['o'];
    $item_tag = $atts['post_list_html_tag'] !== 'ol' && $atts['post_list_html_tag'] !== 'ul' ? 'div' : 'li';
    $new_excerpt_more = function( $more ) {
        return '&hellip;';
    };
    add_filter( 'excerpt_more', $new_excerpt_more );
    while ( $posts_query->have_posts() ) {
        $posts_query->the_post();
        $html_output .= $item_html_func( $posts_query, $atts, $item_tag );
    }
    $html_output .= $item_list_html['c'] . $navigation_html_func( $posts_query, $atts ) . $content_html['c'];
    //restore the global $post variable of the main query loop
    wp_reset_postdata();
    // Return custom embed code
    return $html_output;
}
?>
