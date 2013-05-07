<?php
class Fieldtype_text extends Fieldtype
{
  public function render()
  {
    $html = "<input type='text' name='{$this->fieldname}' tabindex='{$this->tabindex}' value='".htmlspecialchars($this->field_data, ENT_QUOTES)."' id='{$this->field_id}' />";

    return $html;
  }

}
