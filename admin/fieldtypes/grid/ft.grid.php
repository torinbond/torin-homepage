<?php
/**
 * Fieldtype_grid
 * Grid fieldtype
 */
class Fieldtype_grid extends Fieldtype
{
    /**
     * Renders the grid field
     *
     * @return void
     */
    public function render()
  {
    // determine boundaries
    $max_rows = (isset($this->field_config['max_rows']) && is_numeric($this->field_config['max_rows'])) ? ' data-max-rows="' . $this->field_config['max_rows'] . '"' : '';
    $min_rows = (isset($this->field_config['min_rows']) && is_numeric($this->field_config['min_rows'])) ? ' data-min-rows="' . $this->field_config['min_rows'] . '"' : '';

    // not here, we'll do this last so we can inject another data setting
    // $html = "<table class='grid table-list' tabindex='{$this->tabindex}'" . $max_rows . $min_rows . ">";


    // create header row
    // -------------------------------------------------------------------------
    $html  = "<thead>\n<tr>\n";
    $html .= "<th class='row-count'></th>";

    // loop through internal field configuration
    foreach ($this->field_config['fields'] as $cell_field_config) {
      // set width
      $width = isset($cell_field_config['width']) ? $cell_field_config['width'] : 'auto';

      // append to HTML output
      $html .= "<th style='width:{$width}'>{$cell_field_config['display']}</th>\n";
    }
    $html .= "<th class='action-col'></th>\n";
    $html .= "</tr>\n</thead>\n";


    // create grid rows
    // -------------------------------------------------------------------------
    $html .= "<tbody>\n";

    # rows to render, in order will prefer: starting_rows, min_rows, 1
    if (isset($this->field_config['starting_rows']) && is_numeric($this->field_config['starting_rows'])) {
      $rows_to_render = $this->field_config['starting_rows'];
    } elseif (isset($this->field_config['min_rows']) && is_numeric($this->field_config['min_rows'])) {
      $rows_to_render = $this->field_config['min_rows'];
    } else {
      $rows_to_render = 1;
    }

    # render the rows
    $i = 1;
    if (isset($this->field_data) && is_array($this->field_data) && count($this->field_data) > 0) {
      foreach ($this->field_data as $key => $row) {
        $html_row  = "<tr>";
        $html_row .= "<th class='row-count drag-indicator'>{$i}</th>";

        // foreach ($row as $column => $column_data) {
        foreach ($this->field_config['fields'] as $cell_field_name => $cell_field_config) {
          $column = key($row);
          $column_data = isset($row[$cell_field_name]) ? $row[$cell_field_name] : '';

          $default = isset($cell_field_config['default']) ? $cell_field_config['default'] : '';
          $celltype = $cell_field_config['type'];

          $html_row .= "<td class='cell-{$celltype}' data-default='{$default}'>";

          if ($cell_field_config['type'] == 'file') {
            //$name = $column.'['.$key.']';
            $name = $this->field.']['.$key.']['.$cell_field_name;
          } else {
            $name = $this->field.']['.$key.']['.$cell_field_name;
          }

          $html_row .= Fieldtype::render_fieldtype($celltype, $name, $cell_field_config, $column_data);
          $html_row .= "</td>";
        }
        $html_row .= '<td class="action"><a href="#" class="grid-delete-row confirm"><span class="icon">u</span></a></td>';
        $html_row .= "</tr>\n";

        $html .= $html_row;

        $i++;
      }
    } else { # no rows, set a blank one
      for ($i; $i <= $rows_to_render; $i++) {
        $html .= $this->render_empty_row($i);
      }
    }
    $html .= "</tbody>\n</table>\n";
    $html .= "<a href='#' class='grid-add-row btn btn-small btn-icon'><span class='icon'>Z</span>add row</a>";

    $empty_row = ' data-empty-row="' . htmlspecialchars($this->render_empty_row(0)) . '"';
    $html = "<table class='grid table-list' tabindex='{$this->tabindex}'" . $max_rows . $min_rows . $empty_row . ">" . $html;

    return $html;
  }

  public function render_empty_row($index)
  {
    $row = "<tr>";
    $row .= "<th class='row-count drag-indicator'>{$index}</th>";

    foreach ($this->field_config['fields'] as $cell_field_name => $cell_field_config) {

      $celltype = $cell_field_config['type'];

      $default = isset($cell_field_config['default']) ? $cell_field_config['default'] : '';

      if ($cell_field_config['type'] == 'file') {
        //$name = $cell_field_name.'[0]';
        $name = $this->field.'][0]['.$cell_field_name;
      } else {
        $name = $this->field.'][0]['.$cell_field_name;
      }

      $row .= "<td class='cell-{$celltype}' data-default='{$default}'>".Fieldtype::render_fieldtype($celltype, $name, $cell_field_config, $default)."</td>";
    }
    $row .= '<td class="action"><a href="#" class="grid-delete-row confirm"><span class="icon">u</span></a></td>';
    $row .= "</tr>\n";

    return $row;
  }

  public function process()
  {
    if (isset($_FILES['page']['name']['yaml'][$this->fieldname])) {

      $grid_field = $_FILES['page']['name']['yaml'][$this->fieldname];

      foreach ($grid_field as $index => $fields) {
        foreach ($fields as $field => $value) {
          if (array_get($this->settings['fields'][$field], 'type') === 'file') {
            if ($value != '') {
              $file_values = array(
                'name' => $_FILES['page']['name']['yaml'][$this->fieldname][$index][$field],
                'type' => $_FILES['page']['type']['yaml'][$this->fieldname][$index][$field],
                'tmp_name' => $_FILES['page']['tmp_name']['yaml'][$this->fieldname][$index][$field],
                'error' => $_FILES['page']['error']['yaml'][$this->fieldname][$index][$field],
                'size' => $_FILES['page']['size']['yaml'][$this->fieldname][$index][$field]
              );

              $this->field_data[$index][$field] = Fieldtype::process_field_data('file', $file_values, $this->settings['fields'][$field]);

            } else {
              if (isset($this->field_data[$index]["{$field}_remove"])) {
                $this->field_data[$index][$field] = '';
              } else {
                $this->field_data[$index][$field] = isset($this->field_data[$index][$field]) ? $this->field_data[$index][$field] : '';
              }
            }

            // unset the remove column
            if (isset($this->field_data[$index]["{$field}_remove"])) {
              unset($this->field_data[$index]["{$field}_remove"]);
            }
          }
        }
      }
    }

    foreach ($this->field_data as $row => $column) {
      foreach ($column as $field => $data) {
        if (isset($this->settings['fields'][$field]) && $this->settings['fields'][$field]['type'] != 'file' ) {
          $this->field_data[$row][$field] = Fieldtype::process_field_data($this->settings['fields'][$field]['type'], $data);
        }
      }
    }
    return $this->field_data;
  }

}
