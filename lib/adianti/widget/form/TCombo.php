<?php
Namespace Adianti\Widget\Form;

use Adianti\Widget\Form\AdiantiWidgetInterface;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Form\TField;
use Exception;

/**
 * ComboBox Widget
 *
 * @version    2.0
 * @package    widget
 * @subpackage form
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006-2014 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCombo extends TField implements AdiantiWidgetInterface
{
    private   $changeAction;
    protected $id;
    protected $items; // array containing the combobox options
    protected $formName;
    private   $defaultOption;

    /**
     * Class Constructor
     * @param  $name widget's name
     */
    public function __construct($name)
    {
        // executes the parent class constructor
        parent::__construct($name);
        $this->id   = 'tcombo_'.uniqid();
        $this->defaultOption = '';

        // creates a <select> tag
        $this->tag = new TElement('select');
        $this->tag->{'class'} = 'tcombo'; // CSS
    }
    
    /**
     * Add items to the combo box
     * @param $items An indexed array containing the combo options
     */
    public function addItems($items)
    {
        if (is_array($items))
        {
            $this->items = $items;
        }
    }
    
    /**
     * Return the post data
     */
    public function getPostData()
    {
        if (isset($_POST[$this->name]))
        {
            $val = $_POST[$this->name];
            
            if ($val == '') // empty option
            {
                return '';
            }
            else
            {
                if (strpos($val, '::'))
                {
                    $tmp = explode('::', $val);
                    return trim($tmp[0]);
                }
                else
                {
                    return $val;
                }
            }
        }
        else
        {
            return '';
        }
    }
    
    /**
     * Define the action to be executed when the user changes the combo
     * @param $action TAction object
     */
    public function setChangeAction(TAction $action)
    {
        if ($action->isStatic())
        {
            $this->changeAction = $action;
        }
        else
        {
            $string_action = $action->toString();
            throw new Exception(AdiantiCoreTranslator::translate('Action (^1) must be static to be used in ^2', $string_action, __METHOD__));
        }
    }
    
    /**
     * Reload combobox items after it is already shown
     * @param $formname form name (used in gtk version)
     * @param $name field name
     * @param $items array with items
     * @param $startEmpty ...
     */
    public static function reload($formname, $name, $items, $startEmpty = FALSE)
    {
        $code = "tcombo_clear('{$formname}', '{$name}'); ";
        if ($startEmpty)
        {
            $code .= "tcombo_add_option('{$formname}', '{$name}', '', ''); ";
        }
        
        if ($items)
        {
            foreach ($items as $key => $value)
            {
                $code .= "tcombo_add_option('{$formname}', '{$name}', '{$key}', '{$value}'); ";
            }
        }
        TScript::create($code);
    }
    
    /**
     * Enable the field
     * @param $form_name Form name
     * @param $field Field name
     */
    public static function enableField($form_name, $field)
    {
        TScript::create( " tcombo_enable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Disable the field
     * @param $form_name Form name
     * @param $field Field name
     */
    public static function disableField($form_name, $field)
    {
        TScript::create( " tcombo_disable_field('{$form_name}', '{$field}'); " );
    }
    
    /**
     * Define the combo default option value
     * @param $option option value
     */
    public function setDefaultOption($option)
    {
        $this->defaultOption = $option;
    }

    /**
     * Shows the widget
     */
    public function show()
    {
        // define the tag properties
        $this->tag-> name  = $this->name;    // tag name
        
        if ($this->id)
        {
            $this->tag-> id    = $this->id;
        }
        
        if (strstr($this->size, '%') !== FALSE)
        {
            $this->setProperty('style', "width:{$this->size};", false); //aggregate style info
        }
        else
        {
            $this->setProperty('style', "width:{$this->size}px;", false); //aggregate style info
        }
                
        if ($this->defaultOption !== FALSE)
        {
            // creates an empty <option> tag
            $option = new TElement('option');
            
            $option->add( $this->defaultOption );
            $option-> value = '';   // tag value

            // add the option tag to the combo
            $this->tag->add($option);
        }
                    
        if ($this->items)
        {
            // iterate the combobox items
            foreach ($this->items as $chave => $item)
            {
                if (substr($chave, 0, 3) == '>>>')
                {
                    $optgroup = new TElement('optgroup');
                    $optgroup-> label = $item;
                    // add the option to the combo
                    $this->tag->add($optgroup);
                }
                else
                {
                    // creates an <option> tag
                    $option = new TElement('option');
                    $option-> value = $chave;  // define the index
                    $option->add($item);      // add the item label
                    
                    // verify if this option is selected
                    if (($chave == $this->value) AND ($this->value !== NULL))
                    {
                        // mark as selected
                        $option-> selected = 1;
                    }
                    
                    if (isset($optgroup))
                    {
                        $optgroup->add($option);
                    }
                    else
                    {
                        $this->tag->add($option);
                    }                    
                }
            }
        }
        
        // verify whether the widget is editable
        if (parent::getEditable())
        {
            if (isset($this->changeAction))
            {
                if (!TForm::getFormByName($this->formName) instanceof TForm)
                {
                    throw new Exception(AdiantiCoreTranslator::translate('You must pass the ^1 (^2) as a parameter to ^3', __CLASS__, $this->name, 'TForm::setFields()') );
                }
                
                $string_action = $this->changeAction->serialize(FALSE);
                $this->setProperty('changeaction', "serialform=(\$('#{$this->formName}').serialize());
                                              __adianti_ajax_lookup('$string_action&'+serialform, this)", FALSE);
                $this->setProperty('onChange', $this->getProperty('changeaction'));
            }
        }
        else
        {
            // make the widget read-only
            //$this->tag-> disabled   = "1"; // the value don't post
            $this->tag->{'onclick'} = "return false;";
            $this->tag->{'style'}   = 'pointer-events:none';
            $this->tag->{'class'} = 'tfield_disabled'; // CSS
        }
        // shows the combobox
        $this->tag->show();
    }
}
