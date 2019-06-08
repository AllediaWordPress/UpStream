<?php

/**
 * CMB button field type
 *
 * @since     2.2.2
 *
 * @category  WordPress_Plugin
 * @package   CMB2
 * @author    CMB2 team
 * @license   GPL-2.0+
 * @link      https://cmb2.io
 */
class CMB2_Type_Button extends CMB2_Type_Base
{

    /**
     * The type of field
     *
     * @var string
     */
    public $type = 'button';

    /**
     * Handles outputting an 'title' element
     *
     * @return string Heading element
     */
    public function render()
    {
        $class = $this->field->args('class');

        $args = $this->parse_args($this->type, [
            'id'      => $this->_id(),
            'desc'    => $this->_desc(true),
            'label'   => $this->field->args('label'),
            'class'   => ! empty($class) ? $class : 'button-secondary',
            'onclick' => $this->field->args('onclick'),
            'nonce'   => $this->field->args('nonce'),
        ]);

        $html = sprintf('<button class="%s" id="%s" data-nonce="%s">%s</button>',
            $args['class'],
            $args['id'],
            $args['nonce'],
            $args['label']);

        $html .= $args['desc'];

        $selector = '#' . $args['id'];

        $html .= '<script>';
        $html .= 'jQuery("' . $selector . '").on("click", function(event){event.preventDefault(); ' . $args['onclick'] . '});';
        $html .= '</script>';

        return $this->rendered($html);
    }
}
