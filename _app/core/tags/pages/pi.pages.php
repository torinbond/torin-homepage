<?php
/**
 * Plugin_pages
 * Display lists of entries
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 *
 * @copyright  2013
 * @link       http://statamic.com/
 * @license    http://statamic.com/license-agreement
 */
class Plugin_pages extends Plugin
{
    /**
     * Lists entries based on passed parameters
     *
     * @return array|string
     */
    public function listing()
    {
        $folders = $this->fetchParam('folder', ltrim($this->fetchParam('from', URL::getCurrent()), "/"));
        $folders = ($folders === "/") ? "" : $folders;

        if ($this->fetchParam('taxonomy', false, null, true, null)) {
            $taxonomy_parts  = Taxonomy::getCriteria(URL::getCurrent());
            $taxonomy_type   = $taxonomy_parts[0];
            $taxonomy_slug   = Config::get('_taxonomy_slugify') ? Slug::humanize($taxonomy_parts[1]) : urldecode($taxonomy_parts[1]);

            $content_set = ContentService::getContentByTaxonomyValue($taxonomy_type, $taxonomy_slug, $folders);
        } else {
            $content_set = ContentService::getContentByFolders($folders);
        }

        // filter
        $content_set->filter(array(
            'show_all'    => $this->fetchParam('show_hidden', false, null, true, false),
            'since'       => $this->fetchParam('since'),
            'until'       => $this->fetchParam('until'),
            'show_past'   => $this->fetchParam('show_past', TRUE, NULL, TRUE),
            'show_future' => $this->fetchParam('show_future', FALSE, NULL, TRUE),
            'type'        => 'pages',
            'conditions'  => trim($this->fetchParam('conditions', ""))
        ));

        // sort
        $content_set->sort($this->fetchParam('sort_by', 'order_key'), $this->fetchParam('sort_dir'));

        // limit
        $limit     = $this->fetchParam('limit', null, 'is_numeric');
        $offset    = $this->fetchParam('offset', 0, 'is_numeric');
        $paginate  = $this->fetchParam('paginate', true, null, true, false);

        if ($limit || $offset) {
            if ($limit && $paginate) {
                // pagination requested, isolate the appropriate page
                $content_set->isolatePage($limit, URL::getCurrentPaginationPage());
            } else {
                // just limit
                $content_set->limit($limit, $offset);
            }
        }

        // check for results
        if (!$content_set->count()) {
            return array('no_results' => true);
        }

        // if content is used in this entries loop, parse it
        $parse_content = (bool) preg_match(Pattern::USING_CONTENT, $this->content);

        return Parse::tagLoop($this->content, $content_set->get($parse_content));
    }
}