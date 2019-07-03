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
class CMB2_Type_ButtonsGroup extends CMB2_Type_Base
{
    /**
     * The type of field
     *
     * @var string
     */
    public $type = 'buttonsgroup';

    /**
     * Handles outputting an 'title' element
     *
     * @return string Heading element
     */
    public function render()
    {
        $class = $this->field->args('class');
        $count = (int)$this->field->args('count');

        $args = $this->parse_args($this->type, [
            'id'      => $this->_id(),
            'desc'    => $this->_desc(true),
            'labels'  => (array)$this->field->args('labels'),
            'slugs'   => (array)$this->field->args('slugs'),
            'class'   => ! empty($class) ? $class : 'button-secondary',
            'count'   => ! empty($count) ? $count : 1,
            'onclick' => $this->field->args('onclick'),
            'nonce'   => $this->field->args('nonce'),
        ]);

        $html      = '';
        $selectors = [];

        for ($i = 0; $i < $count; $i++) {
            $id          = $args['id'] . '_' . $i;
            $selectors[] = '#' . $id;

            $html .= sprintf('<button class="%s" id="%s" data-nonce="%s" data-slug="%s">%s</button>',
                $args['class'],
                $id,
                $args['nonce'],
                $args['slugs'][$i],
                $args['labels'][$i]);

        }

        $selector = implode(', ', $selectors);

        $html .= '<script>';
        $html .= 'jQuery("' . $selector . '").on("click", function(event){event.preventDefault(); ' . $args['onclick'] . '});';
        $html .= '</script>';


        $html .= $args['desc'];

        return $this->rendered($html);
    }
}
