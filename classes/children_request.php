<?php

/**
 * Processes API requests for the immediate children of a given page
 *
 * @author ryanajarrett
 * @since 0.1
 */
class children_request extends api_request {

    public static $params = array('pageid','orderby','order');

    private static $post_types = array('page','webchat');

    function __construct($param_array) {
            // Setup vars from url params
            $this->set_params($param_array);

            // Set post parent
            $post_parent = $this->data['pageid'] ?: 0;

            // Get page details
            $submenu_page = new WP_Query(array(
                'p' => $post_parent,
                'post_type' => self::$post_types
            ));
            $submenu_page->the_post();


            // Start JSON
            // Page name
            $this->results_array['title'] = $post_parent?get_the_title():"None";
            $this->results_array['id'] = $post_parent?get_the_ID():0;
            $this->results_array['url'] = $post_parent ? get_the_permalink() : site_url();
            // Subpages Start
            $subpages_args = array(
                'post_parent' => $post_parent,
                'post_type' => self::$post_types,
                'posts_per_page' => -1,
                'orderby' => $this->data['orderby'] ?: 'menu_order title',
                'order' => $this->data['order'] ?: 'asc'
            );
            if(!$post_parent) {
                $subpages_args['meta_key'] = 'is_top_level';
                $subpages_args['meta_value'] = 1;
            }

            $subpages = new WP_Query($subpages_args);
            $this->results_array['total_results'] = (int) $subpages->found_posts;

            if ($subpages->have_posts()) {
                while ($subpages->have_posts()) {
                    $subpages->the_post();
                    $this->results_array['results'][] = $this->build_subpage(get_the_ID(),$this->data['orderby'],$this->data['order']);
                }
            } else {
                $this->results_array['results'] = array();
            }
            // Subpages End
            // End JSON

            // Force alpha sort on results
            usort($this->results_array['results'],array($this,'sortByTitle'));

            return($this->results_array);
    }

    /**
     * Creates array containing subpage details
     *
     * @since 0.1
     */
    function build_subpage($subpage_id = 0, $orderby, $order) {
        $subpage = new WP_Query(array(
            'p' => $subpage_id,
            'post_type' => self::$post_types
        ));

        $children = new WP_Query(array(
            'post_type' => self::$post_types,
            'post_parent' => $subpage_id,
            'posts_per_page' => -1
        ));

        $subpage->the_post();
        // Subpage Start
        $subpage_array = array();
        // Subpage ID
        $subpage_array['id'] = $subpage_id;
        // Subpage Title - ignores content before colon
        $subpage_array['title'] = $this->filter_titles(get_the_title());
        // Subpage URL
        $subpage_array['url'] = get_the_permalink();
        // Subpage Slug
        $subpage_array['slug'] = $subpage->posts[0]->post_name;
        // Subpage Excerpt
        $subpage_array['excerpt'] = get_the_excerpt();
        // Subpage Order
        $subpage_array['order'] = $subpage->posts[0]->menu_order;
        // Subpage Child count
        $subpage_array['child_count'] = count($children->posts);
        // Subpage Redirect
        $subpage_array['is_external'] = (boolean) get_post_meta( $subpage_id, 'redirect_enabled', true );
        // Subpage Status
        $subpage_array['status'] = $subpage->posts[0]->post_status;
        // Subpage End
        return $subpage_array;
    }

    function sortByTitle($a, $b) {
      return strcmp(strtolower($a['title']), strtolower($b['title']));
    }

    public function filter_titles($title) {
      return preg_replace('/(.*:\s*)/', "", $title);
    }

}
