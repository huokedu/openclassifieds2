<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Form helper class.
 * Modifies the Kohana_Form methods to force the addition of "form_" prefix to the "id" fields attributes.
 *
 * @package    OC
 * @category   Helpers
 * @author     Chema <chema@garridodiaz.com>
 * @copyright  (c) 2009-2013 Open Classifieds Team
 * @license    GPL v3
 */

class Form extends Kohana_Form {

	/**
	 * @var  array  Internal list of errors 
	 */
	private static $_errors = array();

	/**
	 * @var  string  Template for a single error message
	 */
	public static $error_tpl       = '<span class="error">%s</span>';

	/**
	 * @var  string  Template for a error message block
	 */
	public static $errors_tpl      = '<div class="alert full error"><h2>%s</h2><ul>%s</ul></div>';

	/**
	 * @var  string  Template for each item in a error message block
	 */
	public static $errors_item_tpl = '<li>%s</li>';

	/**
	 * Returns a formatted error for a field name (if it exists)
	 * @param   string  $name Field name
	 * @return  string  HTML formatted error
	 */
	public static function error($name)
	{
		$out = NULL;
		if (isset(self::$_errors[$name]))
		{
			$out = sprintf(self::$error_tpl, self::$_errors[$name]);
		}
		else
		{
			// Searchs for the error in any inner error array
			if( count(self::$_errors) )
			{
				foreach(self::$_errors as $k=>$v)
				{
					if(is_array($v)){
						if (isset($v[$name]))
						{
							$out = sprintf(self::$error_tpl, $v[$name]);
							break;
						}
					}
				}
			}
		}

		return $out;
	}

	/**
	 * Returns a formatted error block for all errors
	 * @param   array  $errors  
	 * @return  string  HTML formatted error
	 */
	public static function errors($errors = NULL)
	{
		//Log::instance()->add(LOG_DEBUG, 'TM_Form::errors('.print_r($errors,1).')');
		$out = NULL;

		// Assigns the view errors to the Form Helper
		if ( ! count(self::$_errors) AND $errors !== NULL)
		{
			self::set_errors($errors);
		}

		// Searchs for the error in any inner error array
		if (self::$_errors)
		{
			if ( ! is_array(self::$_errors))
			{
				self::$_errors = array(self::$_errors);
			}

			foreach (self::$_errors as $k=>$v)
			{
				if (is_array($v))
				{
					foreach ($v as $k2=>$v2)
					{
						$out .= sprintf(self::$errors_item_tpl, $v2);
					}
				}
				else
				{
					$out .= sprintf(self::$errors_item_tpl, $v);
				}
			}
		}


		if (strlen($out))
		{
			$out = sprintf(self::$errors_tpl, __('Some errors in the form'),$out);
		}

		return $out;
	}

	/**
	 * Assigns an error array to a static local reference
	 */
	public static function set_errors($array)
	{
		self::$_errors = $array;
	}

	/**
	 * 
	 * Creates a hidden input for the CSRF prevention
	 * @param string $namespace
	 * @return string
	 */
	public static function CSRF($namespace=NULL)
	{
		if ($namespace===NULL)
			$namespace = URL::title(Request::current()->uri());
		
		return CSRF::form($namespace);		
	}



    /**
     * get the html tag code for a field
     * @param  string $name input name
     * @param  array  $options as defined
     * @param  mixed $value value of the field, optional.
     * @return string        HTML
     */
    public static function form_tag($name, $options, $value = NULL)
    {
        if ($options['display'] != 'hidden')
            $label = FORM::label($name, (isset($options['label']))?$options['label']:$name, array('class'=>'control-label', 'for'=>$name));
        else
            $label = '';

        //$out = '';
        
            $value = (isset($options['default'])) ? $options['default']:NULL;
            $selected = (isset($options['selected'])) ? $options['selected']:NULL;
            $value_select = (isset($options['def_select'])) ? $options['def_select']:NULL;
        
        // dependent classes on type
        if($options['display']=='date')
        	$class = 'cf_date_fields';
        elseif($options['display']=='text')
        	$class = 'cf_text_fields';
        elseif($options['display']=='checkbox')
        	$class = 'cf_checkbox_fields';
        elseif($options['display']=='radio')
        	$class = 'cf_radio_fields';
        elseif($options['display']=='select')
        	$class = 'cf_select_fields';
        else
        	$class = '';

        $attributes = array('placeholder' 		=> (isset($options['label'])) ? $options['label']:$name, 
                            'class'       		=> 'input-large'.' '.$class, 
                            'id'          		=> $name, 
                            'data-date'       	=> ($options['display']=='date') ? $value : '', // optional attr for datapicker.js
                            'data-date-format'  => ($options['display']=='date') ? 'yyyy-mm-dd' : '', // optional attr for datapicker.js
                            ($options['required'] == TRUE)?'required':''
                    );

        switch ($options['display']) 
        {

            case 'select':
                $input = FORM::select('cf_'.$name, $value_select, $selected, $attributes);
                break;
            case 'text':
            
                $input = FORM::textarea('cf_'.$name, $value, $attributes);
                break;
            case 'hidden':
                $input = FORM::hidden('cf_'.$name, $value, $attributes);
                break;
            case 'date':
                $input = FORM::input('cf_'.$name, $value, $attributes);
                break;
			case 'checkbox':
				$checked = ($value == 1) ? TRUE : FALSE ;  
				// hidden input + checkbox is a trick to get value of a non selected checkbox.
                $input = FORM::hidden('cf_'.$name, 0).FORM::checkbox('cf_'.$name, NULL, $checked, $attributes);
                break;
            case 'radio':
			
				$checked = ($value == 1) ? TRUE : FALSE ;
				// hidden input + checkbox is a trick to get value of a non selected radio.
                $input = FORM::hidden('cf_'.$name, 0).FORM::radio('cf_'.$name, NULL, $checked, $attributes);
                break;
            case 'string':
            default:
                $input = FORM::input('cf_'.$name, $value, $attributes);
                break;
        }

        $out = $label.'<div class="controls">'.$input.'</div>';

        

        return $out;

    }

} // End TM_Form