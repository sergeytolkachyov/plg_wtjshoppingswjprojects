<?php
/**
 * @package     WT JoomShopping SW Projects
 * @version     1.0.0
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.0.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
FormHelper::loadFieldClass('list');
class JFormFieldJshoppingextrafields extends JFormFieldList
{

	protected $type = 'jshoppingextrafields';

	protected function getOptions()
	{
		$lang = Factory::getLanguage();
		$current_lang = $lang->getTag();
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('name_'.$current_lang));
		$query->select($db->quoteName('id'))
			->from($db->quoteName('#__jshopping_products_extra_fields'));
		$db->setQuery($query);
		$extra_fields = $db->loadAssocList();
		$name = 'name_'.$current_lang;
		$options = array();
		if (!empty($extra_fields))
		{
			foreach ($extra_fields as $extra_field)
			{
				$options[] = HTMLHelper::_('select.option', $extra_field["id"], $extra_field[$name]);
			}
		}

		return $options;
	}
}
?>