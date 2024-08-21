<?php
/**
 * @package     WT JoomShopping SW Projects
 * @version     2.1.1
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL 3.0
 * @link https://septdir.com, https://web-tolk.ru
 * @since       1.0.0
 * @note        Since JoomShopping 5.5 use JoomShopping JForm field from Joomla\Component\Jshopping\Administrator\Field
 */

namespace Joomla\Plugin\Jshoppingorder\Wtjshoppingswjprojects\Fields;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;

class JshoppingextrafieldsField extends ListField
{
	protected $type = 'jshoppingextrafields';

	protected function getOptions(): array
	{
		if (!file_exists((JPATH_SITE . '/components/com_jshopping/bootstrap.php')))
		{
			return ['-- JoomShopping component is not installed -- '];
		}

		require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
		$options = [];
		$productfield = JSFactory::getTable('productfield');
		$list = $productfield->getList();

		$default = $this->element['default'] ?? null;
		if (isset($default) && ((string)$default) === '') {
			$options[] = HTMLHelper::_('select.option', '', '');
		}
		foreach ($list as $item) {
			$options[] = HTMLHelper::_('select.option', $item->id, $item->name);
		}

		return $options;
	}
}