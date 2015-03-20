<?php

/**
 * Processes API requests for the immediate children of a given page
 *
 * @author ryanajarrett
 * @since 0.1
 */
class children_request extends api_request {

    public static $params = array('pageid','orderby','order');

    function __construct($param_array) {
            // Setup vars from url params
            $this->set_params($param_array);
            // Get page details
            $submenu_page = new WP_Query(array(
                'p' => $this->data['pageid'],
                'post_type' => array('page')
            ));
            $submenu_page->the_post();

            // Start JSON
            // Page name
            $this->results_array['title'] = get_the_title();
            // Subpages Start
            $subpages = new WP_Query(array(
                'post_parent' => $this->data['pageid'],
                'post_type' => array('page'),
                'posts_per_page' => -1,
                'orderby' => $this->data['orderby'],
                'order' => $this->data['order']
            ));
            if ($subpages->have_posts()) {
                while ($subpages->have_posts()) {
                    $subpages->the_post();
                    $this->results_array['items'][] = $this->build_subpage(get_the_ID(),$this->data['orderby'],$this->data['order']);
                }
            } else {
                $this->results_array['items'] = array();
            }
            // Subpages End
            // End JSON

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
            'post_type' => array('page')
        ));

        $children = new WP_Query(array(
            'post_type' => array('page'),
            'post_parent' => $subpage_id,
            'posts_per_page' => -1
        ));

        $subpage->the_post();
        // Subpage Start
        $subpage_array = array();
        // Subpage ID
        $subpage_array['id'] = $subpage_id;
        // Subpage Title
        $subpage_array['title'] = get_the_title();
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
        $subpage_array['is_external'] = (int) get_post_meta( $subpage_id, 'redirect_enabled', true );
        // Subpage End
        return $subpage_array;
    }

}
