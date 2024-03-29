<?php
/**
 * @package     WT JoomShopping SW Projects
 * @version     2.0.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL 3.0
 * @link https://septdir.com, https://web-tolk.ru
 * @since       1.0.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');

class JFormFieldJshoppingfreeattr extends JFormFieldList
{

	protected $type = 'jshoppingfreeattr';

	protected function getOptions()
	{
		if (!file_exists((JPATH_SITE . '/components/com_jshopping/bootstrap.php')))
		{
			return '-- JoomShopping component is not installed -- ';
		}

		require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
		$db = Factory::getContainer()->get('DatabaseDriver');

		$lang         = Factory::getApplication()->getLanguage();
		$current_lang = $lang->getTag();
		$query        = $db->getQuery(true);
		$query->select($db->quoteName('name_' . $current_lang));
		$query->select($db->quoteName('id'))
			->from($db->quoteName('#__jshopping_free_attr'));
		$db->setQuery($query);
		$free_attrs = $db->loadAssocList();
		$name       = 'name_' . $current_lang;
		$options    = array();
		if (!empty($free_attrs))
		{
			foreach ($free_attrs as $free_attr)
			{
				$options[] = HTMLHelper::_('select.option', $free_attr["id"], $free_attr[$name]);
			}
		}

		return $options;

	}
}

?>