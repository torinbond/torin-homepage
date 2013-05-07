<?php
class Fieldtype_checkbox extends Fieldtype
{
    public function render_field()
    {
        $html  = "<div class='checkbox-block'>";
        $html .= $this->render();
        $html .= $this->render_label();
        $html .= $this->render_instructions_above();
        $html .= $this->render_instructions_below();
        $html .= "</div>";

        return $html;
    }

    public function render()
    {
        $checked = ($this->field_data) ? ' checked="checked"' : '';

        return "<input type='checkbox' name='{$this->fieldname}' tabindex='{$this->tabindex}' class='checkbox' id='{$this->field_id}' value='1'{$checked} />";
    }
}
