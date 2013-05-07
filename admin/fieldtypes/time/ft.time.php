<?php
class Fieldtype_time extends Fieldtype
{
  public function render()
  {
    $html = "<span class='icon'>N</span><input type='text' class='timepicker' name='{$this->fieldname}' tabindex='{$this->tabindex}' value='{$this->field_data}' />";

    return $html;
  }

}
