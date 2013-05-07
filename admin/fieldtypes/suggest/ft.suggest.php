<?php

class Fieldtype_suggest extends Fieldtype
{
    public function render()
    {
        /*
        |--------------------------------------------------------------------------
        | Multi-select
        |--------------------------------------------------------------------------
        |
        | We need to set an empty array brace [] and add the "multiple" attribute
        | in  the event we want to allow multi-selects. We also change the
        | plurality of the placeholder content.
        |
        */

        $multiple = array_get($this->field_config, 'multiple', true);
        $multiple_setting = $multiple ? "multiple" : "";
        $multiple_array_holder = $multiple ? "[]" : "";

        $default_placeholder = $multiple ? "Choose some options" : "Choose an option";
        $placeholder = isset($this->field_config['placeholder']) ? $this->field_config['placeholder'] : $default_placeholder;

        $suggestions = array();

        /*
        |--------------------------------------------------------------------------
        | Hardcoded list of options
        |--------------------------------------------------------------------------
        |
        | Any list can contain a preset list of options available for suggestion,
        | exactly like how the Select fieldtype works.
        |
        */

        if (isset($this->field_config['options'])) {
            $options = $this->field_config['options'];
            $suggestions = array_merge($suggestions, $options);
        }

        /*
        |--------------------------------------------------------------------------
        | Entries & Pages
        |--------------------------------------------------------------------------
        |
        | Fetch a list of pages and/or entries, using any existing fields as
        | labels and values
        |
        */

        if (isset($this->field_config['content'])) {

            $config = $this->field_config['content'];

            $value   = array_get($config, 'value', 'url');
            $label   = array_get($config, 'label', 'title');
            $folder  = array_get($config, 'folder');


            $content_set = ContentService::getContentByFolders(array($folder));

            $content_set->filter(array(
                    'show_all'    => array_get($config, 'show_all', false),
                    'since'       => array_get($config, 'since'),
                    'until'       => array_get($config, 'until'),
                    'show_past'   => array_get($config, 'show_past', true),
                    'show_future' => array_get($config, 'show_future', true),
                    'type'        => 'entries',
                    'conditions'  => trim(array_get($config, 'conditions'))
                )
            );
            $entries = $content_set->get();

            // rd($entries);

            foreach ($entries as $key => $entry) {
                if (isset($entry[$label]) && isset($entry[$value])) {
                    $suggestions[$entry[$value]] = $entry[$label];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Taxonomies
        |--------------------------------------------------------------------------
        |
        | Single taxonomy types can be fetched from any folder. The taxonomy label
        | and value will be identical to ensure consistency with template logic
        |
        */

        if (isset($this->field_config['taxonomy'])) {

            $taxonomy_type = array_get($this->field_config, 'taxonomy:type');
            $folder        = array_get($this->field_config, 'taxonomy:folder');
            $taxonomy_set  = ContentService::getTaxonomiesByType($taxonomy_type);

            // now filter that down to just what we want
            $taxonomy_set->filter(array(
                "folders"   => array($folder)
            ));

            $taxonomy_set->contextualize($folder);
            $taxonomies = $taxonomy_set->get();


            foreach ($taxonomies as $key => $value) {
                $taxonomies[$key] = $value['name'];
            }

            $suggestions = array_merge($suggestions, $taxonomies);
        }

        /*
        |--------------------------------------------------------------------------
        | Input HTML
        |--------------------------------------------------------------------------
        |
        | Generate the HTML for the select field. A single, blank option is
        | needed if in single select mode.
        |
        */

        $html  = "<div class='input-suggest-wrap'>";
        $html .= "<select name='{$this->fieldname}{$multiple_array_holder}' tabindex='{$this->tabindex}' {$multiple_setting} class='chosen-select' data-placeholder='{$placeholder}'>\n";

        if ( ! $multiple) {
            $html .= "<option value=''></option>\n";
        }

        foreach ($suggestions as $value => $label) {
            if ($multiple && is_array($this->field_data)) {
                $selected = in_array($value, $this->field_data) ? " selected " : '';
            } else {
                $selected = $this->field_data == $value ? " selected " : '';
            }
            $html .= "<option value='{$value}'{$selected}>{$label}</option>\n";
        }

        $html .= "</select></div>";

        return $html;
    }
}
