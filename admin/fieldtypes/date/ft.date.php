<?php
class Fieldtype_date extends Fieldtype
{
  public function render()
  {
    $html =  "<span class='icon'>P</span>";
    $html .= "<input type='text' name='{$this->fieldname}' tabindex='{$this->tabindex}' value='{$this->field_data}' class='datepicker' />";

    return $html;
  }

}
